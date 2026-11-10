<?php
/**
 * Safeguards: a health check, duplicate leads and merging.
 *
 * The health check looks for what would quietly lose a lead: the scheduler not running (alerts
 * would not escalate, emails would not send), email failing or not yet on Brevo, CRM deliveries
 * that gave up, new leads escalated with nobody on them, drafts waiting for approval for days,
 * and likely duplicates. Problems show on Leads > Today, the dashboard widget, Leads > Settings
 * and Tools > Site Health, for team leaders.
 *
 * Duplicates: two leads with the same phone number, or the same name with different emails.
 * Merging moves everything (timeline, tasks, sequences, emails, consents, deliveries) onto the
 * lead kept, fills its empty fields, and deletes the other.
 */

defined( 'ABSPATH' ) || exit;

/** [ [level: error|warning|info, message, link], ... ] */
function ot_leads_health() {
	global $wpdb;
	$out  = array();
	$ev   = ot_leads_table( 'lead_events' );
	$week = gmdate( 'Y-m-d H:i:s', time() - 7 * DAY_IN_SECONDS );
	$set  = admin_url( 'admin.php?page=ot-leads-settings' );

	$tick = (int) get_option( 'ot_leads_last_tick' );
	if ( ! $tick || $tick < time() - 10 * MINUTE_IN_SECONDS ) {
		$out[] = array( 'error', $tick ? 'The scheduler last ran ' . human_time_diff( $tick ) . ' ago, so alerts may not escalate and emails may not send. Add the server cron job.' : 'The scheduler has not run yet. Add the server cron job.', $set . '#cron' );
	}
	if ( ! ot_leads_opt( 'brevo_key' ) ) {
		$out[] = array( 'warning', 'Email goes out through WordPress mail, which often lands in spam or never arrives. Connect Brevo. Every lead is still saved in the inbox.', $set );
	}
	$failed = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $ev WHERE type IN ('alert_sent','email_sent') AND created_at >= %s AND payload LIKE %s", $week, '%"ok":false%' ) )
		+ (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . ot_leads_table( 'messages' ) . " WHERE status = 'failed' AND created_at >= %s", $week ) );
	if ( $failed ) {
		$out[] = array( 'error', $failed . ' email' . ( 1 === $failed ? '' : 's' ) . ' failed to send in the last 7 days.', $set );
	}
	$crm = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . ot_leads_table( 'outbox' ) . " WHERE state = 'failed'" );
	if ( $crm ) {
		$out[] = array( 'error', $crm . ' CRM deliver' . ( 1 === $crm ? 'y' : 'ies' ) . ' gave up after retrying. The leads are safe here; check the CRM settings.', $set );
	}
	$esc = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . ot_leads_table( 'leads' ) . " WHERE escalated = 1 AND contacted_at IS NULL AND stage = 'new' AND created_at >= %s", $week ) );
	if ( $esc ) {
		$out[] = array( 'warning', $esc . ' new lead' . ( 1 === $esc ? ' was' : 's were' ) . ' escalated and still ' . ( 1 === $esc ? 'has' : 'have' ) . ' no first reply.', admin_url( 'admin.php?page=ot-leads-today' ) );
	}
	$old = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . ot_leads_table( 'messages' ) . " WHERE status = 'draft' AND created_at < %s", gmdate( 'Y-m-d H:i:s', time() - 3 * DAY_IN_SECONDS ) ) );
	if ( $old ) {
		$out[] = array( 'warning', $old . ' follow-up email' . ( 1 === $old ? ' has' : 's have' ) . ' waited more than 3 days for approval.', admin_url( 'admin.php?page=ot-leads-queue' ) );
	}
	$dup = count( ot_leads_duplicates() );
	if ( $dup ) {
		$out[] = array( 'info', $dup . ' possible duplicate lead' . ( 1 === $dup ? '' : 's' ) . ' to review.', admin_url( 'admin.php?page=ot-leads-reports#duplicates' ) );
	}
	return $out;
}

