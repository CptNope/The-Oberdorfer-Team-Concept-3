<?php
/**
 * Admin: the Leads menu, the shared action handler and screen styles.
 *
 * Screens (spec § 12): Today, Inbox, Board, Approval queue, Reports, Sequences, Newsletter,
 * Settings, and the lead screen. They share one workspace shell (a navy rail and an ivory page,
 * assets/admin.css, scoped to body.ot-ws) around WordPress's own forms, list table and nonces.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	global $wpdb;
	$new   = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . ot_leads_table( 'leads' ) . " WHERE stage = 'new'" . ( current_user_can( 'ot_leads_view_all' ) ? '' : $wpdb->prepare( ' AND assigned_agent = %s', ot_leads_user_agent() ) ) );
	$badge = $new ? ' <span class="awaiting-mod count-' . $new . '"><span class="pending-count">' . $new . '</span></span>' : '';
	add_menu_page( 'Leads', 'Leads' . $badge, 'ot_leads_own', 'ot-leads', 'ot_leads_inbox_screen', 'dashicons-groups', 3 );
	// Today comes first, so the Leads menu opens on it.
	add_submenu_page( 'ot-leads', 'Today', 'Today', 'ot_leads_own', 'ot-leads-today', 'ot_leads_today_screen' );
	add_submenu_page( 'ot-leads', 'Leads inbox', 'All leads', 'ot_leads_own', 'ot-leads', 'ot_leads_inbox_screen' );
	add_submenu_page( 'ot-leads', 'Pipeline board', 'Board', 'ot_leads_own', 'ot-leads-board', 'ot_leads_board_screen' );
	$drafts = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . ot_leads_table( 'messages' ) . " WHERE status = 'draft'" );
	add_submenu_page( 'ot-leads', 'Approval queue', 'Approval queue' . ( $drafts ? ' <span class="awaiting-mod"><span class="pending-count">' . $drafts . '</span></span>' : '' ), 'ot_leads_own', 'ot-leads-queue', 'ot_leads_queue_screen' );
	add_submenu_page( 'ot-leads', 'Reports', 'Reports', 'ot_leads_own', 'ot-leads-reports', 'ot_leads_reports_screen' );
	add_submenu_page( 'ot-leads', 'Sequences', 'Sequences', 'ot_leads_manage', 'ot-leads-sequences', 'ot_leads_sequences_screen' );
	add_submenu_page( 'ot-leads', 'Newsletter', 'Newsletter', 'ot_leads_manage', 'ot-leads-newsletter', 'ot_leads_newsletter_screen' );
	add_submenu_page( 'ot-leads', 'Leads settings', 'Settings', 'ot_leads_manage', 'ot-leads-settings', 'ot_leads_settings_screen' );
	add_submenu_page( 'ot-leads', 'Lead', 'Lead', 'ot_leads_own', 'ot-lead', 'ot_leads_lead_screen' );
} );

// The lead screen is reached from the inbox, not the menu.
add_action( 'admin_head', function () {
	remove_submenu_page( 'ot-leads', 'ot-lead' );
	if ( ot_leads_is_screen() ) {
		nocache_headers();
	}
} );

/** True on any Leads screen. */
function ot_leads_is_screen() {
	return is_admin() && str_starts_with( sanitize_key( $_GET['page'] ?? '' ), 'ot-lead' ); // phpcs:ignore WordPress.Security.NonceVerification
}

// The workspace: its own class on <body>, WordPress's menu folded to icons, the brand faces.
add_filter( 'admin_body_class', fn( $c ) => ot_leads_is_screen() ? $c . ' ot-ws folded' : $c );
add_action( 'admin_enqueue_scripts', function () {
	if ( ! ot_leads_is_screen() ) {
		return;
	}
	// The faces are self-hosted in assets/fonts, so no staff request goes to a third party.
	wp_enqueue_style( 'ot-leads-admin', OT_LEADS_URL . '/assets/admin.css', array( 'dashicons' ), OT_LEADS_VERSION );
} );

