<?php
/**
 * AI (spec § 9), off until a team leader switches it on with an Anthropic API key
 * (wp-config.php: define( 'OT_LEADS_ANTHROPIC_KEY', '...' ) is preferred over the settings field).
 *
 * - Calls run server-side only, through the Messages API.
 * - The grounding pack is the only material the model may use: the lead's own fields and
 *   events with phone numbers and email addresses removed, the guide records for the lead's
 *   towns (with sources), the team's listings there, and the assigned agent's confirmed profile.
 * - Every draft passes the validator: each number in it must appear in the grounding pack; no
 *   fair-housing deny-list term; a length limit; no contact details or unsubscribe text (the
 *   system adds those). A failed draft is regenerated once, then shown flagged, never sent.
 * - Every call is logged in ot_ai_log with its token counts.
 */

defined( 'ABSPATH' ) || exit;

const OT_AI_DENY = array( 'safe neighborhood', 'safe area', 'safe town', 'safest', 'crime', 'exclusive', 'family-friendly', 'family friendly', 'great for families', 'good schools', 'great schools', 'top-rated schools', 'best schools', 'school rating', 'young professionals', 'retirees', 'empty nesters', 'diverse', 'ethnic', 'church', 'synagogue', 'mosque', 'christian', 'jewish', 'muslim', 'bachelor', 'walking distance to church', 'perfect for a couple', 'no children', 'adult community', 'handicap', 'able-bodied', 'master bedroom', 'guaranteed', 'will appreciate', 'great investment', 'prices will rise' );

function ot_ai_enabled() {
	return (bool) ot_leads_opt( 'ai_enabled' ) && (bool) ot_leads_opt( 'ai_key' );
}

/** One Messages API call. Returns [ text, usage ] or null. */
function ot_ai_call( $system, $user, $purpose, $lead_id = 0, $max = 900 ) {
	if ( ! ot_ai_enabled() ) {
		return null;
	}
	$model = (string) ot_leads_opt( 'ai_model' );
	$res   = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
		'timeout' => 45,
		'headers' => array( 'x-api-key' => ot_leads_opt( 'ai_key' ), 'anthropic-version' => '2023-06-01', 'content-type' => 'application/json' ),
		'body'    => wp_json_encode( array( 'model' => $model, 'max_tokens' => $max, 'system' => $system, 'messages' => array( array( 'role' => 'user', 'content' => $user ) ) ) ),
	) );
	$json = is_wp_error( $res ) ? null : json_decode( wp_remote_retrieve_body( $res ), true );
	$text = '';
	foreach ( (array) ( $json['content'] ?? array() ) as $c ) {
		if ( 'text' === ( $c['type'] ?? '' ) ) {
			$text .= $c['text'];
		}
	}
	global $wpdb;
	$wpdb->insert( ot_leads_table( 'ai_log' ), array(
		'purpose'       => $purpose,
		'model'         => $model,
		'lead_id'       => $lead_id,
		'prompt_hash'   => hash( 'sha256', $system . $user ),
		'output'        => $text ?: ( is_wp_error( $res ) ? $res->get_error_message() : substr( wp_remote_retrieve_body( $res ), 0, 500 ) ),
		'input_tokens'  => (int) ( $json['usage']['input_tokens'] ?? 0 ),
		'output_tokens' => (int) ( $json['usage']['output_tokens'] ?? 0 ),
		'created_at'    => ot_leads_now(),
	) );
	return $text ? array( $text, $json['usage'] ?? array() ) : null;
}

/** Remove phone numbers and email addresses (the AI never needs them). */
function ot_ai_scrub( $s ) {
	$s = preg_replace( '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[email]', (string) $s );
	return preg_replace( '/(\+?1[\s.-]?)?\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4}/', '[phone]', $s );
}

