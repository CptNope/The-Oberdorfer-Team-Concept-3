<?php
/**
 * Leads > Today: what needs doing now, for the signed-in agent (team leaders can look at
 * anyone's). New leads waiting for a first reply, tasks due today or overdue, people who are
 * back on the site, and what is coming up this week.
 *
 * One-tap logging: each row (and the lead screen) has buttons for what just happened, Called,
 * Voicemail, No answer, Texted, Emailed, Met, They replied. A tap logs it on the timeline,
 * counts as the first reply if there was none, closes the task it came from, and books the
 * next follow-up: tomorrow after a voicemail or no answer, in 3 days after a text or email,
 * or whenever is chosen.
 */

defined( 'ABSPATH' ) || exit;

const OT_TOUCH_OUTCOMES = array(
	'called'    => 'Called, spoke',
	'voicemail' => 'Voicemail',
	'no_answer' => 'No answer',
	'texted'    => 'Texted',
	'emailed'   => 'Emailed',
	'met'       => 'Met',
	'replied'   => 'They replied',
);

/** Follow-up booked automatically after each outcome, in days (0 = none). */
const OT_TOUCH_NEXT = array( 'called' => 0, 'voicemail' => 1, 'no_answer' => 1, 'texted' => 3, 'emailed' => 3, 'met' => 0, 'replied' => 0 );

/** The one-tap buttons for a lead ($task: the task the row came from, closed on a tap). */
function ot_leads_touch_form( $id, $task = 0, $compact = true ) {
	$h = ot_leads_form( 'touch', $id, 'class="ot-touch"' ) . ( $task ? '<input type="hidden" name="task" value="' . (int) $task . '">' : '' );
	foreach ( OT_TOUCH_OUTCOMES as $k => $label ) {
		$h .= '<button class="button button-small" name="outcome" value="' . esc_attr( $k ) . '">' . esc_html( $label ) . '</button> ';
	}
	$h .= '<select name="next" aria-label="Then follow up"><option value="auto">Next: automatic</option><option value="0">No follow-up</option><option value="1">Tomorrow</option><option value="3">In 3 days</option><option value="7">In a week</option><option value="14">In 2 weeks</option></select>';
	if ( ! $compact ) {
		$h .= '<p><input type="text" name="note" class="large-text" placeholder="Optional note: what they said, what is next"></p>';
	}
	return $h . '</form>';
}

/** The lead screen's card. */
function ot_leads_touch_card( $id ) {
	return '<div class="ot-card"><h2>Log a touch</h2><p class="ot-muted" style="margin-top:-6px">Tap what just happened. It counts as the first reply, closes today\'s task and books the next one.</p>' . ot_leads_touch_form( $id, 0, false ) . '</div>';
}

