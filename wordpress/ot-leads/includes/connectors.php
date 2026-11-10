<?php
/**
 * Connectors (spec § 7). Every lead the site forwards goes into the outbox first and is sent
 * from there, retrying after 1 minute, 5, 30, 2 hours and 12 hours, then parked as "needs
 * attention" with the error. The site's own record is never deleted because of a connector.
 *
 * - Webhook: a signed JSON POST to any URL (Zapier, Make, Lofty, kvCORE, HubSpot...). Headers:
 *   X-OT-Timestamp, X-OT-Id and X-OT-Signature = hex HMAC-SHA256 of "<timestamp>.<id>.<body>"
 *   with the shared secret, so the receiver can reject old or repeated requests.
 * - Follow Up Boss: POST https://api.followupboss.com/v1/events (Basic auth with the API key,
 *   X-System and X-System-Key from registering the integration). Follow Up Boss recommends
 *   /events over /people because it de-duplicates, runs lead flow and alerts the agent.
 *
 * Modes (Settings > CRM): built-in only (default), built-in + forward, forward only.
 *
 * Each agent can also have their own CRM (Settings > Each agent's CRM, agent-crm.php): their
 * leads go there too, as "webhook@<agent>" or "fub@<agent>" in the outbox, whatever the
 * team-wide mode. The payload's "event" says why it was sent: lead, assigned or unsubscribed.
 */

defined( 'ABSPATH' ) || exit;

const OT_OUTBOX_DELAYS = array( 60, 300, 1800, 7200, 43200 );

/** Where a lead is forwarded: the team-wide connectors (unless built-in only), then its agent's own. */
function ot_leads_connectors( $lead = null ) {
	$out = array();
	if ( 'builtin' !== ot_leads_opt( 'crm_mode' ) ) {
		if ( ot_leads_https( (string) ot_leads_opt( 'webhook_url' ) ) ) {
			$out[] = 'webhook';
		}
		if ( ot_leads_opt( 'fub_key' ) ) {
			$out[] = 'fub';
		}
	}
	$slug = $lead['assigned_agent'] ?? '';
	if ( $slug ) {
		$cfg = ot_leads_agent_cfg( $slug );
		if ( ot_leads_https( $cfg['webhook_url'] ) ) {
			$out[] = 'webhook@' . $slug;
		}
		if ( $cfg['fub_key'] ) {
			$out[] = 'fub@' . $slug;
		}
	}
	return $out;
}

function ot_leads_https( $url ) {
	return (bool) filter_var( $url, FILTER_VALIDATE_URL ) && str_starts_with( $url, 'https://' );
}

/** A connector id's settings: the team-wide ones, or the named agent's. */
function ot_leads_connector_cfg( $connector ) {
	list( $kind, $slug ) = array_pad( explode( '@', $connector, 2 ), 2, '' );
	if ( $slug ) {
		$a = ot_leads_agent_cfg( $slug );
		return array( 'kind' => $kind, 'agent' => $slug, 'url' => $a['webhook_url'], 'secret' => $a['webhook_secret'], 'fub_key' => $a['fub_key'] );
	}
	return array( 'kind' => $kind, 'agent' => '', 'url' => (string) ot_leads_opt( 'webhook_url' ), 'secret' => (string) ot_leads_opt( 'webhook_secret' ), 'fub_key' => (string) ot_leads_opt( 'fub_key' ) );
}

