<?php
/**
 * Leads, events, consents, tasks and settings: the data layer every other part uses.
 */

defined( 'ABSPATH' ) || exit;

const OT_LEAD_STAGES = array(
	'new'             => 'New',
	'contacted'       => 'Contacted',
	'appointment'     => 'Appointment set',
	'active'          => 'Active',
	'under_agreement' => 'Under agreement',
	'closed'          => 'Closed',
	'nurture'         => 'Nurture',
	'lost'            => 'Lost',
);

const OT_LEAD_TYPES = array(
	'buyer'      => 'Buyer',
	'seller'     => 'Seller (market review)',
	'showing'    => 'Showing request',
	'community'  => 'Town guide',
	'agent'      => 'Agent profile',
	'recruiting' => 'Recruiting',
	'office'     => 'Office appointment',
	'newsletter' => 'Newsletter',
	'listings'   => 'New-listing alerts',
	'openhouse'  => 'Open house sign-in',
	'general'    => 'General',
);

function ot_leads_now() {
	return current_time( 'mysql', true ); // UTC
}

/* --------------------------------------------------------------------- settings */

function ot_leads_defaults() {
	return array(
		'leaders'          => 'brandonoberdorfer@gmail.com, kaitlynoberdorfer@gmail.com',
		'alert_cc'         => array(),
		'response_minutes' => 15,
		'hours_start'      => 8,
		'hours_end'        => 20,
		'town_owners'      => array(), // town slug => agent slug
		'type_owners'      => array(), // '' = the team (Brandon and Kaitlyn)
		'turnstile_site'   => '',
		'turnstile_secret' => '',
		'brevo_key'        => '',
		'brevo_token'      => '',
		'from_domain'      => 'oberdorferteam.com',
		'from_team'        => 'hello',
		'confirm_email'    => 1,
		'crm_mode'         => 'builtin',
		'webhook_url'      => '',
		'webhook_secret'   => '',
		'fub_key'          => '',
		'fub_system'       => '',
		'fub_system_key'   => '',
		'ai_enabled'       => 0,
		'ai_key'           => '',
		'ai_model'         => 'claude-sonnet-5-5',
		'retention_months' => 24,
		'freq_days'        => 3,
		'consent_text'     => 'Email me market updates and new listings from The Oberdorfer Team. I can unsubscribe at any time.',
	);
}

/** A setting. Secrets can also come from wp-config.php constants, which win over the database. */
function ot_leads_opt( $key ) {
	$const = array(
		'brevo_key'        => 'OT_LEADS_BREVO_KEY',
		'brevo_token'      => 'OT_LEADS_BREVO_TOKEN',
		'turnstile_secret' => 'OT_LEADS_TURNSTILE_SECRET',
		'webhook_secret'   => 'OT_LEADS_WEBHOOK_SECRET',
		'fub_key'          => 'OT_LEADS_FUB_KEY',
		'ai_key'           => 'OT_LEADS_ANTHROPIC_KEY',
	);
	if ( isset( $const[ $key ] ) && defined( $const[ $key ] ) && constant( $const[ $key ] ) ) {
		return constant( $const[ $key ] );
	}
	$all = wp_parse_args( (array) get_option( 'ot_leads_settings', array() ), ot_leads_defaults() );
	return $all[ $key ] ?? null;
}

/** True when the secret comes from wp-config.php (the settings screen then hides the field). */
function ot_leads_opt_locked( $key ) {
	$map = array( 'brevo_key' => 'OT_LEADS_BREVO_KEY', 'brevo_token' => 'OT_LEADS_BREVO_TOKEN', 'turnstile_secret' => 'OT_LEADS_TURNSTILE_SECRET', 'webhook_secret' => 'OT_LEADS_WEBHOOK_SECRET', 'fub_key' => 'OT_LEADS_FUB_KEY', 'ai_key' => 'OT_LEADS_ANTHROPIC_KEY' );
	return isset( $map[ $key ] ) && defined( $map[ $key ] ) && constant( $map[ $key ] );
}

/** The key that signs links and hashes IP addresses (spec § 11a: a keyed HMAC, not a plain hash). */
function ot_leads_secret() {
	return defined( 'OT_LEADS_SECRET' ) && OT_LEADS_SECRET ? OT_LEADS_SECRET : wp_salt( 'auth' ) . 'ot-leads';
}

