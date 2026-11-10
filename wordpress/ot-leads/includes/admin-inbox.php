<?php
/**
 * Leads > Inbox: every lead the user may see, with saved views (New today, Mine, Unassigned,
 * Warm), filters by type, stage and agent, search, bulk assign or move, and CSV export.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class OT_Leads_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct( array( 'singular' => 'lead', 'plural' => 'leads', 'ajax' => false ) );
	}

	public function get_columns() {
		return array( 'cb' => '<input type="checkbox">', 'name' => 'Name', 'lead_type' => 'Type', 'about' => 'About', 'assigned_agent' => 'Agent', 'stage' => 'Stage', 'intent' => 'Intent', 'created_at' => 'Received' );
	}

	protected function get_sortable_columns() {
		return array( 'name' => array( 'first_name', false ), 'created_at' => array( 'created_at', true ), 'stage' => array( 'stage', false ) );
	}

	/** The WHERE clause for the current view, filters and search. */
	public static function where() {
		global $wpdb;
		$w = array( '1=1' );
		if ( ! current_user_can( 'ot_leads_view_all' ) ) {
			$w[] = $wpdb->prepare( 'assigned_agent = %s', ot_leads_user_agent() );
		}
		switch ( sanitize_key( $_GET['view'] ?? '' ) ) {
			case 'today':
				$w[] = $wpdb->prepare( 'created_at >= %s', ( new DateTime( 'today', new DateTimeZone( 'America/New_York' ) ) )->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ) );
				break;
			case 'new':
				$w[] = "stage = 'new'";
				break;
			case 'mine':
				$w[] = $wpdb->prepare( 'assigned_agent = %s', ot_leads_user_agent() );
				break;
			case 'unassigned':
				$w[] = "assigned_agent = ''";
				break;
			case 'warm':
				$w[] = $wpdb->prepare( 'id IN (SELECT lead_id FROM ' . ot_leads_table( 'lead_events' ) . " WHERE created_at >= %s AND type IN ('form','page_view','reply','click') GROUP BY lead_id HAVING SUM(CASE WHEN type IN ('form','reply') THEN 3 ELSE 1 END) >= 4)", gmdate( 'Y-m-d H:i:s', time() - 14 * DAY_IN_SECONDS ) );
				break;
		}
		foreach ( array( 'lead_type', 'stage', 'assigned_agent' ) as $k ) {
			if ( 'assigned_agent' === $k && 'team' === ( $_GET['f_assigned_agent'] ?? '' ) ) {
				$w[] = "assigned_agent = ''";
			} elseif ( ! empty( $_GET[ 'f_' . $k ] ) ) {
				$w[] = $wpdb->prepare( "$k = %s", sanitize_text_field( wp_unslash( $_GET[ 'f_' . $k ] ) ) );
			}
		}
		if ( ! empty( $_GET['s'] ) ) {
			$s   = '%' . $wpdb->esc_like( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) . '%';
			$w[] = $wpdb->prepare( '(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR phone LIKE %s OR towns LIKE %s)', $s, $s, $s, $s, $s );
		}
		return implode( ' AND ', $w );
	}

	public function prepare_items() {
		global $wpdb;
		$t       = ot_leads_table( 'leads' );
		$per     = 30;
		$page    = $this->get_pagenum();
		$order   = in_array( $_GET['orderby'] ?? '', array( 'first_name', 'created_at', 'stage' ), true ) ? $_GET['orderby'] : 'created_at';
		$dir     = 'asc' === strtolower( $_GET['order'] ?? '' ) ? 'ASC' : 'DESC';
		$where   = self::where();
		$total   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE $where" ); // phpcs:ignore WordPress.DB.PreparedSQL
		$this->items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t WHERE $where ORDER BY $order $dir LIMIT %d OFFSET %d", $per, ( $page - 1 ) * $per ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		$this->set_pagination_args( array( 'total_items' => $total, 'per_page' => $per ) );
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
	}

	protected function get_views() {
		$cur   = sanitize_key( $_GET['view'] ?? '' );
		$base  = admin_url( 'admin.php?page=ot-leads' );
		$views = array( '' => 'All', 'today' => 'New today', 'new' => 'Waiting for a reply', 'warm' => 'Warm' );
		if ( ot_leads_user_agent() ) {
			$views['mine'] = 'Mine';
		}
		if ( current_user_can( 'ot_leads_view_all' ) ) {
			$views['unassigned'] = 'Team inbox';
		}
		$out = array();
		foreach ( $views as $k => $label ) {
			$out[ $k ?: 'all' ] = '<a href="' . esc_url( $k ? add_query_arg( 'view', $k, $base ) : $base ) . '"' . ( $cur === $k ? ' class="current" aria-current="page"' : '' ) . '>' . esc_html( $label ) . '</a>';
		}
		return $out;
	}

	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		echo '<div class="alignleft actions">';
		echo '<select name="f_lead_type"><option value="">All types</option>';
		foreach ( OT_LEAD_TYPES as $k => $v ) {
			echo '<option value="' . esc_attr( $k ) . '"' . selected( $_GET['f_lead_type'] ?? '', $k, false ) . '>' . esc_html( $v ) . '</option>';
		}
		echo '</select><select name="f_stage"><option value="">All stages</option>';
		foreach ( OT_LEAD_STAGES as $k => $v ) {
			echo '<option value="' . esc_attr( $k ) . '"' . selected( $_GET['f_stage'] ?? '', $k, false ) . '>' . esc_html( $v ) . '</option>';
		}
		echo '</select>';
		if ( current_user_can( 'ot_leads_view_all' ) ) {
			echo ot_leads_agent_select( 'f_assigned_agent', sanitize_text_field( wp_unslash( $_GET['f_assigned_agent'] ?? '' ) ), 'All agents', true ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		submit_button( 'Filter', '', 'filter_action', false );
		echo '</div>';
	}

	protected function get_bulk_actions() {
		$a = array( 'bulk_stage_contacted' => 'Move to Contacted', 'bulk_stage_nurture' => 'Move to Nurture', 'bulk_stage_lost' => 'Move to Lost' );
		if ( current_user_can( 'ot_leads_view_all' ) ) {
			foreach ( ot_leads_agents() as $slug => $ag ) {
				$a[ 'bulk_assign_' . $slug ] = 'Assign to ' . $ag['name'];
			}
		}
		if ( current_user_can( 'ot_leads_manage' ) ) {
			$a['bulk_csv'] = 'Export to CSV';
		}
		return $a;
	}

	protected function column_cb( $item ) {
		return '<input type="checkbox" name="ids[]" value="' . (int) $item['id'] . '">';
	}

	protected function column_name( $item ) {
		$url = ot_leads_admin_link( $item['id'] );
		return '<strong><a class="row-title" href="' . esc_url( $url ) . '">' . esc_html( ot_lead_name( $item ) ) . '</a></strong><br><span class="ot-muted">' . esc_html( $item['email'] ) . ( $item['phone'] ? ' · ' . esc_html( $item['phone'] ) : '' ) . '</span>';
	}

	protected function column_default( $item, $col ) {
		switch ( $col ) {
			case 'lead_type':
				return esc_html( OT_LEAD_TYPES[ $item['lead_type'] ] ?? $item['lead_type'] );
			case 'about':
				return $item['listing_id'] ? esc_html( get_the_title( $item['listing_id'] ) ) : esc_html( ot_leads_towns_label( $item['towns'] ) ?: $item['topic'] );
			case 'assigned_agent':
				return esc_html( ot_leads_agent_name( $item['assigned_agent'] ) );
			case 'stage':
				return ot_leads_stage_pill( $item['stage'] ) . ( 'new' === $item['stage'] && ! $item['contacted_at'] && strtotime( $item['created_at'] . ' UTC' ) < time() - (int) ot_leads_opt( 'response_minutes' ) * 60 ? ' <span class="ot-pill ot-pill--bad">Waiting</span>' : '' );
			case 'intent':
				$i = ot_lead_intent( (int) $item['id'] );
				return '<span class="ot-pill' . ( 'Warm' === $i[0] ? ' ot-pill--warm' : '' ) . '" title="' . esc_attr( $i[1] ) . '">' . esc_html( $i[0] ) . '</span>';
			case 'created_at':
				return esc_html( ot_leads_time( $item['created_at'] ) ) . '<br><span class="ot-muted">' . esc_html( human_time_diff( strtotime( $item['created_at'] . ' UTC' ) ) ) . ' ago</span>';
		}
		return '';
	}

	public function no_items() {
		echo 'No leads here yet. Every form on the site saves into this inbox.';
	}
}