/** The only material the AI may use, as text. */
function ot_ai_grounding( $lead ) {
	$a    = ot_leads_agents()[ $lead['assigned_agent'] ] ?? array();
	$out  = "LEAD\nFirst name: " . ( $lead['first_name'] ?: 'unknown' ) . "\nType: " . ( OT_LEAD_TYPES[ $lead['lead_type'] ] ?? $lead['lead_type'] ) . "\nStage: " . ( OT_LEAD_STAGES[ $lead['stage'] ] ?? $lead['stage'] ) . "\nInterest: " . $lead['topic'] . "\n";
	if ( $lead['listing_id'] ) {
		$out .= 'Asked about listing: ' . get_the_title( $lead['listing_id'] ) . ' ' . get_permalink( $lead['listing_id'] ) . "\n";
	}
	$out .= "Recent activity (newest first):\n";
	foreach ( array_slice( ot_lead_events( (int) $lead['id'], 30 ), 0, 15 ) as $e ) {
		$p = json_decode( (string) $e['payload'], true ) ?: array();
		if ( in_array( $e['type'], array( 'form', 'page_view', 'note', 'reply', 'stage_change' ), true ) ) {
			$out .= '- ' . substr( $e['created_at'], 0, 10 ) . ' ' . $e['type'] . ': ' . ot_ai_scrub( trim( implode( ' · ', array_filter( array( $p['topic'] ?? '', $p['message'] ?? '', $p['path'] ?? '', $p['title'] ?? '', $p['note'] ?? '', isset( $p['to'] ) && is_string( $p['to'] ) ? 'to ' . $p['to'] : '' ) ) ) ) ) . "\n";
		}
	}
	$out .= "\nAGENT\nName: " . ( $a['name'] ?? 'Brandon & Kaitlyn Oberdorfer' ) . "\nTeam: The Oberdorfer Team, a husband-and-wife real estate team based in Worcester, MA, serving Massachusetts, under REWAP Brokerage LLC.\n";
	foreach ( array_slice( array_filter( explode( ',', (string) $lead['towns'] ) ), 0, 3 ) as $slug ) {
		$p = get_page_by_path( $slug, OBJECT, 'ot_community' );
		if ( ! $p ) {
			continue;
		}
		$r    = json_decode( (string) get_post_meta( $p->ID, 'ot_record', true ), true ) ?: array();
		$m    = $r['market'] ?? array();
		$out .= "\nTOWN GUIDE: " . $p->post_title . ' (' . get_permalink( $p ) . ")\nSummary: " . ( $r['answer'] ?? '' ) . "\n";
		if ( ! empty( $m['sf_median_2025'] ) ) {
			$out .= 'Market: single-family median sale price $' . number_format( (int) $m['sf_median_2025'] ) . ' (' . ( $m['period'] ?? '' ) . '), $' . number_format( (int) ( $m['sf_median_2024'] ?? 0 ) ) . ' in 2024, ' . (int) ( $m['sf_sales_2025'] ?? 0 ) . ' sales. Source: ' . ( $m['source'] ?? '' ) . ". Not live; this town only.\n";
		}
		if ( function_exists( 'ot_listings' ) ) {
			foreach ( ot_listings( array( 'town' => $slug, 'limit' => 3 ) ) as $l ) {
				$out .= 'Team listing: ' . $l['address'] . ', $' . number_format( (int) $l['price'] ) . ', ' . $l['url'] . "\n";
			}
		}
	}
	return $out;
}

function ot_ai_system() {
	return 'You write short emails for The Oberdorfer Team, a Massachusetts real estate team, to a person who contacted them. Voice: warm, plain, specific, no hype, first person from the agent. Rules: '
		. '1) Use only facts in the GROUNDING section; never invent listings, prices, statistics, dates, awards or claims. Any number you use must appear in the grounding exactly, with its source and period when it is a market figure. '
		. '2) Fair housing: never describe who lives in a place or who a home suits; never use words like safe, exclusive or family-friendly; never rate or rank schools (link to the district\'s DESE profile instead); never mention anything about the person\'s race, religion, sex, family, disability, origin or age, even if they did. '
		. '3) No promises about prices, appreciation or investment returns; no legal, tax or lending advice (suggest an attorney or lender). '
		. '4) Do not include phone numbers, email addresses, a signature, an unsubscribe line or a postal address; the system adds them. '
		. '5) 60 to 160 words. Reply with JSON only: {"subject": "...", "body_html": "<p>...</p>"}.';
}

