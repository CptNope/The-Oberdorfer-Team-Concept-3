<?php
/**
 * Capture (spec § 4): every form on the site posts here and is saved before anything else runs.
 *
 * - POST /wp-json/ot-leads/v1/lead takes the inquiry forms (ot/inquiry-form), newsletter forms
 *   and the sign-up forms below. It needs no WordPress nonce, because the page cache (Varnish)
 *   would serve stale ones; protection is Cloudflare Turnstile (when keys are set), a hidden
 *   honeypot field and a per-address rate limit.
 * - The page, town, listing or agent, the UTM tags, the first page of the visit and the referrer
 *   are recorded. A second submission with the same email is merged into the existing lead.
 * - After a submission a signed first-party cookie lets later page visits be logged as events
 *   (POST /seen), which feed the intent signal. The privacy policy must say so.
 * - Sign-up forms added by this plugin: new-listing alerts on each agent's listings page, the
 *   monthly update on each town guide, and an open-house sign-in page (/?ot_signin=<listing id>).
 */

defined( 'ABSPATH' ) || exit;

/* --------------------------------------------------------------------- forms on the page */

// The inquiry forms become real: leads.js handles [data-ot-lead] instead of the theme's demo.
add_filter( 'ot_inquiry_form_attr', fn() => 'data-ot-lead' );

/** Consent box (unticked), honeypot and the Turnstile slot inside every inquiry form. */
add_filter( 'ot_inquiry_form_extra', function ( $html, $context, $p ) {
	$recruit = str_starts_with( (string) $context, 'recruiting' );
	$consent = $recruit ? '' : '<label class="check"><input type="checkbox" name="consent_email" value="1"><span>' . esc_html( ot_leads_opt( 'consent_text' ) ) . '</span></label>';
	return $html . $consent . ot_leads_trap() . ot_leads_turnstile_slot();
}, 10, 3 );

/** A field people never see and bots fill in. */
function ot_leads_trap() {
	return '<div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden"><label>Leave this empty <input type="text" name="ot_website" tabindex="-1" autocomplete="off"></label></div>';
}

function ot_leads_turnstile_slot() {
	return ot_leads_opt( 'turnstile_site' ) ? '<div class="ot-turnstile" data-sitekey="' . esc_attr( ot_leads_opt( 'turnstile_site' ) ) . '"></div>' : '';
}

/** A one-line email sign-up with its own consent wording, shown above the field. */
function ot_leads_signup_form( $type, $title, $wording, $button = 'Sign me up', $class = '' ) {
	static $n = 0;
	$n++;
	return '<form class="signup ' . esc_attr( $class ) . '" data-ot-signup data-lead-type="' . esc_attr( $type ) . '" data-consent="' . esc_attr( $wording ) . '" aria-labelledby="su-' . $n . '-t">'
		. '<p class="h4" id="su-' . $n . '-t">' . esc_html( $title ) . '</p><p class="body-s">' . esc_html( $wording ) . '</p>'
		. '<div class="signup__row"><label class="visually-hidden" for="su-' . $n . '-e">Email address</label><input class="input" id="su-' . $n . '-e" type="email" name="email" autocomplete="email" required placeholder="Email address"><button class="btn" type="submit">' . esc_html( $button ) . '</button></div>'
		. ot_leads_trap() . ot_leads_turnstile_slot() . '<div data-form-status hidden tabindex="-1" role="status"></div></form>';
}

// New-listing alerts on an agent's listings page.
add_filter( 'render_block_ot/agent-profile', function ( $html, $block ) {
	if ( 'listings-page' !== ( $block['attrs']['section'] ?? '' ) || ! is_singular( 'ot_agent' ) ) {
		return $html;
	}
	$slug = get_post_field( 'post_name', get_queried_object_id() );
	$a    = ot_leads_agents()[ $slug ] ?? null;
	if ( ! $a ) {
		return $html;
	}
	$who  = $a['team'] ? 'the team' : $a['given'];
	$form = ot_leads_signup_form( 'listings:' . $slug, 'Hear about ' . ( $a['team'] ? 'our' : $a['given'] . '\'s' ) . ' next listing first', 'Email me when ' . $who . ' lists a new home, and about homes coming to market. I can unsubscribe at any time.', 'Email me' );
	return '<section class="section"><div class="container"><div class="panel">' . $form . '</div></div></section>' . $html;
}, 10, 2 );

