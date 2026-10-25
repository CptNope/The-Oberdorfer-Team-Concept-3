<?php
/**
 * Importer: creates or updates, by slug, everything in data/content.json (built from the
 * researched records by tools/build_wp.py): regions, community guides (towns, then their
 * neighborhoods as children), agents, the office and the site's pages; then sets the static
 * front page, the posts page and pretty permalinks. Safe to run again: it updates in place
 * and never deletes. Post content written by the team in the editor is left alone.
 *
 *   Tools > Import Oberdorfer content
 *   wp ot import [--file=<path>]
 */

defined( 'ABSPATH' ) || exit;

function ot_import_file() {
	return OT_CORE_DIR . '/data/content.json';
}

/** Find a post by slug, type and parent (pages and communities are hierarchical). */
function ot_find_post( $type, $slug, $parent = 0 ) {
	$found = get_posts( array(
		'post_type'      => $type,
		'name'           => $slug,
		'post_parent'    => $parent,
		'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
		'posts_per_page' => 1,
		'no_found_rows'  => true,
	) );
	return $found ? $found[0] : null;
}

function ot_upsert( $type, $slug, $title, $parent, $order, $meta, $excerpt = '' ) {
	$existing = ot_find_post( $type, $slug, $parent );
	$data     = array(
		'post_type'   => $type,
		'post_name'   => $slug,
		'post_title'  => $title,
		'post_status' => 'publish',
		'post_parent' => $parent,
		'menu_order'  => $order,
	);
	if ( $excerpt ) {
		$data['post_excerpt'] = $excerpt;
	}
	if ( $existing ) {
		$data['ID'] = $existing->ID;
		$id         = wp_update_post( wp_slash( $data ), true );
		$created    = false;
	} else {
		$data['post_content'] = '';
		$id                   = wp_insert_post( wp_slash( $data ), true );
		$created              = true;
	}
	if ( is_wp_error( $id ) ) {
		return $id;
	}
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, is_array( $v ) ? wp_slash( wp_json_encode( $v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) : wp_slash( $v ) );
	}
	return array( $id, $created );
}

/** Run the import. Returns a summary array, or WP_Error. */
function ot_import( $file = null ) {
	$file = $file ?: ot_import_file();
	if ( ! is_readable( $file ) ) {
		return new WP_Error( 'ot_import_missing', 'Content file not found: ' . $file );
	}
	$data = json_decode( (string) file_get_contents( $file ), true );
	if ( ! is_array( $data ) ) {
		return new WP_Error( 'ot_import_json', 'Content file is not valid JSON.' );
	}
	$sum = array( 'created' => 0, 'updated' => 0, 'errors' => array() );
	$tally = function ( $res, $label ) use ( &$sum ) {
		if ( is_wp_error( $res ) ) {
			$sum['errors'][] = $label . ': ' . $res->get_error_message();
			return 0;
		}
		$sum[ $res[1] ? 'created' : 'updated' ]++;
		return $res[0];
	};

	// Regions.
	foreach ( $data['regions'] ?? array() as $r ) {
		$term = term_exists( $r['key'], 'ot_region' );
		if ( ! $term ) {
			$term = wp_insert_term( $r['name'], 'ot_region', array( 'slug' => $r['key'] ) );
		} else {
			wp_update_term( (int) $term['term_id'], 'ot_region', array( 'name' => $r['name'] ) );
		}
		if ( ! is_wp_error( $term ) ) {
			update_term_meta( (int) $term['term_id'], 'ot_order', (int) $r['order'] );
			update_term_meta( (int) $term['term_id'], 'ot_blurb', $r['blurb'] );
		}
	}

	// Communities: parents first, so neighborhoods can find their city.
	$ids   = array();
	$comms = $data['communities'] ?? array();
	usort( $comms, fn( $a, $b ) => (int) ! empty( $a['parent'] ) <=> (int) ! empty( $b['parent'] ) );
	foreach ( $comms as $c ) {
		$parent = ! empty( $c['parent'] ) ? (int) ( $ids[ $c['parent'] ] ?? 0 ) : 0;
		$id     = $tally( ot_upsert( 'ot_community', $c['slug'], $c['title'], $parent, (int) $c['order'], $c['meta'], $c['excerpt'] ?? '' ), 'community ' . $c['slug'] );
		if ( $id ) {
			if ( ! $parent ) {
				$ids[ $c['slug'] ] = $id;
			}
			if ( ! empty( $c['region'] ) ) {
				wp_set_object_terms( $id, $c['region'], 'ot_region' );
			}
		}
	}

	foreach ( $data['agents'] ?? array() as $a ) {
		$tally( ot_upsert( 'ot_agent', $a['slug'], $a['title'], 0, (int) $a['order'], $a['meta'], $a['excerpt'] ?? '' ), 'agent ' . $a['slug'] );
	}
	foreach ( $data['offices'] ?? array() as $o ) {
		$tally( ot_upsert( 'ot_office', $o['slug'], $o['title'], 0, 0, $o['meta'], $o['excerpt'] ?? '' ), 'office ' . $o['slug'] );
	}

	$page_ids = array();
	foreach ( $data['pages'] ?? array() as $p ) {
		$page_ids[ $p['slug'] ] = $tally( ot_upsert( 'page', $p['slug'], $p['title'], 0, (int) $p['order'], $p['meta'] ), 'page ' . $p['slug'] );
	}
	if ( ! empty( $data['front_page'] ) && ! empty( $page_ids[ $data['front_page'] ] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_ids[ $data['front_page'] ] );
	}
	if ( ! empty( $data['posts_page'] ) && ! empty( $page_ids[ $data['posts_page'] ] ) ) {
		update_option( 'page_for_posts', $page_ids[ $data['posts_page'] ] );
	}
	if ( '/%postname%/' !== get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}
	update_option( 'ot_core_imported', array( 'version' => $data['version'] ?? '', 'at' => gmdate( 'c' ) ), false );
	flush_rewrite_rules( false );
	return $sum;
}

