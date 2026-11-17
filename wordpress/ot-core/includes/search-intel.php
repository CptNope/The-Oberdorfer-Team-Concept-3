<?php
/**
 * JAT Search Intel (github.com/CptNope/JAT-Search-Intel) prints the site's search output when it
 * is active (decided 2026-11-16): titles, meta descriptions, canonicals, Open Graph, the JSON-LD
 * graph, the XML sitemap and /llms.txt. ot-core then stops printing its own (seo.php) and gives
 * Search Intel what only ot-core knows:
 *
 *   - the team as a RealEstateAgent, with its office, phone and REWAP, on Search Intel's
 *     Organization node; the page-specific nodes (agent ProfilePage and Person, the joint page's
 *     AboutPage, town guides' City, listings, the market Dataset, the guide index) and the site's
 *     own breadcrumb trail, through the `jat_search_intel_schema_graph` filter;
 *   - each page's search title and description (ot_seo_title, ot_seo_description), copied once
 *     into Search Intel's fields, where the team then edits them;
 *   - hidden town guides, marked noindex so they leave its sitemap;
 *   - the curated /llms.txt text (ot_llms_txt()).
 *
 * An agent's listings page (/agents/<slug>/listings/) is a view of the profile that Search
 * Intel cannot tell apart from the profile, so there ot-core keeps its own title, description,
 * canonical and Open Graph, and Search Intel's are removed.
 */

defined( 'ABSPATH' ) || exit;

function ot_si_active() {
	return defined( 'JAT_SEARCH_INTEL_VERSION' );
}

/* --------------------------------------------------------------------- the graph */

add_filter( 'jat_search_intel_schema_graph', 'ot_si_graph', 20, 2 );

/**
 * Add ot-core's knowledge to Search Intel's graph. Nodes that share an @id are merged after
 * this filter, so a page node given the page's @id extends Search Intel's WebPage.
 */
function ot_si_graph( $nodes, $context = array() ) {
	$page_id = (string) ( $context['page_id'] ?? '' );
	$pub_id  = (string) ( $context['publisher_id'] ?? '' );
	$ours    = ot_jsonld_graph();
	if ( ot_is_agent_listings() ) {
		// Search Intel describes the profile here; this page is its listings view, with its own URL.
		$view  = ot_agent_listings_url( get_queried_object_id() );
		$nodes = array_map( function ( $n ) use ( $page_id, $view ) {
			foreach ( array( '@id', 'url' ) as $k ) {
				if ( isset( $n[ $k ] ) && is_string( $n[ $k ] ) && str_starts_with( $n[ $k ], $page_id ) && ! str_starts_with( $n[ $k ], $page_id . '#primaryimage' ) ) {
					$n[ $k ] = $view . substr( $n[ $k ], strlen( $page_id ) );
				}
			}
			if ( isset( $n['breadcrumb']['@id'] ) ) {
				$n['breadcrumb']['@id'] = $view . '#breadcrumb';
			}
			if ( isset( $n['name'] ) && ( $n['@id'] ?? '' ) === $view ) {
				$n['name'] = ot_agent_listings_seo()['title'];
			}
			return $n;
		}, $nodes );
		$page_id = $view;
	}
	if ( is_singular( 'ot_listing' ) ) {
		$ours[] = ot_listing_jsonld( get_queried_object_id() );
	}
	// Our ids point at the team (OT_SITE_URL#team) and at each page as URL#page: use Search Intel's.
	$map = function ( $v ) use ( &$map, $pub_id ) {
		if ( is_array( $v ) ) {
			if ( isset( $v['@id'] ) && is_string( $v['@id'] ) ) {
				if ( OT_SITE_URL . '#team' === $v['@id'] && $pub_id ) {
					$v['@id'] = $pub_id;
				} elseif ( str_ends_with( $v['@id'], '#page' ) ) {
					$v['@id'] = substr( $v['@id'], 0, -5 );
				}
			}
			foreach ( $v as $k => $x ) {
				$v[ $k ] = $map( $x );
			}
		}
		return $v;
	};

	// 1. The team: Search Intel's Organization becomes the RealEstateAgent, with its details.
	$team = $map( array_shift( $ours ) );
	unset( $team['@context'], $team['@id'], $team['name'], $team['url'] );
	foreach ( $nodes as $i => $n ) {
		if ( $pub_id && ( $n['@id'] ?? '' ) === $pub_id ) {
			$type = $team['@type'];
			unset( $team['@type'] );
			if ( ! empty( $n['logo'] ) ) {
				unset( $team['logo'] );
			}
			$n['sameAs'] = array_values( array_unique( array_merge( (array) ( $n['sameAs'] ?? array() ), (array) ( $team['sameAs'] ?? array() ) ) ) );
			unset( $team['sameAs'] );
			$nodes[ $i ] = array_merge( $n, $team, array( '@type' => $type ) );
		}
	}

	$author_ids = array();
	foreach ( $ours as $node ) {
		$node = $map( $node );
		unset( $node['@context'] );
		$type = (string) $node['@type'];
		if ( 'BreadcrumbList' === $type ) {
			// 2. The site's own trail (Home › Communities › Worcester), not a generic one; added
			// where Search Intel has none (the guide index, the journal).
			$found = false;
			foreach ( $nodes as $i => $n ) {
				if ( 'BreadcrumbList' === ( $n['@type'] ?? '' ) ) {
					$nodes[ $i ]['itemListElement'] = $node['itemListElement'];
					$found = true;
				}
			}
			if ( ! $found ) {
				$node['@id'] = $page_id . '#breadcrumb';
				$nodes[]     = $node;
				foreach ( $nodes as $i => $n ) {
					if ( ( $n['@id'] ?? '' ) === $page_id ) {
						$nodes[ $i ]['breadcrumb'] = array( '@id' => $node['@id'] );
					}
				}
			}
			continue;
		}
		if ( in_array( $type, array( 'WebPage', 'ProfilePage', 'AboutPage', 'CollectionPage' ), true ) ) {
			// 3. The page itself: same @id as Search Intel's WebPage, so the two merge.
			$node['@id'] = $page_id;
		} else {
			$node['@id'] = $page_id . '#' . strtolower( $type );
		}
		$nodes[] = $node;
	}

	// 4. Pages, guides and profiles are the team's, not the WordPress account that saved them.
	if ( is_singular() && ! is_singular( 'post' ) ) {
		foreach ( $nodes as $i => $n ) {
			if ( isset( $n['author']['@id'] ) ) {
				$author_ids[] = $n['author']['@id'];
				unset( $nodes[ $i ]['author'] );
			}
		}
		$nodes = array_values( array_filter( $nodes, fn( $n ) => ! in_array( $n['@id'] ?? '', $author_ids, true ) ) );
	}
	return $nodes;
}

