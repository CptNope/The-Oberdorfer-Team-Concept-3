<?php
/**
 * Leads > Approval queue: nurture steps, new-listing alerts and AI drafts waiting for a person.
 * Each can be approved (sent in business hours), edited, regenerated (AI) or skipped. A draft
 * that failed the AI checks shows the problems and cannot be approved until edited.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'ot_leads_admin_action', function ( $do ) {
	if ( 'queue' !== $do ) {
		return;
	}
	global $wpdb;
	$m   = ot_leads_table( 'messages' );
	$me  = wp_get_current_user()->user_login;
	$n   = 0;
	$sel = array_map( 'intval', (array) ( $_POST['sel'] ?? array() ) );
	$one = (int) ( $_POST['one'] ?? 0 );
	$act = sanitize_key( $_POST['act'] ?? '' );
	foreach ( $one ? array( $one ) : $sel as $mid ) {
		$msg  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $m WHERE id = %d AND status = 'draft'", $mid ), ARRAY_A );
		$lead = $msg && $msg['lead_id'] ? ot_lead( (int) $msg['lead_id'] ) : null;
		if ( ! $msg || ( $lead && ! ot_leads_can_see( $lead ) ) ) {
			continue;
		}
		$subject = isset( $_POST['subject'][ $mid ] ) ? sanitize_text_field( wp_unslash( $_POST['subject'][ $mid ] ) ) : $msg['subject'];
		$body    = isset( $_POST['body'][ $mid ] ) ? wp_kses_post( wp_unslash( $_POST['body'][ $mid ] ) ) : $msg['body'];
		$edited  = $subject !== $msg['subject'] || $body !== $msg['body'];
		$checks  = json_decode( (string) $msg['checks'], true );
		if ( 'ai' === $msg['source'] && $edited && $lead ) {
			$checks = ot_ai_validate( $subject, $body, ot_ai_grounding( $lead ) ) + array( 'sources' => $checks['sources'] ?? '' );
		}
		if ( 'approve' === $act ) {
			if ( 'ai' === $msg['source'] && empty( $checks['pass'] ) ) {
				$wpdb->update( $m, array( 'subject' => $subject, 'body' => $body, 'checks' => wp_json_encode( $checks ) ), array( 'id' => $mid ) );
				continue; // must be fixed first
			}
			$wpdb->update( $m, array( 'subject' => $subject, 'body' => $body, 'status' => 'approved', 'approved_by' => $me . ( $edited ? ' (edited)' : '' ), 'checks' => $checks ? wp_json_encode( $checks ) : null ), array( 'id' => $mid ) );
		} elseif ( 'save' === $act ) {
			$wpdb->update( $m, array( 'subject' => $subject, 'body' => $body, 'checks' => $checks ? wp_json_encode( $checks ) : null ), array( 'id' => $mid ) );
		} elseif ( 'skip' === $act ) {
			$wpdb->update( $m, array( 'status' => 'skipped', 'approved_by' => $me ), array( 'id' => $mid ) );
		} elseif ( 'regen' === $act && $lead && ot_ai_enabled() ) {
			$e     = $msg['enrollment_id'] ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . ot_leads_table( 'enrollments' ) . ' WHERE id = %d', $msg['enrollment_id'] ), ARRAY_A ) : null;
			$brief = $e ? (string) ( ot_nurture_sequences()[ $e['sequence'] ]['steps'][ (int) $msg['step'] ]['brief'] ?? '' ) : '';
			$d     = ot_ai_draft( $lead, $brief );
			if ( $d ) {
				$wpdb->update( $m, array( 'subject' => $d[0], 'body' => $d[1], 'checks' => wp_json_encode( $d[2] ), 'source' => 'ai' ), array( 'id' => $mid ) );
			}
		}
		$n++;
	}
	if ( 'approve' === $act ) {
		ot_nurture_send_due( 10 ); // within business hours it goes now; otherwise at 8 a.m.
	}
	$labels = array( 'approve' => 'approved', 'save' => 'saved', 'skip' => 'skipped', 'regen' => 'redrafted' );
	wp_safe_redirect( add_query_arg( 'ot_msg', rawurlencode( $n . ' ' . ( $labels[ $act ] ?? 'updated' ) . '.' . ( 'approve' === $act && ! ot_leads_business_hours() ? ' They send at 8 a.m.' : '' ) ), admin_url( 'admin.php?page=ot-leads-queue' ) ) );
	exit;
} );

function ot_leads_queue_screen() {
	global $wpdb;
	$own  = current_user_can( 'ot_leads_view_all' ) ? '' : $wpdb->prepare( ' AND l.assigned_agent = %s', ot_leads_user_agent() );
	$rows = $wpdb->get_results( 'SELECT m.*, l.first_name, l.last_name, l.assigned_agent FROM ' . ot_leads_table( 'messages' ) . ' m LEFT JOIN ' . ot_leads_table( 'leads' ) . " l ON l.id = m.lead_id WHERE m.status = 'draft'$own ORDER BY m.id ASC LIMIT 50", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
	ot_leads_shell( 'queue' );
	echo '<div class="wrap ot-wrap" style="max-width:1180px">';
	ot_leads_head( $rows ? 'Read, then send.' : 'Nothing to approve.', 'Emails wait here until a person approves them. Approved emails send between ' . (int) ot_leads_opt( 'hours_start' ) . ' a.m. and ' . ( (int) ot_leads_opt( 'hours_end' ) - 12 ) . ' p.m., Monday to Saturday, with the agent\'s name, the office address and an unsubscribe link added.' );
	ot_leads_notice();
	if ( ! $rows ) {
		echo '<p class="ot-empty">Follow-up steps set to wait for approval, new-listing alerts and AI drafts appear here. Approve sends them in business hours; Skip drops them.</p></div>';
		ot_leads_shell_end();
		return;
	}
	echo ot_leads_form( 'queue' ) . '<div class="ot-actions" style="margin:0 0 16px"><button class="button button-primary" onclick="this.form.elements.act.value=\'approve\'">Approve selected</button> <button class="button" onclick="this.form.elements.act.value=\'skip\'">Skip selected</button></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	$seqs = ot_nurture_sequences();
	foreach ( $rows as $m ) {
		$checks = json_decode( (string) $m['checks'], true );
		$bad    = 'ai' === $m['source'] && empty( $checks['pass'] );
		$what   = 'alert' === $m['kind'] ? 'New-listing alert' : ( 'nurture' === $m['kind'] ? 'Sequence step ' . ( (int) $m['step'] + 1 ) : ucfirst( $m['kind'] ) );
		echo '<div class="ot-msg" id="m' . (int) $m['id'] . '"><p><label><input type="checkbox" name="sel[]" value="' . (int) $m['id'] . '"' . disabled( $bad, true, false ) . '> <strong>To ' . esc_html( trim( $m['first_name'] . ' ' . $m['last_name'] ) ?: $m['to_email'] ) . '</strong></label> · ' . esc_html( $what ) . ' · <span class="ot-pill">' . esc_html( 'ai' === $m['source'] ? 'AI draft' : ( 'town_update' === $m['source'] ? 'Town update' : 'Template' ) ) . '</span> · ' . esc_html( ot_leads_agent_name( $m['assigned_agent'] ) ) . ( $m['lead_id'] ? ' · <a href="' . esc_url( ot_leads_admin_link( $m['lead_id'] ) ) . '">Lead</a>' : '' ) . '</p>';
		if ( $checks ) {
			echo '<p>' . ( $checks['pass'] ? '<span class="ot-pill ot-pill--ok">Checks passed</span>' : '<span class="ot-pill ot-pill--bad">Fix before sending</span> ' . esc_html( implode( ' · ', (array) $checks['issues'] ) ) ) . ( ! empty( $checks['sources'] ) ? ' <span class="ot-muted">Sources: ' . esc_html( $checks['sources'] ) . '</span>' : '' ) . '</p>';
		}
		echo '<p><input type="text" class="large-text" name="subject[' . (int) $m['id'] . ']" value="' . esc_attr( $m['subject'] ) . '"></p><div class="ot-msg__grid"><div class="preview">' . wp_kses_post( $m['body'] ) . '</div><textarea name="body[' . (int) $m['id'] . ']" aria-label="Email body (HTML)">' . esc_textarea( $m['body'] ) . '</textarea></div>';
		echo '<div class="ot-actions"><button class="button button-primary" name="one" value="' . (int) $m['id'] . '" onclick="this.form.elements.act.value=\'approve\'">Approve and send</button> <button class="button" name="one" value="' . (int) $m['id'] . '" onclick="this.form.elements.act.value=\'save\'">Save edits</button> ' . ( ot_ai_enabled() && $m['lead_id'] ? '<button class="button" name="one" value="' . (int) $m['id'] . '" onclick="this.form.elements.act.value=\'regen\'">Redraft with AI</button> ' : '' ) . '<button class="button-link button-link-delete" name="one" value="' . (int) $m['id'] . '" onclick="this.form.elements.act.value=\'skip\'">Skip</button></div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '<input type="hidden" name="act" value="approve"></form></div>';
	ot_leads_shell_end();
}
