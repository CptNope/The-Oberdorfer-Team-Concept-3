<?php
/**
 * The lead screen: facts, consent, AI summary and next step, timeline, notes, tasks and
 * nurture status. Every view is logged (spec § 11).
 */

defined( 'ABSPATH' ) || exit;

const OT_EVENT_LABELS = array(
	'form'         => 'Sent a form',
	'page_view'    => 'Visited',
	'note'         => 'Note',
	'reply'        => 'Replied',
	'call_logged'  => 'Call logged',
	'stage_change' => 'Stage changed',
	'assigned'     => 'Assigned',
	'alert_sent'   => 'Alert sent',
	'email_sent'   => 'Email sent',
	'click'        => 'Clicked a link in an email',
	'escalated'    => 'Escalated to team leaders',
	'claimed'      => 'Marked contacted ("I\'m on it")',
	'enrolled'     => 'Enrolled in a sequence',
	'unsubscribed' => 'Unsubscribed',
	'task_added'   => 'Task added',
	'task_done'    => 'Task done',
	'crm_sent'     => 'Sent to the CRM',
	'crm_failed'   => 'CRM delivery failed',
	'exported'     => 'Exported',
	'viewed_by'    => 'Viewed',
);

function ot_leads_event_text( $e ) {
	$p = json_decode( (string) $e['payload'], true ) ?: array();
	switch ( $e['type'] ) {
		case 'form':
			return trim( ( OT_LEAD_TYPES[ $p['type'] ?? '' ] ?? '' ) . ( ! empty( $p['listing'] ) ? ' · ' . $p['listing'] : '' ) . ( ! empty( $p['topic'] ) ? ' · ' . $p['topic'] : '' ) ) . ( ! empty( $p['message'] ) ? '<br><em>' . nl2br( esc_html( $p['message'] ) ) . '</em>' : '' ) . ( ! empty( $p['page'] ) ? '<br><span class="ot-muted">' . esc_html( wp_parse_url( $p['page'], PHP_URL_PATH ) ?: '/' ) . '</span>' : '' );
		case 'page_view':
			return esc_html( ( $p['title'] ?? '' ) . ' · ' . ( $p['path'] ?? '' ) );
		case 'stage_change':
			return esc_html( ( OT_LEAD_STAGES[ $p['from'] ?? '' ] ?? '' ) . ' → ' . ( OT_LEAD_STAGES[ $p['to'] ?? '' ] ?? '' ) );
		case 'assigned':
			return esc_html( ot_leads_agent_name( $p['to'] ?? '' ) );
		case 'alert_sent':
			return esc_html( ( $p['to'] ?? '' ) . ( empty( $p['ok'] ) ? ' (failed: ' . ( $p['info'] ?? '' ) . ')' : '' ) );
		case 'email_sent':
			return esc_html( ( $p['subject'] ?? '' ) . ( empty( $p['ok'] ) ? ' (failed)' : '' ) );
		case 'enrolled':
			return esc_html( $p['sequence'] ?? '' );
		case 'note':
		case 'reply':
		case 'call_logged':
			return nl2br( esc_html( $p['note'] ?? '' ) );
		case 'crm_failed':
			return esc_html( ( $p['connector'] ?? '' ) . ': ' . ( $p['error'] ?? '' ) );
	}
	return esc_html( implode( ' · ', array_filter( array_map( fn( $v ) => is_scalar( $v ) ? (string) $v : '', $p ) ) ) );
}

