<?php
/**
 * Routing and alerts (spec § 5).
 *
 * Rules, first match wins: (1) the named agent (an agent's form, or the listing agent of a
 * showing or open-house sign-in); (2) the agent who owns the town (Settings > Routing);
 * (3) the owner set for the lead type; (4) the team (no single owner; both team leaders).
 *
 * The assigned agent gets an instant email with the lead and a signed "I'm on it" link. If
 * nobody marks the lead contacted within the response window during business hours, the alert
 * goes to the team leaders. "I'm on it" opens a confirmation page and changes the lead only on
 * POST, so email scanners that open links cannot mark leads contacted.
 */

defined( 'ABSPATH' ) || exit;

function ot_leads_route( $lead, $named = '' ) {
	$agents = ot_leads_agents();
	if ( OT_LEADS_TEAM_PROFILE === $named ) {
		return ''; // the joint profile's form: the team
	}
	if ( $named && isset( $agents[ $named ] ) ) {
		return $named;
	}
	$owners = (array) ot_leads_opt( 'town_owners' );
	foreach ( array_filter( explode( ',', (string) $lead['towns'] ) ) as $town ) {
		$top = strtok( $town, '/' ); // a neighborhood follows its city
		foreach ( array( $town, $top ) as $k ) {
			if ( ! empty( $owners[ $k ] ) && isset( $agents[ $owners[ $k ] ] ) ) {
				return $owners[ $k ];
			}
		}
	}
	$types = (array) ot_leads_opt( 'type_owners' );
	if ( ! empty( $types[ $lead['lead_type'] ] ) && isset( $agents[ $types[ $lead['lead_type'] ] ] ) ) {
		return $types[ $lead['lead_type'] ];
	}
	return ''; // the team: Brandon and Kaitlyn
}

/* --------------------------------------------------------------------- alerts */

function ot_leads_admin_link( $id ) {
	return admin_url( 'admin.php?page=ot-lead&id=' . (int) $id );
}

function ot_leads_claim_url( $id ) {
	$exp = time() + 3 * DAY_IN_SECONDS;
	return add_query_arg( 'ot_claim', $id . '.' . $exp . '.' . ot_leads_sign( 'claim|' . $id . '|' . $exp ), home_url( '/' ) );
}

/** The lead in a few lines, for alert emails. Personal data goes only to the team's own inboxes. */
function ot_leads_summary_html( $lead, $form = array() ) {
	$rows = array(
		'Name'      => ot_lead_name( $lead ),
		'Email'     => '<a href="mailto:' . esc_attr( $lead['email'] ) . '">' . esc_html( $lead['email'] ) . '</a>',
		'Phone'     => $lead['phone'] ? '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $lead['phone'] ) ) . '">' . esc_html( $lead['phone'] ) . '</a>' : '',
		'Prefers'   => ucfirst( $lead['preferred'] ),
		'Type'      => OT_LEAD_TYPES[ $lead['lead_type'] ] ?? $lead['lead_type'],
		'About'     => $lead['listing_id'] ? '<a href="' . esc_url( get_permalink( $lead['listing_id'] ) ) . '">' . esc_html( get_the_title( $lead['listing_id'] ) ) . '</a>' : esc_html( str_replace( ',', ', ', $lead['towns'] ) ),
		'Interest'  => esc_html( $form['topic'] ?? $lead['topic'] ),
		'Message'   => nl2br( esc_html( $form['message'] ?? '' ) ),
		'From page' => $lead['source_url'] ? '<a href="' . esc_url( $lead['source_url'] ) . '">' . esc_html( wp_parse_url( $lead['source_url'], PHP_URL_PATH ) ?: '/' ) . '</a>' : '',
	);
	$out = '<table style="border-collapse:collapse;width:100%;font-size:15px">';
	foreach ( $rows as $k => $v ) {
		if ( '' !== trim( wp_strip_all_tags( (string) $v ) ) ) {
			$out .= '<tr><th style="text-align:left;vertical-align:top;padding:6px 12px 6px 0;color:#5B6670;font-weight:500;width:7.5rem">' . esc_html( $k ) . '</th><td style="padding:6px 0">' . ( in_array( $k, array( 'Email', 'Phone', 'About', 'Message', 'From page', 'Interest' ), true ) ? $v : esc_html( $v ) ) . '</td></tr>';
		}
	}
	return $out . '</table>';
}

