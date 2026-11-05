<?php
/**
 * Show or hide community guides. The team may not want every researched town on the site yet;
 * a hidden guide keeps its record and comes back with one click.
 *
 * While a guide is hidden (meta ot_hidden = 1; a hidden city also hides its neighborhoods):
 *   - its page returns 404, and it is left out of the sitemap and /llms.txt;
 *   - it drops out of the community index, region counts, cards, the market table, town stats,
 *     "nearby" and "more guides" links, and the city's neighborhood list;
 *   - links to it in list blocks (the footer's town list) are removed.
 * Re-running the importer never changes this switch.
 *
 * Communities > Show or hide guides is the screen; the community list also shows the state.
 */

defined( 'ABSPATH' ) || exit;

function ot_community_hidden( $post_id ) {
	return (bool) get_post_meta( $post_id, 'ot_hidden', true );
}

/** True when the guide or its city is hidden. */
function ot_community_off( $post_id ) {
	if ( ot_community_hidden( $post_id ) ) {
		return true;
	}
	$parent = (int) wp_get_post_parent_id( $post_id );
	return $parent ? ot_community_hidden( $parent ) : false;
}

/** IDs of every hidden guide, including neighborhoods of hidden cities. */
function ot_hidden_community_ids() {
	static $ids = null;
	if ( null !== $ids ) {
		return $ids;
	}
	$ids   = array();
	$posts = get_posts( array( 'post_type' => 'ot_community', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true ) );
	foreach ( $posts as $id ) {
		if ( ot_community_off( $id ) ) {
			$ids[] = (int) $id;
		}
	}
	return $ids;
}

add_action( 'init', function () {
	register_post_meta( 'ot_community', 'ot_hidden', array(
		'type' => 'boolean', 'single' => true, 'show_in_rest' => true, 'default' => false,
		'auth_callback' => fn() => current_user_can( 'edit_posts' ),
	) );
} );

/* --------------------------------------------------------------------- front end */

add_action( 'template_redirect', function () {
	if ( is_singular( 'ot_community' ) && ot_community_off( get_queried_object_id() ) && ! current_user_can( 'edit_posts' ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}, 1 );

add_filter( 'wp_sitemaps_posts_query_args', function ( $args, $post_type ) {
	if ( 'ot_community' === $post_type && ot_hidden_community_ids() ) {
		$args['post__not_in'] = array_merge( $args['post__not_in'] ?? array(), ot_hidden_community_ids() );
	}
	return $args;
}, 10, 2 );

/** Drop list items that link to a hidden guide (the footer's town list and the like). */
add_filter( 'render_block', function ( $html, $block ) {
	if ( 'core/list-item' !== ( $block['blockName'] ?? '' ) || is_admin() || ! ot_hidden_community_ids() ) {
		return $html;
	}
	foreach ( ot_hidden_community_ids() as $id ) {
		$url = get_permalink( $id );
		if ( $url && ( str_contains( $html, 'href="' . $url . '"' ) || str_contains( $html, 'href="' . esc_url( $url ) . '"' ) ) ) {
			return '';
		}
	}
	return $html;
}, 10, 2 );

/* --------------------------------------------------------------------- admin */

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=ot_community', 'Show or hide guides', 'Show or hide guides', 'edit_others_posts', 'ot-guides', 'ot_guides_screen' );
} );

function ot_guides_screen() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}
	$towns = get_posts( array( 'post_type' => 'ot_community', 'post_status' => 'publish', 'post_parent' => 0, 'posts_per_page' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) );
	if ( isset( $_POST['ot_guides_save'] ) && check_admin_referer( 'ot_guides' ) ) {
		$on = array_map( 'intval', (array) ( $_POST['ot_on'] ?? array() ) );
		$all = get_posts( array( 'post_type' => 'ot_community', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids' ) );
		foreach ( $all as $id ) {
			if ( in_array( (int) $id, $on, true ) ) {
				delete_post_meta( $id, 'ot_hidden' );
			} else {
				update_post_meta( $id, 'ot_hidden', 1 );
			}
		}
		echo '<div class="notice notice-success"><p>Saved. Purge the page caches (Breeze, then Cloudflare) so visitors see the change.</p></div>';
	}
	$regions = array();
	foreach ( ot_regions() as $r ) {
		$regions[ $r['key'] ] = $r['name'];
	}
	$by_region = array();
	foreach ( $towns as $t ) {
		$rec = json_decode( (string) get_post_meta( $t->ID, 'ot_record', true ), true ) ?: array();
		$by_region[ $rec['region_key'] ?? 'other' ][] = $t;
	}
	echo '<div class="wrap"><h1>Show or hide guides</h1>';
	echo '<p>Untick a guide to take it off the site. Its page stops being public (it returns "not found" and leaves the sitemap), and it drops out of the guide index, cards, market table and links from other guides. Nothing is deleted: tick it again to bring it back. Hiding a city also hides its neighborhood guides. You can still preview a hidden guide while logged in.</p>';
	echo '<form method="post">';
	wp_nonce_field( 'ot_guides' );
	echo '<p><button type="button" class="button" onclick="document.querySelectorAll(\'.ot-on\').forEach(function(c){c.checked=true})">Show all</button> <button type="button" class="button" onclick="document.querySelectorAll(\'.ot-on\').forEach(function(c){c.checked=false})">Hide all</button></p>';
	foreach ( $regions + array( 'other' => 'Other' ) as $key => $name ) {
		if ( empty( $by_region[ $key ] ) ) {
			continue;
		}
		echo '<h2 style="margin-top:1.5rem">' . esc_html( $name ) . '</h2><table class="widefat striped" style="max-width:48rem"><tbody>';
		foreach ( $by_region[ $key ] as $t ) {
			$kids = get_posts( array( 'post_type' => 'ot_community', 'post_status' => 'publish', 'post_parent' => $t->ID, 'posts_per_page' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) );
			printf(
				'<tr><td><label><input class="ot-on" type="checkbox" name="ot_on[]" value="%d"%s> <strong>%s</strong></label>%s</td><td style="width:8rem"><a href="%s">View</a> · <a href="%s">Edit</a></td></tr>',
				$t->ID, checked( ! ot_community_hidden( $t->ID ), true, false ), esc_html( $t->post_title ),
				$kids ? ' <span class="description">(' . count( $kids ) . ' neighborhood guides)</span>' : '',
				esc_url( get_permalink( $t ) ), esc_url( get_edit_post_link( $t->ID ) )
			);
			foreach ( $kids as $k ) {
				printf(
					'<tr><td style="padding-left:2.5rem"><label><input class="ot-on" type="checkbox" name="ot_on[]" value="%d"%s> %s</label></td><td><a href="%s">View</a> · <a href="%s">Edit</a></td></tr>',
					$k->ID, checked( ! ot_community_hidden( $k->ID ), true, false ), esc_html( $k->post_title ),
					esc_url( get_permalink( $k ) ), esc_url( get_edit_post_link( $k->ID ) )
				);
			}
		}
		echo '</tbody></table>';
	}
	submit_button( 'Save', 'primary', 'ot_guides_save' );
	echo '</form></div>';
}

/** "On site" column in the community list. */
add_filter( 'manage_ot_community_posts_columns', function ( $cols ) {
	$cols['ot_on_site'] = 'On site';
	return $cols;
} );
add_action( 'manage_ot_community_posts_custom_column', function ( $col, $id ) {
	if ( 'ot_on_site' === $col ) {
		echo ot_community_off( $id ) ? '<span style="color:#b32d2e">Hidden</span>' : 'Shown';
	}
}, 10, 2 );
