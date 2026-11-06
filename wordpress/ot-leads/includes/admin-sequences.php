<?php
/**
 * Leads > Sequences: each sequence's steps (day, subject, body or AI brief), on or off, and
 * whether its emails wait for approval or send automatically. Only team leaders see this.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'ot_leads_admin_action', function ( $do ) {
	if ( 'sequences' !== $do || ! current_user_can( 'ot_leads_manage' ) ) {
		return;
	}
	if ( ! empty( $_POST['reset'] ) ) {
		delete_option( 'ot_leads_sequences' );
		wp_safe_redirect( add_query_arg( 'ot_msg', rawurlencode( 'Sequences reset to the starter set.' ), admin_url( 'admin.php?page=ot-leads-sequences' ) ) );
		exit;
	}
	$in   = (array) wp_unslash( $_POST['seq'] ?? array() );
	$cur  = ot_nurture_sequences();
	$out  = array();
	foreach ( $cur as $key => $s ) {
		$p     = (array) ( $in[ $key ] ?? array() );
		$steps = array();
		foreach ( (array) ( $p['steps'] ?? array() ) as $st ) {
			if ( ! empty( $st['delete'] ) || ( '' === trim( (string) ( $st['subject'] ?? '' ) ) && 'town_update' !== ( $st['mode'] ?? '' ) ) ) {
				continue;
			}
			$steps[] = array_filter( array(
				'day'     => max( 0, (int) ( $st['day'] ?? 0 ) ),
				'subject' => sanitize_text_field( $st['subject'] ?? '' ),
				'mode'    => in_array( $st['mode'] ?? '', array( 'template', 'ai', 'town_update' ), true ) ? $st['mode'] : 'template',
				'body'    => wp_kses_post( $st['body'] ?? '' ),
				'brief'   => sanitize_textarea_field( $st['brief'] ?? '' ),
				'repeat'  => max( 0, (int) ( $st['repeat'] ?? 0 ) ),
			), fn( $v ) => '' !== $v && 0 !== $v ) + array( 'day' => 0 );
		}
		usort( $steps, fn( $a, $b ) => $a['day'] <=> $b['day'] );
		$out[ $key ] = array(
			'name'   => sanitize_text_field( $p['name'] ?? $s['name'] ),
			'when'   => $s['when'] ?? '',
			'active' => empty( $p['active'] ) ? 0 : 1,
			'mode'   => 'auto' === ( $p['mode'] ?? '' ) ? 'auto' : 'manual',
			'steps'  => $steps ?: $s['steps'],
		);
	}
	update_option( 'ot_leads_sequences', $out, false );
	wp_safe_redirect( add_query_arg( 'ot_msg', rawurlencode( 'Sequences saved.' ), admin_url( 'admin.php?page=ot-leads-sequences' ) ) );
	exit;
} );

function ot_leads_sequences_screen() {
	echo '<div class="wrap ot-wrap"><h1>Sequences</h1>';
	ot_leads_notice();
	echo '<p class="ot-muted">Each step becomes an email to the lead on its day (counted from when they enrolled). Write the body in HTML; the merge tags are {first_name}, {agent_name}, {agent_first}, {agent_phone}, {towns}, {town_links}, {listing_link}, {buy_url}, {sell_url} and {market_url}. "Town update" builds the monthly email from the guides of the towns they follow. "AI draft" writes the email from the brief, using only the lead\'s record and the guides; it always passes the checks before it can be sent. Signature, office address and unsubscribe link are added to every email.</p>';
	echo ot_leads_form( 'sequences' ); // phpcs:ignore WordPress.Security.EscapeOutput
	foreach ( ot_nurture_sequences() as $key => $s ) {
		$k = esc_attr( $key );
		echo '<div class="ot-card"><h2><input type="text" name="seq[' . $k . '][name]" value="' . esc_attr( $s['name'] ) . '" style="font-weight:600"> <label><input type="checkbox" name="seq[' . $k . '][active]" value="1"' . checked( ! empty( $s['active'] ), true, false ) . '> On</label> &nbsp; <select name="seq[' . $k . '][mode]"><option value="manual"' . selected( $s['mode'] ?? 'manual', 'manual', false ) . '>Wait for approval</option><option value="auto"' . selected( $s['mode'] ?? '', 'auto', false ) . '>Send automatically</option></select></h2>';
		echo '<p class="ot-muted">Starts when: ' . esc_html( $s['when'] ?? '' ) . '. Stops when the lead replies, moves to Appointment set or later, unsubscribes, or an agent stops it.</p>';
		echo '<table class="widefat ot-steps"><thead><tr><th style="width:70px">Day</th><th style="width:130px">Kind</th><th>Subject and body (or AI brief)</th><th style="width:90px">Repeat every (days)</th><th style="width:60px">Remove</th></tr></thead><tbody>';
		$steps   = $s['steps'];
		$steps[] = array( 'day' => '', 'subject' => '', 'mode' => 'template', 'body' => '' ); // a blank row to add a step
		foreach ( $steps as $i => $st ) {
			$n    = 'seq[' . $k . '][steps][' . $i . ']';
			$mode = $st['mode'] ?? 'template';
			echo '<tr><td><input type="number" min="0" name="' . esc_attr( $n ) . '[day]" value="' . esc_attr( $st['day'] ) . '" style="width:60px"></td><td><select name="' . esc_attr( $n ) . '[mode]">';
			foreach ( array( 'template' => 'Template', 'town_update' => 'Town update', 'ai' => 'AI draft' ) as $mk => $ml ) {
				echo '<option value="' . esc_attr( $mk ) . '"' . selected( $mode, $mk, false ) . '>' . esc_html( $ml ) . '</option>';
			}
			echo '</select></td><td><input type="text" class="large-text" name="' . esc_attr( $n ) . '[subject]" value="' . esc_attr( $st['subject'] ?? '' ) . '" placeholder="' . ( '' === $st['day'] ? 'Add a step: subject' : 'Subject' ) . '"><textarea name="' . esc_attr( $n ) . '[body]" placeholder="Body (HTML)">' . esc_textarea( $st['body'] ?? '' ) . '</textarea><input type="text" class="large-text" name="' . esc_attr( $n ) . '[brief]" value="' . esc_attr( $st['brief'] ?? '' ) . '" placeholder="AI brief, for AI draft steps: what this email should do"></td><td><input type="number" min="0" name="' . esc_attr( $n ) . '[repeat]" value="' . esc_attr( $st['repeat'] ?? '' ) . '" style="width:70px"></td><td>' . ( '' !== $st['day'] ? '<input type="checkbox" name="' . esc_attr( $n ) . '[delete]" value="1" aria-label="Remove this step">' : '' ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}
	echo '<p><button class="button button-primary">Save sequences</button> <button class="button" name="reset" value="1" onclick="return confirm(\'Replace all sequences with the starter set?\')">Reset to the starter set</button></p></form></div>';
}
