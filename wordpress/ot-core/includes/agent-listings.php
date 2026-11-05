<?php
/**
 * Each agent's own listings page, inside the team site: /agents/<slug>/listings/.
 *
 * Kept on oberdorferteam.com rather than a separate domain or subdomain, so every agent page
 * and listing builds the one site's search standing, and each listing keeps a single URL
 * (/listings/<address>/) that agent pages link to, never a second copy.
 *
 * The page lists the homes the agent is listing or co-listing (Listings > Listing agent), the
 * ones recently sold, and the towns they serve ("Agent details" > Towns served). Search: its
 * own title, description and canonical; noindex while it has no listings; breadcrumbs; a
 * CollectionPage with an ItemList in JSON-LD; listed in the sitemap and /llms.txt once it has
 * listings. A profile shows a strip of current listings linking here.
 */

defined( 'ABSPATH' ) || exit;

/* --------------------------------------------------------------------- the address */

add_action( 'init', function () {
	add_rewrite_rule( '^agents/([^/]+)/listings/?$', 'index.php?ot_agent=$matches[1]&ot_view=listings', 'top' );
	// New rules take effect once per plugin version, without anyone visiting Settings > Permalinks.
	if ( get_option( 'ot_rewrite_version' ) !== OT_CORE_VERSION ) {
		flush_rewrite_rules( false );
		update_option( 'ot_rewrite_version', OT_CORE_VERSION, false );
	}
}, 30 );

add_filter( 'query_vars', function ( $v ) {
	$v[] = 'ot_view';
	return $v;
} );

/** True on an agent's listings page. */
function ot_is_agent_listings() {
	return is_singular( 'ot_agent' ) && 'listings' === get_query_var( 'ot_view' );
}

function ot_agent_listings_url( $agent ) {
	return trailingslashit( get_permalink( $agent ) ) . 'listings/';
}

/** The agent's listings: [ current, sold ]. */
function ot_agent_listing_sets( $slug ) {
	return array(
		ot_listings( array( 'agent' => $slug, 'limit' => 50 ) ),
		ot_listings( array( 'agent' => $slug, 'limit' => 12, 'statuses' => array( 'sold' ) ) ),
	);
}

/** The block template for this page: single-ot_agent-listings (the theme), before the profile's. */
add_filter( 'single_template_hierarchy', function ( $templates ) {
	if ( 'listings' === get_query_var( 'ot_view' ) && is_singular( 'ot_agent' ) ) {
		array_unshift( $templates, 'single-ot_agent-listings.php' );
	}
	return $templates;
} );

// The listings page is its own URL, not a variant of the profile to fold back into it.
add_filter( 'redirect_canonical', function ( $redirect ) {
	return 'listings' === get_query_var( 'ot_view' ) ? false : $redirect;
} );

/* --------------------------------------------------------------------- sections */

/** Towns the agent serves: community cards for the guides ticked in "Agent details". */
function ot_agent_areas_html( $x ) {
	$slugs = array_filter( array_map( 'trim', explode( ',', (string) ( $x['r']['areas'] ?? '' ) ) ) );
	$cards = $slugs ? ot_render_community_cards( $slugs ) : '';
	if ( ! $cards ) {
		return '';
	}
	return '<section class="section surface-muted" aria-labelledby="areas-title"><div class="container">' . ot_section_head( 'Towns served', 'Community guides', array( 'Where ' . $x['g'] . ' works.' ), null, ot_link_arrow( get_post_type_archive_link( 'ot_community' ), 'All town guides' ), 'h2', 'areas-title' ) . '<div class="card-grid-3">' . $cards . '</div></div></section>';
}

