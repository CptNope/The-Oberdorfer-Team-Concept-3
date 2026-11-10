<?php
/**
 * Nurture (spec § 8): sequences, monthly town updates, new-listing alerts and the At Home
 * newsletter.
 *
 * - Only people with recorded email-marketing consent are enrolled or emailed; suppressed
 *   addresses never are. A sequence step becomes a draft in the approval queue; a sequence a
 *   team leader has switched to automatic sends without waiting.
 * - Sending happens 8 a.m. to 8 p.m. Massachusetts time, Monday to Saturday, at most one
 *   nurture email per person every few days (Settings > Email), from the assigned agent's name
 *   with their address as the reply-to, with the postal address and unsubscribe in the footer.
 * - Exits: the agent moves the lead to Appointment set or later, logs a reply, unsubscribes,
 *   or pauses the sequence.
 *
 * Merge tags in templates: {first_name} {agent_name} {agent_first} {agent_phone} {agent_email}
 * {towns} {town_links} {listing_link} {buy_url} {sell_url} {market_url}.
 */

defined( 'ABSPATH' ) || exit;

/** The starter sequences. Steps: day (after enrollment), subject, body; "repeat" days on the last step. */
function ot_nurture_default_sequences() {
	$sig = "<p>{agent_name}<br>The Oberdorfer Team · {agent_phone}</p>";
	return array(
		'buyer'     => array(
			'name'   => 'New buyer',
			'when'   => 'Buyer, showing, open-house or town-guide inquiry, or an agent message about buying',
			'mode'   => 'manual',
			'active' => 1,
			'steps'  => array(
				array( 'day' => 0, 'subject' => 'Thank you, {first_name}', 'body' => "<p>Hi {first_name},</p><p>Thank you for reaching out about buying a home. I will be in touch shortly to hear what you are looking for. In the meantime, our town guides cover housing, commutes and recent prices, each with its source: {town_links}</p>" . $sig ),
				array( 'day' => 2, 'subject' => 'The first steps to buying in Massachusetts', 'body' => "<p>Hi {first_name},</p><p>Most buyers start with a lender's preapproval, then a short list of towns, then a few showings to calibrate. Our buying guide walks through each step, including the costs beyond the price: <a href=\"{buy_url}\">{buy_url}</a></p><p>When you are ready, reply with a few times that work for a 20-minute call.</p>" . $sig ),
				array( 'day' => 7, 'subject' => 'Comparing towns', 'body' => "<p>Hi {first_name},</p><p>If you are weighing more than one town, the market page compares median prices across the towns we cover, with the source and period on every figure: <a href=\"{market_url}\">{market_url}</a></p><p>Tell me which matter most to you, commute, housing type or price, and I will send a short comparison.</p>" . $sig ),
				array( 'day' => 14, 'subject' => 'Still looking?', 'body' => "<p>Hi {first_name},</p><p>Just checking in. If your plans have changed, that is fine; if you are still looking, reply and tell me what would help most right now.</p>" . $sig ),
				array( 'day' => 44, 'subject' => 'Your {towns} update', 'mode' => 'town_update', 'repeat' => 30 ),
			),
		),
		'seller'    => array(
			'name'   => 'New seller',
			'when'   => 'Market review request, or an agent message about selling',
			'mode'   => 'manual',
			'active' => 1,
			'steps'  => array(
				array( 'day' => 0, 'subject' => 'Your market review', 'body' => "<p>Hi {first_name},</p><p>Thank you for asking for a market review. I will look at comparable closed sales near you and be in touch to arrange a visit. If there is anything about the home I should know first, updates, timing, a date you are working toward, reply here.</p>" . $sig ),
				array( 'day' => 3, 'subject' => 'How we price and launch a home', 'body' => "<p>Hi {first_name},</p><p>Here is how we approach a sale: pricing from closed comparable sales, preparation and photography, then a launch planned around your calendar. The details are here: <a href=\"{sell_url}\">{sell_url}</a></p>" . $sig ),
				array( 'day' => 10, 'subject' => 'Any questions about selling?', 'body' => "<p>Hi {first_name},</p><p>If you have questions about timing, costs or what to fix before listing, reply and I will answer them directly.</p>" . $sig ),
				array( 'day' => 40, 'subject' => 'Your {towns} update', 'mode' => 'town_update', 'repeat' => 30 ),
			),
		),
		'relocation' => array(
			'name'   => 'Relocation',
			'when'   => 'Town-guide inquiry about comparing towns',
			'mode'   => 'manual',
			'active' => 1,
			'steps'  => array(
				array( 'day' => 0, 'subject' => 'Moving to Massachusetts', 'body' => "<p>Hi {first_name},</p><p>Thank you for getting in touch. Comparing towns from a distance is hard; our guides set out housing, commutes and prices for each, with sources: {town_links}</p><p>Reply with your work location and the kind of home you want, and I will suggest a short list.</p>" . $sig ),
				array( 'day' => 3, 'subject' => 'A short list, when you are ready', 'body' => "<p>Hi {first_name},</p><p>When you have a moment, a 20-minute call is the quickest way to narrow the towns down. Reply with a few times that suit you.</p>" . $sig ),
				array( 'day' => 10, 'subject' => 'Your {towns} update', 'mode' => 'town_update', 'repeat' => 30 ),
			),
		),
		'nurture'   => array(
			'name'   => 'Long-term nurture',
			'when'   => 'New-listing alert or town-update sign-up, or a lead moved to Nurture',
			'mode'   => 'manual',
			'active' => 1,
			'steps'  => array(
				array( 'day' => 0, 'subject' => 'Your {towns} update', 'mode' => 'town_update', 'repeat' => 30 ),
			),
		),
		'past'      => array(
			'name'   => 'Past client',
			'when'   => 'A lead moved to Closed',
			'mode'   => 'manual',
			'active' => 1,
			'steps'  => array(
				array( 'day' => 90, 'subject' => 'How is the new home?', 'body' => "<p>Hi {first_name},</p><p>It has been a few months since your move. I hope the home is treating you well. If anything comes up, a contractor, a question about your assessment, a friend who is thinking of moving, I am always glad to help.</p>" . $sig, 'repeat' => 91 ),
			),
		),
		'anniversary' => array(
			'name'   => 'Home anniversary',
			'when'   => 'A lead moved to Closed: once a year, on the anniversary of that day',
			'mode'   => 'manual',
			'active' => 1,
			'steps'  => array(
				array( 'day' => 365, 'subject' => 'Happy home anniversary, {first_name}', 'body' => "<p>Hi {first_name},</p><p>It has been another year since you got the keys. Happy home anniversary.</p><p>If you would ever like to know what homes like yours have recently sold for nearby, reply and I will put together a short review. No obligation, and no need to be thinking of selling.</p>" . $sig, 'repeat' => 365 ),
			),
		),
	);
}