// The monthly town update on each town guide, above its contact section.
add_filter( 'render_block_ot/community-section', function ( $html, $block ) {
	if ( 'contact' !== ( $block['attrs']['section'] ?? '' ) || ! is_singular( 'ot_community' ) ) {
		return $html;
	}
	$post = get_post( get_queried_object_id() );
	$path = $post->post_parent ? get_post_field( 'post_name', $post->post_parent ) . '/' . $post->post_name : $post->post_name;
	$form = ot_leads_signup_form( 'community:' . $path, 'The ' . $post->post_title . ' update, monthly', 'Email me a short monthly update on ' . $post->post_title . ': recent sales from the public record and new listings. I can unsubscribe at any time.', 'Email me' );
	return '<section class="section section--tight"><div class="container"><div class="panel">' . $form . '</div></div></section>' . $html;
}, 10, 2 );

/* --------------------------------------------------------------------- open-house sign-in */

/** /?ot_signin=<listing id>: a full-screen sign-in for the tablet at the door. */
add_action( 'template_redirect', function () {
	if ( empty( $_GET['ot_signin'] ) || ! function_exists( 'ot_listing' ) ) {
		return;
	}
	$l = ot_listing( (int) $_GET['ot_signin'] );
	if ( ! $l || 'publish' !== get_post_status( $l['id'] ) ) {
		return;
	}
	nocache_headers();
	header( 'X-Robots-Tag: noindex' );
	$form = '<form class="form" data-ot-lead data-lead-type="openhouse:' . esc_attr( $l['slug'] ) . '" data-reset-after aria-labelledby="oh-t">
<h1 class="display-m" id="oh-t">Welcome to ' . esc_html( $l['address'] ) . '</h1>
<p class="lead">Sign in for the open house. We will send you the details of this home, and nothing else unless you tick the box.</p>
<div class="form__row"><div class="field"><label class="field__label" for="oh-name">Full name</label><input class="input" id="oh-name" name="name" autocomplete="name" required data-label="Full name"></div><div class="field"><label class="field__label" for="oh-email">Email</label><input class="input" id="oh-email" name="email" type="email" autocomplete="email" required data-label="Email"></div></div>
<div class="form__row"><div class="field"><label class="field__label" for="oh-phone">Phone <span class="field__opt">(optional)</span></label><input class="input" id="oh-phone" name="phone" type="tel" autocomplete="tel"></div><div class="field"><label class="field__label" for="oh-agent">Are you working with an agent?</label><select class="select" id="oh-agent" name="topic"><option>No</option><option>Yes</option></select></div></div>
<label class="check"><input type="checkbox" name="consent_email" value="1"><span>' . esc_html( ot_leads_opt( 'consent_text' ) ) . '</span></label>'
		. ot_leads_trap() . ot_leads_turnstile_slot() . '
<div><button class="btn" type="submit">Sign in</button></div><div data-form-status hidden tabindex="-1" role="status"></div></form>';
	?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Open house sign-in · <?php echo esc_html( $l['address'] ); ?></title><?php wp_head(); ?></head>
<body class="ot-signin"><main id="main" class="section"><div class="container" style="max-width:46rem"><div id="live" class="visually-hidden" aria-live="polite"></div><?php echo $form; // phpcs:ignore WordPress.Security.EscapeOutput ?><p class="caption" style="margin-top:2rem">The Oberdorfer Team · REWAP Brokerage LLC · <?php echo esc_html( OT_PHONE ); ?></p></div></main><?php wp_footer(); ?></body></html>
	<?php
	exit;
} );

/* --------------------------------------------------------------------- scripts */

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_script( 'ot-leads', OT_LEADS_URL . '/assets/leads.js', array(), OT_LEADS_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_localize_script( 'ot-leads', 'otLeads', array(
		'endpoint'  => esc_url_raw( rest_url( 'ot-leads/v1/' ) ),
		'turnstile' => (string) ot_leads_opt( 'turnstile_site' ),
		'icons'     => function_exists( 'ot_asset' ) ? ot_asset( 'assets/img/icons.svg' ) : '',
	) );
	if ( ot_leads_opt( 'turnstile_site' ) ) {
		wp_enqueue_script( 'cf-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit', array(), null, array( 'in_footer' => true, 'strategy' => 'defer' ) ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	}
}, 20 );