/** Sections shared by person and joint profiles: listings strip, listings page, intro video. */
function ot_render_agent_listings_section( $section, $x ) {
	$slug = $x['p']->post_name;
	$who  = $x['team'] ? 'Brandon &amp; Kaitlyn' : $x['g'];
	$url  = ot_agent_listings_url( $x['p'] );
	switch ( $section ) {
		case 'listings':
			list( $current ) = ot_agent_listing_sets( $slug );
			if ( ! $current ) {
				return '';
			}
			$grid = '<div class="listing-grid">' . implode( '', array_map( 'ot_listing_card', array_slice( $current, 0, 3 ) ) ) . '</div>';
			return '<section class="section" id="listings" aria-labelledby="listings-title"><div class="container">' . ot_section_head( 'Current listings', count( $current ) . ' ' . _n( 'home', 'homes', count( $current ) ), array( 'Homes ' . $who . ' is listing.' ), null, ot_link_arrow( $url, 'All of ' . $who . '\'s listings' ), 'h2', 'listings-title' ) . $grid . '</div></section>';

		case 'listings-hero':
			$r     = $x['r'];
			$phone = $x['team'] ? OT_PHONE : ( ( $r['direct'] ?? '' ) ?: OT_PHONE );
			return '<section class="section page-hero" style="padding-top:var(--space-7)"><div class="container stack" style="--stack:var(--space-5)">
  ' . ot_breadcrumbs_html( ot_current_trail() ) . '
  <p class="label">Listings · The Oberdorfer Team</p>
  <h1 class="display-l" id="page-title">' . $who . '\'s listings</h1>
  <p class="lead">Homes ' . $who . ' is listing in Massachusetts with The Oberdorfer Team, REWAP Brokerage LLC. Each one links to its full page, with photos, tours and a way to ask for a showing.</p>
  <div class="cluster"><a class="btn" href="' . esc_url( get_permalink( $x['p'] ) ) . '#contact-agent">Message ' . ( $x['team'] ? 'the team' : $x['g'] ) . '</a><a class="btn btn--secondary" href="' . esc_attr( ot_tel_href( $phone ) ) . '">Call ' . esc_html( $phone ) . '</a><a class="link-arrow" href="' . esc_url( get_permalink( $x['p'] ) ) . '">About ' . $who . ' ' . ot_arrow() . '</a></div>
</div></section>';

		case 'listings-page':
			list( $current, $sold ) = ot_agent_listing_sets( $slug );
			$out = '<section class="section" aria-labelledby="current-title"><div class="container">' . ot_section_head( 'For sale and coming soon', count( $current ) . ' ' . _n( 'home', 'homes', count( $current ) ), array( 'Current listings.' ), null, null, 'h2', 'current-title' );
			$out .= $current
				? '<div class="listing-grid">' . implode( '', array_map( 'ot_listing_card', $current ) ) . '</div>'
				: '<div class="placeholder-block"><p class="h4">No homes listed right now.</p><p>New listings appear here first. To hear about the next one, or about homes coming to market before they are listed, <a href="' . esc_url( get_permalink( $x['p'] ) ) . '#contact-agent">send ' . $who . ' a message</a>.</p></div>';
			$out .= '</div></section>';
			if ( $sold ) {
				$out .= '<section class="section surface-muted" aria-labelledby="sold-title"><div class="container">' . ot_section_head( 'Recently sold', count( $sold ) . ' ' . _n( 'home', 'homes', count( $sold ) ), array( 'Recently sold.' ), null, null, 'h2', 'sold-title' ) . '<div class="listing-grid">' . implode( '', array_map( 'ot_listing_card', $sold ) ) . '</div></div></section>';
			}
			return $out . ot_agent_areas_html( $x );

		case 'video':
			$info = ot_media_embed( $x['r']['intro_video'] ?? '' );
			if ( ! $info || 'tour' === $info[0] ) {
				return '';
			}
			$poster = $info[2] ? '<img src="' . esc_url( $info[2] ) . '" alt="" loading="lazy">' : '<img src="' . esc_url( $x['r']['photo_url'] ) . '" alt="" loading="lazy" style="object-position:50% 20%">';
			$info[3] = 'Watch ' . html_entity_decode( $x['g'] ) . '\'s introduction';
			return '<figure class="stack" style="--stack:var(--space-3)">' . ot_embed_facade( $info, $poster, 'Introduction from ' . html_entity_decode( $x['name'] ) ) . '<figcaption class="caption">A short introduction from ' . $x['g'] . '.</figcaption></figure>';
	}
	return null; // not a listings section
}

/* --------------------------------------------------------------------- search */

/** Title and description of an agent's listings page. */
function ot_agent_listings_seo() {
	$x   = ot_ax( get_queried_object_id() );
	$who = $x['team'] ? 'Brandon & Kaitlyn Oberdorfer' : html_entity_decode( $x['name'] );
	return array(
		'title' => $who . '\'s Listings | The Oberdorfer Team',
		'desc'  => 'Homes ' . $who . ' is listing in Massachusetts with The Oberdorfer Team, REWAP Brokerage LLC: current listings, recently sold homes and the towns they serve.',
	);
}

add_filter( 'pre_get_document_title', function ( $t ) {
	return ot_is_agent_listings() && ot_seo_enabled() ? ot_agent_listings_seo()['title'] : $t;
}, 25 );

add_filter( 'get_canonical_url', function ( $url, $post ) {
	if ( 'listings' === get_query_var( 'ot_view' ) && $post instanceof WP_Post && 'ot_agent' === $post->post_type ) {
		return ot_seo_enabled() ? ot_prod_url( ot_agent_listings_url( $post ) ) : ot_agent_listings_url( $post );
	}
	return $url;
}, 20, 2 );

/** Not indexed while there is nothing on it; links are still followed. */
add_filter( 'wp_robots', function ( $robots ) {
	if ( ot_is_agent_listings() ) {
		list( $current, $sold ) = ot_agent_listing_sets( get_post_field( 'post_name', get_queried_object_id() ) );
		if ( ! $current && ! $sold ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}
	}
	return $robots;
} );

/** The sitemap lists each agent's listings page that has listings. */
add_action( 'init', function () {
	if ( ! class_exists( 'WP_Sitemaps_Provider' ) ) {
		return;
	}
	class OT_Agent_Listings_Sitemap extends WP_Sitemaps_Provider {
		public function __construct() {
			$this->name        = 'otagentlistings';
			$this->object_type = 'otagentlistings';
		}
		public function get_url_list( $page_num, $object_subtype = '' ) {
			$out = array();
			foreach ( get_posts( array( 'post_type' => 'ot_agent', 'post_status' => 'publish', 'posts_per_page' => -1 ) ) as $a ) {
				list( $current, $sold ) = ot_agent_listing_sets( $a->post_name );
				if ( $current || $sold ) {
					$out[] = array( 'loc' => ot_agent_listings_url( $a ) );
				}
			}
			return $out;
		}
		public function get_max_num_pages( $object_subtype = '' ) {
			return 1;
		}
	}
	wp_register_sitemap_provider( 'otagentlistings', new OT_Agent_Listings_Sitemap() );
}, 20 );