function ot_nurture_sequences() {
	$saved = get_option( 'ot_leads_sequences' );
	if ( ! is_array( $saved ) || ! $saved ) {
		return ot_nurture_default_sequences();
	}
	// Sequences added in an update (Home anniversary, v0.9) join the team's saved ones.
	return $saved + array_diff_key( ot_nurture_default_sequences(), $saved );
}

/* --------------------------------------------------------------------- enrollment */

function ot_nurture_enrolled( $lead_id, $seq ) {
	global $wpdb;
	return (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . ot_leads_table( 'enrollments' ) . " WHERE lead_id = %d AND sequence = %s AND state IN ('active','paused')", $lead_id, $seq ) );
}

function ot_nurture_enroll( $lead_id, $seq_key, $actor = 'system' ) {
	global $wpdb;
	$seqs = ot_nurture_sequences();
	$lead = ot_lead( $lead_id );
	// Never both: an agent whose own CRM sends follow-up gets no sequences from the site.
	if ( ! $lead || empty( $seqs[ $seq_key ]['active'] ) || ot_nurture_enrolled( $lead_id, $seq_key ) || ! ot_lead_has_consent( $lead['email'] ) || 'forward' === ot_leads_opt( 'crm_mode' ) || ot_leads_agent_crm_nurtures( (string) $lead['assigned_agent'] ) ) {
		return false;
	}
	$first = (int) ( $seqs[ $seq_key ]['steps'][0]['day'] ?? 0 );
	$wpdb->insert( ot_leads_table( 'enrollments' ), array(
		'lead_id'    => $lead_id,
		'sequence'   => $seq_key,
		'step'       => 0,
		'state'      => 'active',
		'next_at'    => gmdate( 'Y-m-d H:i:s', time() + $first * DAY_IN_SECONDS ),
		'created_at' => ot_leads_now(),
	) );
	ot_lead_event( $lead_id, 'enrolled', array( 'sequence' => $seqs[ $seq_key ]['name'] ), $actor );
	return true;
}