/** The workspace rail and the opening of the page; $current is a rail key. */
function ot_leads_shell( $current ) {
	global $wpdb;
	$own    = current_user_can( 'ot_leads_view_all' ) ? '' : $wpdb->prepare( ' AND assigned_agent = %s', ot_leads_user_agent() );
	$wait   = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . ot_leads_table( 'leads' ) . " WHERE stage = 'new' AND contacted_at IS NULL$own" ); // phpcs:ignore WordPress.DB.PreparedSQL
	$drafts = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . ot_leads_table( 'messages' ) . " WHERE status = 'draft'" );
	$items  = array(
		'today'    => array( 'ot-leads-today', 'Today', 'calendar-alt', $wait, 'ot_leads_own' ),
		'inbox'    => array( 'ot-leads', 'All leads', 'groups', 0, 'ot_leads_own' ),
		'board'    => array( 'ot-leads-board', 'Pipeline', 'columns', 0, 'ot_leads_own' ),
		'queue'    => array( 'ot-leads-queue', 'Approvals', 'email-alt', $drafts, 'ot_leads_own' ),
		'reports'  => array( 'ot-leads-reports', 'Reports', 'chart-bar', 0, 'ot_leads_own' ),
		'-'        => null,
		'sequence' => array( 'ot-leads-sequences', 'Sequences', 'update', 0, 'ot_leads_manage' ),
		'news'     => array( 'ot-leads-newsletter', 'Newsletter', 'megaphone', 0, 'ot_leads_manage' ),
		'settings' => array( 'ot-leads-settings', 'Settings', 'admin-generic', 0, 'ot_leads_manage' ),
	);
	$o = '<div class="ot-app"><nav class="ot-rail" aria-label="Leads"><a class="ot-rail__brand" href="' . esc_url( admin_url( 'admin.php?page=ot-leads-today' ) ) . '">The Oberdorfer Team<span>Leads</span></a><ul>';
	foreach ( $items as $key => $it ) {
		if ( null === $it ) {
			$o .= '</ul><hr><ul>';
			continue;
		}
		if ( ! current_user_can( $it[4] ) ) {
			continue;
		}
		$o .= '<li><a class="ot-nav" href="' . esc_url( admin_url( 'admin.php?page=' . $it[0] ) ) . '"' . ( $key === $current ? ' aria-current="page"' : '' ) . '><span class="dashicons dashicons-' . esc_attr( $it[2] ) . '" aria-hidden="true"></span>' . esc_html( $it[1] ) . ( $it[3] ? '<span class="ot-nav__count">' . (int) $it[3] . '<span class="screen-reader-text"> waiting</span></span>' : '' ) . '</a></li>';
	}
	echo $o . '</ul></nav><main class="ot-main" id="ot-main">'; // phpcs:ignore WordPress.Security.EscapeOutput
}

function ot_leads_shell_end() {
	// On a phone the rail is a scrolling strip: bring the current screen into view.
	echo '</main></div><script>(function(){var a=document.querySelector(".ot-rail a[aria-current]");if(a&&window.matchMedia("(max-width: 960px)").matches){a.scrollIntoView({block:"nearest",inline:"center"});}})();</script>';
}

/** An hour of the day (0 to 24) as "8 am", "8 pm", "noon" or "midnight". */
function ot_leads_hour( $h ) {
	$h = (int) $h % 24;
	if ( 0 === $h || 12 === $h ) {
		return $h ? 'noon' : 'midnight';
	}
	return ( $h % 12 ) . ( $h < 12 ? ' am' : ' pm' );
}

/** The sending window from Settings, as words ("between 8 am and 8 pm", or "at any time"). */
function ot_leads_hours_text() {
	$start = ot_leads_opt( 'hours_start' );
	$end   = ot_leads_opt( 'hours_end' );
	$start = ( '' === $start || null === $start ) ? 8 : (int) $start;
	$end   = ( '' === $end || null === $end ) ? 20 : (int) $end;
	return ( 0 === $start && 24 === $end ) ? 'at any time' : 'between ' . ot_leads_hour( $start ) . ' and ' . ot_leads_hour( $end );
}

