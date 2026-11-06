<?php
/**
 * Sending email: Brevo's transactional API when a key is set, otherwise wp_mail().
 *
 * - Everything the site sends goes through ot_leads_mail(): agent alerts, the lead's
 *   confirmation, nurture steps and newsletter issues.
 * - Marketing email (nurture, newsletter) carries the agent's signature, the office's postal
 *   address (CAN-SPAM), a visible unsubscribe link and RFC 8058 one-click unsubscribe headers.
 *   Suppressed addresses are never sent marketing email.
 * - Brevo's webhook (bounces, spam complaints, unsubscribes) adds to the suppression list.
 *
 * Mailboxes (Rackspace) and sending (Brevo) are separate: people read and reply in their
 * Rackspace inbox; the site sends through Brevo with their address as the reply-to.
 */

defined( 'ABSPATH' ) || exit;

const OT_LEADS_ADDRESS = 'The Oberdorfer Team · REWAP Brokerage LLC · 652 Park Ave, Unit B, Worcester, MA 01603';

/** The From address: the team address (hello@oberdorferteam.com by default) with a display name. */
function ot_leads_from( $name = 'The Oberdorfer Team' ) {
	return array( 'name' => $name, 'email' => ot_leads_opt( 'from_team' ) . '@' . ot_leads_opt( 'from_domain' ) );
}

/**
 * Send one email. $args: from_name, reply_to (email), reply_name, marketing (bool), tags (array),
 * lead_id. Returns [ ok (bool), provider id or error ].
 */
function ot_leads_mail( $to, $subject, $html, $args = array() ) {
	$args = wp_parse_args( $args, array( 'from_name' => 'The Oberdorfer Team', 'reply_to' => '', 'reply_name' => '', 'marketing' => false, 'tags' => array(), 'lead_id' => 0 ) );
	if ( ! is_email( $to ) ) {
		return array( false, 'Invalid address' );
	}
	if ( $args['marketing'] && ot_leads_suppressed( $to ) ) {
		return array( false, 'Suppressed' );
	}
	$headers = array();
	if ( $args['marketing'] ) {
		$unsub                       = ot_leads_unsub_url( $to );
		$headers['List-Unsubscribe'] = '<' . $unsub . '>, <mailto:' . ot_leads_from()['email'] . '?subject=unsubscribe>';
		$headers['List-Unsubscribe-Post'] = 'List-Unsubscribe=One-Click';
		$html .= ot_leads_footer( $to );
	}
	$text = trim( html_entity_decode( wp_strip_all_tags( preg_replace( array( '#<br\s*/?>#i', '#</p>#i', '#<a [^>]*href="([^"]+)"[^>]*>(.*?)</a>#i' ), array( "\n", "\n\n", '$2 ($1)' ), $html ) ) ) );
	$doc  = '<!doctype html><html><body style="margin:0;padding:24px;background:#F7F3EC;font:16px/1.6 -apple-system,BlinkMacSystemFont,\'Segoe UI\',Arial,sans-serif;color:#142A38"><div style="max-width:600px;margin:0 auto;background:#ffffff;padding:28px 28px 20px">' . $html . '</div></body></html>';
	$from = ot_leads_from( $args['from_name'] );

	if ( ot_leads_opt( 'brevo_key' ) ) {
		$body = array(
			'sender'      => $from,
			'to'          => array( array( 'email' => $to ) ),
			'subject'     => $subject,
			'htmlContent' => $doc,
			'textContent' => $text,
		);
		if ( is_email( $args['reply_to'] ) ) {
			$body['replyTo'] = array_filter( array( 'email' => $args['reply_to'], 'name' => $args['reply_name'] ) );
		}
		if ( $headers ) {
			$body['headers'] = $headers;
		}
		if ( $args['tags'] ) {
			$body['tags'] = array_values( $args['tags'] );
		}
		$res = wp_remote_post( 'https://api.brevo.com/v3/smtp/email', array(
			'timeout' => 15,
			'headers' => array( 'api-key' => ot_leads_opt( 'brevo_key' ), 'accept' => 'application/json', 'content-type' => 'application/json' ),
			'body'    => wp_json_encode( $body ),
		) );
		if ( is_wp_error( $res ) ) {
			return array( false, $res->get_error_message() );
		}
		$code = wp_remote_retrieve_response_code( $res );
		$json = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( $code >= 200 && $code < 300 ) {
			return array( true, (string) ( $json['messageId'] ?? '' ) );
		}
		return array( false, 'Brevo ' . $code . ': ' . substr( (string) ( $json['message'] ?? wp_remote_retrieve_body( $res ) ), 0, 180 ) );
	}

	// No Brevo key yet: WordPress's own mail (Cloudways' server mail or an SMTP plugin).
	$h = array( 'Content-Type: text/html; charset=UTF-8', 'From: ' . $from['name'] . ' <' . $from['email'] . '>' );
	if ( is_email( $args['reply_to'] ) ) {
		$h[] = 'Reply-To: ' . ( $args['reply_name'] ? $args['reply_name'] . ' ' : '' ) . '<' . $args['reply_to'] . '>';
	}
	foreach ( $headers as $k => $v ) {
		$h[] = $k . ': ' . $v;
	}
	$ok = wp_mail( $to, $subject, $doc, $h );
	return array( (bool) $ok, $ok ? 'wp_mail' : 'wp_mail failed' );
}