/**
 * After a form: which sequence it starts. A sign-up (new-listing alerts, town update) is
 * long-term nurture; a seller or market-review inquiry is "New seller"; "Comparing towns" on a
 * town guide is "Relocation"; any other buying conversation is "New buyer". One sales sequence
 * at a time; the newsletter has no sequence.
 */
function ot_nurture_sequence_for( $type, $topic, $name ) {
	if ( 'newsletter' === $type ) {
		return '';
	}
	if ( 'listings' === $type || ( 'community' === $type && '' === $name ) ) {
		return 'nurture';
	}
	if ( 'seller' === $type || ( 'agent' === $type && in_array( $topic, array( 'Selling', 'A market review' ), true ) ) ) {
		return 'seller';
	}
	if ( 'community' === $type && 'Comparing towns' === $topic ) {
		return 'relocation';
	}
	if ( in_array( $type, array( 'buyer', 'showing', 'openhouse', 'community' ), true ) || ( 'agent' === $type && in_array( $topic, array( 'Buying', 'Both', '' ), true ) ) ) {
		return 'buyer';
	}
	return '';
}

function ot_nurture_enroll_for( $lead, $form ) {
	if ( ! ot_lead_has_consent( $lead['email'] ) ) {
		return;
	}
	$key = ot_nurture_sequence_for( $form['type'], (string) $form['topic'], (string) $form['name'] );
	if ( ! $key ) {
		return;
	}
	if ( 'nurture' !== $key ) {
		foreach ( array( 'buyer', 'seller', 'relocation' ) as $s ) {
			if ( ot_nurture_enrolled( (int) $lead['id'], $s ) ) {
				return;
			}
		}
		ot_nurture_exit_all( (int) $lead['id'], 'Started a conversation', array( 'nurture' ) );
	}
	ot_nurture_enroll( (int) $lead['id'], $key );
}

/** Stage changes: appointment or later ends the sales sequences; Nurture and Closed start theirs. */
add_action( 'ot_lead_stage_changed', function ( $id, $to ) {
	if ( in_array( $to, array( 'appointment', 'active', 'under_agreement', 'closed', 'lost' ), true ) ) {
		ot_nurture_exit_all( $id, 'Stage: ' . ( OT_LEAD_STAGES[ $to ] ?? $to ), array( 'buyer', 'seller', 'relocation', 'nurture' ) );
	}
	if ( 'nurture' === $to ) {
		ot_nurture_exit_all( $id, 'Moved to long-term', array( 'buyer', 'seller', 'relocation' ) );
		ot_nurture_enroll( $id, 'nurture' );
	}
	if ( 'closed' === $to ) {
		ot_nurture_enroll( $id, 'past' );
		ot_nurture_enroll( $id, 'anniversary' );
	}
}, 10, 2 );

