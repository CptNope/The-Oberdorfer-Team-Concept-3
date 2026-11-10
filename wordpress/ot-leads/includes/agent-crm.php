<?php
/**
 * Each agent's own CRM, and updates coming back from a CRM.
 *
 * Neither Brandon nor Kaitlyn uses an outside CRM yet (2026-11-10), so everything defaults to
 * the built-in one. When an agent gets one, a team leader fills in their row in Settings >
 * Each agent's CRM:
 * - Their leads are forwarded there (a webhook, for Zapier, Make or any CRM, or Follow Up Boss),
 *   on arrival and when a lead is reassigned to them, through the outbox with its retries.
 * - "Follow-up emails" says who nurtures their leads: this site (the sequences) or their CRM.
 *   Never both: with "their CRM", the site enrolls none of their leads and stops any running.
 * - An unsubscribe on the site is sent to their webhook ("event": "unsubscribed").
 * Updates the other way come in at POST /wp-json/ot-leads/v1/crm-update with the bearer token
 * shown in Settings: {"email": "...", "event": "unsubscribed"}, or {"email": "...", "event":
 * "stage", "stage": "closed"}. An unsubscribe there stops email from the site too.
 */

defined( 'ABSPATH' ) || exit;

/** An agent's CRM settings. */
function ot_leads_agent_cfg( $slug ) {
	$all = (array) get_option( 'ot_leads_agent_crm', array() );
	return wp_parse_args( (array) ( $all[ $slug ] ?? array() ), array( 'nurture' => 'site', 'webhook_url' => '', 'webhook_secret' => '', 'fub_key' => '' ) );
}

/** True when the agent's own CRM sends their follow-up email (so the site must not). */
function ot_leads_agent_crm_nurtures( $slug ) {
	$c = ot_leads_agent_cfg( $slug );
	return 'crm' === $c['nurture'] && ( ot_leads_https( $c['webhook_url'] ) || $c['fub_key'] );
}

/** The token a CRM uses to send updates back. */
function ot_leads_inbound_token() {
	$t = (string) get_option( 'ot_leads_inbound_token' );
	if ( ! $t ) {
		$t = wp_generate_password( 40, false );
		update_option( 'ot_leads_inbound_token', $t, false );
	}
	return $t;
}

/* --------------------------------------------------------------------- events */

/** Reassigned: forward to the new agent's CRM, and hand nurture to it if that is their choice. */
add_action( 'ot_lead_assigned', function ( $id, $to, $from, $actor ) {
	$lead = ot_lead( $id );
	if ( ! $lead || ! $to ) {
		return;
	}
	if ( ot_leads_agent_crm_nurtures( $to ) ) {
		ot_nurture_exit_all( $id, 'Their CRM sends follow-up' );
	}
	$mine = array_values( array_filter( ot_leads_connectors( $lead ), fn( $c ) => str_ends_with( $c, '@' . $to ) ) );
	// A new lead's first assignment is forwarded with the lead itself (capture.php).
	if ( $mine && 'routing' !== $actor ) {
		ot_leads_outbox_add( $lead, array(), 'assigned', $mine );
	}
}, 10, 4 );

/** Unsubscribed on the site: tell each lead's CRM (webhooks; see connectors.php). */
add_action( 'ot_leads_suppressed', function ( $email, $reason, $ids ) {
	if ( 'crm' === $reason ) {
		return; // it came from the CRM; nothing to send back
	}
	foreach ( $ids as $id ) {
		$lead = ot_lead( (int) $id );
		if ( $lead ) {
			ot_leads_outbox_add( $lead, array(), 'unsubscribed' );
		}
	}
}, 10, 3 );

/* --------------------------------------------------------------------- updates from a CRM */

add_action( 'rest_api_init', function () {
	register_rest_route( 'ot-leads/v1', '/crm-update', array(
		'methods'             => 'POST',
		'permission_callback' => function ( WP_REST_Request $r ) {
			$h = (string) $r->get_header( 'authorization' );
			return str_starts_with( $h, 'Bearer ' ) && hash_equals( ot_leads_inbound_token(), substr( $h, 7 ) );
		},
		'callback'            => 'ot_leads_rest_crm_update',
	) );
} );

function ot_leads_rest_crm_update( WP_REST_Request $r ) {
	global $wpdb;
	$email = strtolower( sanitize_email( (string) $r->get_param( 'email' ) ) );
	$event = sanitize_key( (string) $r->get_param( 'event' ) );
	if ( ! is_email( $email ) ) {
		return new WP_Error( 'ot_email', 'A valid email is required.', array( 'status' => 400 ) );
	}
	$ids = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . ot_leads_table( 'leads' ) . ' WHERE email = %s', $email ) ) );
	if ( in_array( $event, array( 'unsubscribed', 'do_not_contact', 'unsubscribe' ), true ) ) {
		ot_leads_suppress( $email, 'crm' );
		return array( 'ok' => true, 'leads' => count( $ids ) );
	}
	if ( 'stage' === $event ) {
		$stage = sanitize_key( (string) $r->get_param( 'stage' ) );
		if ( ! isset( OT_LEAD_STAGES[ $stage ] ) ) {
			return new WP_Error( 'ot_stage', 'Unknown stage. Use one of: ' . implode( ', ', array_keys( OT_LEAD_STAGES ) ), array( 'status' => 400 ) );
		}
		foreach ( $ids as $id ) {
			ot_lead_update( $id, array( 'stage' => $stage ), 'crm' );
		}
		return array( 'ok' => true, 'leads' => count( $ids ) );
	}
	return new WP_Error( 'ot_event', 'Unknown event. Use "unsubscribed" or "stage".', array( 'status' => 400 ) );
}

