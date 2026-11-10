<?php
/**
 * Leads > Reports: where leads come from and how fast the team answers them, for a period
 * (30 days, 90 days, 12 months, all time), for everyone or one agent (agents see their own).
 *
 * - Totals: inquiries, sign-ups, first replies and the median time to a first reply.
 * - By agent: leads, % answered within the response window, median first reply.
 * - Sources: form type, referring site, campaign (utm_source), top pages, towns.
 * - Pipeline: where the period's leads are now.
 * - By month: the last 12 months.
 * - Possible duplicates, with Merge and "Not a duplicate".
 * Everything is computed in PHP from the lead rows, so it runs on any database.
 */

defined( 'ABSPATH' ) || exit;

function ot_leads_median( $nums ) {
	if ( ! $nums ) {
		return null;
	}
	sort( $nums );
	$n = count( $nums );
	return $n % 2 ? $nums[ intdiv( $n, 2 ) ] : ( $nums[ $n / 2 - 1 ] + $nums[ $n / 2 ] ) / 2;
}

/** Minutes as "12 min", "3 h 5 min" or "2 days". */
function ot_leads_minutes( $m ) {
	if ( null === $m ) {
		return '—';
	}
	$m = (int) round( $m );
	if ( $m < 60 ) {
		return $m . ' min';
	}
	if ( $m < 1440 ) {
		return intdiv( $m, 60 ) . ' h' . ( $m % 60 ? ' ' . ( $m % 60 ) . ' min' : '' );
	}
	return round( $m / 1440, 1 ) . ' days';
}

/** A table of label => count with bars. */
function ot_leads_bars( $counts, $label_cb = null, $limit = 10 ) {
	arsort( $counts );
	$counts = array_slice( $counts, 0, $limit, true );
	if ( ! $counts ) {
		return '<p class="ot-muted">None in this period.</p>';
	}
	$max = max( $counts ) ?: 1;
	$o   = '<table class="widefat striped"><tbody>';
	foreach ( $counts as $k => $n ) {
		$o .= '<tr><td>' . ( $label_cb ? $label_cb( $k ) : esc_html( (string) $k ) ) . '</td><td style="width:45%;white-space:nowrap"><span class="ot-bar" style="width:' . max( 2, round( $n / $max * 75 ) ) . '%"></span>' . (int) $n . '</td></tr>';
	}
	return $o . '</tbody></table>';
}