function ot_leads_sign( $data ) {
	return substr( hash_hmac( 'sha256', (string) $data, ot_leads_secret() ), 0, 32 );
}

function ot_leads_ip_hash() {
	$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
	return $ip ? hash_hmac( 'sha256', $ip, ot_leads_secret() ) : '';
}

/* --------------------------------------------------------------------- leads */

function ot_lead( $id ) {
	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . ot_leads_table( 'leads' ) . ' WHERE id = %d', $id ), ARRAY_A );
	return $row ?: null;
}

function ot_lead_name( $l ) {
	return trim( ( $l['first_name'] ?? '' ) . ' ' . ( $l['last_name'] ?? '' ) ) ?: ( $l['email'] ?? 'Lead #' . ( $l['id'] ?? '' ) );
}

/** Update fields on a lead; records a stage change or assignment as an event. */
function ot_lead_update( $id, $fields, $actor = '' ) {
	global $wpdb;
	$old = ot_lead( $id );
	if ( ! $old ) {
		return false;
	}
	$fields['updated_at'] = ot_leads_now();
	$wpdb->update( ot_leads_table( 'leads' ), $fields, array( 'id' => $id ) );
	if ( isset( $fields['stage'] ) && $fields['stage'] !== $old['stage'] ) {
		ot_lead_event( $id, 'stage_change', array( 'from' => $old['stage'], 'to' => $fields['stage'] ), $actor );
		do_action( 'ot_lead_stage_changed', $id, $fields['stage'], $old['stage'] );
	}
	if ( isset( $fields['assigned_agent'] ) && $fields['assigned_agent'] !== $old['assigned_agent'] ) {
		ot_lead_event( $id, 'assigned', array( 'to' => $fields['assigned_agent'] ), $actor );
		do_action( 'ot_lead_assigned', $id, $fields['assigned_agent'], $old['assigned_agent'], $actor );
	}
	return true;
}

function ot_lead_event( $lead_id, $type, $payload = array(), $actor = '' ) {
	global $wpdb;
	$now = ot_leads_now();
	$wpdb->insert( ot_leads_table( 'lead_events' ), array(
		'lead_id'    => $lead_id,
		'type'       => $type,
		'payload'    => wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
		'actor'      => $actor ?: ( is_user_logged_in() ? wp_get_current_user()->user_login : 'site' ),
		'created_at' => $now,
	) );
	if ( ! in_array( $type, array( 'viewed_by', 'exported' ), true ) ) {
		$wpdb->update( ot_leads_table( 'leads' ), array( 'last_activity_at' => $now ), array( 'id' => $lead_id ) );
	}
	return (int) $wpdb->insert_id;
}

function ot_lead_events( $lead_id, $limit = 200 ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . ot_leads_table( 'lead_events' ) . ' WHERE lead_id = %d ORDER BY id DESC LIMIT %d', $lead_id, $limit ), ARRAY_A );
}

/** Record consent given or withdrawn, with the exact wording shown (spec § 4). */
function ot_lead_consent( $lead_id, $email, $channel, $granted, $wording, $page = '' ) {
	global $wpdb;
	$wpdb->insert( ot_leads_table( 'consents' ), array(
		'lead_id'         => $lead_id,
		'email'           => strtolower( $email ),
		'channel'         => $channel,
		'granted'         => $granted ? 1 : 0,
		'wording'         => $wording,
		'wording_version' => substr( md5( $wording ), 0, 8 ),
		'page_url'        => substr( $page, 0, 255 ),
		'ip_hash'         => ot_leads_ip_hash(),
		'created_at'      => ot_leads_now(),
	) );
}

/** The latest email-marketing consent for an address. */
function ot_lead_has_consent( $email, $channel = 'email_marketing' ) {
	global $wpdb;
	$g = $wpdb->get_var( $wpdb->prepare( 'SELECT granted FROM ' . ot_leads_table( 'consents' ) . ' WHERE email = %s AND channel = %s ORDER BY id DESC LIMIT 1', strtolower( $email ), $channel ) );
	return '1' === (string) $g && ! ot_leads_suppressed( $email );
}