/** For Settings > Status. */
function ot_leads_health_html() {
	$h = ot_leads_health();
	if ( ! $h ) {
		return '<span class="ot-pill ot-pill--ok">All good</span>';
	}
	$o = '<ul class="ot-health" style="margin:0">';
	foreach ( $h as list( $lvl, $msg, $link ) ) {
		$o .= '<li><span class="ot-pill' . ( 'error' === $lvl ? ' ot-pill--bad' : '' ) . '">' . esc_html( ucfirst( $lvl ) ) . '</span> ' . esc_html( $msg ) . ' <a href="' . esc_url( $link ) . '">Fix</a></li>';
	}
	return $o . '</ul>';
}

/** Problems as admin notices ($compact: errors only, for the dashboard). */
function ot_leads_health_notices( $compact = false ) {
	$o = '';
	foreach ( ot_leads_health() as list( $lvl, $msg, $link ) ) {
		if ( $compact && 'error' !== $lvl ) {
			continue;
		}
		$o .= '<div class="notice notice-' . ( 'error' === $lvl ? 'error' : ( 'warning' === $lvl ? 'warning' : 'info' ) ) . ' inline"><p>' . esc_html( $msg ) . ' <a href="' . esc_url( $link ) . '">Fix it</a></p></div>';
	}
	return $o;
}

/* Tools > Site Health. */
add_filter( 'site_status_tests', function ( $tests ) {
	$tests['direct']['ot_leads'] = array(
		'label' => 'Oberdorfer Leads',
		'test'  => function () {
			$h    = ot_leads_health();
			$bad  = array_filter( $h, fn( $x ) => 'error' === $x[0] );
			$warn = array_filter( $h, fn( $x ) => 'warning' === $x[0] );
			$list = '<ul>' . implode( '', array_map( fn( $x ) => '<li>' . esc_html( $x[1] ) . '</li>', $h ) ) . '</ul>';
			return array(
				'label'       => $bad ? 'Oberdorfer Leads needs attention' : ( $warn ? 'Oberdorfer Leads has warnings' : 'Oberdorfer Leads is working' ),
				'status'      => $bad ? 'critical' : ( $warn ? 'recommended' : 'good' ),
				'badge'       => array( 'label' => 'Leads', 'color' => $bad ? 'red' : 'blue' ),
				'description' => $h ? $list : '<p>Forms, alerts, the scheduler and email are working.</p>',
				'actions'     => '<a href="' . esc_url( admin_url( 'admin.php?page=ot-leads-settings' ) ) . '">Leads settings</a>',
				'test'        => 'ot_leads',
			);
		},
	);
	return $tests;
} );

/* --------------------------------------------------------------------- duplicates */

/** The last 10 digits of a phone number, or ''. */
function ot_leads_phone_key( $phone ) {
	$d = preg_replace( '/\D/', '', (string) $phone );
	return strlen( $d ) >= 10 ? substr( $d, -10 ) : '';
}

/**
 * Pairs of leads that look like the same person: [ [keep, other, reason], ... ], keeping the
 * older lead. Emails are already unique per lead, so these are the same phone number, or the
 * same full name, under two addresses. Pairs marked "not a duplicate" are skipped.
 */
function ot_leads_duplicates( $lead_id = 0 ) {
	global $wpdb;
	static $cache = null;
	if ( null === $cache ) {
		$rows    = $wpdb->get_results( 'SELECT id, first_name, last_name, email, phone, created_at FROM ' . ot_leads_table( 'leads' ) . ' ORDER BY id ASC', ARRAY_A );
		$ignored = (array) get_option( 'ot_leads_not_dupes', array() );
		$seen    = array();
		$cache   = array();
		foreach ( $rows as $r ) {
			$keys = array();
			if ( $pk = ot_leads_phone_key( $r['phone'] ) ) {
				$keys[] = array( 'p:' . $pk, 'same phone number' );
			}
			$name = strtolower( trim( $r['first_name'] . ' ' . $r['last_name'] ) );
			if ( $r['last_name'] && strlen( $name ) > 4 ) {
				$keys[] = array( 'n:' . $name, 'same name' );
			}
			foreach ( $keys as list( $k, $why ) ) {
				if ( isset( $seen[ $k ] ) && $seen[ $k ] !== (int) $r['id'] && ! in_array( $seen[ $k ] . '-' . $r['id'], $ignored, true ) && ! isset( $cache[ $seen[ $k ] . '-' . $r['id'] ] ) ) {
					$cache[ $seen[ $k ] . '-' . $r['id'] ] = array( $seen[ $k ], (int) $r['id'], $why );
				} elseif ( ! isset( $seen[ $k ] ) ) {
					$seen[ $k ] = (int) $r['id'];
				}
			}
		}
		$cache = array_values( $cache );
	}
	if ( ! $lead_id ) {
		return $cache;
	}
	return array_values( array_filter( $cache, fn( $p ) => $p[0] === (int) $lead_id || $p[1] === (int) $lead_id ) );
}