/** Email the assigned agent about a new lead (or a follow-up from an existing one). */
function ot_leads_alert( $lead, $form = array(), $followup = false ) {
	$to    = ot_leads_agent_emails( $lead['assigned_agent'] );
	$type  = OT_LEAD_TYPES[ $lead['lead_type'] ] ?? 'Lead';
	$subj  = ( $followup ? 'Follow-up from ' : 'New lead: ' ) . ot_lead_name( $lead ) . ' (' . $type . ')';
	$btn   = 'display:inline-block;padding:12px 20px;background:#071D2D;color:#F7F3EC;text-decoration:none;font-weight:600;margin:4px 8px 4px 0';
	$html  = '<p style="margin:0 0 16px">' . ( $followup ? 'An existing lead sent another message.' : 'A new lead from oberdorferteam.com. Reply by their preferred method, then press "I\'m on it".' ) . '</p>'
		. ot_leads_summary_html( $lead, $form )
		. '<p style="margin:22px 0 0"><a href="' . esc_url( ot_leads_claim_url( $lead['id'] ) ) . '" style="' . $btn . '">I\'m on it</a><a href="' . esc_url( ot_leads_admin_link( $lead['id'] ) ) . '" style="' . $btn . ';background:#ffffff;color:#071D2D;border:1px solid #071D2D">Open the lead</a></p>';
	foreach ( $to as $email ) {
		list( $ok, $info ) = ot_leads_mail( $email, $subj, $html, array( 'reply_to' => $lead['email'], 'reply_name' => ot_lead_name( $lead ), 'tags' => array( 'alert' ), 'lead_id' => $lead['id'] ) );
		ot_lead_event( $lead['id'], 'alert_sent', array( 'to' => $email, 'ok' => $ok, 'info' => $ok ? '' : $info ), 'system' );
	}
}

/** "We have your message" to the person, from the assigned agent; at most one a day per address. */
function ot_leads_confirm( $lead, $form ) {
	if ( ! ot_leads_opt( 'confirm_email' ) || in_array( $lead['lead_type'], array( 'openhouse' ), true ) ) {
		return;
	}
	$key = 'ot_conf_' . md5( strtolower( $lead['email'] ) );
	if ( get_transient( $key ) ) {
		return;
	}
	set_transient( $key, 1, DAY_IN_SECONDS );
	$a    = ot_leads_agents()[ $lead['assigned_agent'] ] ?? null;
	$from = $a && ! $a['team'] ? $a['name'] . ', The Oberdorfer Team' : 'Brandon & Kaitlyn Oberdorfer';
	$who  = $a && ! $a['team'] ? $a['given'] : 'Brandon or Kaitlyn';
	$tel  = $a && ! $a['team'] && $a['phone'] ? $a['phone'] : OT_PHONE;
	$html = '<p>Hi ' . esc_html( $lead['first_name'] ?: 'there' ) . ',</p><p>Thank you for getting in touch. ' . esc_html( $who ) . ' has your message and will reply ' . ( $lead['preferred'] ? 'by ' . esc_html( $lead['preferred'] ) . ' ' : '' ) . 'soon, usually within the hour during business hours.</p><p>If it is urgent, call or text ' . esc_html( $tel ) . '.</p><p>' . esc_html( $from ) . '<br>The Oberdorfer Team · REWAP Brokerage LLC</p>';
	$reply = $a && ! $a['team'] && $a['email'] ? $a['email'] : ( ot_leads_leader_emails()[0] ?? '' );
	list( $ok ) = ot_leads_mail( $lead['email'], 'We have your message', $html, array( 'from_name' => $from, 'reply_to' => $reply, 'tags' => array( 'confirmation' ) ) );
	ot_lead_event( $lead['id'], 'email_sent', array( 'subject' => 'We have your message', 'ok' => $ok ), 'system' );
}

/* --------------------------------------------------------------------- I'm on it */