/* --------------------------------------------------------------------- settings */

/** Settings > Each agent's CRM (printed inside the settings screen). */
function ot_leads_agent_crm_card() {
	$rows = '';
	foreach ( ot_leads_agents() as $slug => $a ) {
		if ( $a['team'] ) {
			continue;
		}
		$c   = ot_leads_agent_cfg( $slug );
		$n   = 'agent_crm[' . esc_attr( $slug ) . ']';
		$set = fn( $v ) => $v ? ' placeholder="(set, leave empty to keep)"' : '';
		$rows .= '<tr><th>' . esc_html( $a['name'] ) . '</th><td>'
			. '<p><label>Follow-up emails <select name="' . $n . '[nurture]"><option value="site"' . selected( $c['nurture'], 'site', false ) . '>Sent by this site (sequences)</option><option value="crm"' . selected( $c['nurture'], 'crm', false ) . '>Sent by their own CRM</option></select></label></p>'
			. '<p><label>Webhook URL (Zapier, Make, or their CRM) <input type="url" class="regular-text" name="' . $n . '[webhook_url]" value="' . esc_attr( $c['webhook_url'] ) . '" placeholder="https://"></label></p>'
			. '<p><label>Webhook secret <input type="password" autocomplete="new-password" name="' . $n . '[webhook_secret]" value=""' . $set( $c['webhook_secret'] ) . '></label> &nbsp; <label>Follow Up Boss API key <input type="password" autocomplete="new-password" name="' . $n . '[fub_key]" value=""' . $set( $c['fub_key'] ) . '></label></p>'
			. ( 'crm' === $c['nurture'] && ! ot_leads_agent_crm_nurtures( $slug ) ? '<p class="ot-muted">Their CRM is chosen for follow-up but no connection is set, so this site keeps sending it.</p>' : '' )
			. '</td></tr>';
	}
	$endpoint = rest_url( 'ot-leads/v1/crm-update' );
	return '<div class="ot-card" id="agent-crm"><h2>Each agent\'s CRM</h2><p class="ot-muted">Optional. Leave empty while an agent works from this inbox. When an agent uses their own CRM, their leads are forwarded to it (with retries) and it can send their follow-up emails instead of this site, never both.</p>'
		. ot_leads_form( 'save_agent_crm' ) . '<table class="form-table">' . $rows . '</table><p><button class="button button-primary">Save agents\' CRM settings</button></p></form>'
		. '<h3 style="margin:18px 0 6px;font-size:14px">Updates from a CRM</h3><p class="ot-muted">A CRM (or Zapier) can tell this site when someone unsubscribes or changes stage. POST JSON to <code>' . esc_html( $endpoint ) . '</code> with the header <code>Authorization: Bearer ' . esc_html( ot_leads_inbound_token() ) . '</code>. Body: <code>{"email":"…","event":"unsubscribed"}</code> or <code>{"email":"…","event":"stage","stage":"closed"}</code>.</p></div>';
}

add_action( 'ot_leads_admin_action', function ( $do ) {
	if ( 'save_agent_crm' !== $do || ! current_user_can( 'ot_leads_manage' ) ) {
		return;
	}
	$in  = (array) wp_unslash( $_POST['agent_crm'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$all = (array) get_option( 'ot_leads_agent_crm', array() );
	foreach ( $in as $slug => $v ) {
		$slug = sanitize_title( $slug );
		if ( ! isset( ot_leads_agents()[ $slug ] ) ) {
			continue;
		}
		$old = ot_leads_agent_cfg( $slug );
		$url = esc_url_raw( trim( (string) ( $v['webhook_url'] ?? '' ) ) );
		$all[ $slug ] = array(
			'nurture'        => 'crm' === ( $v['nurture'] ?? '' ) ? 'crm' : 'site',
			'webhook_url'    => ot_leads_https( $url ) ? $url : '',
			'webhook_secret' => '' !== ( $v['webhook_secret'] ?? '' ) ? sanitize_text_field( $v['webhook_secret'] ) : $old['webhook_secret'],
			'fub_key'        => '' !== ( $v['fub_key'] ?? '' ) ? sanitize_text_field( $v['fub_key'] ) : $old['fub_key'],
		);
		// Handing follow-up to their CRM stops the site's sequences for their leads.
		if ( 'crm' === $all[ $slug ]['nurture'] && 'crm' !== $old['nurture'] ) {
			update_option( 'ot_leads_agent_crm', $all, false );
			global $wpdb;
			foreach ( $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . ot_leads_table( 'leads' ) . ' WHERE assigned_agent = %s', $slug ) ) as $id ) {
				if ( ot_leads_agent_crm_nurtures( $slug ) ) {
					ot_nurture_exit_all( (int) $id, 'Their CRM sends follow-up' );
				}
			}
		}
	}
	update_option( 'ot_leads_agent_crm', $all, false );
	wp_safe_redirect( add_query_arg( 'ot_msg', rawurlencode( 'Agents\' CRM settings saved.' ), admin_url( 'admin.php?page=ot-leads-settings#agent-crm' ) ) );
	exit;
} );