function ot_leads_suppressed( $email ) {
	global $wpdb;
	return (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT reason FROM ' . ot_leads_table( 'suppressions' ) . ' WHERE email = %s', strtolower( $email ) ) );
}

function ot_leads_suppress( $email, $reason ) {
	global $wpdb;
	$email = strtolower( trim( $email ) );
	if ( ! is_email( $email ) ) {
		return;
	}
	$wpdb->replace( ot_leads_table( 'suppressions' ), array( 'email' => $email, 'reason' => $reason, 'created_at' => ot_leads_now() ) );
	// Withdrawing consent also ends any nurture for that address.
	$ids = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . ot_leads_table( 'leads' ) . ' WHERE email = %s', $email ) );
	foreach ( $ids as $id ) {
		ot_lead_consent( (int) $id, $email, 'email_marketing', false, 'Withdrawn: ' . $reason );
		ot_nurture_exit_all( (int) $id, $reason );
		ot_lead_event( (int) $id, 'unsubscribed', array( 'reason' => $reason ), 'system' );
	}
	do_action( 'ot_leads_suppressed', $email, $reason, array_map( 'intval', $ids ) );
}

/* --------------------------------------------------------------------- agents */

/**
 * The joint Brandon & Kaitlyn profile is a page on the site, not an agent in the CRM (team
 * request, 2026-11-10): a lead with no single owner has assigned_agent '' and belongs to the team,
 * so its alerts go to both team leaders.
 */
const OT_LEADS_TEAM_PROFILE = 'brandon-kaitlyn-oberdorfer';
const OT_LEADS_TEAM_LABEL   = 'Team (Brandon & Kaitlyn)';

/** The team's individual agents from ot-core: slug => [name, given, email, phone, url]. */
function ot_leads_agents() {
	static $out = null;
	if ( null !== $out ) {
		return $out;
	}
	$out = array();
	foreach ( get_posts( array( 'post_type' => 'ot_agent', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ) ) as $p ) {
		if ( OT_LEADS_TEAM_PROFILE === $p->post_name ) {
			continue;
		}
		$r     = function_exists( 'ot_agent_rec' ) ? ot_agent_rec( $p ) : array();
		$email = get_post_meta( $p->ID, 'ot_email', true ) ?: ( $r['email'] ?? '' );
		$out[ $p->post_name ] = array(
			'name'  => $p->post_title,
			'given' => $r['given'] ?? strtok( $p->post_title, ' ' ),
			'email' => is_email( $email ) ? $email : '',
			'phone' => get_post_meta( $p->ID, 'ot_phone', true ) ?: ( $r['direct'] ?? '' ),
			'url'   => get_permalink( $p ),
			'team'  => false,
		);
	}
	return $out;
}

/** The agent a WordPress user is (Users > profile > "Agent profile"), or ''. */
function ot_leads_user_agent( $user_id = 0 ) {
	return (string) get_user_meta( $user_id ?: get_current_user_id(), 'ot_agent_slug', true );
}

/**
 * Emails that receive an agent's alerts: the agent's own address (the one on their profile)
 * plus any extra addresses set for them in Leads > Settings ("Also alert"), or the team leaders
 * for team leads.
 */
function ot_leads_agent_emails( $slug ) {
	$a = ot_leads_agents()[ $slug ] ?? null;
	if ( $a && ! $a['team'] && $a['email'] ) {
		return array_values( array_unique( array_merge( array( $a['email'] ), ot_leads_agent_cc( $slug ) ) ) );
	}
	return ot_leads_leader_emails();
}

/** The extra alert addresses for an agent. */
function ot_leads_agent_cc( $slug ) {
	$cc = (array) ( ot_leads_opt( 'alert_cc' ) ?: array() );
	return array_values( array_filter( array_map( 'trim', explode( ',', (string) ( $cc[ $slug ] ?? '' ) ) ), 'is_email' ) );
}

function ot_leads_leader_emails() {
	return array_values( array_filter( array_map( 'trim', explode( ',', (string) ot_leads_opt( 'leaders' ) ) ), 'is_email' ) );
}

/* --------------------------------------------------------------------- access */