add_action( 'init', function () {
	if ( empty( $_GET['ot_claim'] ) ) {
		return;
	}
	list( $id, $exp, $sig ) = array_pad( explode( '.', sanitize_text_field( wp_unslash( $_GET['ot_claim'] ) ) ), 3, '' );
	$valid = ctype_digit( $id ) && ctype_digit( $exp ) && (int) $exp > time() && hash_equals( ot_leads_sign( 'claim|' . $id . '|' . $exp ), $sig );
	$lead  = $valid ? ot_lead( (int) $id ) : null;
	header( 'X-Robots-Tag: noindex' );
	if ( ! $lead ) {
		ot_leads_page( 'This link has expired', '<p>Open the lead in WordPress instead: Leads, in the admin menu.</p>' );
	}
	$first = esc_html( $lead['first_name'] ?: 'this lead' );
	if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
		if ( ! $lead['contacted_at'] ) {
			ot_lead_update( (int) $id, array( 'contacted_at' => ot_leads_now(), 'stage' => 'new' === $lead['stage'] ? 'contacted' : $lead['stage'] ), 'claim link' );
			ot_lead_event( (int) $id, 'claimed', array(), 'claim link' );
		}
		ot_leads_page( 'Thank you', '<p>' . $first . ' is marked as contacted, so the alert will not go to the team leaders.</p><p><a href="' . esc_url( ot_leads_admin_link( $id ) ) . '">Open the lead</a></p>' );
	}
	ot_leads_page( 'Are you on it?', '<p>Mark ' . $first . ' as contacted. Do this once you have replied or called.</p><form method="post"><button type="submit" style="font:inherit;padding:12px 22px;background:#071D2D;color:#F7F3EC;border:0;cursor:pointer">I\'m on it</button></form>' );
}, 1 );

/* --------------------------------------------------------------------- escalation */

/** Inside business hours, Massachusetts time? */
function ot_leads_business_hours( $ts = null ) {
	$d = new DateTime( '@' . ( $ts ?? time() ) );
	$d->setTimezone( new DateTimeZone( 'America/New_York' ) );
	$h = (int) $d->format( 'G' );
	return 7 !== (int) $d->format( 'N' ) && $h >= (int) ot_leads_opt( 'hours_start' ) && $h < (int) ot_leads_opt( 'hours_end' );
}

/** New leads nobody has marked contacted within the window go to the leaders (once). */
function ot_leads_escalate() {
	global $wpdb;
	if ( ! ot_leads_business_hours() ) {
		return;
	}
	$cut  = gmdate( 'Y-m-d H:i:s', time() - max( 5, (int) ot_leads_opt( 'response_minutes' ) ) * MINUTE_IN_SECONDS );
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . ot_leads_table( 'leads' ) . " WHERE stage = 'new' AND contacted_at IS NULL AND escalated = 0 AND created_at <= %s AND created_at >= %s", $cut, gmdate( 'Y-m-d H:i:s', time() - 3 * DAY_IN_SECONDS ) ), ARRAY_A );
	foreach ( $rows as $lead ) {
		$wpdb->update( ot_leads_table( 'leads' ), array( 'escalated' => 1 ), array( 'id' => $lead['id'] ) );
		$html = '<p>Nobody has marked this lead contacted yet (' . esc_html( human_time_diff( strtotime( $lead['created_at'] . ' UTC' ) ) ) . ' since it arrived). It was assigned to ' . esc_html( ot_leads_agents()[ $lead['assigned_agent'] ]['name'] ?? 'nobody' ) . '.</p>' . ot_leads_summary_html( $lead ) . '<p style="margin-top:20px"><a href="' . esc_url( ot_leads_claim_url( $lead['id'] ) ) . '">I\'m on it</a> · <a href="' . esc_url( ot_leads_admin_link( $lead['id'] ) ) . '">Open the lead</a></p>';
		foreach ( ot_leads_leader_emails() as $to ) {
			ot_leads_mail( $to, 'Waiting for a reply: ' . ot_lead_name( $lead ), $html, array( 'reply_to' => $lead['email'], 'tags' => array( 'escalation' ) ) );
		}
		ot_lead_event( (int) $lead['id'], 'escalated', array( 'to' => ot_leads_leader_emails() ), 'system' );
	}
}
