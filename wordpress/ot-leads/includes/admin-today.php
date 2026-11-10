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
	$h = ot_leads_form( 'touch', $id, 'class="ot-touch"' ) . ( $task ? '<input type="hidden" name="task" value="' . (int) $task . '">' : '' ) . '<p class="ot-touch__label">What just happened?</p>';
	foreach ( OT_TOUCH_OUTCOMES as $k => $label ) {
		$h .= '<button class="button button-small" name="outcome" value="' . esc_attr( $k ) . '">' . esc_html( $label ) . '</button>';
	}
	$h .= '<label class="ot-touch__next">Then <select name="next"><option value="auto">follow up automatically</option><option value="0">no follow-up</option><option value="1">follow up tomorrow</option><option value="3">follow up in 3 days</option><option value="7">follow up in a week</option><option value="14">follow up in 2 weeks</option></select></label>';
	if ( ! $compact ) {
		$h .= '<p class="ot-touch__note"><input type="text" name="note" aria-label="Note" placeholder="Optional note: what they said, what is next"></p>';
	}
	return $h . '</form>';
}

/** The Log disclosure on a Today row. */
function ot_leads_log_menu( $id, $task = 0 ) {
	return '<details class="ot-log"><summary class="ot-btn">Log <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></summary><div class="ot-log__panel">' . ot_leads_touch_form( $id, $task ) . '</div></details>';
}

/** The lead screen's card. */
function ot_leads_touch_card( $id ) {
	return '<div class="ot-card"><h2>Log a touch</h2><p class="ot-muted" style="margin-top:-6px">Tap what just happened. It counts as the first reply, closes today\'s task and books the next one.</p>' . ot_leads_touch_form( $id, 0, false ) . '</div>';
}

/** Call, Text and Email as buttons, the person's preferred way first and filled. */
function ot_leads_contact_buttons( $l ) {
	$tel  = preg_replace( '/[^0-9+]/', '', (string) $l['phone'] );
	$opts = array();
	if ( $tel ) {
		$opts['call'] = '<a class="ot-btn%s" href="tel:' . esc_attr( $tel ) . '"><span class="dashicons dashicons-phone" aria-hidden="true"></span>Call</a>';
		$opts['text'] = '<a class="ot-btn%s" href="sms:' . esc_attr( $tel ) . '"><span class="dashicons dashicons-format-chat" aria-hidden="true"></span>Text</a>';
	}
	if ( $l['email'] ) {
		$opts['email'] = '<a class="ot-btn%s" href="mailto:' . esc_attr( $l['email'] ) . '"><span class="dashicons dashicons-email" aria-hidden="true"></span>Email</a>';
	}
	$pref = isset( $opts[ $l['preferred'] ] ) ? $l['preferred'] : array_key_first( $opts );
	$out  = '';
	foreach ( $opts as $k => $html ) {
		$out = $k === $pref ? sprintf( $html, ' ot-btn--primary' ) . $out : $out . sprintf( $html, '' );
	}
	return $out;
}