function ot_nurture_exit_all( $lead_id, $reason, $only = null ) {
	global $wpdb;
	$t = ot_leads_table( 'enrollments' );
	foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t WHERE lead_id = %d AND state IN ('active','paused')", $lead_id ), ARRAY_A ) as $e ) {
		if ( null === $only || in_array( $e['sequence'], $only, true ) ) {
			$wpdb->update( $t, array( 'state' => 'exited', 'exit_reason' => substr( $reason, 0, 100 ) ), array( 'id' => $e['id'] ) );
		}
	}
	// Drafts not yet sent for this lead are withdrawn too.
	$wpdb->query( $wpdb->prepare( 'UPDATE ' . ot_leads_table( 'messages' ) . " SET status = 'skipped', error = %s WHERE lead_id = %d AND kind = 'nurture' AND status IN ('draft','approved')", substr( 'Exited: ' . $reason, 0, 250 ), $lead_id ) );
}

/* --------------------------------------------------------------------- content */

function ot_nurture_merge( $text, $lead ) {
	$a      = ot_leads_agents()[ $lead['assigned_agent'] ] ?? array();
	$team   = ! $a || ! empty( $a['team'] );
	$towns  = array_filter( explode( ',', (string) $lead['towns'] ) );
	$names  = array();
	$links  = array();
	foreach ( $towns as $slug ) {
		$p = get_page_by_path( $slug, OBJECT, 'ot_community' );
		if ( $p && 'publish' === $p->post_status && ! get_post_meta( $p->ID, 'ot_hidden', true ) ) {
			$names[] = $p->post_title;
			$links[] = '<a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( $p->post_title ) . '</a>';
		}
	}
	$page = fn( $s ) => function_exists( 'ot_page_url' ) ? ot_page_url( $s ) : home_url( '/' . $s . '/' );
	$vars = array(
		'{first_name}'   => esc_html( $lead['first_name'] ?: 'there' ),
		'{agent_name}'   => esc_html( $team ? 'Brandon & Kaitlyn Oberdorfer' : $a['name'] ),
		'{agent_first}'  => esc_html( $team ? 'Brandon & Kaitlyn' : $a['given'] ),
		'{agent_phone}'  => esc_html( ! $team && $a['phone'] ? $a['phone'] : OT_PHONE ),
		'{agent_email}'  => esc_html( ! $team ? $a['email'] : '' ),
		'{towns}'        => esc_html( $names ? implode( ' and ', array_slice( $names, 0, 2 ) ) : 'Massachusetts' ),
		'{town_links}'   => $links ? implode( ', ', $links ) : '<a href="' . esc_url( get_post_type_archive_link( 'ot_community' ) ) . '">all town guides</a>',
		'{listing_link}' => $lead['listing_id'] ? '<a href="' . esc_url( get_permalink( $lead['listing_id'] ) ) . '">' . esc_html( get_the_title( $lead['listing_id'] ) ) . '</a>' : '',
		'{buy_url}'      => esc_url( $page( 'buy' ) ),
		'{sell_url}'     => esc_url( $page( 'sell' ) ),
		'{market_url}'   => esc_url( $page( 'market' ) ),
	);
	return strtr( $text, $vars );
}

/**
 * The monthly town update: each followed town's latest market figures from its guide (with the
 * source and period, never presented as live), the team's listings there, and the guide link.
 * Built from records only, so it contains no fact the site does not already publish.
 */