/* --------------------------------------------------------------------- titles and descriptions */

/** Copy a post's ot-core search title and description into Search Intel's empty fields. */
function ot_si_copy_meta( $post_id ) {
	foreach ( array( 'ot_seo_title' => '_jat_search_intel_title', 'ot_seo_description' => '_jat_search_intel_description' ) as $from => $to ) {
		$v = (string) get_post_meta( $post_id, $from, true );
		if ( '' !== $v && '' === (string) get_post_meta( $post_id, $to, true ) ) {
			update_post_meta( $post_id, $to, wp_slash( $v ) );
		}
	}
}

/** Every post with an ot-core title or description, and the hidden guides; once per version. */
function ot_si_sync() {
	$ids = get_posts( array(
		'post_type'      => 'any',
		'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array( 'relation' => 'OR', array( 'key' => 'ot_seo_title', 'compare' => 'EXISTS' ), array( 'key' => 'ot_seo_description', 'compare' => 'EXISTS' ) ),
	) );
	foreach ( $ids as $id ) {
		ot_si_copy_meta( $id );
	}
	ot_si_sync_hidden();
}

add_action( 'admin_init', function () {
	if ( ! ot_si_active() || ! current_user_can( 'edit_pages' ) || get_option( 'ot_si_synced' ) === OT_CORE_VERSION ) {
		return;
	}
	ot_si_sync();
	update_option( 'ot_si_synced', OT_CORE_VERSION, false );
} );

// Titles the code makes rather than a field: the guide index and a listing without its own.
add_filter( 'pre_get_document_title', function ( $t ) {
	if ( ! ot_si_active() ) {
		return $t;
	}
	if ( is_post_type_archive( 'ot_community' ) ) {
		return 'Massachusetts Town Guides | The Oberdorfer Team';
	}
	if ( is_singular( 'ot_listing' ) && '' === (string) get_post_meta( get_queried_object_id(), '_jat_search_intel_title', true ) ) {
		$l = ot_listing( get_queried_object_id() );
		return $l['address'] . ( $l['town'] ? ', ' . $l['town'] . ', MA' : '' ) . ' | The Oberdorfer Team';
	}
	return $t;
}, 30 );