/** A time today as "9:42 am", otherwise "Mon, Nov 9". */
function ot_leads_when( $utc ) {
	return ot_leads_time( $utc, 'Y-m-d' ) === ( new DateTime( 'now', new DateTimeZone( 'America/New_York' ) ) )->format( 'Y-m-d' ) ? ot_leads_time( $utc, 'g:i a' ) : ot_leads_time( $utc, 'D, M j' );
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
	global $wpdb;
	$all   = current_user_can( 'ot_leads_view_all' );
	$agent = $all ? sanitize_title( wp_unslash( $_GET['agent'] ?? '' ) ) : ot_leads_user_agent(); // phpcs:ignore WordPress.Security.NonceVerification
	$d     = ot_leads_today_data( $agent );
	$late  = time() - (int) ot_leads_opt( 'response_minutes' ) * 60;
	$owner = fn( $l ) => $all && ! $agent ? ot_leads_agent_name( $l['assigned_agent'] ) : '';
	$about = fn( $l ) => $l['listing_id'] ? get_the_title( $l['listing_id'] ) : ( $l['towns'] ? ot_leads_towns_label( $l['towns'] ) : $l['topic'] );
	$row   = function ( $l, $time, $meta, $is_late, $task = 0 ) {
		return '<article class="ot-entry' . ( $is_late ? ' ot-entry--late' : '' ) . '"><div class="ot-entry__time">' . esc_html( $time ) . '</div><div><h3 class="ot-entry__who"><a href="' . esc_url( ot_leads_admin_link( $l['id'] ) ) . '">' . esc_html( ot_lead_name( $l ) ) . '</a></h3><p class="ot-entry__meta">' . $meta . '</p></div><div class="ot-entry__acts">' . ot_leads_contact_buttons( $l ) . ot_leads_log_menu( (int) $l['id'], $task ) . '</div></article>';
	};
	$meta  = fn( ...$bits ) => implode( ' · ', array_filter( $bits ) );

	$words = array( 'No', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine' );
	$num   = fn( $n ) => $words[ $n ] ?? (string) $n;
	$w     = count( $d['waiting'] );
	$due   = count( $d['due'] );
	$back  = count( $d['back'] );
	if ( $w ) {
		$title = esc_html( 1 === $w ? 'One person is waiting.' : $num( $w ) . ' people are waiting.' ) . ' <em>Start with ' . esc_html( $d['waiting'][0]['first_name'] ?: ot_lead_name( $d['waiting'][0] ) ) . '.</em>';
	} elseif ( $due ) {
		$title = 'Nothing new is waiting. <em>' . esc_html( 1 === $due ? 'One follow-up is due.' : $num( $due ) . ' follow-ups are due.' ) . '</em>';
	} elseif ( $back ) {
		$title = 'Everyone is answered. <em>' . esc_html( 1 === $back ? 'One person is back on the site.' : $num( $back ) . ' people are back on the site.' ) . '</em>';
	} else {
		$title = 'All caught up. <em>Nothing is due today.</em>';
	}

	ot_leads_shell( 'today' );
	echo '<div class="ot-day"><section aria-labelledby="ot-today-title">';
	echo '<h1 class="ot-title" id="ot-today-title">' . $title . '</h1><hr class="wp-header-end" style="display:none">'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<div class="ot-day__bar"><span class="ot-day__date">' . esc_html( ( new DateTime( 'now', new DateTimeZone( 'America/New_York' ) ) )->format( 'l, F j' ) ) . ( $agent ? ' · ' . esc_html( ot_leads_agent_name( $agent ) ) : '' ) . '</span>' . ( $all ? ot_leads_agent_chips( 'ot-leads-today', $agent ) : '' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	ot_leads_notice();
	if ( current_user_can( 'ot_leads_manage' ) ) {
		echo ot_leads_health_notices(); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	echo '<section class="ot-group" aria-label="Waiting for a first reply"><h2 class="ot-group__head">Waiting for a first reply <b>' . (int) $w . '</b></h2>';
	if ( ! $w ) {
		echo '<p class="ot-empty">Nobody is waiting. New inquiries land here the moment a form is sent, oldest first.</p>';
	}
	foreach ( $d['waiting'] as $l ) {
		$created = strtotime( $l['created_at'] . ' UTC' );
		echo $row( $l, ot_leads_when( $l['created_at'] ), $meta( esc_html( OT_LEAD_TYPES[ $l['lead_type'] ] ?? $l['lead_type'] ), esc_html( $about( $l ) ), esc_html( 'waiting ' . human_time_diff( $created ) ), $l['preferred'] ? esc_html( 'prefers ' . $l['preferred'] ) : '', esc_html( $owner( $l ) ) ) . ( $created < $late ? ' <span class="ot-pill ot-pill--late">Late</span>' : '' ), $created < $late ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</section>';

	echo '<section class="ot-group" aria-label="Follow-ups due"><h2 class="ot-group__head">Follow-ups due today <b>' . (int) $due . '</b></h2>';
	if ( ! $due ) {
		echo '<p class="ot-empty">Nothing due. Logging a voicemail, text or email books the next follow-up here for you.</p>';
	}
	$today_start = strtotime( ot_leads_day_end( -1 ) . ' UTC' );
	foreach ( $d['due'] as $l ) {
		$overdue = strtotime( $l['due_at'] . ' UTC' ) < $today_start;
		echo $row( $l, $overdue ? 'Since ' . ot_leads_time( $l['due_at'], 'M j' ) : ot_leads_time( $l['due_at'], 'g:i a' ), $meta( esc_html( ucfirst( $l['kind'] ) . ( $l['note'] ? ': ' . $l['note'] : '' ) ), esc_html( $owner( $l ) ) ) . ( $overdue ? ' <span class="ot-pill ot-pill--late">Overdue</span>' : '' ), $overdue, (int) $l['task_id'] ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</section>';

	echo '<section class="ot-group" aria-label="Back on the site"><h2 class="ot-group__head">Back on the site <b>' . (int) $back . '</b></h2>';
	if ( ! $back ) {
		echo '<p class="ot-empty">No one yet. When someone who sent a form comes back, reads a new town guide or looks at a listing, they show up here for three days.</p>';
	}
	foreach ( $d['back'] as $l ) {
		echo $row( $l, ot_leads_when( $l['seen_at'] ), $meta( esc_html( ot_leads_last_signal( (int) $l['id'] ) ), esc_html( $owner( $l ) ) ) . ' <span class="ot-pill ot-pill--warm">Warm</span>', false ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</section></section>';

	// The aside: this week, reply speed, approvals.
	echo '<aside aria-label="This week">';
	echo '<div class="ot-card"><h2>This week</h2>';
	if ( ! $d['week'] ) {
		echo '<p class="ot-muted" style="margin:0">Nothing booked for the next seven days.</p>';
	} else {
		echo '<ul class="ot-agenda">';
		foreach ( $d['week'] as $l ) {
			echo '<li><time>' . esc_html( ot_leads_time( $l['due_at'], 'D g:i a' ) ) . '</time><span><a href="' . esc_url( ot_leads_admin_link( $l['id'] ) ) . '">' . esc_html( ot_lead_name( $l ) ) . '</a><br><span class="ot-muted">' . esc_html( ucfirst( $l['kind'] ) . ( $l['note'] ? ': ' . $l['note'] : '' ) ) . '</span></span></li>';
		}
		echo '</ul>';
	}
	echo '</div>';

	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT created_at, contacted_at FROM ' . ot_leads_table( 'leads' ) . " WHERE created_at >= %s AND lead_type NOT IN ('newsletter','listings')" . ot_leads_agent_where( $agent ), gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
	$mins = array();
	foreach ( $rows as $r ) {
		if ( $r['contacted_at'] ) {
			$mins[] = max( 0, ( strtotime( $r['contacted_at'] . ' UTC' ) - strtotime( $r['created_at'] . ' UTC' ) ) / 60 );
		}
	}
	$win = max( 5, (int) ot_leads_opt( 'response_minutes' ) );
	echo '<div class="ot-card"><h2>Reply speed</h2>';
	if ( $mins ) {
		echo '<p class="ot-speed">' . esc_html( ot_leads_minutes( ot_leads_median( $mins ) ) ) . '</p><p>Median first reply over the last 30 days, ' . (int) round( count( array_filter( $mins, fn( $m ) => $m <= $win ) ) / count( $rows ) * 100 ) . '% within ' . (int) $win . ' minutes. <a href="' . esc_url( admin_url( 'admin.php?page=ot-leads-reports' ) ) . '">Reports</a></p>';
	} else {
		echo '<p class="ot-muted" style="margin:0">Shows here once the first inquiries are answered.</p>';
	}
	echo '</div>';
	if ( $d['drafts'] ) {
		echo '<div class="ot-card ot-card--note"><h2>' . esc_html( 1 === $d['drafts'] ? 'One email to approve' : $d['drafts'] . ' emails to approve' ) . '</h2><p class="ot-muted">Follow-up emails wait for a person before they send.</p><a class="ot-btn ot-btn--primary" href="' . esc_url( admin_url( 'admin.php?page=ot-leads-queue' ) ) . '">Review them</a></div>';
	}
	echo '</aside></div>';
	ot_leads_shell_end();
}