function ot_nurture_town_update( $lead ) {
	$parts = array();
	foreach ( array_slice( array_filter( explode( ',', (string) $lead['towns'] ) ), 0, 3 ) as $slug ) {
		$p = get_page_by_path( $slug, OBJECT, 'ot_community' );
		if ( ! $p || 'publish' !== $p->post_status || get_post_meta( $p->ID, 'ot_hidden', true ) ) {
			continue;
		}
		$r    = json_decode( (string) get_post_meta( $p->ID, 'ot_record', true ), true ) ?: array();
		$m    = $r['market'] ?? array();
		$html = '<h2 style="font:400 22px/1.3 Georgia,serif;margin:24px 0 8px">' . esc_html( $p->post_title ) . '</h2>';
		if ( ! empty( $m['sf_median_2025'] ) ) {
			$chg   = ! empty( $m['sf_median_2024'] ) ? round( ( $m['sf_median_2025'] - $m['sf_median_2024'] ) / $m['sf_median_2024'] * 100, 1 ) : null;
			$html .= '<p>Single-family median sale price: <strong>$' . number_format( (int) $m['sf_median_2025'] ) . '</strong>' . ( null !== $chg ? ' (' . ( $chg >= 0 ? 'up ' : 'down ' ) . abs( $chg ) . '% from $' . number_format( (int) $m['sf_median_2024'] ) . ' in 2024)' : '' ) . ( ! empty( $m['sf_sales_2025'] ) ? ', across ' . number_format( (int) $m['sf_sales_2025'] ) . ' sales' : '' ) . '.<br><span style="font-size:13px;color:#5B6670">' . esc_html( ( $m['period'] ?? '' ) . ', ' . $p->post_title . ' only. Source: ' . ( $m['source'] ?? '' ) ) . '.</span></p>';
		}
		if ( function_exists( 'ot_listings' ) ) {
			$ls = ot_listings( array( 'town' => $slug, 'limit' => 3 ) );
			if ( $ls ) {
				$html .= '<p style="margin-bottom:4px">Our listings in ' . esc_html( $p->post_title ) . ':</p><ul style="margin-top:0">';
				foreach ( $ls as $l ) {
					$html .= '<li><a href="' . esc_url( $l['url'] ) . '">' . esc_html( $l['address'] ) . '</a>' . ( $l['price'] ? ', $' . number_format( (int) $l['price'] ) : '' ) . '</li>';
				}
				$html .= '</ul>';
			}
		}
		$html   .= '<p><a href="' . esc_url( get_permalink( $p ) ) . '">The ' . esc_html( $p->post_title ) . ' guide</a></p>';
		$parts[] = $html;
	}
	if ( ! $parts ) {
		return '';
	}
	return ot_nurture_merge( '<p>Hi {first_name},</p><p>Here is your monthly update.</p>', $lead ) . implode( '', $parts ) . ot_nurture_merge( '<p>Questions about any of these? Reply here.</p><p>{agent_name}<br>The Oberdorfer Team · {agent_phone}</p>', $lead );
}

/** Create the message for an enrollment's current step: a template, a town update or an AI draft. */
function ot_nurture_draft( $e ) {
	global $wpdb;
	$seq  = ot_nurture_sequences()[ $e['sequence'] ] ?? null;
	$step = $seq['steps'][ (int) $e['step'] ] ?? null;
	$lead = ot_lead( (int) $e['lead_id'] );
	if ( ! $seq || ! $step || ! $lead ) {
		return 0;
	}
	$mode   = $step['mode'] ?? 'template';
	$checks = array();
	if ( 'town_update' === $mode ) {
		$body = ot_nurture_town_update( $lead );
		if ( '' === $body ) {
			return 0; // no followed town with data: skip this month
		}
		$subject = ot_nurture_merge( $step['subject'] ?? 'Your monthly update', $lead );
		$source  = 'town_update';
	} elseif ( 'ai' === $mode && function_exists( 'ot_ai_draft' ) ) {
		$d = ot_ai_draft( $lead, (string) ( $step['brief'] ?? '' ) );
		if ( ! $d ) {
			return 0;
		}
		list( $subject, $body, $checks ) = $d;
		$source = 'ai';
	} else {
		$subject = ot_nurture_merge( (string) $step['subject'], $lead );
		$body    = ot_nurture_merge( (string) $step['body'], $lead );
		$source  = 'template';
	}
	$auto = 'auto' === ( $seq['mode'] ?? 'manual' ) && ( 'ai' !== $source || ! empty( $checks['pass'] ) );
	$wpdb->insert( ot_leads_table( 'messages' ), array(
		'lead_id'       => $lead['id'],
		'enrollment_id' => $e['id'],
		'kind'          => 'nurture',
		'step'          => (int) $e['step'],
		'to_email'      => $lead['email'],
		'subject'       => wp_strip_all_tags( $subject ),
		'body'          => wp_kses_post( $body ),
		'source'        => $source,
		'status'        => $auto ? 'approved' : 'draft',
		'checks'        => $checks ? wp_json_encode( $checks ) : null,
		'approved_by'   => $auto ? 'automatic (' . $seq['name'] . ')' : '',
		'created_at'    => ot_leads_now(),
	) );
	return (int) $wpdb->insert_id;
}