/** Bulk actions and CSV export (before any output). */
add_action( 'admin_init', function () {
	if ( 'ot-leads' !== ( $_GET['page'] ?? '' ) || empty( $_REQUEST['ids'] ) ) {
		return;
	}
	$action = sanitize_key( ( $_REQUEST['action'] ?? '-1' ) !== '-1' ? $_REQUEST['action'] : ( $_REQUEST['action2'] ?? '' ) );
	if ( ! str_starts_with( $action, 'bulk_' ) ) {
		return;
	}
	check_admin_referer( 'bulk-leads' );
	$ids   = array_map( 'intval', (array) $_REQUEST['ids'] );
	$leads = array_filter( array_map( 'ot_lead', $ids ), 'ot_leads_can_see' );
	$me    = wp_get_current_user()->user_login;
	if ( 'bulk_csv' === $action && current_user_can( 'ot_leads_manage' ) ) {
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="leads-' . gmdate( 'Y-m-d' ) . '.csv"' );
		$out  = fopen( 'php://output', 'w' );
		$cols = array( 'id', 'created_at', 'first_name', 'last_name', 'email', 'phone', 'preferred', 'lead_type', 'stage', 'assigned_agent', 'towns', 'topic', 'source_url' );
		fputcsv( $out, array_merge( $cols, array( 'email_consent' ) ), ',', '"', '' );
		foreach ( $leads as $l ) {
			// Cells starting with = + - @ are prefixed so a spreadsheet never runs them as formulas.
			$row = array_map( fn( $c ) => preg_match( '/^[=+\-@]/', (string) $l[ $c ] ) ? "'" . $l[ $c ] : $l[ $c ], $cols );
			fputcsv( $out, array_merge( $row, array( ot_lead_has_consent( $l['email'] ) ? 'yes' : 'no' ) ), ',', '"', '' );
			ot_lead_event( (int) $l['id'], 'exported', array( 'format' => 'csv' ), $me );
		}
		exit;
	}
	foreach ( $leads as $l ) {
		if ( str_starts_with( $action, 'bulk_stage_' ) ) {
			ot_lead_update( (int) $l['id'], array( 'stage' => substr( $action, 11 ) ), $me );
		} elseif ( str_starts_with( $action, 'bulk_assign_' ) && current_user_can( 'ot_leads_view_all' ) ) {
			ot_lead_update( (int) $l['id'], array( 'assigned_agent' => substr( $action, 12 ) ), $me );
		}
	}
	wp_safe_redirect( add_query_arg( 'ot_msg', rawurlencode( count( $leads ) . ' leads updated.' ), remove_query_arg( array( 'ids', 'action', 'action2', '_wpnonce', '_wp_http_referer' ) ) ) );
	exit;
} );

function ot_leads_inbox_screen() {
	$table = new OT_Leads_Table();
	$table->prepare_items();
	ot_leads_shell( 'inbox' );
	echo '<div class="wrap ot-wrap">';
	ot_leads_head( 'All leads', 'Everyone who has sent a form or signed up, newest first. Search by name, email, phone or town.' );
	if ( ! current_user_can( 'ot_leads_view_all' ) && ! ot_leads_user_agent() ) {
		echo '<div class="notice notice-warning"><p>Your account is not linked to an agent profile yet, so no leads show. A team leader can link it under Users > your name > Agent profile.</p></div>';
	}
	ot_leads_notice();
	echo '<form method="get"><input type="hidden" name="page" value="ot-leads">';
	if ( ! empty( $_GET['view'] ) ) {
		echo '<input type="hidden" name="view" value="' . esc_attr( sanitize_key( $_GET['view'] ) ) . '">';
	}
	$table->views();
	$table->search_box( 'Search leads', 'ot-leads' );
	$table->display();
	echo '</form></div>';
	ot_leads_shell_end();
}