// The guide index is a post-type archive, where Search Intel prints no description or canonical.
add_action( 'wp_head', function () {
	if ( ot_si_active() && is_post_type_archive( 'ot_community' ) ) {
		$s = ot_seo_current();
		echo '<meta name="description" content="' . esc_attr( $s['desc'] ) . '">' . "\n";
		echo '<link rel="canonical" href="' . esc_url( ot_prod_url( get_post_type_archive_link( 'ot_community' ) ) ) . '">' . "\n";
	}
}, 2 );

// An agent's listings page keeps its own title (Search Intel would print the profile's).
add_filter( 'pre_get_document_title', function ( $t ) {
	return ot_si_active() && ot_is_agent_listings() ? ot_agent_listings_seo()['title'] : $t;
}, 40 );

/* --------------------------------------------------------------------- hidden guides */

/** A hidden guide (or one in a hidden city) is noindex in Search Intel, so it leaves its sitemap. */
function ot_si_sync_hidden() {
	if ( ! ot_si_active() ) {
		return;
	}
	$all = get_posts( array( 'post_type' => 'ot_community', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true ) );
	foreach ( $all as $id ) {
		$ours = (bool) get_post_meta( $id, '_ot_si_noindex', true ); // set by this function, not the team
		if ( ot_community_off( $id ) ) {
			if ( ! get_post_meta( $id, '_jat_search_intel_noindex', true ) ) {
				update_post_meta( $id, '_jat_search_intel_noindex', '1' );
				update_post_meta( $id, '_ot_si_noindex', 1 );
			}
		} elseif ( $ours ) {
			delete_post_meta( $id, '_jat_search_intel_noindex' );
			delete_post_meta( $id, '_ot_si_noindex' );
		}
	}
}

foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $hook ) {
	add_action( $hook, function ( $mid, $post_id, $key ) {
		if ( 'ot_hidden' === $key ) {
			ot_si_sync_hidden();
		}
	}, 10, 3 );
}

/* --------------------------------------------------------------------- llms.txt */

// The curated summary (sourced facts, people, every page and guide) instead of a generated one.
add_filter( 'jat_search_intel_llms_txt', function ( $text, $full = false ) {
	return $full ? $text : ot_llms_txt();
}, 10, 2 );

/* --------------------------------------------------------------------- an agent's listings page */

add_action( 'template_redirect', function () {
	if ( ! ot_si_active() || ! ot_is_agent_listings() ) {
		return;
	}
	global $wp_filter;
	if ( empty( $wp_filter['wp_head'] ) ) {
		return;
	}
	$drop = array( 'render_meta_tags', 'render_canonical', 'render_open_graph', 'render_twitter_card' );
	foreach ( $wp_filter['wp_head']->callbacks as $priority => $callbacks ) {
		foreach ( $callbacks as $cb ) {
			$f = $cb['function'];
			if ( is_array( $f ) && is_object( $f[0] ) && $f[0] instanceof \JAT_Search_Intel\SEO_Manager && in_array( $f[1], $drop, true ) ) {
				remove_action( 'wp_head', $f, $priority );
			}
		}
	}
} );

/* --------------------------------------------------------------------- the 404 log */

/**
 * Search Intel's redirect manager logs requests for pages that do not exist (the address, the
 * linking page, the browser and a salted hash of the IP address) so broken links can be fixed.
 * It keeps them until someone clears the log; the privacy policy promises 90 days.
 */
add_action( 'init', function () {
	if ( ot_si_active() && ! wp_next_scheduled( 'ot_si_purge_404_log' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'ot_si_purge_404_log' );
	}
} );

add_action( 'ot_si_purge_404_log', function () {
	global $wpdb;
	$table = $wpdb->prefix . 'jat_search_intel_404_log';
	if ( $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE last_hit < %s', $table, gmdate( 'Y-m-d H:i:s', time() - 90 * DAY_IN_SECONDS ) ) );
	}
} );

/* --------------------------------------------------------------------- robots.txt */

// Search Intel lists its JSON schema map as a "Sitemap:"; sitemaps must be XML (or RSS or text),
// so Search Console would report it as invalid. The schema map stays available at its address.
add_filter( 'robots_txt', function ( $output ) {
	return ot_si_active() ? (string) preg_replace( '#^Sitemap:\s*\S*/schema-map\s*$\R?#mi', '', (string) $output ) : $output;
}, 1000 );
