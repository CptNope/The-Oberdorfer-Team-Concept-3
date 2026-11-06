<?php
/**
 * Leads > Newsletter: write an At Home issue, send a test, then queue it for every subscriber.
 * Issues go out through the scheduler in business hours, each with the postal address and a
 * one-click unsubscribe.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'ot_leads_admin_action', function ( $do ) {
	if ( 'newsletter' !== $do || ! current_user_can( 'ot_leads_manage' ) ) {
		return;
	}
	$subject = sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) );
	$body    = wp_kses_post( wp_unslash( $_POST['body'] ?? '' ) );
	update_option( 'ot_leads_nl_draft', array( 'subject' => $subject, 'body' => $body ), false );
	$back = admin_url( 'admin.php?page=ot-leads-newsletter' );
	if ( ! $subject || ! $body ) {
		wp_safe_redirect( add_query_arg( 'ot_msg', rawurlencode( 'Add a subject and a body first.' ), $back ) );
		exit;
	}
	if ( ! empty( $_POST['test'] ) ) {
		list( $ok, $info ) = ot_leads_mail( wp_get_current_user()->user_email, '[Test] ' . $subject, $body, array( 'marketing' => true, 'tags' => array( 'newsletter-test' ) ) );
		$msg = $ok ? 'Test sent to ' . wp_get_current_user()->user_email . '.' : 'Test failed: ' . $info;
	} else {
		$n = ot_newsletter_queue( $subject, $body );
		delete_option( 'ot_leads_nl_draft' );
		$msg = $n . ' emails queued. They send in business hours.';
	}
	wp_safe_redirect( add_query_arg( 'ot_msg', rawurlencode( $msg ), $back ) );
	exit;
} );

function ot_leads_newsletter_screen() {
	global $wpdb;
	$draft = get_option( 'ot_leads_nl_draft', array( 'subject' => '', 'body' => '' ) );
	$n     = count( ot_newsletter_recipients() );
	echo '<div class="wrap ot-wrap"><h1>Newsletter</h1>';
	ot_leads_notice();
	echo '<p><strong>' . (int) $n . '</strong> subscribers: people who signed up for the At Home issues or ticked the email-updates box. Unsubscribed and bounced addresses are left out.</p>';
	echo ot_leads_form( 'newsletter' ) . '<p><label for="nl-s"><strong>Subject</strong></label><br><input id="nl-s" class="large-text" type="text" name="subject" value="' . esc_attr( $draft['subject'] ) . '" placeholder="At Home: Fall 2026"></p>'; // phpcs:ignore WordPress.Security.EscapeOutput
	wp_editor( $draft['body'], 'ot_nl_body', array( 'textarea_name' => 'body', 'media_buttons' => true, 'textarea_rows' => 18 ) );
	echo '<p class="ot-muted">Same rules as the site: every market figure with its source, period and town; no descriptions of who lives somewhere. The office address and unsubscribe link are added.</p><p><button class="button" name="test" value="1">Send me a test</button> <button class="button button-primary" onclick="return confirm(\'Queue this issue for ' . (int) $n . ' subscribers?\')">Send to ' . (int) $n . ' subscribers</button></p></form>';
	$hist = $wpdb->get_results( 'SELECT subject, COUNT(*) n, SUM(status = \'sent\') sent, SUM(status = \'failed\') failed, SUM(status = \'approved\') waiting, MIN(created_at) at FROM ' . ot_leads_table( 'messages' ) . " WHERE kind = 'newsletter' GROUP BY subject ORDER BY at DESC LIMIT 12", ARRAY_A );
	if ( $hist ) {
		echo '<h2>Issues</h2><table class="widefat striped"><thead><tr><th>Subject</th><th>Queued</th><th>Sent</th><th>Waiting</th><th>Failed</th></tr></thead><tbody>';
		foreach ( $hist as $h ) {
			echo '<tr><td>' . esc_html( $h['subject'] ) . '</td><td>' . esc_html( ot_leads_time( $h['at'], 'M j, Y' ) ) . '</td><td>' . (int) $h['sent'] . '</td><td>' . (int) $h['waiting'] . '</td><td>' . (int) $h['failed'] . '</td></tr>';
		}
		echo '</tbody></table>';
	}
	echo '</div>';
}