/** The lead as forwarded: contact details, what it is about, and the form's message. */
function ot_leads_payload( $lead, $form, $event = 'lead' ) {
	$agent = ot_leads_agents()[ $lead['assigned_agent'] ] ?? array();
	return array(
		'event'      => $event,
		'id'         => (int) $lead['id'],
		'stage'      => $lead['stage'],
		'source'     => 'oberdorferteam.com',
		'type'       => $lead['lead_type'],
		'context'    => $form['context'] ?? $lead['context'],
		'first_name' => $lead['first_name'],
		'last_name'  => $lead['last_name'],
		'email'      => $lead['email'],
		'phone'      => $lead['phone'],
		'preferred'  => $lead['preferred'],
		'towns'      => array_values( array_filter( explode( ',', $lead['towns'] ) ) ),
		'listing'    => $lead['listing_id'] ? array( 'title' => get_the_title( $lead['listing_id'] ), 'url' => get_permalink( $lead['listing_id'] ), 'price' => (int) get_post_meta( $lead['listing_id'], 'ot_price', true ), 'mls' => (string) get_post_meta( $lead['listing_id'], 'ot_mls', true ) ) : null,
		'topic'      => $form['topic'] ?? '',
		'message'    => $form['message'] ?? '',
		'page'       => $lead['source_url'],
		'utm'        => json_decode( (string) $lead['utm'], true ) ?: (object) array(),
		'agent'      => $agent ? array( 'slug' => $lead['assigned_agent'], 'name' => $agent['name'], 'email' => $agent['email'] ) : null,
		'consent'    => array( 'email_marketing' => ot_lead_has_consent( $lead['email'] ) ),
		'created_at' => $lead['created_at'] . 'Z',
	);
}