/** Merge lead $drop into lead $keep. */
function ot_leads_merge( $keep, $drop, $actor = '' ) {
	global $wpdb;
	$a = ot_lead( $keep );
	$b = ot_lead( $drop );
	if ( ! $a || ! $b || $keep === $drop ) {
		return false;
	}
	foreach ( array( 'lead_events', 'tasks', 'enrollments', 'messages', 'consents', 'outbox' ) as $t ) {
		$wpdb->update( ot_leads_table( $t ), array( 'lead_id' => $keep ), array( 'lead_id' => $drop ) );
	}
	$upd = array(
		'towns'            => implode( ',', array_unique( array_filter( array_merge( explode( ',', $a['towns'] ), explode( ',', $b['towns'] ) ) ) ) ),
		'tags'             => implode( ',', array_unique( array_filter( array_map( 'trim', array_merge( explode( ',', $a['tags'] ), explode( ',', $b['tags'] ) ) ) ) ) ),
		'last_activity_at' => max( $a['last_activity_at'], $b['last_activity_at'] ),
	);
	foreach ( array( 'first_name', 'last_name', 'phone', 'preferred', 'listing_id', 'topic', 'first_url', 'referrer' ) as $k ) {
		if ( empty( $a[ $k ] ) && ! empty( $b[ $k ] ) ) {
			$upd[ $k ] = $b[ $k ];
		}
	}
	if ( ! $a['contacted_at'] && $b['contacted_at'] ) {
		$upd['contacted_at'] = $b['contacted_at'];
	}
	if ( $b['created_at'] < $a['created_at'] ) {
		$upd['created_at'] = $b['created_at'];
	}
	// Keep the further-along stage (a closed client stays closed).
	$order = array_keys( OT_LEAD_STAGES );
	if ( ! in_array( $a['stage'], array( 'closed', 'lost' ), true ) && array_search( $b['stage'], $order, true ) > array_search( $a['stage'], $order, true ) && 'nurture' !== $b['stage'] && 'lost' !== $b['stage'] ) {
		$upd['stage'] = $b['stage'];
	}
	ot_lead_update( $keep, $upd, $actor ?: 'merge' );
	// Two copies of the same sequence: keep the one further along.
	$t = ot_leads_table( 'enrollments' );
	foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT sequence, COUNT(*) n FROM $t WHERE lead_id = %d AND state IN ('active','paused') GROUP BY sequence HAVING COUNT(*) > 1", $keep ), ARRAY_A ) as $dup ) {
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $t WHERE lead_id = %d AND sequence = %s AND state IN ('active','paused') ORDER BY step DESC, id ASC", $keep, $dup['sequence'] ) );
		foreach ( array_slice( $ids, 1 ) as $eid ) {
			$wpdb->update( $t, array( 'state' => 'exited', 'exit_reason' => 'Merged duplicate' ), array( 'id' => $eid ) );
		}
	}
	$wpdb->delete( ot_leads_table( 'leads' ), array( 'id' => $drop ) );
	ot_lead_event( $keep, 'merged', array( 'name' => ot_lead_name( $b ), 'email' => $b['email'], 'id' => $drop ), $actor );
	delete_option( 'ot_ai_sum_' . $drop );
	return true;
}
