<?php
/**
 * Signals: when someone the team already knows comes back, the agent hears about it.
 *
 * After a form, the site notes the pages that person views (the ot_lid cookie, capture.php;
 * the privacy policy says so). Here those visits become signals:
 * - back on the site after 7 days or more away;
 * - looked at one of the team's listings;
 * - opened a town guide they had not asked about (the town is added to their interests, so
 *   their monthly update covers it);
 * - three pages in a day.
 * A signal is logged on the timeline. At most once every 3 days per lead, it also creates a
 * call task for the assigned agent and emails them. Lost leads are left alone.
 *
 * Daily: a contacted, appointment or active lead with no activity for 14 days and no open
 * task gets a "check in" task, so nobody goes quiet by accident.
 */

defined( 'ABSPATH' ) || exit;

const OT_SIGNAL_GAP_DAYS   = 7;
const OT_SIGNAL_QUIET_DAYS = 3;
const OT_STALE_DAYS        = 14;

/** Called after a known visitor's page view is recorded. */
function ot_leads_signal_check( $id, $path ) {
	global $wpdb;
	$lead = ot_lead( $id );
	if ( ! $lead || 'lost' === $lead['stage'] ) {
		return;
	}
	$ev  = ot_leads_table( 'lead_events' );
	$why = array();

	// Back after a gap: the visit or form before this one was a week or more ago.
	$prev = $wpdb->get_var( $wpdb->prepare( "SELECT created_at FROM $ev WHERE lead_id = %d AND type IN ('page_view','form') ORDER BY id DESC LIMIT 1 OFFSET 1", $id ) );
	if ( $prev && strtotime( $prev . ' UTC' ) < time() - OT_SIGNAL_GAP_DAYS * DAY_IN_SECONDS ) {
		$why[] = 'back on the site after ' . human_time_diff( strtotime( $prev . ' UTC' ) );
	}

	// One of the team's listings.
	$pid = url_to_postid( home_url( $path ) );
	if ( $pid && 'ot_listing' === get_post_type( $pid ) ) {
		$why[] = 'looked at ' . get_the_title( $pid );
	}

	// A town guide they had not asked about: add it to their interests.
	if ( preg_match( '#^/communities/([a-z0-9-]+)(?:/([a-z0-9-]+))?/?$#', (string) $path, $m ) ) {
		$slug = $m[1] . ( ! empty( $m[2] ) ? '/' . $m[2] : '' );
		$town = get_page_by_path( $slug, OBJECT, 'ot_community' );
		$have = array_filter( explode( ',', (string) $lead['towns'] ) );
		if ( $town && ! in_array( $slug, $have, true ) && ! in_array( $m[1], $have, true ) ) {
			$have[] = $slug;
			ot_lead_update( $id, array( 'towns' => implode( ',', $have ) ), 'signal' );
			$why[] = 'started reading the ' . $town->post_title . ' guide';
		}
	}

	// Three pages in a day (counted once, on the third).
	$today = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $ev WHERE lead_id = %d AND type = 'page_view' AND created_at >= %s", $id, gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ) ) );
	if ( 3 === $today ) {
		$why[] = 'three pages in a day';
	}

	if ( ! $why ) {
		return;
	}
	$text = ucfirst( implode( '; ', $why ) );
	ot_lead_event( $id, 'signal', array( 'why' => $text, 'path' => $path ), 'system' );

	$key = 'ot_sig_' . $id;
	if ( get_transient( $key ) ) {
		return;
	}
	set_transient( $key, 1, OT_SIGNAL_QUIET_DAYS * DAY_IN_SECONDS );
	$wpdb->insert( ot_leads_table( 'tasks' ), array(
		'lead_id'    => $id,
		'assignee'   => $lead['assigned_agent'],
		'kind'       => 'call',
		'note'       => substr( 'Back on the site: ' . $text, 0, 250 ),
		'due_at'     => ot_leads_now(),
		'created_at' => ot_leads_now(),
	) );
	ot_lead_event( $id, 'task_added', array( 'kind' => 'call', 'due' => 'now', 'why' => 'signal' ), 'system' );
	$lead = ot_lead( $id );
	$html = '<p><strong>' . esc_html( ot_lead_name( $lead ) ) . '</strong> is on oberdorferteam.com: ' . esc_html( $text ) . '.</p><p>A good moment to reach out. A call task is on your list.</p>'
		. ot_leads_summary_html( $lead )
		. '<p style="margin-top:20px"><a href="' . esc_url( ot_leads_admin_link( $id ) ) . '">Open the lead</a> · <a href="' . esc_url( admin_url( 'admin.php?page=ot-leads-today' ) ) . '">Today</a></p>';
	foreach ( ot_leads_agent_emails( $lead['assigned_agent'] ) as $to ) {
		ot_leads_mail( $to, ot_lead_name( $lead ) . ' is back on the site', $html, array( 'tags' => array( 'signal' ), 'lead_id' => $id ) );
	}
}