/* --------------------------------------------------------------------- admin screen */

add_action( 'admin_menu', function () {
	add_management_page( 'Import Oberdorfer content', 'Import Oberdorfer content', 'manage_options', 'ot-import', 'ot_import_screen' );
} );

function ot_import_screen() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$result = null;
	if ( isset( $_POST['ot_import'] ) && check_admin_referer( 'ot_import' ) ) {
		$result = ot_import();
	}
	$data = json_decode( (string) @file_get_contents( ot_import_file() ), true ) ?: array();
	$last = get_option( 'ot_core_imported' );
	echo '<div class="wrap"><h1>Import Oberdorfer content</h1>';
	if ( $result ) {
		if ( is_wp_error( $result ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
		} else {
			echo '<div class="notice notice-success"><p>' . esc_html( sprintf( 'Done: %d created, %d updated.', $result['created'], $result['updated'] ) ) . '</p>';
			foreach ( $result['errors'] as $e ) {
				echo '<p>' . esc_html( $e ) . '</p>';
			}
			echo '</div>';
		}
	}
	echo '<p>Creates or updates, by slug, the researched community guides, the agent profiles, the Worcester office and the site\'s pages, then sets the front page, the journal page and pretty permalinks. Running it again updates the same items in place. Nothing is deleted, and text the team has written in a page or profile body is kept.</p>';
	echo '<table class="widefat striped" style="max-width:40rem"><tbody>';
	printf( '<tr><th>Content snapshot</th><td>%s</td></tr>', esc_html( $data['version'] ?? 'not found' ) );
	printf( '<tr><th>Community guides</th><td>%d</td></tr>', count( $data['communities'] ?? array() ) );
	printf( '<tr><th>Agents</th><td>%d</td></tr>', count( $data['agents'] ?? array() ) );
	printf( '<tr><th>Pages</th><td>%d</td></tr>', count( $data['pages'] ?? array() ) );
	printf( '<tr><th>Last import</th><td>%s</td></tr>', esc_html( $last ? $last['at'] . ' (' . $last['version'] . ')' : 'never' ) );
	echo '</tbody></table><form method="post" style="margin-top:1.5rem">';
	wp_nonce_field( 'ot_import' );
	submit_button( 'Import now', 'primary', 'ot_import' );
	echo '</form></div>';
}

/* --------------------------------------------------------------------- WP-CLI */

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'ot import', function ( $args, $assoc ) {
		$res = ot_import( $assoc['file'] ?? null );
		if ( is_wp_error( $res ) ) {
			WP_CLI::error( $res->get_error_message() );
		}
		foreach ( $res['errors'] as $e ) {
			WP_CLI::warning( $e );
		}
		WP_CLI::success( sprintf( '%d created, %d updated.', $res['created'], $res['updated'] ) );
	}, array( 'shortdesc' => 'Import the Oberdorfer Team content snapshot (communities, agents, office, pages).' ) );
}