function ot_leads_lead_screen() {
	global $wpdb;
	$id   = (int) ( $_GET['id'] ?? 0 );
	$lead = ot_lead( $id );
	if ( ! $lead || ! ot_leads_can_see( $lead ) ) {
		echo '<div class="wrap"><h1>Lead not found</h1><p>It may have been deleted, or it is assigned to another agent.</p></div>';
		return;
	}
	$k = 'ot_viewed_' . get_current_user_id() . '_' . $id;
	if ( ! get_transient( $k ) ) {
		set_transient( $k, 1, HOUR_IN_SECONDS );
		ot_lead_event( $id, 'viewed_by', array( 'user' => wp_get_current_user()->user_login ) );
	}
	$intent  = ot_lead_intent( $id );
	$consent = ot_lead_has_consent( $lead['email'] );
	$utm     = json_decode( (string) $lead['utm'], true ) ?: array();
	$sum     = get_option( 'ot_ai_sum_' . $id );
	$tasks   = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . ot_leads_table( 'tasks' ) . ' WHERE lead_id = %d ORDER BY done_at IS NOT NULL, due_at', $id ), ARRAY_A );
	$enr     = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . ot_leads_table( 'enrollments' ) . ' WHERE lead_id = %d ORDER BY id DESC', $id ), ARRAY_A );
	$msgs    = $wpdb->get_results( $wpdb->prepare( 'SELECT id, subject, status, kind, created_at, sent_at FROM ' . ot_leads_table( 'messages' ) . ' WHERE lead_id = %d ORDER BY id DESC LIMIT 20', $id ), ARRAY_A );
	$seqs    = ot_nurture_sequences();

	echo '<div class="wrap ot-wrap"><p><a href="' . esc_url( admin_url( 'admin.php?page=ot-leads' ) ) . '">← Inbox</a></p><h1>' . esc_html( ot_lead_name( $lead ) ) . ' ' . ot_leads_stage_pill( $lead['stage'] ) . ' <span class="ot-pill' . ( 'Warm' === $intent[0] ? ' ot-pill--warm' : '' ) . '" title="' . esc_attr( $intent[1] ) . '">' . esc_html( $intent[0] ) . '</span></h1>'; // phpcs:ignore WordPress.Security.EscapeOutput
	ot_leads_notice();
	echo '<div class="ot-grid"><div>';

	// Status and assignment.
	echo '<div class="ot-card"><h2>Status</h2>' . ot_leads_form( 'update', $id ) . '<table class="form-table"><tr><th>Stage</th><td><select name="stage">'; // phpcs:ignore WordPress.Security.EscapeOutput
	foreach ( OT_LEAD_STAGES as $s => $label ) {
		echo '<option value="' . esc_attr( $s ) . '"' . selected( $lead['stage'], $s, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select> <input type="text" name="lost_reason" placeholder="Reason, if lost" value="' . esc_attr( $lead['lost_reason'] ) . '"></td></tr>';
	if ( current_user_can( 'ot_leads_view_all' ) ) {
		echo '<tr><th>Agent</th><td>' . ot_leads_agent_select( 'assigned_agent', $lead['assigned_agent'] ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
	} else {
		echo '<tr><th>Agent</th><td>' . esc_html( ot_leads_agent_name( $lead['assigned_agent'] ) ) . '</td></tr>';
	}
	echo '<tr><th>First reply</th><td>' . ( $lead['contacted_at'] ? esc_html( ot_leads_time( $lead['contacted_at'] ) ) . ' (' . esc_html( human_time_diff( strtotime( $lead['created_at'] . ' UTC' ), strtotime( $lead['contacted_at'] . ' UTC' ) ) ) . ' after the inquiry)' : '<strong>Not yet</strong>' ) . '</td></tr></table><p><button class="button button-primary">Save</button></p></form>';
	if ( ! $lead['contacted_at'] ) {
		echo ot_leads_form( 'contacted', $id ) . '<button class="button">Mark contacted</button></form>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</div>';

	// Contact facts.
	$tel = preg_replace( '/[^0-9+]/', '', $lead['phone'] );
	echo '<div class="ot-card"><h2>Contact</h2><table class="form-table">'
		. '<tr><th>Email</th><td><a href="mailto:' . esc_attr( $lead['email'] ) . '">' . esc_html( $lead['email'] ) . '</a></td></tr>'
		. '<tr><th>Phone</th><td>' . ( $lead['phone'] ? '<a href="tel:' . esc_attr( $tel ) . '">' . esc_html( $lead['phone'] ) . '</a> · <a href="sms:' . esc_attr( $tel ) . '">Text</a>' : '<span class="ot-muted">Not given</span>' ) . '</td></tr>'
		. '<tr><th>Prefers</th><td>' . esc_html( ucfirst( $lead['preferred'] ) ?: '—' ) . '</td></tr>'
		. '<tr><th>Type</th><td>' . esc_html( OT_LEAD_TYPES[ $lead['lead_type'] ] ?? $lead['lead_type'] ) . ( $lead['topic'] ? ' · ' . esc_html( $lead['topic'] ) : '' ) . '</td></tr>'
		. '<tr><th>Towns</th><td>' . esc_html( str_replace( ',', ', ', $lead['towns'] ) ?: '—' ) . '</td></tr>'
		. ( $lead['listing_id'] ? '<tr><th>Listing</th><td><a href="' . esc_url( get_permalink( $lead['listing_id'] ) ) . '">' . esc_html( get_the_title( $lead['listing_id'] ) ) . '</a></td></tr>' : '' )
		. '<tr><th>Email updates</th><td>' . ( $consent ? '<span class="ot-pill ot-pill--ok">Consented</span>' : '<span class="ot-pill">No consent</span>' ) . ( ot_leads_suppressed( $lead['email'] ) ? ' <span class="ot-pill ot-pill--bad">Unsubscribed</span>' : '' ) . '</td></tr>'
		. '<tr><th>Came from</th><td>' . esc_html( $lead['first_url'] ? wp_parse_url( $lead['first_url'], PHP_URL_PATH ) : '—' ) . ( $lead['referrer'] ? ' <span class="ot-muted">via ' . esc_html( wp_parse_url( $lead['referrer'], PHP_URL_HOST ) ) . '</span>' : '' ) . ( $utm ? '<br><span class="ot-muted">' . esc_html( http_build_query( $utm, '', ' · ' ) ) . '</span>' : '' ) . '</td></tr>'
		. '<tr><th>Received</th><td>' . esc_html( ot_leads_time( $lead['created_at'], 'l, M j, Y g:i a' ) ) . '</td></tr>'
		. ( $lead['crm_state'] ? '<tr><th>CRM</th><td>' . esc_html( $lead['crm_state'] ) . '</td></tr>' : '' )
		. '</table>';
	if ( $consent && current_user_can( 'ot_leads_view_all' ) ) {
		echo ot_leads_form( 'consent', $id ) . '<button class="button-link">Record an opt-out (they asked by phone or email)</button></form>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</div>';

	// AI summary.
	if ( ot_ai_enabled() ) {
		echo '<div class="ot-card"><h2>Summary and next step <span class="ot-muted" style="font-weight:400">· AI</span></h2>';
		if ( $sum ) {
			echo '<p>' . nl2br( esc_html( $sum['summary'] ) ) . '</p><p><strong>Next:</strong> ' . esc_html( $sum['next'] ) . '</p><p class="ot-muted">' . esc_html( ot_leads_time( $sum['at'] ) ) . '</p>';
		}
		echo ot_leads_form( 'summary', $id ) . '<button class="button">' . ( $sum ? 'Refresh' : 'Summarize this lead' ) . '</button></form></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	// Tasks.
	echo '<div class="ot-card"><h2>Tasks</h2><ul>';
	foreach ( $tasks as $t ) {
		echo '<li>' . ( $t['done_at'] ? '<s>' : '' ) . esc_html( ucfirst( $t['kind'] ) . ' · ' . ot_leads_time( $t['due_at'] ) ) . ( $t['note'] ? ' · ' . esc_html( $t['note'] ) : '' ) . ( $t['done_at'] ? '</s>' : ' ' . ot_leads_form( 'task_done', $id, 'style="display:inline"' ) . '<input type="hidden" name="task" value="' . (int) $t['id'] . '"><button class="button-link">Done</button></form>' ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</ul>' . ot_leads_form( 'task', $id ) . '<select name="kind"><option value="call">Call</option><option value="email">Email</option><option value="text">Text</option><option value="meeting">Meeting</option><option value="other">Other</option></select> <input type="datetime-local" name="due" value="' . esc_attr( ( new DateTime( '+1 day 9:00', new DateTimeZone( 'America/New_York' ) ) )->format( 'Y-m-d\TH:i' ) ) . '"> <input type="text" name="note" placeholder="Note" style="width:12rem"> <button class="button">Add task</button></form></div>'; // phpcs:ignore WordPress.Security.EscapeOutput

	// Nurture.
	echo '<div class="ot-card"><h2>Email nurture</h2>';
	if ( $enr ) {
		echo '<table class="widefat striped"><tbody>';
		foreach ( $enr as $e ) {
			echo '<tr><td>' . esc_html( $seqs[ $e['sequence'] ]['name'] ?? $e['sequence'] ) . '</td><td>Step ' . ( (int) $e['step'] + 1 ) . '</td><td>' . esc_html( ucfirst( $e['state'] ) ) . ( $e['exit_reason'] ? ' · ' . esc_html( $e['exit_reason'] ) : '' ) . ( 'active' === $e['state'] && $e['next_at'] ? '<br><span class="ot-muted">Next ' . esc_html( ot_leads_time( $e['next_at'], 'M j' ) ) . '</span>' : '' ) . '</td><td>';
			if ( in_array( $e['state'], array( 'active', 'paused' ), true ) ) {
				foreach ( 'active' === $e['state'] ? array( 'paused' => 'Pause', 'exited' => 'Stop' ) : array( 'active' => 'Resume', 'exited' => 'Stop' ) as $st => $label ) {
					echo ot_leads_form( 'enrollment', $id, 'style="display:inline"' ) . '<input type="hidden" name="enrollment" value="' . (int) $e['id'] . '"><input type="hidden" name="state" value="' . esc_attr( $st ) . '"><button class="button-link">' . esc_html( $label ) . '</button></form> '; // phpcs:ignore WordPress.Security.EscapeOutput
				}
			}
			echo '</td></tr>';
		}
		echo '</tbody></table>';
	}
	if ( $consent ) {
		echo '<p>' . ot_leads_form( 'enroll', $id ) . '<select name="sequence">'; // phpcs:ignore WordPress.Security.EscapeOutput
		foreach ( $seqs as $key => $s ) {
			echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $s['name'] ) . '</option>';
		}
		echo '</select> <button class="button">Enroll</button></form></p>';
	} else {
		echo '<p class="ot-muted">Not enrolled: no email consent. Reply to them directly.</p>';
	}
	if ( $msgs ) {
		echo '<p><strong>Emails</strong></p><ul>';
		foreach ( $msgs as $m ) {
			echo '<li>' . esc_html( $m['subject'] ) . ' · <span class="ot-pill' . ( 'sent' === $m['status'] ? ' ot-pill--ok' : ( 'failed' === $m['status'] ? ' ot-pill--bad' : '' ) ) . '">' . esc_html( $m['status'] ) . '</span> <span class="ot-muted">' . esc_html( ot_leads_time( $m['sent_at'] ?: $m['created_at'], 'M j' ) ) . '</span>' . ( 'draft' === $m['status'] ? ' <a href="' . esc_url( admin_url( 'admin.php?page=ot-leads-queue#m' . $m['id'] ) ) . '">Review</a>' : '' ) . '</li>';
		}
		echo '</ul>';
	}
	echo '</div>';

	if ( current_user_can( 'ot_leads_manage' ) ) {
		echo '<div class="ot-card"><h2>Their data</h2><p>' . ot_leads_form( 'export', $id, 'style="display:inline"' ) . '<button class="button">Export (JSON)</button></form> ' . ot_leads_form( 'delete', $id, 'style="display:inline" onsubmit="return confirm(\'Delete this lead and its history? Consent records are kept.\')"' ) . '<button class="button button-link-delete">Delete on request</button></form></p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</div><div>';

	// Timeline and notes.
	echo '<div class="ot-card"><h2>Add to the timeline</h2>' . ot_leads_form( 'note', $id ) . '<textarea name="note" rows="3" style="width:100%" placeholder="What happened, what they said, what is next"></textarea><p><label><input type="radio" name="kind" value="note" checked> Note</label> &nbsp; <label><input type="radio" name="kind" value="call_logged"> Call</label> &nbsp; <label><input type="radio" name="kind" value="reply"> They replied <span class="ot-muted">(stops the sales sequence)</span></label></p><p><button class="button button-primary">Add</button></p></form></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<div class="ot-card"><h2>Timeline</h2><ul class="ot-timeline">';
	foreach ( ot_lead_events( $id ) as $e ) {
		if ( 'viewed_by' === $e['type'] ) {
			continue;
		}
		echo '<li><strong>' . esc_html( OT_EVENT_LABELS[ $e['type'] ] ?? $e['type'] ) . '</strong> <span class="when">' . esc_html( ot_leads_time( $e['created_at'] ) ) . ' · ' . esc_html( $e['actor'] ) . '</span><br>' . ot_leads_event_text( $e ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</ul></div></div></div></div>';
}