/* --------------------------------------------------------------------- REST */

add_action( 'rest_api_init', function () {
	register_rest_route( 'ot-leads/v1', '/lead', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => 'ot_leads_rest_submit',
	) );
	register_rest_route( 'ot-leads/v1', '/seen', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => 'ot_leads_rest_seen',
	) );
} );

function ot_leads_turnstile_ok( $token ) {
	$secret = ot_leads_opt( 'turnstile_secret' );
	if ( ! $secret || ! ot_leads_opt( 'turnstile_site' ) ) {
		return true; // not configured: honeypot and rate limit only
	}
	if ( ! $token ) {
		return false;
	}
	$res = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
		'timeout' => 8,
		'body'    => array( 'secret' => $secret, 'response' => $token, 'remoteip' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '' ),
	) );
	$json = is_wp_error( $res ) ? array() : json_decode( wp_remote_retrieve_body( $res ), true );
	return ! empty( $json['success'] );
}

/** Split "Jane Q. Doe" into first and last name. */
function ot_leads_split_name( $name ) {
	$name  = trim( preg_replace( '/\s+/', ' ', $name ) );
	$parts = explode( ' ', $name );
	$first = array_shift( $parts );
	return array( (string) $first, implode( ' ', $parts ) );
}

/** What a form's data-lead-type says: [ type, context detail, towns, listing id, agent slug ]. */
function ot_leads_parse_context( $ctx ) {
	$ctx = sanitize_text_field( (string) $ctx );
	list( $kind, $detail ) = array_pad( explode( ':', $ctx, 2 ), 2, '' );
	$towns   = array();
	$listing = 0;
	$agent   = '';
	switch ( $kind ) {
		case 'community':
			$towns[] = $detail;
			break;
		case 'showing':
		case 'openhouse':
			$p = $detail ? get_page_by_path( $detail, OBJECT, 'ot_listing' ) : null;
			if ( $p ) {
				$listing = $p->ID;
				$town    = (string) get_post_meta( $p->ID, 'ot_town_slug', true );
				if ( $town ) {
					$towns[] = $town;
				}
				$agent = (string) get_post_meta( $p->ID, 'ot_agent', true );
			}
			break;
		case 'agent':
		case 'listings':
			$agent = 'brandon-kaitlyn' === $detail ? 'brandon-kaitlyn-oberdorfer' : $detail;
			break;
		case 'pf':
			$kind = 'agent';
			break;
	}
	$type = isset( OT_LEAD_TYPES[ $kind ] ) ? $kind : 'general';
	return array( $type, $detail, $towns, $listing, $agent );
}