function ot_leads_reports_screen() {
	global $wpdb;
	$all    = current_user_can( 'ot_leads_view_all' );
	$agent  = $all ? sanitize_title( wp_unslash( $_GET['agent'] ?? '' ) ) : ot_leads_user_agent(); // phpcs:ignore WordPress.Security.NonceVerification
	$period = sanitize_key( $_GET['period'] ?? '90' ); // phpcs:ignore WordPress.Security.NonceVerification
	$days   = array( '30' => 30, '90' => 90, '365' => 365, 'all' => 0 );
	$days   = $days[ $period ] ?? 90;
	$since  = $days ? gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ) : '1970-01-01 00:00:00';
	$where  = $wpdb->prepare( ' WHERE created_at >= %s', $since ) . ot_leads_agent_where( $agent );
	$leads  = $wpdb->get_results( 'SELECT * FROM ' . ot_leads_table( 'leads' ) . $where, ARRAY_A );
	$win    = max( 5, (int) ot_leads_opt( 'response_minutes' ) );

	$signups = array( 'newsletter', 'listings' );
	$inq     = array_filter( $leads, fn( $l ) => ! in_array( $l['lead_type'], $signups, true ) );
	$mins    = fn( $l ) => $l['contacted_at'] ? max( 0, ( strtotime( $l['contacted_at'] . ' UTC' ) - strtotime( $l['created_at'] . ' UTC' ) ) / 60 ) : null;
	$replied = array_filter( array_map( $mins, $inq ), fn( $m ) => null !== $m );

	ot_leads_shell( 'reports' );
	echo '<div class="wrap ot-wrap">';
	ot_leads_head( 'Reports', 'Where leads come from and how fast the team answers them.' );
	ot_leads_notice();
	echo '<form method="get" class="ot-inline-form" style="margin:0 0 18px"><input type="hidden" name="page" value="ot-leads-reports"><label class="screen-reader-text" for="ot-period">Period</label><select id="ot-period" name="period">';
	foreach ( array( '30' => 'Last 30 days', '90' => 'Last 90 days', '365' => 'Last 12 months', 'all' => 'All time' ) as $k => $label ) {
		echo '<option value="' . esc_attr( $k ) . '"' . selected( $period, $k, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select> ' . ( $all ? ot_leads_agent_select( 'agent', $agent, 'Everyone', true ) : '' ) . ' <button class="button">Show</button></form>'; // phpcs:ignore WordPress.Security.EscapeOutput

	$within = $replied ? count( array_filter( $replied, fn( $m ) => $m <= $win ) ) : 0;
	echo '<div class="ot-card"><h2>At a glance</h2><ul class="ot-figures"><li><b>' . count( $inq ) . '</b>inquiries</li><li><b>' . ( count( $leads ) - count( $inq ) ) . '</b>sign-ups</li><li><b>' . count( $replied ) . '</b>answered</li>'
		. '<li><b>' . esc_html( ot_leads_minutes( ot_leads_median( $replied ) ) ) . '</b>median first reply</li>'
		. ( $inq ? '<li><b>' . round( $within / count( $inq ) * 100 ) . '%</b>within ' . $win . ' minutes</li>' : '' ) . '</ul>'
		. '<p class="ot-muted">A first reply is the first call, text or email logged, or "I\'m on it". Sign-ups (newsletter, listing alerts) are not counted as inquiries.</p></div>';

	echo '<div class="ot-grid"><div>';
	if ( $all && ! $agent ) {
		echo '<div class="ot-card"><h2>By agent</h2><table class="widefat striped"><thead><tr><th>Agent</th><th>Inquiries</th><th>Within ' . (int) $win . ' min</th><th>Median first reply</th></tr></thead><tbody>';
		$by = array();
		foreach ( $inq as $l ) {
			$by[ $l['assigned_agent'] ][] = $l;
		}
		foreach ( $by as $slug => $ls ) {
			$m = array_filter( array_map( $mins, $ls ), fn( $x ) => null !== $x );
			echo '<tr><td>' . esc_html( ot_leads_agent_name( $slug ) ) . '</td><td>' . count( $ls ) . '</td><td>' . round( count( array_filter( $m, fn( $x ) => $x <= $win ) ) / count( $ls ) * 100 ) . '%</td><td>' . esc_html( ot_leads_minutes( ot_leads_median( $m ) ) ) . '</td></tr>';
		}
		echo $by ? '' : '<tr><td colspan="4" class="ot-muted">None in this period.</td></tr>';
		echo '</tbody></table></div>';
	}

	$types = array_count_values( array_map( fn( $l ) => $l['lead_type'], $leads ) );
	echo '<div class="ot-card"><h2>What they asked for</h2>' . ot_leads_bars( $types, fn( $k ) => esc_html( OT_LEAD_TYPES[ $k ] ?? $k ) ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput

	$refs = array_count_values( array_map( function ( $l ) {
		$h = $l['referrer'] ? (string) wp_parse_url( $l['referrer'], PHP_URL_HOST ) : '';
		$h = preg_replace( '/^www\./', '', $h );
		return ( ! $h || str_contains( home_url(), $h ) ) ? 'Direct or this site' : $h;
	}, $leads ) );
	echo '<div class="ot-card"><h2>Where they came from</h2>' . ot_leads_bars( $refs ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput

	$camp = array();
	foreach ( $leads as $l ) {
		$u = json_decode( (string) $l['utm'], true ) ?: array();
		if ( ! empty( $u['utm_source'] ) || ! empty( $u['source'] ) ) {
			$k          = ( $u['utm_source'] ?? $u['source'] ) . ( ! empty( $u['utm_campaign'] ?? $u['campaign'] ?? '' ) ? ' · ' . ( $u['utm_campaign'] ?? $u['campaign'] ) : '' );
			$camp[ $k ] = ( $camp[ $k ] ?? 0 ) + 1;
		}
	}
	echo '<div class="ot-card"><h2>Campaigns <span class="ot-muted" style="font-weight:400">· links tagged with utm_source</span></h2>' . ot_leads_bars( $camp ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '</div><div>';

	$pages = array_count_values( array_map( fn( $l ) => (string) ( wp_parse_url( $l['source_url'], PHP_URL_PATH ) ?: '/' ), $leads ) );
	echo '<div class="ot-card"><h2>Pages that brought leads</h2>' . ot_leads_bars( $pages, fn( $k ) => '<a href="' . esc_url( home_url( $k ) ) . '">' . esc_html( $k ) . '</a>' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput

	$towns = array();
	foreach ( $leads as $l ) {
		foreach ( array_filter( explode( ',', $l['towns'] ) ) as $t ) {
			$towns[ $t ] = ( $towns[ $t ] ?? 0 ) + 1;
		}
	}
	echo '<div class="ot-card"><h2>Towns</h2>' . ot_leads_bars( $towns, function ( $k ) {
		$p = get_page_by_path( $k, OBJECT, 'ot_community' );
		return esc_html( $p ? $p->post_title : $k );
	} ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput

	$stages = array();
	foreach ( OT_LEAD_STAGES as $k => $label ) {
		$stages[ $label ] = count( array_filter( $inq, fn( $l ) => $l['stage'] === $k ) );
	}
	echo '<div class="ot-card"><h2>Where this period\'s inquiries are now</h2><table class="widefat striped"><tbody>';
	$max = max( $stages ) ?: 1;
	foreach ( $stages as $label => $n ) {
		echo '<tr><td>' . esc_html( $label ) . '</td><td style="width:45%;white-space:nowrap"><span class="ot-bar" style="width:' . max( 2, round( $n / $max * 75 ) ) . '%"></span>' . (int) $n . '</td></tr>';
	}
	echo '</tbody></table></div>';

	// The last 12 months, whatever the period.
	$months = array();
	for ( $i = 11; $i >= 0; $i-- ) {
		$months[ gmdate( 'Y-m', strtotime( "first day of -$i month" ) ) ] = 0;
	}
	$ym = $wpdb->get_col( $wpdb->prepare( 'SELECT created_at FROM ' . ot_leads_table( 'leads' ) . ' WHERE created_at >= %s' . ot_leads_agent_where( $agent ), gmdate( 'Y-m-01 00:00:00', strtotime( 'first day of -11 month' ) ) ) );
	foreach ( $ym as $c ) {
		$k = substr( $c, 0, 7 );
		if ( isset( $months[ $k ] ) ) {
			++$months[ $k ];
		}
	}
	echo '<div class="ot-card"><h2>By month <span class="ot-muted" style="font-weight:400">· last 12 months</span></h2><table class="widefat striped"><tbody>';
	$max = max( $months ) ?: 1;
	foreach ( $months as $k => $n ) {
		echo '<tr><td style="width:7rem">' . esc_html( gmdate( 'M Y', strtotime( $k . '-01' ) ) ) . '</td><td><span class="ot-bar" style="width:' . max( 1, round( $n / $max * 80 ) ) . '%"></span>' . (int) $n . '</td></tr>';
	}
	echo '</tbody></table></div>';
	echo '</div></div>';

	if ( current_user_can( 'ot_leads_manage' ) ) {
		$dups = ot_leads_duplicates();
		echo '<div class="ot-card" id="duplicates"><h2>Possible duplicates</h2>';
		if ( ! $dups ) {
			echo '<p class="ot-muted">None found.</p>';
		} else {
			echo '<table class="widefat striped"><tbody>';
			foreach ( $dups as list( $a, $b, $why ) ) {
				echo '<tr><td>' . ot_leads_dupe_name( $a ) . '</td><td>' . ot_leads_dupe_name( $b ) . '</td><td>' . esc_html( ucfirst( $why ) ) . '</td><td>' . ot_leads_dupe_actions( $a, $b ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</tbody></table>';
		}
		echo '<p class="ot-muted">Exports: tick leads in the Inbox and choose "Export to CSV", or export one lead\'s full record from its screen.</p></div>';
	}
	echo '</div>';
	ot_leads_shell_end();
}

function ot_leads_dupe_name( $id ) {
	$l = ot_lead( $id );
	return $l ? '<a href="' . esc_url( ot_leads_admin_link( $id ) ) . '">' . esc_html( ot_lead_name( $l ) ) . '</a><br><span class="ot-muted">' . esc_html( $l['email'] . ( $l['phone'] ? ' · ' . $l['phone'] : '' ) . ' · ' . ot_leads_time( $l['created_at'], 'M j, Y' ) ) . '</span>' : '';
}

function ot_leads_dupe_actions( $a, $b ) {
	return ot_leads_form( 'merge', $a, 'style="display:inline" onsubmit="return confirm(\'Merge the second lead into the first? Its history moves over and it is deleted.\')"' ) . '<input type="hidden" name="drop" value="' . (int) $b . '"><button class="button button-small">Merge into the first</button></form> '
		. ot_leads_form( 'not_dupe', $a, 'style="display:inline"' ) . '<input type="hidden" name="other" value="' . (int) $b . '"><button class="button-link">Not a duplicate</button></form>';
}

/** On the lead screen. */
function ot_leads_duplicate_card( $lead ) {
	if ( ! current_user_can( 'ot_leads_manage' ) ) {
		return '';
	}
	$d = ot_leads_duplicates( (int) $lead['id'] );
	if ( ! $d ) {
		return '';
	}
	$o = '<div class="ot-card ot-card--note"><h2>Possible duplicate</h2>';
	foreach ( $d as list( $a, $b, $why ) ) {
		$other = (int) $lead['id'] === $a ? $b : $a;
		$o    .= '<p>' . ot_leads_dupe_name( $other ) . ' <span class="ot-muted">(' . esc_html( $why ) . ')</span></p><p>' . ot_leads_dupe_actions( $a, $b ) . '</p>';
	}
	return $o . '</div>';
}

add_action( 'ot_leads_admin_action', function ( $do, $id ) {
	if ( ! in_array( $do, array( 'merge', 'not_dupe' ), true ) || ! current_user_can( 'ot_leads_manage' ) ) {
		return;
	}
	if ( 'merge' === $do ) {
		$drop = (int) ( $_POST['drop'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification -- checked in the handler
		$ok   = ot_leads_merge( $id, $drop, wp_get_current_user()->user_login );
		wp_safe_redirect( add_query_arg( 'ot_msg', rawurlencode( $ok ? 'Merged. Everything is now on this lead.' : 'Could not merge.' ), ot_leads_admin_link( $id ) ) );
		exit;
	}
	$other = (int) ( $_POST['other'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
	$list  = (array) get_option( 'ot_leads_not_dupes', array() );
	$list[] = min( $id, $other ) . '-' . max( $id, $other );
	update_option( 'ot_leads_not_dupes', array_values( array_unique( $list ) ), false );
	wp_safe_redirect( add_query_arg( 'ot_msg', rawurlencode( 'Marked as not a duplicate.' ), wp_get_referer() ?: admin_url( 'admin.php?page=ot-leads-reports' ) ) );
	exit;
}, 10, 2 );
