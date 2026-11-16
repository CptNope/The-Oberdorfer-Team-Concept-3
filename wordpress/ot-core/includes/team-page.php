<?php
/**
 * The joint page for Brandon and Kaitlyn (/team/brandon-kaitlyn-oberdorfer/).
 *
 * Until v0.10.12 it was an entry in Agents, which made it look like a third agent in the
 * Agents list and in the agent pickers. It is now an ordinary page under Our Team that looks
 * exactly the same (the theme's page-brandon-kaitlyn-oberdorfer template). This file moves an
 * existing site's joint profile across once, keeping its introduction, record and search
 * title, and points the old address at the new one.
 */

defined( 'ABSPATH' ) || exit;

/** Move the old joint profile (an ot_agent) to its page, once. Returns the page ID or 0. */
function ot_move_team_profile() {
	$old = get_posts( array( 'post_type' => 'ot_agent', 'name' => OT_TEAM_PAGE, 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ), 'posts_per_page' => 1, 'no_found_rows' => true ) );
	$old = $old ? $old[0] : null;
	$page = ot_team_page();
	if ( ! $old ) {
		return $page ? $page->ID : 0;
	}
	if ( ! $page ) {
		$team = get_page_by_path( 'team' );
		$id   = wp_insert_post( wp_slash( array(
			'post_type'      => 'page',
			'post_name'      => OT_TEAM_PAGE,
			'post_title'     => $old->post_title,
			'post_content'   => $old->post_content,
			'post_status'    => 'publish',
			'post_parent'    => $team ? $team->ID : 0,
			'menu_order'     => 0,
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		) ), true );
		if ( is_wp_error( $id ) ) {
			return 0;
		}
		foreach ( array( 'ot_record', 'ot_seo_title', 'ot_seo_description' ) as $k ) {
			foreach ( array( $k, '_ot_import_' . $k ) as $key ) {
				$v = get_post_meta( $old->ID, $key, true );
				if ( '' !== (string) $v ) {
					update_post_meta( $id, $key, wp_slash( $v ) );
				}
			}
		}
		$page = get_post( $id );
	} elseif ( '' === trim( (string) $page->post_content ) && '' !== trim( (string) $old->post_content ) ) {
		// The page came from the importer: keep the introduction the team wrote on the old profile.
		wp_update_post( wp_slash( array( 'ID' => $page->ID, 'post_content' => $old->post_content ) ) );
	}
	wp_trash_post( $old->ID ); // in Trash, not deleted: it can be restored
	return $page->ID;
}

add_action( 'admin_init', function () {
	if ( get_option( 'ot_team_page_moved' ) || ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	if ( ot_move_team_profile() ) {
		update_option( 'ot_team_page_moved', OT_CORE_VERSION, false );
	}
} );

// The old address, /agents/brandon-kaitlyn-oberdorfer/ (and its listings page), goes to the page.
add_action( 'template_redirect', function () {
	if ( ! is_404() ) {
		return;
	}
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( preg_match( '#(^|/)agents/' . OT_TEAM_PAGE . '(/listings)?$#', $path ) && ot_team_page() ) {
		wp_safe_redirect( ot_team_page_url(), 301 );
		exit;
	}
} );

// What the page is, above the editor: the team writes its introduction there.
add_action( 'edit_form_after_title', function ( $post ) {
	if ( ot_is_team_page( $post ) ) {
		echo '<div class="notice notice-info inline" style="margin:12px 0"><p>This is the joint page for Brandon and Kaitlyn. Write its introduction in the editor below; while the editor is empty, the page shows the three paragraphs from <em>Wakefield at Home</em>. Each person\'s details are edited on their own profile under Agents.</p></div>';
	}
} );