function ot_leads_rest_submit( WP_REST_Request $r ) {
	$p = (array) $r->get_json_params();
	$f = fn( $k, $max = 190 ) => substr( sanitize_text_field( (string) ( $p[ $k ] ?? '' ) ), 0, $max );

	// Spam: honeypot (pretend success, store nothing), rate limit, Turnstile.
	if ( '' !== trim( (string) ( $p['ot_website'] ?? '' ) ) ) {
		return array( 'ok' => true, 'message' => 'Thank you.' );
	}
	$rl   = 'ot_rl_' . substr( ot_leads_ip_hash(), 0, 20 );
	$hits = (int) get_transient( $rl );
	if ( $hits >= 6 ) {
		return new WP_Error( 'ot_rate', 'Too many messages from this connection. Please call 406-250-7620, or try again in a few minutes.', array( 'status' => 429 ) );
	}
	set_transient( $rl, $hits + 1, 10 * MINUTE_IN_SECONDS );
	if ( ! ot_leads_turnstile_ok( (string) ( $p['turnstile'] ?? '' ) ) ) {
		return new WP_Error( 'ot_turnstile', 'Please complete the check above the button and send again.', array( 'status' => 400 ) );
	}

	$email = strtolower( sanitize_email( (string) ( $p['email'] ?? '' ) ) );
	if ( ! is_email( $email ) ) {
		return new WP_Error( 'ot_email', 'Enter an email address like name@example.com.', array( 'status' => 400 ) );
	}
	list( $type, $detail, $towns, $listing, $agent ) = ot_leads_parse_context( $p['lead_type'] ?? '' );
	$form = array(
		'name'      => $f( 'name' ),
		'email'     => $email,
		'phone'     => preg_replace( '/[^0-9+()\-\s.]/', '', $f( 'phone', 40 ) ),
		'preferred' => in_array( $p['pref'] ?? '', array( 'call', 'text', 'email' ), true ) ? $p['pref'] : '',
		'topic'     => $f( 'topic' ),
		'message'   => substr( sanitize_textarea_field( (string) ( $p['message'] ?? '' ) ), 0, 4000 ),
		'page'      => esc_url_raw( (string) ( $p['page'] ?? '' ) ),
		'first'     => esc_url_raw( (string) ( $p['first'] ?? '' ) ),
		'referrer'  => esc_url_raw( (string) ( $p['referrer'] ?? '' ) ),
		'utm'       => array_map( 'sanitize_text_field', array_intersect_key( (array) ( $p['utm'] ?? array() ), array_flip( array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid' ) ) ) ),
		'type'      => $type,
		'context'   => $f( 'lead_type' ),
		'consent'   => ! empty( $p['consent_email'] ),
		'consent_text' => $f( 'consent_text', 500 ),
	);
	if ( in_array( $type, array( 'buyer', 'seller', 'showing', 'agent', 'office', 'recruiting', 'general', 'openhouse' ), true ) && '' === $form['name'] ) {
		return new WP_Error( 'ot_name', 'Full name is required.', array( 'status' => 400 ) );
	}

	$lead_id = ot_leads_capture( $form, $towns, $listing, $agent );
	if ( ! $lead_id ) {
		return new WP_Error( 'ot_store', 'Something went wrong on our side. Please call 406-250-7620.', array( 'status' => 500 ) );
	}
	ot_leads_set_cookie( $lead_id );

	$lead = ot_lead( $lead_id );
	$a    = ot_leads_agents()[ $lead['assigned_agent'] ] ?? null;
	$who  = $a && ! $a['team'] ? $a['given'] : 'Brandon or Kaitlyn';
	$msg  = in_array( $type, array( 'newsletter', 'listings', 'community' ), true ) && '' === $form['name']
		? 'You are signed up. The first email will come from The Oberdorfer Team; you can unsubscribe from any of them.'
		: ( 'openhouse' === $type ? 'Thank you for signing in. Enjoy the home.' : 'Thank you. ' . $who . ' has your message and will reply by your preferred method, usually within the hour during business hours.' );
	return array( 'ok' => true, 'message' => $msg );
}

/**
 * Save a submission: merge into an existing lead with the same email or create one, record the
 * form and consent, route it, alert the agent, forward it and enroll it in nurture.
 */
function ot_leads_capture( $form, $towns = array(), $listing = 0, $agent = '' ) {
	global $wpdb;
	$t   = ot_leads_table( 'leads' );
	$now = ot_leads_now();
	list( $first, $last ) = ot_leads_split_name( $form['name'] );
	$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE email = %s ORDER BY id ASC LIMIT 1", $form['email'] ), ARRAY_A );
	$is_signup = in_array( $form['type'], array( 'newsletter', 'listings' ), true ) || ( 'community' === $form['type'] && '' === $form['name'] );

	if ( $existing ) {
		$id     = (int) $existing['id'];
		$merged = array_values( array_unique( array_filter( array_merge( explode( ',', $existing['towns'] ), $towns ) ) ) );
		$upd    = array( 'towns' => implode( ',', $merged ) );
		foreach ( array( 'first_name' => $first, 'last_name' => $last, 'phone' => $form['phone'], 'preferred' => $form['preferred'] ) as $k => $v ) {
			if ( $v && ! $existing[ $k ] ) {
				$upd[ $k ] = $v;
			}
		}
		if ( $listing ) {
			$upd['listing_id'] = $listing;
		}
		// A real inquiry from someone in long-term nurture or lost starts a new conversation.
		if ( ! $is_signup && in_array( $existing['stage'], array( 'nurture', 'lost' ), true ) ) {
			$upd['stage']     = 'new';
			$upd['escalated'] = 0;
			$upd['contacted_at'] = null;
		}
		ot_lead_update( $id, $upd, 'site' );
		// Only a re-opened conversation counts as new; anything else is a follow-up to the agent.
		$fresh = ! $is_signup && isset( $upd['stage'] );
	} else {
		$wpdb->insert( $t, array(
			'created_at'       => $now,
			'updated_at'       => $now,
			'last_activity_at' => $now,
			'first_name'       => $first,
			'last_name'        => $last,
			'email'            => $form['email'],
			'phone'            => $form['phone'],
			'preferred'        => $form['preferred'],
			'lead_type'        => $form['type'],
			'context'          => $form['context'],
			'stage'            => $is_signup ? 'nurture' : 'new',
			'towns'            => implode( ',', array_unique( array_filter( $towns ) ) ),
			'listing_id'       => $listing,
			'topic'            => $form['topic'],
			'source_url'       => substr( $form['page'], 0, 255 ),
			'first_url'        => substr( $form['first'], 0, 255 ),
			'referrer'         => substr( $form['referrer'], 0, 255 ),
			'utm'              => $form['utm'] ? wp_json_encode( $form['utm'] ) : null,
		) );
		$id = (int) $wpdb->insert_id;
		if ( ! $id ) {
			return 0;
		}
		$fresh = ! $is_signup;
	}

	ot_lead_event( $id, 'form', array(
		'type'    => $form['type'],
		'context' => $form['context'],
		'topic'   => $form['topic'],
		'message' => $form['message'],
		'pref'    => $form['preferred'],
		'page'    => $form['page'],
		'listing' => $listing ? get_the_title( $listing ) : '',
	), 'visitor' );

	if ( $form['consent'] ) {
		$wording = $form['consent_text'] ?: ot_leads_opt( 'consent_text' );
		ot_lead_consent( $id, $form['email'], 'newsletter' === $form['type'] ? 'newsletter' : 'email_marketing', true, $wording, $form['page'] );
		if ( ot_leads_suppressed( $form['email'] ) ) {
			// Signing up again after unsubscribing is a new, explicit request.
			$wpdb->delete( ot_leads_table( 'suppressions' ), array( 'email' => $form['email'], 'reason' => 'unsubscribe' ) );
		}
	}

	$lead = ot_lead( $id );
	if ( ! $existing || $fresh ) {
		$route = ot_leads_route( $lead, $agent );
		if ( $route && ( ! $existing || ! $lead['assigned_agent'] || $agent ) ) {
			ot_lead_update( $id, array( 'assigned_agent' => $route ), 'routing' );
		}
	}
	$lead = ot_lead( $id );
	if ( $fresh ) {
		ot_leads_alert( $lead, $form );
		ot_leads_confirm( $lead, $form );
	} elseif ( $existing && ! $is_signup ) {
		ot_leads_alert( $lead, $form, true ); // the assigned agent hears about the follow-up
	}
	ot_leads_outbox_add( $lead, $form );
	ot_nurture_enroll_for( $lead, $form );
	do_action( 'ot_lead_captured', $id, $form );
	return $id;
}

/* --------------------------------------------------------------------- page visits */

function ot_leads_set_cookie( $id ) {
	$val = $id . '.' . ot_leads_sign( 'lid|' . $id );
	setcookie( 'ot_lid', $val, array( 'expires' => time() + YEAR_IN_SECONDS, 'path' => '/', 'secure' => is_ssl(), 'httponly' => false, 'samesite' => 'Lax' ) );
}

/** A known visitor viewed a page: one event per path per 30 minutes. */
function ot_leads_rest_seen( WP_REST_Request $r ) {
	$c = (string) ( $_COOKIE['ot_lid'] ?? '' );
	list( $id, $sig ) = array_pad( explode( '.', $c, 2 ), 2, '' );
	if ( ! ctype_digit( $id ) || ! hash_equals( ot_leads_sign( 'lid|' . $id ), $sig ) || ! ot_lead( (int) $id ) ) {
		return array( 'ok' => false );
	}
	$path = substr( (string) wp_parse_url( esc_url_raw( (string) $r->get_param( 'path' ) ), PHP_URL_PATH ), 0, 200 );
	$key  = 'ot_seen_' . md5( $id . $path );
	if ( $path && ! get_transient( $key ) ) {
		set_transient( $key, 1, 30 * MINUTE_IN_SECONDS );
		ot_lead_event( (int) $id, 'page_view', array( 'path' => $path, 'title' => sanitize_text_field( (string) $r->get_param( 'title' ) ) ), 'visitor' );
	}
	return array( 'ok' => true );
}