/** Signature, postal address and unsubscribe link at the foot of marketing email. */
function ot_leads_footer( $to ) {
	return '<hr style="border:0;border-top:1px solid #E6DFD3;margin:28px 0 14px"><p style="font-size:12px;line-height:1.5;color:#5B6670;margin:0">' . esc_html( OT_LEADS_ADDRESS ) . '<br>You are receiving this because you asked for updates from The Oberdorfer Team. <a href="' . esc_url( ot_leads_unsub_url( $to ) ) . '" style="color:#5B6670">Unsubscribe</a>.</p>';
}

/* --------------------------------------------------------------------- unsubscribe */

function ot_leads_unsub_token( $email ) {
	$email = strtolower( trim( $email ) );
	return rtrim( strtr( base64_encode( $email ), '+/', '-_' ), '=' ) . '.' . ot_leads_sign( 'unsub|' . $email );
}

function ot_leads_unsub_url( $email ) {
	return add_query_arg( 'ot_unsub', ot_leads_unsub_token( $email ), home_url( '/' ) );
}

/**
 * /?ot_unsub=<token>. A GET shows a page with one button (link scanners prefetch GETs, so a GET
 * never unsubscribes); a POST unsubscribes at once, including the RFC 8058 one-click POST.
 */
add_action( 'init', function () {
	if ( empty( $_GET['ot_unsub'] ) ) {
		return;
	}
	list( $b64, $sig ) = array_pad( explode( '.', sanitize_text_field( wp_unslash( $_GET['ot_unsub'] ) ), 2 ), 2, '' );
	$email = strtolower( (string) base64_decode( strtr( $b64, '-_', '+/' ) ) );
	$ok    = is_email( $email ) && hash_equals( ot_leads_sign( 'unsub|' . $email ), $sig );
	nocache_headers();
	header( 'X-Robots-Tag: noindex' );
	if ( $ok && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
		ot_leads_suppress( $email, 'unsubscribe' );
		if ( ! empty( $_POST['List-Unsubscribe'] ) ) {
			status_header( 200 );
			exit; // one-click from the mail client
		}
		ot_leads_page( 'You are unsubscribed', '<p>' . esc_html( $email ) . ' will not receive any more updates from The Oberdorfer Team. If you wrote to an agent about a home, they can still reply to you.</p>' );
	}
	if ( ! $ok ) {
		ot_leads_page( 'This link has expired', '<p>Reply to any of our emails with "unsubscribe", or call 406-250-7620, and we will remove you.</p>' );
	}
	ot_leads_page( 'Unsubscribe', '<p>Stop sending updates to <strong>' . esc_html( $email ) . '</strong>?</p><form method="post"><button type="submit" style="font:inherit;padding:12px 22px;background:#071D2D;color:#F7F3EC;border:0;cursor:pointer">Unsubscribe</button></form>' );
}, 1 );

/** A plain standalone page for unsubscribe and "I'm on it" (no theme, no cache). */
function ot_leads_page( $title, $html ) {
	nocache_headers();
	status_header( 200 );
	echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>' . esc_html( $title ) . ' · The Oberdorfer Team</title></head><body style="margin:0;padding:48px 20px;background:#F7F3EC;font:17px/1.6 -apple-system,BlinkMacSystemFont,\'Segoe UI\',Arial,sans-serif;color:#142A38"><main style="max-width:34rem;margin:0 auto"><p style="letter-spacing:.14em;text-transform:uppercase;font-size:12px;color:#7A6638">The Oberdorfer Team</p><h1 style="font:400 2rem/1.2 Georgia,serif;margin:.2em 0 .6em">' . esc_html( $title ) . '</h1>' . $html . '</main></body></html>'; // phpcs:ignore WordPress.Security.EscapeOutput
	exit;
}

/* --------------------------------------------------------------------- Brevo webhook */

/**
 * POST /wp-json/ot-leads/v1/brevo, with "Authorization: Bearer <token>" (Settings > Email shows the
 * URL and token to paste into Brevo > Transactional > Settings > Webhook). Bounces, complaints
 * and unsubscribes suppress the address; clicks become events on the lead.
 */
add_action( 'rest_api_init', function () {
	register_rest_route( 'ot-leads/v1', '/brevo', array(
		'methods'             => 'POST',
		'permission_callback' => function ( WP_REST_Request $r ) {
			$token = (string) ot_leads_opt( 'brevo_token' );
			$auth  = (string) $r->get_header( 'authorization' );
			return $token && hash_equals( 'Bearer ' . $token, $auth );
		},
		'callback'            => function ( WP_REST_Request $r ) {
			$items = $r->get_json_params();
			$items = isset( $items['event'] ) ? array( $items ) : (array) $items;
			foreach ( $items as $e ) {
				$event = (string) ( $e['event'] ?? '' );
				$email = strtolower( (string) ( $e['email'] ?? '' ) );
				if ( ! is_email( $email ) ) {
					continue;
				}
				$map = array( 'hard_bounce' => 'bounce', 'hardBounce' => 'bounce', 'invalid_email' => 'bounce', 'blocked' => 'bounce', 'spam' => 'complaint', 'complaint' => 'complaint', 'unsubscribed' => 'unsubscribe' );
				if ( isset( $map[ $event ] ) ) {
					ot_leads_suppress( $email, $map[ $event ] );
				} elseif ( 'click' === $event ) {
					global $wpdb;
					$id = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . ot_leads_table( 'leads' ) . ' WHERE email = %s ORDER BY id DESC LIMIT 1', $email ) );
					if ( $id ) {
						ot_lead_event( $id, 'click', array( 'link' => esc_url_raw( (string) ( $e['link'] ?? '' ) ) ), 'brevo' );
					}
				}
			}
			return array( 'ok' => true );
		},
	) );
} );