/** Due enrollments draft their step and move to the next one. */
function ot_nurture_advance() {
	global $wpdb;
	$t = ot_leads_table( 'enrollments' );
	foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t WHERE state = 'active' AND next_at <= %s ORDER BY next_at ASC LIMIT 25", ot_leads_now() ), ARRAY_A ) as $e ) {
		$lead = ot_lead( (int) $e['lead_id'] );
		if ( ! $lead || ! ot_lead_has_consent( $lead['email'] ) ) {
			$wpdb->update( $t, array( 'state' => 'exited', 'exit_reason' => 'No consent' ), array( 'id' => $e['id'] ) );
			continue;
		}
		ot_nurture_draft( $e );
		$steps = ot_nurture_sequences()[ $e['sequence'] ]['steps'] ?? array();
		$cur   = $steps[ (int) $e['step'] ] ?? array();
		if ( isset( $steps[ (int) $e['step'] + 1 ] ) ) {
			$start = strtotime( $e['created_at'] . ' UTC' );
			$wpdb->update( $t, array( 'step' => (int) $e['step'] + 1, 'next_at' => gmdate( 'Y-m-d H:i:s', max( time() + HOUR_IN_SECONDS, $start + (int) $steps[ (int) $e['step'] + 1 ]['day'] * DAY_IN_SECONDS ) ) ), array( 'id' => $e['id'] ) );
		} elseif ( ! empty( $cur['repeat'] ) ) {
			$wpdb->update( $t, array( 'next_at' => gmdate( 'Y-m-d H:i:s', time() + (int) $cur['repeat'] * DAY_IN_SECONDS ) ), array( 'id' => $e['id'] ) );
		} else {
			$wpdb->update( $t, array( 'state' => 'finished', 'next_at' => null ), array( 'id' => $e['id'] ) );
		}
	}
}

/* --------------------------------------------------------------------- sending */