/** Can the current user see this lead? Leaders and coordinators see all; agents see their own. */
function ot_leads_can_see( $lead ) {
	if ( current_user_can( 'ot_leads_view_all' ) ) {
		return true;
	}
	return current_user_can( 'ot_leads_own' ) && $lead && $lead['assigned_agent'] && $lead['assigned_agent'] === ot_leads_user_agent();
}

/* --------------------------------------------------------------------- intent */

/**
 * Warm, Cooling or Quiet, with the reasons (spec § 9: weight return visits, form submissions and
 * replies; never email opens, which Apple Mail Privacy Protection inflates).
 */
function ot_lead_intent( $lead_id ) {
	global $wpdb;
	$since = gmdate( 'Y-m-d H:i:s', time() - 14 * DAY_IN_SECONDS );
	$rows  = $wpdb->get_results( $wpdb->prepare( 'SELECT type, COUNT(*) n FROM ' . ot_leads_table( 'lead_events' ) . ' WHERE lead_id = %d AND created_at >= %s AND type IN (\'form\',\'page_view\',\'reply\',\'click\') GROUP BY type', $lead_id, $since ), OBJECT_K );
	$n     = fn( $t ) => (int) ( $rows[ $t ]->n ?? 0 );
	$score = $n( 'form' ) * 3 + $n( 'reply' ) * 3 + $n( 'page_view' ) + $n( 'click' );
	$why   = array();
	foreach ( array( 'form' => 'form sent', 'reply' => 'reply logged', 'page_view' => 'page visit', 'click' => 'email link clicked' ) as $t => $label ) {
		if ( $n( $t ) ) {
			$why[] = $n( $t ) . ' ' . $label . ( $n( $t ) > 1 ? 's' : '' );
		}
	}
	if ( $score >= 4 ) {
		return array( 'Warm', implode( ', ', $why ) . ' in 14 days' );
	}
	$last = $wpdb->get_var( $wpdb->prepare( 'SELECT MAX(created_at) FROM ' . ot_leads_table( 'lead_events' ) . ' WHERE lead_id = %d AND type IN (\'form\',\'page_view\',\'reply\',\'click\')', $lead_id ) );
	if ( $last && strtotime( $last . ' UTC' ) > time() - 45 * DAY_IN_SECONDS ) {
		return array( 'Cooling', $why ? implode( ', ', $why ) . ' in 14 days' : 'Last active ' . human_time_diff( strtotime( $last . ' UTC' ) ) . ' ago' );
	}
	return array( 'Quiet', 'No visits, forms or replies in 45 days' );
}

/** SQL for "assigned to $agent": '' = no filter, 'team' = the team's leads (no single owner). */
function ot_leads_agent_where( $agent, $col = 'assigned_agent' ) {
	global $wpdb;
	if ( '' === (string) $agent ) {
		return '';
	}
	return 'team' === $agent ? " AND $col = ''" : $wpdb->prepare( " AND $col = %s", $agent );
}

/** Once: leads, tasks and routing settings that named the joint profile now belong to the team. */
add_action( 'plugins_loaded', function () {
	if ( get_option( 'ot_leads_team_v2' ) ) {
		return;
	}
	global $wpdb;
	$wpdb->update( ot_leads_table( 'leads' ), array( 'assigned_agent' => '' ), array( 'assigned_agent' => OT_LEADS_TEAM_PROFILE ) );
	$wpdb->update( ot_leads_table( 'tasks' ), array( 'assignee' => '' ), array( 'assignee' => OT_LEADS_TEAM_PROFILE ) );
	$set = (array) get_option( 'ot_leads_settings', array() );
	foreach ( array( 'town_owners', 'type_owners' ) as $k ) {
		if ( ! empty( $set[ $k ] ) && is_array( $set[ $k ] ) ) {
			$set[ $k ] = array_map( fn( $v ) => OT_LEADS_TEAM_PROFILE === $v ? '' : $v, $set[ $k ] );
		}
	}
	if ( isset( $set['type_owners'] ) || isset( $set['town_owners'] ) ) {
		update_option( 'ot_leads_settings', $set );
	}
	update_option( 'ot_leads_team_v2', 1, false );
}, 20 );
