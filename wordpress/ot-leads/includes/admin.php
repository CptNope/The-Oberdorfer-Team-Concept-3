<?php
/**
 * Admin: the Leads menu, the shared action handler and screen styles.
 *
 * Screens (spec § 12): Inbox, Board, Approval queue, Sequences, Newsletter, Settings, and the
 * lead screen. They use WordPress's own admin markup, so they look native and stay accessible;
 * the team's navy and gold appear as accents only.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	global $wpdb;
	$new   = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . ot_leads_table( 'leads' ) . " WHERE stage = 'new'" . ( current_user_can( 'ot_leads_view_all' ) ? '' : $wpdb->prepare( ' AND assigned_agent = %s', ot_leads_user_agent() ) ) );
	$badge = $new ? ' <span class="awaiting-mod count-' . $new . '"><span class="pending-count">' . $new . '</span></span>' : '';
	add_menu_page( 'Leads', 'Leads' . $badge, 'ot_leads_own', 'ot-leads', 'ot_leads_inbox_screen', 'dashicons-groups', 3 );
	add_submenu_page( 'ot-leads', 'Leads inbox', 'Inbox', 'ot_leads_own', 'ot-leads', 'ot_leads_inbox_screen' );
	add_submenu_page( 'ot-leads', 'Pipeline board', 'Board', 'ot_leads_own', 'ot-leads-board', 'ot_leads_board_screen' );
	$drafts = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . ot_leads_table( 'messages' ) . " WHERE status = 'draft'" );
	add_submenu_page( 'ot-leads', 'Approval queue', 'Approval queue' . ( $drafts ? ' <span class="awaiting-mod"><span class="pending-count">' . $drafts . '</span></span>' : '' ), 'ot_leads_own', 'ot-leads-queue', 'ot_leads_queue_screen' );
	add_submenu_page( 'ot-leads', 'Sequences', 'Sequences', 'ot_leads_manage', 'ot-leads-sequences', 'ot_leads_sequences_screen' );
	add_submenu_page( 'ot-leads', 'Newsletter', 'Newsletter', 'ot_leads_manage', 'ot-leads-newsletter', 'ot_leads_newsletter_screen' );
	add_submenu_page( 'ot-leads', 'Leads settings', 'Settings', 'ot_leads_manage', 'ot-leads-settings', 'ot_leads_settings_screen' );
	add_submenu_page( 'ot-leads', 'Lead', 'Lead', 'ot_leads_own', 'ot-lead', 'ot_leads_lead_screen' );
} );

// The lead screen is reached from the inbox, not the menu.
add_action( 'admin_head', function () {
	remove_submenu_page( 'ot-leads', 'ot-lead' );
	$page = sanitize_key( $_GET['page'] ?? '' );
	if ( str_starts_with( $page, 'ot-lead' ) ) {
		nocache_headers();
		echo '<style>
.ot-wrap{max-width:1280px}.ot-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.4fr);gap:20px;align-items:start}@media(max-width:1100px){.ot-grid{grid-template-columns:1fr}}
.ot-card{background:#fff;border:1px solid #dcdcde;padding:16px 18px;margin:0 0 16px}.ot-card h2{margin:0 0 12px;font-size:15px}.ot-card table.form-table th{width:150px;padding:8px 10px 8px 0}.ot-card table.form-table td{padding:8px 0}
.ot-pill{display:inline-block;padding:2px 8px;border-radius:2px;font-size:12px;line-height:1.6;background:#f0f0f1;color:#1d2327}.ot-pill--new{background:#071D2D;color:#F7F3EC}.ot-pill--warm{background:#B49A65;color:#071D2D}.ot-pill--ok{background:#d1e7dd;color:#0a3622}.ot-pill--bad{background:#f8d7da;color:#58151c}
.ot-timeline{list-style:none;margin:0;padding:0}.ot-timeline li{border-left:2px solid #dcdcde;padding:0 0 14px 14px;margin-left:6px;position:relative}.ot-timeline li:before{content:"";position:absolute;left:-6px;top:3px;width:10px;height:10px;border-radius:50%;background:#B49A65}.ot-timeline .when{color:#646970;font-size:12px}
.ot-board{display:grid;grid-auto-flow:column;grid-auto-columns:minmax(220px,1fr);gap:12px;overflow-x:auto;padding-bottom:12px}.ot-col{background:#f6f7f7;border:1px solid #dcdcde;min-height:300px;padding:8px}.ot-col h2{font-size:13px;margin:4px 4px 10px;display:flex;justify-content:space-between}.ot-col.is-over{outline:2px dashed #B49A65}
.ot-bcard{background:#fff;border:1px solid #dcdcde;border-left:3px solid #071D2D;padding:8px 10px;margin-bottom:8px;cursor:grab}.ot-bcard a{font-weight:600;text-decoration:none}.ot-bcard small{display:block;color:#646970}
.ot-msg{background:#fff;border:1px solid #dcdcde;padding:14px 16px;margin-bottom:14px}.ot-msg textarea{width:100%;min-height:180px;font-family:Menlo,Consolas,monospace;font-size:12px}.ot-msg .preview{border:1px solid #f0f0f1;padding:10px 14px;background:#fcfcfc;max-height:320px;overflow:auto}
.ot-steps td,.ot-steps th{vertical-align:top}.ot-steps textarea{width:100%;min-height:110px;font-size:12px}.ot-muted{color:#646970}
</style>';
	}
} );

/** The lead's agent as a name. */
function ot_leads_agent_name( $slug ) {
	return ot_leads_agents()[ $slug ]['name'] ?? ( $slug ?: 'Unassigned' );
}

function ot_leads_stage_pill( $stage ) {
	return '<span class="ot-pill' . ( 'new' === $stage ? ' ot-pill--new' : '' ) . '">' . esc_html( OT_LEAD_STAGES[ $stage ] ?? $stage ) . '</span>';
}

function ot_leads_agent_select( $name, $cur, $none = 'Unassigned' ) {
	$o = '<select name="' . esc_attr( $name ) . '"><option value="">' . esc_html( $none ) . '</option>';
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
		echo '<p style="font-size:14px"><strong>' . $new . '</strong> waiting for a first reply · <strong>' . $week . '</strong> in the last 7 days · <strong>' . $queue . '</strong> emails to approve</p><p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=ot-leads' ) ) . '">Open the inbox</a> <a class="button" href="' . esc_url( admin_url( 'admin.php?page=ot-leads-queue' ) ) . '">Approval queue</a></p>'; // phpcs:ignore WordPress.Security.EscapeOutput
	} );
} );