function ot_leads_outbox_add( $lead, $form, $event = 'lead', $only = null ) {
	global $wpdb;
	$cons = ot_leads_connectors( $lead );
	if ( null !== $only ) {
		$cons = array_values( array_intersect( $cons, $only ) );
	}
	if ( 'unsubscribed' === $event ) {
		// Follow Up Boss's events API cannot unsubscribe a person: webhooks only.
		$cons = array_values( array_filter( $cons, fn( $c ) => str_starts_with( $c, 'webhook' ) ) );
	}
	foreach ( $cons as $c ) {
		$wpdb->insert( ot_leads_table( 'outbox' ), array(
			'lead_id'         => $lead['id'],
			'connector'       => $c,
			'payload'         => wp_json_encode( ot_leads_payload( $lead, $form, $event ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
			'next_attempt_at' => ot_leads_now(),
			'state'           => 'pending',
			'created_at'      => ot_leads_now(),
		) );
	}
	if ( $cons ) {
		ot_leads_outbox_run( 5 ); // try at once; the scheduler retries anything that fails
	}
}

/** Deliver up to $max due items. */
function ot_leads_outbox_run( $max = 20 ) {
	global $wpdb;
	$t    = ot_leads_table( 'outbox' );
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t WHERE state = 'pending' AND next_attempt_at <= %s ORDER BY id ASC LIMIT %d", ot_leads_now(), $max ), ARRAY_A );
	foreach ( $rows as $row ) {
		$cfg = ot_leads_connector_cfg( $row['connector'] );
		list( $ok, $err ) = 'fub' === $cfg['kind'] ? ot_leads_send_fub( $row, $cfg ) : ot_leads_send_webhook( $row, $cfg );
		$attempts = (int) $row['attempts'] + 1;
		if ( $ok ) {
			$wpdb->update( $t, array( 'state' => 'sent', 'attempts' => $attempts, 'last_error' => '' ), array( 'id' => $row['id'] ) );
			$wpdb->update( ot_leads_table( 'leads' ), array( 'crm_state' => 'sent' ), array( 'id' => $row['lead_id'] ) );
			ot_lead_event( (int) $row['lead_id'], 'crm_sent', array( 'connector' => $row['connector'] ), 'system' );
		} elseif ( $attempts > count( OT_OUTBOX_DELAYS ) ) {
			$wpdb->update( $t, array( 'state' => 'failed', 'attempts' => $attempts, 'last_error' => substr( $err, 0, 250 ) ), array( 'id' => $row['id'] ) );
			$wpdb->update( ot_leads_table( 'leads' ), array( 'crm_state' => 'failed' ), array( 'id' => $row['lead_id'] ) );
			ot_lead_event( (int) $row['lead_id'], 'crm_failed', array( 'connector' => $row['connector'], 'error' => $err ), 'system' );
		} else {
			$wpdb->update( $t, array( 'attempts' => $attempts, 'last_error' => substr( $err, 0, 250 ), 'next_attempt_at' => gmdate( 'Y-m-d H:i:s', time() + OT_OUTBOX_DELAYS[ $attempts - 1 ] ) ), array( 'id' => $row['id'] ) );
		}
	}
}

function ot_leads_send_webhook( $row, $cfg = null ) {
	$cfg  = $cfg ?: ot_leads_connector_cfg( 'webhook' );
	if ( ! ot_leads_https( $cfg['url'] ) ) {
		return array( false, 'No https webhook URL is set' );
	}
	$ts   = (string) time();
	$id   = 'ot-' . $row['id'] . '-' . $row['attempts'];
	$body = $row['payload'];
	$res  = wp_remote_post( $cfg['url'], array(
		'timeout' => 10,
		'headers' => array(
			'Content-Type'   => 'application/json',
			'X-OT-Timestamp' => $ts,
			'X-OT-Id'        => $id,
			'X-OT-Signature' => hash_hmac( 'sha256', $ts . '.' . $id . '.' . $body, (string) $cfg['secret'] ),
		),
		'body'    => $body,
	) );
	if ( is_wp_error( $res ) ) {
		return array( false, $res->get_error_message() );
	}
	$code = wp_remote_retrieve_response_code( $res );
	return array( $code >= 200 && $code < 300, 'HTTP ' . $code );
}

function ot_leads_send_fub( $row, $cfg = null ) {
	$cfg  = $cfg ?: ot_leads_connector_cfg( 'fub' );
	$p    = json_decode( $row['payload'], true );
	if ( ! $cfg['fub_key'] ) {
		return array( false, 'No Follow Up Boss API key is set' );
	}
	$type = array( 'showing' => 'Property Inquiry', 'openhouse' => 'Visited Open House', 'seller' => 'Seller Inquiry', 'newsletter' => 'Registration', 'listings' => 'Registration', 'community' => 'General Inquiry' )[ $p['type'] ] ?? 'General Inquiry';
	$ev   = array(
		'source'      => 'oberdorferteam.com',
		'system'      => (string) ot_leads_opt( 'fub_system' ),
		'type'        => $type,
		'message'     => trim( ( $p['topic'] ? $p['topic'] . "\n" : '' ) . $p['message'] ),
		'description' => 'From ' . $p['page'] . ( $p['towns'] ? ' · Towns: ' . implode( ', ', $p['towns'] ) : '' ),
		'person'      => array_filter( array(
			'firstName' => $p['first_name'],
			'lastName'  => $p['last_name'],
			'emails'    => array( array( 'value' => $p['email'] ) ),
			'phones'    => $p['phone'] ? array( array( 'value' => $p['phone'] ) ) : null,
			'tags'      => array_values( array_filter( array( 'Website', OT_LEAD_TYPES[ $p['type'] ] ?? '' ) ) ),
		) ),
	);
	if ( ! empty( $p['listing'] ) ) {
		$ev['property'] = array_filter( array( 'street' => $p['listing']['title'], 'state' => 'MA', 'url' => $p['listing']['url'], 'price' => $p['listing']['price'] ?: null, 'mlsNumber' => $p['listing']['mls'] ?: null ) );
	}
	$res = wp_remote_post( 'https://api.followupboss.com/v1/events', array(
		'timeout' => 10,
		'headers' => array(
			'Authorization' => 'Basic ' . base64_encode( $cfg['fub_key'] . ':' ),
			'Content-Type'  => 'application/json',
			'X-System'      => (string) ot_leads_opt( 'fub_system' ),
			'X-System-Key'  => (string) ot_leads_opt( 'fub_system_key' ),
		),
		'body'    => wp_json_encode( $ev ),
	) );
	if ( is_wp_error( $res ) ) {
		return array( false, $res->get_error_message() );
	}
	$code = wp_remote_retrieve_response_code( $res );
	// 204 means Follow Up Boss ignored the event because lead flow archived it: delivered all the same.
	return array( $code >= 200 && $code < 300, 'HTTP ' . $code . ' ' . substr( wp_remote_retrieve_body( $res ), 0, 150 ) );
}
