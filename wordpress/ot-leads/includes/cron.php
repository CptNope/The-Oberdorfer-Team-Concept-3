<?php
/**
 * The scheduler. Every minute: escalations, CRM deliveries, nurture drafts and sends. Daily:
 * the task digest and retention clean-up.
 *
 * On Cloudways, set define( 'DISABLE_WP_CRON', true ) and add a server cron job (Application
 * Settings > Cron Job Management) that runs every minute:
 *   wget -q -O - "https://oberdorferteam.com/wp-cron.php?doing_wp_cron" >/dev/null 2>&1
 * Without it WordPress runs these on page loads, which is fine for staging but can lag.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'cron_schedules', function ( $s ) {
	$s['ot_minute'] = array( 'interval' => 60, 'display' => 'Every minute (ot-leads)' );
	return $s;
} );

function ot_leads_schedule() {
	if ( ! wp_next_scheduled( 'ot_leads_tick' ) ) {
		wp_schedule_event( time() + 60, 'ot_minute', 'ot_leads_tick' );
	}
	if ( ! wp_next_scheduled( 'ot_leads_daily' ) ) {
		$d = new DateTime( 'tomorrow 7:30', new DateTimeZone( 'America/New_York' ) );
		wp_schedule_event( $d->getTimestamp(), 'daily', 'ot_leads_daily' );
	}
}

add_action( 'ot_leads_tick', function () {
	if ( get_transient( 'ot_leads_tick_lock' ) ) {
		return;
	}
	set_transient( 'ot_leads_tick_lock', 1, 55 );
	ot_leads_escalate();
	ot_leads_outbox_run();
	ot_nurture_advance();
	ot_nurture_send_due();
	delete_transient( 'ot_leads_tick_lock' );
	update_option( 'ot_leads_last_tick', time(), false );
} );

add_action( 'ot_leads_daily', function () {
	ot_leads_task_digest();
	ot_leads_retention();
} );

/** Each agent's tasks due today, and overdue, in one morning email. */
function ot_leads_task_digest() {
	global $wpdb;
	$end  = ( new DateTime( 'tomorrow', new DateTimeZone( 'America/New_York' ) ) )->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT t.*, l.first_name, l.last_name, l.email FROM ' . ot_leads_table( 'tasks' ) . ' t JOIN ' . ot_leads_table( 'leads' ) . ' l ON l.id = t.lead_id WHERE t.done_at IS NULL AND t.due_at < %s ORDER BY t.due_at', $end ), ARRAY_A );
	$by   = array();
	foreach ( $rows as $r ) {
		$by[ $r['assignee'] ][] = $r;
	}
	foreach ( $by as $agent => $tasks ) {
		$html = '<p>Your follow-ups for today:</p><ul>';
		foreach ( $tasks as $t ) {
			$late  = strtotime( $t['due_at'] . ' UTC' ) < time() - DAY_IN_SECONDS / 2;
			$html .= '<li>' . esc_html( ucfirst( $t['kind'] ) ) . ': <a href="' . esc_url( ot_leads_admin_link( $t['lead_id'] ) ) . '">' . esc_html( trim( $t['first_name'] . ' ' . $t['last_name'] ) ?: $t['email'] ) . '</a>' . ( $t['note'] ? ' · ' . esc_html( $t['note'] ) : '' ) . ( $late ? ' <strong>(overdue)</strong>' : '' ) . '</li>';
		}
		foreach ( ot_leads_agent_emails( $agent ) as $to ) {
			ot_leads_mail( $to, 'Today\'s follow-ups (' . count( $tasks ) . ')', $html . '</ul>', array( 'tags' => array( 'digest' ) ) );
		}
	}
}