/** Check a draft against the grounding pack. */
function ot_ai_validate( $subject, $body, $grounding ) {
	$text   = wp_strip_all_tags( $subject . ' ' . $body );
	$norm   = fn( $s ) => preg_replace( '/[,$%]/', '', $s );
	$have   = array_unique( array_map( $norm, preg_match_all( '/\d[\d,.]*/', $grounding, $m ) ? $m[0] : array() ) );
	$issues = array();
	if ( preg_match_all( '/\d[\d,.]*\d|\d/', $text, $nums ) ) {
		foreach ( array_unique( $nums[0] ) as $n ) {
			$k = rtrim( $norm( $n ), '.' );
			if ( ! in_array( $k, $have, true ) && ! in_array( $k, array( '1', '2', '3', '20' ), true ) ) {
				$issues[] = 'Number not in the sources: ' . $n;
			}
		}
	}
	foreach ( OT_AI_DENY as $term ) {
		if ( false !== stripos( $text, $term ) ) {
			$issues[] = 'Fair-housing or advertising term: "' . $term . '"';
		}
	}
	$words = str_word_count( $text );
	if ( $words > 220 || $words < 25 ) {
		$issues[] = 'Length: ' . $words . ' words (25 to 220)';
	}
	if ( preg_match( '/@|unsubscribe|\(\d{3}\)|\d{3}-\d{3}-\d{4}/i', $text ) ) {
		$issues[] = 'Contains contact details or unsubscribe text';
	}
	return array( 'pass' => ! $issues, 'issues' => $issues );
}

/** Draft a nurture email from a brief. Returns [ subject, body, checks ] or null. */
function ot_ai_draft( $lead, $brief ) {
	$g = ot_ai_grounding( $lead );
	for ( $try = 0; $try < 2; $try++ ) {
		$r = ot_ai_call( ot_ai_system(), "BRIEF\n" . ( $brief ?: 'A short, useful check-in that moves the conversation forward.' ) . "\n\nGROUNDING\n" . $g . ( $try ? "\n\nYour previous draft failed the checks; follow the rules exactly." : '' ), 'draft', (int) $lead['id'] );
		if ( ! $r ) {
			return null;
		}
		$j = json_decode( trim( preg_replace( '/^```(?:json)?|```$/m', '', $r[0] ) ), true );
		if ( ! is_array( $j ) || empty( $j['subject'] ) || empty( $j['body_html'] ) ) {
			continue;
		}
		$sig    = ot_nurture_merge( '<p>{agent_name}<br>The Oberdorfer Team · {agent_phone}</p>', $lead );
		$checks = ot_ai_validate( $j['subject'], $j['body_html'], $g );
		$checks['sources'] = 'Lead record and events, guide records for ' . ( $lead['towns'] ?: 'no town' ) . ', agent profile';
		if ( $checks['pass'] || $try ) {
			return array( wp_strip_all_tags( $j['subject'] ), wp_kses_post( $j['body_html'] ) . $sig, $checks );
		}
	}
	return null;
}

/** Three lines on the lead and one suggested next step, for the lead screen. */
function ot_ai_summary( $lead ) {
	$r = ot_ai_call( 'You help a real estate agent. From the LEAD material only, write JSON {"summary": "three short lines separated by \\n: who they are in terms of what they want (never personal characteristics), what they did, where things stand", "next": "one concrete next step with a short reason"}. Never mention protected characteristics. No invented facts.', ot_ai_grounding( $lead ), 'summary', (int) $lead['id'], 400 );
	if ( ! $r ) {
		return null;
	}
	$j = json_decode( trim( preg_replace( '/^```(?:json)?|```$/m', '', $r[0] ) ), true );
	if ( ! is_array( $j ) ) {
		return null;
	}
	$out = array( 'summary' => sanitize_textarea_field( (string) ( $j['summary'] ?? '' ) ), 'next' => sanitize_text_field( (string) ( $j['next'] ?? '' ) ), 'at' => ot_leads_now() );
	update_option( 'ot_ai_sum_' . (int) $lead['id'], $out, false );
	return $out;
}

/** This month's AI usage: [ calls, input tokens, output tokens ]. */
function ot_ai_month_usage() {
	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT COUNT(*) n, SUM(input_tokens) i, SUM(output_tokens) o FROM ' . ot_leads_table( 'ai_log' ) . ' WHERE created_at >= %s', gmdate( 'Y-m-01 00:00:00' ) ), ARRAY_A );
	return array( (int) $row['n'], (int) $row['i'], (int) $row['o'] );
}