/** A screen title row: the title, an optional line under it, and actions on the right. */
function ot_leads_head( $title, $sub = '', $actions = '' ) {
	echo '<div class="ot-head"><div><h1 class="ot-title">' . $title . '</h1>' . ( $sub ? '<p class="ot-sub">' . $sub . '</p>' : '' ) . '</div>' . ( $actions ? '<div class="ot-head__actions">' . $actions . '</div>' : '' ) . '</div><hr class="wp-header-end" style="display:none">'; // phpcs:ignore WordPress.Security.EscapeOutput
}

/** Filter chips for the agent: Everyone, Team, then each agent. */
function ot_leads_agent_chips( $page, $cur, $extra = array() ) {
	$opts = array( '' => 'Everyone', 'team' => 'Team' );
	foreach ( ot_leads_agents() as $slug => $a ) {
		$opts[ $slug ] = strtok( $a['name'], ' ' );
	}
	$o = '<ul class="ot-chips" aria-label="Show leads for">';
	foreach ( $opts as $k => $label ) {
		$o .= '<li><a class="ot-chip" href="' . esc_url( add_query_arg( array_merge( $extra, array( 'page' => $page, 'agent' => $k ?: false ) ), admin_url( 'admin.php' ) ) ) . '"' . ( (string) $cur === (string) $k ? ' aria-current="page"' : '' ) . '>' . esc_html( $label ) . '</a></li>';
	}
	return $o . '</ul>';
}

/** Town slugs ("worcester,west-boylston", "boston/back-bay") as names. */
function ot_leads_towns_label( $csv ) {
	return implode( ', ', array_map( fn( $t ) => ucwords( str_replace( array( '-', '/' ), array( ' ', ' / ' ), trim( $t ) ) ), array_filter( explode( ',', (string) $csv ) ) ) );
}

/** The lead's agent as a name. */
function ot_leads_agent_name( $slug ) {
	return ot_leads_agents()[ $slug ]['name'] ?? ( ( '' === $slug || 'team' === $slug || OT_LEADS_TEAM_PROFILE === $slug ) ? OT_LEADS_TEAM_LABEL : $slug );
}

function ot_leads_stage_pill( $stage ) {
	return '<span class="ot-pill' . ( 'new' === $stage ? ' ot-pill--new' : '' ) . '">' . esc_html( OT_LEAD_STAGES[ $stage ] ?? $stage ) . '</span>';
}

/** An agent menu. '' is $none (the team, or "everyone" in a filter); $team adds a "team" choice to filters. */
function ot_leads_agent_select( $name, $cur, $none = OT_LEADS_TEAM_LABEL, $team = false ) {
	$o = '<select name="' . esc_attr( $name ) . '"><option value="">' . esc_html( $none ) . '</option>' . ( $team ? '<option value="team"' . selected( $cur, 'team', false ) . '>' . esc_html( OT_LEADS_TEAM_LABEL ) . '</option>' : '' );
	foreach ( ot_leads_agents() as $slug => $a ) {
		$o .= '<option value="' . esc_attr( $slug ) . '"' . selected( $cur, $slug, false ) . '>' . esc_html( $a['name'] ) . '</option>';
	}
	return $o . '</select>';
}

function ot_leads_notice() {
	if ( ! empty( $_GET['ot_msg'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['ot_msg'] ) ) ) . '</p></div>';
	}
}

/** Times are stored in UTC and shown in Massachusetts time. */
function ot_leads_time( $utc, $fmt = 'M j, g:i a' ) {
	if ( ! $utc ) {
		return '';
	}
	$d = new DateTime( $utc, new DateTimeZone( 'UTC' ) );
	return $d->setTimezone( new DateTimeZone( 'America/New_York' ) )->format( $fmt );
}

/* --------------------------------------------------------------------- actions */