/** Send approved messages, within business hours, consent and the frequency cap. */
function ot_nurture_send_due( $max = 20 ) {
	global $wpdb;
	if ( ! ot_leads_business_hours() ) {
		return;
	}
	$m    = ot_leads_table( 'messages' );
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $m WHERE status = 'approved' ORDER BY id ASC LIMIT %d", $max ), ARRAY_A );
	foreach ( $rows as $msg ) {
		$lead    = $msg['lead_id'] ? ot_lead( (int) $msg['lead_id'] ) : null;
		$channel = 'newsletter' === $msg['kind'] ? 'newsletter' : 'email_marketing';
		if ( ! ot_lead_has_consent( $msg['to_email'], $channel ) && ! ( 'newsletter' === $channel && ot_lead_has_consent( $msg['to_email'] ) ) ) {
			$wpdb->update( $m, array( 'status' => 'skipped', 'error' => 'No consent or unsubscribed' ), array( 'id' => $msg['id'] ) );
			continue;
		}
		if ( 'nurture' === $msg['kind'] ) {
			$last = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(sent_at) FROM $m WHERE to_email = %s AND kind = 'nurture' AND status = 'sent'", $msg['to_email'] ) );
			if ( $last && strtotime( $last . ' UTC' ) > time() - max( 1, (int) ot_leads_opt( 'freq_days' ) ) * DAY_IN_SECONDS ) {
				continue; // waits for the frequency cap
			}
		}
		$a    = $lead ? ( ot_leads_agents()[ $lead['assigned_agent'] ] ?? array() ) : array();
		$from = $a && empty( $a['team'] ) ? $a['name'] . ', The Oberdorfer Team' : 'Brandon & Kaitlyn Oberdorfer';
		$rto  = $a && empty( $a['team'] ) && $a['email'] ? $a['email'] : ( ot_leads_leader_emails()[0] ?? '' );
		list( $ok, $info ) = ot_leads_mail( $msg['to_email'], $msg['subject'], $msg['body'], array( 'from_name' => $from, 'reply_to' => $rto, 'reply_name' => $from, 'marketing' => true, 'tags' => array( $msg['kind'] ), 'lead_id' => (int) $msg['lead_id'] ) );
		$wpdb->update( $m, $ok ? array( 'status' => 'sent', 'sent_at' => ot_leads_now(), 'provider_id' => substr( $info, 0, 190 ) ) : array( 'status' => 'failed', 'error' => substr( $info, 0, 250 ) ), array( 'id' => $msg['id'] ) );
		if ( $lead ) {
			ot_lead_event( (int) $lead['id'], 'email_sent', array( 'subject' => $msg['subject'], 'kind' => $msg['kind'], 'ok' => $ok, 'error' => $ok ? '' : $info ), 'system' );
		}
	}
}

/* --------------------------------------------------------------------- new-listing alerts */

/**
 * When a team listing is published as coming soon or for sale, people who asked to hear about
 * that agent's listings, or follow the town, get a short alert (a draft in the approval queue
 * unless "Send new-listing alerts automatically" is ticked in Settings > Email).
 */
add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( 'ot_listing' !== $post->post_type || 'publish' !== $new || 'publish' === $old ) {
		return;
	}
	wp_schedule_single_event( time() + 120, 'ot_leads_listing_alert', array( $post->ID ) ); // after its fields are saved
}, 10, 3 );

