<?php
/**
 * Roles, privacy tools and retention (spec § 6 and § 11).
 *
 * Capabilities: ot_leads_manage (settings, sequences, routing, export, delete; team leaders),
 * ot_leads_view_all (every lead; leaders and coordinators), ot_leads_own (an agent's own leads).
 * Administrators get all three, Editors (coordinators) view_all, and the new "Team agent" role
 * own. Each WordPress user can be linked to an agent profile on their user screen.
 */

defined( 'ABSPATH' ) || exit;

function ot_leads_roles() {
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		foreach ( array( 'ot_leads_manage', 'ot_leads_view_all', 'ot_leads_own' ) as $c ) {
			$admin->add_cap( $c );
		}
	}
	$editor = get_role( 'editor' );
	if ( $editor ) {
		$editor->add_cap( 'ot_leads_view_all' );
		$editor->add_cap( 'ot_leads_own' );
	}
	if ( ! get_role( 'ot_team_agent' ) ) {
		add_role( 'ot_team_agent', 'Team agent', array( 'read' => true, 'ot_leads_own' => true, 'upload_files' => true, 'edit_posts' => true ) );
	}
}

/* --------------------------------------------------------------------- user ↔ agent */

function ot_leads_user_field( $user ) {
	if ( ! current_user_can( 'ot_leads_manage' ) ) {
		return;
	}
	$cur = (string) get_user_meta( $user->ID, 'ot_agent_slug', true );
	echo '<h2>Oberdorfer Leads</h2><table class="form-table"><tr><th><label for="ot_agent_slug">Agent profile</label></th><td><select name="ot_agent_slug" id="ot_agent_slug"><option value="">None</option>';
	foreach ( ot_leads_agents() as $slug => $a ) {
		echo '<option value="' . esc_attr( $slug ) . '"' . selected( $cur, $slug, false ) . '>' . esc_html( $a['name'] ) . '</option>';
	}
	echo '</select><p class="description">This person sees the leads assigned to this agent.</p></td></tr></table>';
}
add_action( 'show_user_profile', 'ot_leads_user_field' );
add_action( 'edit_user_profile', 'ot_leads_user_field' );
$ot_leads_save_user = function ( $id ) {
	if ( current_user_can( 'ot_leads_manage' ) && isset( $_POST['ot_agent_slug'] ) && check_admin_referer( 'update-user_' . $id ) ) {
		update_user_meta( $id, 'ot_agent_slug', sanitize_title( wp_unslash( $_POST['ot_agent_slug'] ) ) );
	}
};
add_action( 'personal_options_update', $ot_leads_save_user );
add_action( 'edit_user_profile_update', $ot_leads_save_user );

/* --------------------------------------------------------------------- export and erase */

add_filter( 'wp_privacy_personal_data_exporters', function ( $e ) {
	$e['ot-leads'] = array(
		'exporter_friendly_name' => 'Oberdorfer Leads',
		'callback'               => function ( $email ) {
			global $wpdb;
			$data = array();
			foreach ( $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . ot_leads_table( 'leads' ) . ' WHERE email = %s', strtolower( $email ) ), ARRAY_A ) as $l ) {
				$items = array();
				foreach ( array( 'first_name', 'last_name', 'email', 'phone', 'preferred', 'lead_type', 'stage', 'towns', 'topic', 'source_url', 'created_at' ) as $k ) {
					$items[] = array( 'name' => $k, 'value' => (string) $l[ $k ] );
				}
				foreach ( ot_lead_events( (int) $l['id'], 500 ) as $ev ) {
					$items[] = array( 'name' => 'event ' . $ev['created_at'] . ' ' . $ev['type'], 'value' => (string) $ev['payload'] );
				}
				$data[] = array( 'group_id' => 'ot-leads', 'group_label' => 'Inquiries to The Oberdorfer Team', 'item_id' => 'lead-' . $l['id'], 'data' => $items );
				ot_lead_event( (int) $l['id'], 'exported', array( 'by' => 'privacy export' ) );
			}
			return array( 'data' => $data, 'done' => true );
		},
	);
	return $e;
} );

add_filter( 'wp_privacy_personal_data_erasers', function ( $e ) {
	$e['ot-leads'] = array(
		'eraser_friendly_name' => 'Oberdorfer Leads',
		'callback'             => function ( $email ) {
			global $wpdb;
			$ids = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . ot_leads_table( 'leads' ) . ' WHERE email = %s', strtolower( $email ) ) );
			foreach ( $ids as $id ) {
				ot_leads_erase( (int) $id );
			}
			return array( 'items_removed' => count( $ids ), 'items_retained' => false, 'messages' => array( 'Consent and unsubscribe records are kept as proof, as the law requires.' ), 'done' => true );
		},
	);
	return $e;
} );

/** Delete a lead and everything about it, except consent and suppression records (kept as proof). */
function ot_leads_erase( $id ) {
	global $wpdb;
	foreach ( array( 'lead_events', 'tasks', 'enrollments', 'messages', 'outbox' ) as $t ) {
		$wpdb->delete( ot_leads_table( $t ), array( 'lead_id' => $id ) );
	}
	$wpdb->delete( ot_leads_table( 'leads' ), array( 'id' => $id ) );
	delete_option( 'ot_ai_sum_' . $id );
}

/** Unconverted leads with no activity for the retention period are deleted. */
function ot_leads_retention() {
	global $wpdb;
	$months = max( 6, (int) ot_leads_opt( 'retention_months' ) );
	$cut    = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $months . ' months' ) );
	$ids    = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . ot_leads_table( 'leads' ) . " WHERE last_activity_at < %s AND stage NOT IN ('active','under_agreement','closed')", $cut ) );
	foreach ( $ids as $id ) {
		ot_leads_erase( (int) $id );
	}
	if ( $ids ) {
		update_option( 'ot_leads_last_purge', array( 'at' => ot_leads_now(), 'count' => count( $ids ) ), false );
	}
}