/** Every form on the admin screens posts here: admin-post.php?action=ot_leads with "do". */
add_action( 'admin_post_ot_leads', function () {
	check_admin_referer( 'ot_leads' );
	$do   = sanitize_key( $_POST['do'] ?? '' );
	$id   = (int) ( $_POST['id'] ?? 0 );
	$lead = $id ? ot_lead( $id ) : null;
	$back = wp_get_referer() ?: admin_url( 'admin.php?page=ot-leads' );
	$done = function ( $msg, $url = null ) use ( $back ) {
		wp_safe_redirect( add_query_arg( 'ot_msg', rawurlencode( $msg ), remove_query_arg( 'ot_msg', $url ?: $back ) ) );
		exit;
	};
	if ( $lead && ! ot_leads_can_see( $lead ) ) {
		wp_die( 'You can only change your own leads.' );
	}
	$me = wp_get_current_user()->user_login;
	switch ( $do ) {
		case 'update':
			$f = array( 'stage' => sanitize_key( $_POST['stage'] ?? $lead['stage'] ) );
			if ( current_user_can( 'ot_leads_view_all' ) && isset( $_POST['assigned_agent'] ) ) {
				$f['assigned_agent'] = sanitize_title( wp_unslash( $_POST['assigned_agent'] ) );
			}
			if ( 'lost' === $f['stage'] ) {
				$f['lost_reason'] = sanitize_text_field( wp_unslash( $_POST['lost_reason'] ?? '' ) );
			}
			if ( in_array( $f['stage'], array( 'contacted', 'appointment', 'active' ), true ) && ! $lead['contacted_at'] ) {
				$f['contacted_at'] = ot_leads_now();
			}
			foreach ( array( 'first_name', 'last_name', 'phone', 'tags' ) as $k ) {
				if ( isset( $_POST[ $k ] ) ) {
					$f[ $k ] = sanitize_text_field( wp_unslash( $_POST[ $k ] ) );
				}
			}
			ot_lead_update( $id, $f, $me );
			$done( 'Lead saved.' );
		case 'contacted':
			ot_lead_update( $id, array( 'contacted_at' => ot_leads_now(), 'stage' => 'new' === $lead['stage'] ? 'contacted' : $lead['stage'] ), $me );
			$done( 'Marked contacted.' );
		case 'note':
			$note = sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) );
			$kind = in_array( $_POST['kind'] ?? '', array( 'note', 'reply', 'call_logged' ), true ) ? $_POST['kind'] : 'note';
			if ( $note || 'note' !== $kind ) {
				ot_lead_event( $id, $kind, array( 'note' => $note ), $me );
			}
			if ( 'reply' === $kind ) {
				ot_nurture_exit_all( $id, 'They replied', array( 'buyer', 'seller', 'relocation' ) );
			}
			$done( 'Added to the timeline.' );
		case 'task':
			$due = sanitize_text_field( wp_unslash( $_POST['due'] ?? '' ) );
			$d   = DateTime::createFromFormat( 'Y-m-d\TH:i', $due, new DateTimeZone( 'America/New_York' ) );
			global $wpdb;
			$wpdb->insert( ot_leads_table( 'tasks' ), array( 'lead_id' => $id, 'assignee' => $lead['assigned_agent'], 'kind' => sanitize_key( $_POST['kind'] ?? 'call' ), 'note' => sanitize_text_field( wp_unslash( $_POST['note'] ?? '' ) ), 'due_at' => $d ? $d->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ) : ot_leads_now(), 'created_at' => ot_leads_now() ) );
			ot_lead_event( $id, 'task_added', array( 'kind' => sanitize_key( $_POST['kind'] ?? 'call' ), 'due' => $due ), $me );
			$done( 'Task added.' );
		case 'task_done':
			global $wpdb;
			$wpdb->update( ot_leads_table( 'tasks' ), array( 'done_at' => ot_leads_now() ), array( 'id' => (int) $_POST['task'], 'lead_id' => $id ) );
			ot_lead_event( $id, 'task_done', array( 'task' => (int) $_POST['task'] ), $me );
			$done( 'Task done.' );
		case 'enroll':
			$ok = ot_nurture_enroll( $id, sanitize_key( $_POST['sequence'] ?? '' ), $me );
			$done( $ok ? 'Enrolled.' : 'Not enrolled: the sequence is off, they are already in it, or there is no email consent.' );
		case 'enrollment':
			global $wpdb;
			$state = in_array( $_POST['state'] ?? '', array( 'active', 'paused', 'exited' ), true ) ? $_POST['state'] : 'paused';
			$wpdb->update( ot_leads_table( 'enrollments' ), array( 'state' => $state, 'exit_reason' => 'exited' === $state ? 'Stopped by ' . $me : '' ), array( 'id' => (int) $_POST['enrollment'], 'lead_id' => $id ) );
			$done( 'Sequence ' . $state . '.' );
		case 'summary':
			$s = ot_ai_summary( $lead );
			$done( $s ? 'Summary updated.' : 'The AI did not answer. Check Settings > AI.' );
		case 'consent':
			if ( current_user_can( 'ot_leads_view_all' ) ) {
				ot_leads_suppress( $lead['email'], 'manual' );
			}
			$done( 'Opted out: no more marketing email to ' . $lead['email'] . '.' );
		case 'export':
			if ( ! current_user_can( 'ot_leads_manage' ) ) {
				wp_die( 'Not allowed.' );
			}
			ot_lead_event( $id, 'exported', array(), $me );
			nocache_headers();
			header( 'Content-Type: application/json' );
			header( 'Content-Disposition: attachment; filename="lead-' . $id . '.json"' );
			global $wpdb;
			echo wp_json_encode( array( 'lead' => $lead, 'events' => ot_lead_events( $id, 1000 ), 'consents' => $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . ot_leads_table( 'consents' ) . ' WHERE lead_id = %d OR email = %s', $id, $lead['email'] ), ARRAY_A ) ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			exit;
		case 'delete':
			if ( ! current_user_can( 'ot_leads_manage' ) ) {
				wp_die( 'Not allowed.' );
			}
			ot_leads_erase( $id );
			$done( 'Lead deleted (consent records kept as proof).', admin_url( 'admin.php?page=ot-leads' ) );
		case 'move':
			ot_lead_update( $id, array( 'stage' => sanitize_key( $_POST['stage'] ?? 'new' ) ), $me );
			$done( ot_lead_name( $lead ) . ' moved to ' . ( OT_LEAD_STAGES[ sanitize_key( $_POST['stage'] ?? '' ) ] ?? '' ) . '.' );
	}
	do_action( 'ot_leads_admin_action', $do, $id );
	$done( 'Nothing changed.' );
} );