add_action( 'ot_leads_listing_alert', function ( $id ) {
	global $wpdb;
	if ( get_post_meta( $id, '_ot_alerted', true ) || ! function_exists( 'ot_listing' ) ) {
		return;
	}
	$l = ot_listing( $id );
	if ( ! $l || ! in_array( $l['status'], array( 'coming-soon', 'active' ), true ) ) {
		return;
	}
	update_post_meta( $id, '_ot_alerted', 1 );
	$agents = array_filter( array( $l['agent'], $l['coagent'] ?? '' ) );
	$rules  = array( "context = 'listings:brandon-kaitlyn-oberdorfer'", "context = 'listings:brandon-kaitlyn'" );
	foreach ( $agents as $a ) {
		$rules[] = $wpdb->prepare( 'context = %s', 'listings:' . $a );
	}
	$town = (string) get_post_meta( $id, 'ot_town_slug', true );
	$top  = $town ? strtok( $town, '/' ) : '';
	if ( $town ) {
		$rules[] = $wpdb->prepare( 'towns LIKE %s', '%' . $wpdb->esc_like( $top ) . '%' ); // narrowed to exact towns below
	}
	$leads = $wpdb->get_results( 'SELECT * FROM ' . ot_leads_table( 'leads' ) . ' WHERE ' . implode( ' OR ', $rules ), ARRAY_A );
	$leads = array_filter( $leads, function ( $lead ) use ( $agents, $town, $top ) {
		list( $kind, $who ) = array_pad( explode( ':', (string) $lead['context'], 2 ), 2, '' );
		if ( 'listings' === $kind && ( in_array( $who, $agents, true ) || in_array( $who, array( 'brandon-kaitlyn-oberdorfer', 'brandon-kaitlyn' ), true ) ) ) {
			return true;
		}
		$follows = array_filter( explode( ',', (string) $lead['towns'] ) );
		return $town && ( in_array( $town, $follows, true ) || in_array( $top, $follows, true ) );
	} );
	$auto  = (bool) get_option( 'ot_leads_alerts_auto' );
	foreach ( $leads as $lead ) {
		if ( ! ot_lead_has_consent( $lead['email'] ) || in_array( $lead['stage'], array( 'closed', 'lost' ), true ) ) {
			continue;
		}
		$facts = array_filter( array( $l['beds'] ? $l['beds'] . ' bedrooms' : '', $l['baths'] ? $l['baths'] . ' baths' : '', $l['sqft'] ? number_format( (int) $l['sqft'] ) . ' sq ft' : '' ) );
		$body  = ot_nurture_merge( '<p>Hi {first_name},</p>', $lead ) . '<p>' . ( 'coming-soon' === $l['status'] ? 'Coming soon' : 'Just listed' ) . ': <a href="' . esc_url( $l['url'] ) . '"><strong>' . esc_html( $l['address'] . ( $l['town'] ? ', ' . $l['town'] : '' ) ) . '</strong></a>' . ( $l['price'] ? ', $' . number_format( (int) $l['price'] ) : '' ) . '.' . ( $facts ? ' ' . esc_html( implode( ' · ', $facts ) ) . '.' : '' ) . '</p>'
			. ( ot_open_house_text_safe( $l ) ? '<p>Open house: ' . esc_html( ot_open_house_text_safe( $l ) ) . '.</p>' : '' )
			. '<p><a href="' . esc_url( $l['url'] ) . '#request-showing">Request a showing</a></p>' . ot_nurture_merge( '<p>{agent_name}<br>The Oberdorfer Team · {agent_phone}</p>', $lead );
		$wpdb->insert( ot_leads_table( 'messages' ), array(
			'lead_id'     => $lead['id'],
			'kind'        => 'alert',
			'to_email'    => $lead['email'],
			'subject'     => ( 'coming-soon' === $l['status'] ? 'Coming soon: ' : 'Just listed: ' ) . $l['address'] . ( $l['town'] ? ', ' . $l['town'] : '' ),
			'body'        => wp_kses_post( $body ),
			'source'      => 'listing',
			'status'      => $auto ? 'approved' : 'draft',
			'approved_by' => $auto ? 'automatic (listing alerts)' : '',
			'created_at'  => ot_leads_now(),
		) );
	}
} );

function ot_open_house_text_safe( $l ) {
	return function_exists( 'ot_open_house_text' ) ? ot_open_house_text( $l ) : '';
}

/* --------------------------------------------------------------------- newsletter */

/** Everyone who may receive the At Home newsletter: newsletter or email-marketing consent, not suppressed. */
function ot_newsletter_recipients() {
	global $wpdb;
	$c      = ot_leads_table( 'consents' );
	$emails = $wpdb->get_col( "SELECT DISTINCT email FROM $c WHERE channel IN ('newsletter','email_marketing')" );
	return array_values( array_filter( $emails, fn( $e ) => ot_lead_has_consent( $e, 'newsletter' ) || ot_lead_has_consent( $e ) ) );
}

/** Queue an issue for every recipient (sent by the scheduler in business hours). Returns the count. */
function ot_newsletter_queue( $subject, $html ) {
	global $wpdb;
	$n = 0;
	foreach ( ot_newsletter_recipients() as $email ) {
		$lead_id = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . ot_leads_table( 'leads' ) . ' WHERE email = %s ORDER BY id ASC LIMIT 1', $email ) );
		$wpdb->insert( ot_leads_table( 'messages' ), array(
			'lead_id'     => $lead_id,
			'kind'        => 'newsletter',
			'to_email'    => $email,
			'subject'     => wp_strip_all_tags( $subject ),
			'body'        => wp_kses_post( $html ),
			'source'      => 'newsletter',
			'status'      => 'approved',
			'approved_by' => wp_get_current_user()->user_login,
			'created_at'  => ot_leads_now(),
		) );
		$n++;
	}
	return $n;
}
