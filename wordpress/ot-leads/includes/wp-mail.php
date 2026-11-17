<?php
/**
 * Every email WordPress sends goes through Brevo once its key is set (decided 2026-11-05:
 * "Brevo sends the site's email"). ot_leads_mail() already did this for the lead plugin's own
 * mail; this does it for everything else that calls wp_mail(): security alerts (JAT Site
 * Shield), booking confirmations and reminders (JAT Calendar Sync), password resets and
 * WordPress's own notices. The server's PHP mail is not authenticated for oberdorferteam.com,
 * so those messages were lost or filtered as spam.
 *
 * The From address is always the team's verified sending address; another plugin's From name
 * is kept, and its From address becomes the Reply-To when it differs. If Brevo refuses a
 * message, WordPress's own mail is tried instead, so nothing is worse off than before.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'pre_wp_mail', 'ot_leads_pre_wp_mail', 10, 2 );

function ot_leads_pre_wp_mail( $result, $atts ) {
	if ( null !== $result || ! ot_leads_opt( 'brevo_key' ) ) {
		return $result;
	}
	$split = fn( $v ) => array_values( array_filter( array_map( 'trim', is_array( $v ) ? $v : explode( ',', (string) $v ) ) ) );
	$addr  = function ( $s ) {
		$s = trim( (string) $s );
		if ( preg_match( '/^(.*)<([^>]+)>$/', $s, $m ) ) {
			return array_filter( array( 'email' => trim( $m[2] ), 'name' => trim( trim( $m[1] ), '"' ) ) );
		}
		return array( 'email' => $s );
	};

	$to = array();
	foreach ( $split( $atts['to'] ?? '' ) as $t ) {
		$a = $addr( $t );
		if ( is_email( $a['email'] ) ) {
			$to[] = $a;
		}
	}
	if ( ! $to ) {
		return $result;
	}

	$headers = $atts['headers'] ?? array();
	$headers = is_array( $headers ) ? $headers : explode( "\n", str_replace( "\r\n", "\n", (string) $headers ) );
	$cc      = array();
	$bcc     = array();
	$reply   = null;
	$from    = null;
	$html    = false;
	$extra   = array();
	foreach ( $headers as $h ) {
		if ( ! str_contains( (string) $h, ':' ) ) {
			continue;
		}
		list( $name, $value ) = array_map( 'trim', explode( ':', $h, 2 ) );
		switch ( strtolower( $name ) ) {
			case 'content-type':
				$html = str_contains( strtolower( $value ), 'text/html' );
				break;
			case 'from':
				$from = $addr( $value );
				break;
			case 'reply-to':
				$reply = $addr( $value );
				break;
			case 'cc':
				foreach ( $split( $value ) as $c ) {
					$cc[] = $addr( $c );
				}
				break;
			case 'bcc':
				foreach ( $split( $value ) as $c ) {
					$bcc[] = $addr( $c );
				}
				break;
			default:
				if ( preg_match( '/^(x-|list-)/i', $name ) ) {
					$extra[ $name ] = $value;
				}
		}
	}
	if ( ! $html ) {
		$html = 'text/html' === apply_filters( 'wp_mail_content_type', 'text/plain' );
	}

	$sender = ot_leads_from( $from['name'] ?? apply_filters( 'wp_mail_from_name', 'The Oberdorfer Team' ) );
	if ( ! $reply && ! empty( $from['email'] ) && strtolower( $from['email'] ) !== strtolower( $sender['email'] ) && ! str_starts_with( strtolower( $from['email'] ), 'wordpress@' ) ) {
		$reply = $from;
	}

	$message = (string) ( $atts['message'] ?? '' );
	$body    = array(
		'sender'  => $sender,
		'to'      => $to,
		'subject' => (string) ( $atts['subject'] ?? '' ),
	);
	if ( $html ) {
		$body['htmlContent'] = $message;
	} else {
		$body['textContent'] = '' !== $message ? $message : ' ';
	}
	foreach ( array( 'cc' => $cc, 'bcc' => $bcc ) as $k => $list ) {
		$list = array_values( array_filter( $list, fn( $a ) => is_email( $a['email'] ?? '' ) ) );
		if ( $list ) {
			$body[ $k ] = $list;
		}
	}
	if ( $reply && is_email( $reply['email'] ?? '' ) ) {
		$body['replyTo'] = $reply;
	}
	if ( $extra ) {
		$body['headers'] = $extra;
	}
	$files = array();
	foreach ( (array) ( $atts['attachments'] ?? array() ) as $name => $path ) {
		if ( is_string( $path ) && is_readable( $path ) && filesize( $path ) < 10 * MB_IN_BYTES ) {
			$files[] = array( 'name' => is_string( $name ) ? $name : wp_basename( $path ), 'content' => base64_encode( (string) file_get_contents( $path ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	}
	if ( $files ) {
		$body['attachment'] = $files;
	}

	$res  = wp_remote_post( 'https://api.brevo.com/v3/smtp/email', array(
		'timeout' => 15,
		'headers' => array( 'api-key' => ot_leads_opt( 'brevo_key' ), 'accept' => 'application/json', 'content-type' => 'application/json' ),
		'body'    => wp_json_encode( $body ),
	) );
	$code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
	if ( $code >= 200 && $code < 300 ) {
		do_action( 'wp_mail_succeeded', $atts );
		return true;
	}
	// Brevo refused it (or could not be reached): let WordPress try its own mail.
	error_log( 'ot-leads: Brevo did not send "' . $body['subject'] . '" (' . ( is_wp_error( $res ) ? $res->get_error_message() : $code ) . '); falling back to wp_mail.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
	return null;
}