/** The opening of an admin form that posts to the handler. */
function ot_leads_form( $do, $id = 0, $attrs = '' ) {
	return '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" ' . $attrs . '>' . wp_nonce_field( 'ot_leads', '_wpnonce', true, false ) . '<input type="hidden" name="action" value="ot_leads"><input type="hidden" name="do" value="' . esc_attr( $do ) . '">' . ( $id ? '<input type="hidden" name="id" value="' . (int) $id . '">' : '' );
}

/** "Leads today" on the dashboard, beside the Start here panel. */
add_action( 'wp_dashboard_setup', function () {
	if ( ! current_user_can( 'ot_leads_own' ) ) {
		return;
	}
	wp_add_dashboard_widget( 'ot_leads_today', 'Leads', function () {
		global $wpdb;
		$t     = ot_leads_table( 'leads' );
		$own   = current_user_can( 'ot_leads_view_all' ) ? '' : $wpdb->prepare( ' AND assigned_agent = %s', ot_leads_user_agent() );
		$new   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE stage = 'new'$own" );
		$week  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE created_at >= %s$own", gmdate( 'Y-m-d H:i:s', time() - 7 * DAY_IN_SECONDS ) ) );
		$queue = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . ot_leads_table( 'messages' ) . " WHERE status = 'draft'" );
		echo current_user_can( 'ot_leads_manage' ) ? ot_leads_health_notices( true ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<p style="font-size:14px"><strong>' . $new . '</strong> waiting for a first reply · <strong>' . $week . '</strong> in the last 7 days · <strong>' . $queue . '</strong> emails to approve</p><p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=ot-leads-today' ) ) . '">Today</a> <a class="button" href="' . esc_url( admin_url( 'admin.php?page=ot-leads' ) ) . '">Inbox</a> <a class="button" href="' . esc_url( admin_url( 'admin.php?page=ot-leads-queue' ) ) . '">Approval queue</a></p>'; // phpcs:ignore WordPress.Security.EscapeOutput
	} );
} );