/** Daily: a check-in task for working leads that have gone quiet. */
function ot_leads_stale_tasks() {
	global $wpdb;
	$l    = ot_leads_table( 'leads' );
	$t    = ot_leads_table( 'tasks' );
	$cut  = gmdate( 'Y-m-d H:i:s', time() - OT_STALE_DAYS * DAY_IN_SECONDS );
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $l WHERE stage IN ('contacted','appointment','active') AND last_activity_at < %s", $cut ), ARRAY_A );
	$made = 0;
	foreach ( $rows as $lead ) {
		$open = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE lead_id = %d AND done_at IS NULL", $lead['id'] ) );
		if ( $open ) {
			continue;
		}
		$wpdb->insert( $t, array(
			'lead_id'    => $lead['id'],
			'assignee'   => $lead['assigned_agent'],
			'kind'       => 'call',
			'note'       => 'No activity for ' . OT_STALE_DAYS . ' days: check in',
			'due_at'     => ot_leads_now(),
			'created_at' => ot_leads_now(),
		) );
		ot_lead_event( (int) $lead['id'], 'task_added', array( 'kind' => 'call', 'why' => 'quiet ' . OT_STALE_DAYS . ' days' ), 'system' );
		++$made;
	}
	return $made;
}

// Before the morning digest, so the new tasks are in it.
add_action( 'ot_leads_daily', 'ot_leads_stale_tasks', 5 );

/* --------------------------------------------------------------------- today (shared by Leads > Today and the morning email) */

/** The end of today in Massachusetts, as UTC. */
function ot_leads_day_end( $days = 0 ) {
	return ( new DateTime( 'tomorrow', new DateTimeZone( 'America/New_York' ) ) )->modify( sprintf( '%+d day', (int) $days ) )->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
}

/** Today's lists for an agent ('' = everyone). Shared by the screen and the morning email. */
function ot_leads_today_data( $agent ) {
	global $wpdb;
	$l   = ot_leads_table( 'leads' );
	$t   = ot_leads_table( 'tasks' );
	$e   = ot_leads_table( 'lead_events' );
	$own = ot_leads_agent_where( $agent, 'l.assigned_agent' );
	return array(
		'waiting' => $wpdb->get_results( "SELECT l.* FROM $l l WHERE l.stage = 'new' AND l.contacted_at IS NULL$own ORDER BY l.created_at ASC LIMIT 50", ARRAY_A ),
		'due'     => $wpdb->get_results( $wpdb->prepare( "SELECT t.id AS task_id, t.kind, t.note, t.due_at, l.* FROM $t t JOIN $l l ON l.id = t.lead_id WHERE t.done_at IS NULL AND t.due_at < %s" . ot_leads_agent_where( $agent, 't.assignee' ) . ' ORDER BY t.due_at ASC LIMIT 100', ot_leads_day_end() ), ARRAY_A ),
		'back'    => $wpdb->get_results( $wpdb->prepare( "SELECT l.*, MAX(e.created_at) AS seen_at FROM $e e JOIN $l l ON l.id = e.lead_id WHERE e.type = 'signal' AND e.created_at >= %s$own GROUP BY l.id ORDER BY seen_at DESC LIMIT 20", gmdate( 'Y-m-d H:i:s', time() - 3 * DAY_IN_SECONDS ) ), ARRAY_A ),
		'week'    => $wpdb->get_results( $wpdb->prepare( "SELECT t.id AS task_id, t.kind, t.note, t.due_at, l.* FROM $t t JOIN $l l ON l.id = t.lead_id WHERE t.done_at IS NULL AND t.due_at >= %s AND t.due_at < %s" . ot_leads_agent_where( $agent, 't.assignee' ) . ' ORDER BY t.due_at ASC LIMIT 50', ot_leads_day_end(), ot_leads_day_end( 7 ) ), ARRAY_A ),
		'drafts'  => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . ot_leads_table( 'messages' ) . " WHERE status = 'draft'" ),
	);
}

/** The last signal's reason for a lead. */
function ot_leads_last_signal( $id ) {
	global $wpdb;
	$p = $wpdb->get_var( $wpdb->prepare( 'SELECT payload FROM ' . ot_leads_table( 'lead_events' ) . " WHERE lead_id = %d AND type = 'signal' ORDER BY id DESC LIMIT 1", $id ) );
	return ( json_decode( (string) $p, true ) ?: array() )['why'] ?? '';
}

function ot_leads_contact_links( $l ) {
	$tel = preg_replace( '/[^0-9+]/', '', (string) $l['phone'] );
	return implode( ' · ', array_filter( array(
		$tel ? '<a href="tel:' . esc_attr( $tel ) . '">Call</a>' : '',
		$tel ? '<a href="sms:' . esc_attr( $tel ) . '">Text</a>' : '',
		$l['email'] ? '<a href="mailto:' . esc_attr( $l['email'] ) . '">Email</a>' : '',
	) ) );
}