add_action( 'ot_leads_admin_action', function ( $do, $id ) {
	if ( 'touch' !== $do || ! $id ) {
		return;
	}
	global $wpdb;
	$lead    = ot_lead( $id );
	$outcome = sanitize_key( $_POST['outcome'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification -- checked in the handler
	if ( ! $lead || ! isset( OT_TOUCH_OUTCOMES[ $outcome ] ) ) {
		return;
	}
	$me   = wp_get_current_user()->user_login;
	$note = sanitize_text_field( wp_unslash( $_POST['note'] ?? '' ) );
	$nx   = sanitize_key( $_POST['next'] ?? 'auto' );
	$days = 'auto' === $nx ? OT_TOUCH_NEXT[ $outcome ] : max( 0, min( 60, (int) $nx ) );

	// Close the task the tap came from, or else any task due today or earlier.
	$t = ot_leads_table( 'tasks' );
	if ( ! empty( $_POST['task'] ) ) {
		$wpdb->update( $t, array( 'done_at' => ot_leads_now() ), array( 'id' => (int) $_POST['task'], 'lead_id' => $id ) );
	} else {
		$wpdb->query( $wpdb->prepare( "UPDATE $t SET done_at = %s WHERE lead_id = %d AND done_at IS NULL AND due_at <= %s", ot_leads_now(), $id, ot_leads_day_end() ) );
	}

	$next = '';
	if ( $days ) {
		$due = new DateTime( '+' . $days . ' day 9:00', new DateTimeZone( 'America/New_York' ) );
		$wpdb->insert( $t, array(
			'lead_id'    => $id,
			'assignee'   => $lead['assigned_agent'],
			'kind'       => in_array( $outcome, array( 'texted' ), true ) ? 'text' : ( 'emailed' === $outcome ? 'email' : 'call' ),
			'note'       => 'Follow up (' . strtolower( OT_TOUCH_OUTCOMES[ $outcome ] ) . ' ' . ot_leads_time( ot_leads_now(), 'M j' ) . ')',
			'due_at'     => $due->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ),
			'created_at' => ot_leads_now(),
		) );
		$next = $due->setTimezone( new DateTimeZone( 'America/New_York' ) )->format( 'D M j' );
	}
	ot_lead_event( $id, 'touch', array( 'outcome' => $outcome, 'note' => $note, 'next' => $next ), $me );

	$upd = array();
	if ( ! $lead['contacted_at'] ) {
		$upd['contacted_at'] = ot_leads_now();
	}
	if ( 'new' === $lead['stage'] ) {
		$upd['stage'] = 'contacted';
	}
	if ( $upd ) {
		ot_lead_update( $id, $upd, $me );
	}
	if ( 'replied' === $outcome ) {
		ot_nurture_exit_all( $id, 'They replied', array( 'buyer', 'seller', 'relocation' ) );
	}
	$msg = ot_lead_name( $lead ) . ': ' . strtolower( OT_TOUCH_OUTCOMES[ $outcome ] ) . ' logged' . ( $next ? '; follow-up booked for ' . $next : '' ) . '.';
	wp_safe_redirect( add_query_arg( 'ot_msg', rawurlencode( $msg ), remove_query_arg( 'ot_msg', wp_get_referer() ?: admin_url( 'admin.php?page=ot-leads-today' ) ) ) );
	exit;
}, 10, 2 );

function ot_leads_today_screen() {
	$all   = current_user_can( 'ot_leads_view_all' );
	$agent = $all ? sanitize_title( wp_unslash( $_GET['agent'] ?? '' ) ) : ot_leads_user_agent(); // phpcs:ignore WordPress.Security.NonceVerification
	$d     = ot_leads_today_data( $agent );
	$who   = fn( $l ) => '<a href="' . esc_url( ot_leads_admin_link( $l['id'] ) ) . '"><strong>' . esc_html( ot_lead_name( $l ) ) . '</strong></a>' . ( $all && ! $agent ? ' <span class="ot-muted">· ' . esc_html( ot_leads_agent_name( $l['assigned_agent'] ) ) . '</span>' : '' );

	echo '<div class="wrap ot-wrap"><h1>Today</h1>';
	ot_leads_notice();
	if ( current_user_can( 'ot_leads_manage' ) ) {
		echo ot_leads_health_notices(); // phpcs:ignore WordPress.Security.EscapeOutput
	}
	if ( $all ) {
		echo '<form method="get" style="margin:8px 0 16px"><input type="hidden" name="page" value="ot-leads-today">' . ot_leads_agent_select( 'agent', $agent, 'Everyone' ) . ' <button class="button">Show</button></form>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '<p style="font-size:14px"><strong>' . count( $d['waiting'] ) . '</strong> waiting for a first reply · <strong>' . count( $d['due'] ) . '</strong> follow-ups due · <strong>' . count( $d['back'] ) . '</strong> back on the site · <a href="' . esc_url( admin_url( 'admin.php?page=ot-leads-queue' ) ) . '"><strong>' . (int) $d['drafts'] . '</strong> emails to approve</a></p>';

	echo '<div class="ot-card"><h2>Waiting for a first reply</h2>';
	if ( ! $d['waiting'] ) {
		echo '<p class="ot-muted">Nobody is waiting. Nice work.</p>';
	}
	foreach ( $d['waiting'] as $l ) {
		$age = human_time_diff( strtotime( $l['created_at'] . ' UTC' ) );
		echo '<div class="ot-row"><p>' . $who( $l ) . ' · ' . esc_html( OT_LEAD_TYPES[ $l['lead_type'] ] ?? $l['lead_type'] ) . ' · <span class="' . ( strtotime( $l['created_at'] . ' UTC' ) < time() - (int) ot_leads_opt( 'response_minutes' ) * 60 ? 'ot-late' : 'ot-muted' ) . '">' . esc_html( $age ) . ' ago</span> · ' . ot_leads_contact_links( $l ) . ( $l['preferred'] ? ' <span class="ot-muted">(prefers ' . esc_html( $l['preferred'] ) . ')</span>' : '' ) . '</p>' . ot_leads_touch_form( (int) $l['id'] ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</div>';

	echo '<div class="ot-card"><h2>Follow-ups due today and overdue</h2>';
	if ( ! $d['due'] ) {
		echo '<p class="ot-muted">Nothing due.</p>';
	}
	foreach ( $d['due'] as $l ) {
		$late = strtotime( $l['due_at'] . ' UTC' ) < strtotime( ot_leads_day_end( -1 ) . ' UTC' );
		echo '<div class="ot-row"><p>' . $who( $l ) . ' · ' . esc_html( ucfirst( $l['kind'] ) ) . ( $l['note'] ? ': ' . esc_html( $l['note'] ) : '' ) . ' · <span class="' . ( $late ? 'ot-late' : 'ot-muted' ) . '">' . ( $late ? 'overdue since ' . esc_html( ot_leads_time( $l['due_at'], 'M j' ) ) : 'today' ) . '</span> · ' . ot_leads_contact_links( $l ) . '</p>' . ot_leads_touch_form( (int) $l['id'], (int) $l['task_id'] ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</div>';

	echo '<div class="ot-card"><h2>Back on the site <span class="ot-muted" style="font-weight:400">· last 3 days</span></h2>';
	if ( ! $d['back'] ) {
		echo '<p class="ot-muted">No returning visitors yet. People who send a form and come back show up here.</p>';
	}
	foreach ( $d['back'] as $l ) {
		echo '<div class="ot-row"><p>' . $who( $l ) . ' · ' . esc_html( ot_leads_last_signal( (int) $l['id'] ) ) . ' · <span class="ot-muted">' . esc_html( human_time_diff( strtotime( $l['seen_at'] . ' UTC' ) ) ) . ' ago</span> · ' . ot_leads_contact_links( $l ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</div>';

	echo '<div class="ot-card"><h2>Coming up this week</h2>';
	if ( ! $d['week'] ) {
		echo '<p class="ot-muted">Nothing booked.</p>';
	} else {
		echo '<table class="widefat striped"><tbody>';
		foreach ( $d['week'] as $l ) {
			echo '<tr><td style="width:9rem">' . esc_html( ot_leads_time( $l['due_at'], 'D M j' ) ) . '</td><td>' . $who( $l ) . '</td><td>' . esc_html( ucfirst( $l['kind'] ) . ( $l['note'] ? ': ' . $l['note'] : '' ) ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</tbody></table>';
	}
	echo '</div></div>';
}

add_action( 'admin_head', function () {
	if ( str_starts_with( sanitize_key( $_GET['page'] ?? '' ), 'ot-lead' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<style>.ot-row{border-top:1px solid #f0f0f1;padding:10px 0}.ot-row:first-of-type{border-top:0}.ot-row p{margin:0 0 6px}.ot-touch .button-small{margin:0 2px 4px 0}.ot-late{color:#b32d2e;font-weight:600}.ot-bar{display:inline-block;height:10px;background:#B49A65;vertical-align:middle;margin-right:6px}.ot-health li{margin:4px 0}</style>';
	}
} );
