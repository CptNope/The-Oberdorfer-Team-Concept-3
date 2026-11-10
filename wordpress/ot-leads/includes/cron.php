<?php
/**
 * The scheduler. Every minute: escalations, CRM deliveries, nurture drafts and sends. Daily:
 * the task digest and retention clean-up.
 *
 * On Cloudways, set define( 'DISABLE_WP_CRON', true ) and add a server cron job (Application
 * Settings > Cron Job Management) that runs every minute:
 *   wget -q -O - "https://oberdorferteam.com/wp-cron.php?doing_wp_cron" >/dev/null 2>&1
 * Without it WordPress runs these on page loads, which is fine for staging but can lag.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'cron_schedules', function ( $s ) {
	$s['ot_minute'] = array( 'interval' => 60, 'display' => 'Every minute (ot-leads)' );
	return $s;
} );

function ot_leads_schedule() {
	if ( ! wp_next_scheduled( 'ot_leads_tick' ) ) {
		wp_schedule_event( time() + 60, 'ot_minute', 'ot_leads_tick' );
	}
	if ( ! wp_next_scheduled( 'ot_leads_daily' ) ) {
		$d = new DateTime( 'tomorrow 7:30', new DateTimeZone( 'America/New_York' ) );
		wp_schedule_event( $d->getTimestamp(), 'daily', 'ot_leads_daily' );
	}
}

add_action( 'ot_leads_tick', function () {
	if ( get_transient( 'ot_leads_tick_lock' ) ) {
		return;
	}
	set_transient( 'ot_leads_tick_lock', 1, 55 );
	ot_leads_escalate();
	ot_leads_outbox_run();
	ot_nurture_advance();
	ot_nurture_send_due();
	delete_transient( 'ot_leads_tick_lock' );
	update_option( 'ot_leads_last_tick', time(), false );
} );

add_action( 'ot_leads_daily', function () {
	ot_leads_task_digest();
	ot_leads_retention();
} );

/**
 * The morning email, one per agent (the joint profile's to the team leaders): new leads still
 * waiting for a first reply, follow-ups due today or overdue, and people back on the site.
 * Nothing is sent to an agent with nothing to do.
 */
function ot_leads_task_digest() {
	// One email per address: Brandon's own leads and the team's arrive together.
	$by = array();
	foreach ( ot_leads_agents() as $slug => $a ) {
		$d = ot_leads_today_data( $slug );
		if ( ! $d['waiting'] && ! $d['due'] && ! $d['back'] ) {
			continue;
		}
		foreach ( ot_leads_agent_emails( $slug ) as $to ) {
			$by[ strtolower( $to ) ][] = array( $slug, $a, $d );
		}
	}
	$li = fn( $l, $extra ) => '<li><a href="' . esc_url( ot_leads_admin_link( $l['id'] ) ) . '">' . esc_html( ot_lead_name( $l ) ) . '</a> · ' . $extra . '</li>';
	foreach ( $by as $to => $parts ) {
		$html = '<p>Good morning. Here is today, from oberdorferteam.com.</p>';
		$n    = 0;
		$back = 0;
		foreach ( $parts as list( $slug, $a, $d ) ) {
			if ( count( $parts ) > 1 ) {
				$html .= '<h2 style="font:400 20px/1.3 Georgia,serif;margin:26px 0 4px">' . esc_html( $a['team'] ? 'Team leads' : $a['name'] ) . '</h2>';
			}
			if ( $d['waiting'] ) {
				$html .= '<h3 style="font-size:16px;margin:14px 0 6px">Waiting for a first reply (' . count( $d['waiting'] ) . ')</h3><ul>';
				foreach ( $d['waiting'] as $l ) {
					$html .= $li( $l, esc_html( ( OT_LEAD_TYPES[ $l['lead_type'] ] ?? '' ) . ', ' . human_time_diff( strtotime( $l['created_at'] . ' UTC' ) ) . ' ago' ) . ' · ' . ot_leads_contact_links( $l ) );
				}
				$html .= '</ul>';
			}
			if ( $d['due'] ) {
				$html .= '<h3 style="font-size:16px;margin:14px 0 6px">Follow-ups due (' . count( $d['due'] ) . ')</h3><ul>';
				foreach ( $d['due'] as $l ) {
					$late  = strtotime( $l['due_at'] . ' UTC' ) < strtotime( ot_leads_day_end( -1 ) . ' UTC' );
					$html .= $li( $l, esc_html( ucfirst( $l['kind'] ) . ( $l['note'] ? ': ' . $l['note'] : '' ) ) . ( $late ? ' <strong>(overdue)</strong>' : '' ) . ' · ' . ot_leads_contact_links( $l ) );
				}
				$html .= '</ul>';
			}
			if ( $d['back'] ) {
				$html .= '<h3 style="font-size:16px;margin:14px 0 6px">Back on the site</h3><ul>';
				foreach ( $d['back'] as $l ) {
					$html .= $li( $l, esc_html( ot_leads_last_signal( (int) $l['id'] ) ) );
				}
				$html .= '</ul>';
			}
			$n    += count( $d['waiting'] ) + count( $d['due'] );
			$back += count( $d['back'] );
		}
		$html .= '<p style="margin-top:20px"><a href="' . esc_url( admin_url( 'admin.php?page=ot-leads-today' ) ) . '">Open Today</a>. Tap Called, Voicemail or Texted there and the next follow-up books itself.</p>';
		ot_leads_mail( $to, 'Today: ' . $n . ' to follow up' . ( $back ? ', ' . $back . ' back on the site' : '' ), $html, array( 'tags' => array( 'digest' ) ) );
	}
}
