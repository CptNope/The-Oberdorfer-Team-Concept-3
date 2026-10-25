<?php
/**
 * Search output, owned here so it survives any theme change: title tags, meta descriptions,
 * canonicals on the production domain, Open Graph, the JSON-LD graph, sitemap adjustments
 * and /llms.txt. Titles and descriptions are stored per post (ot_seo_title, ot_seo_description);
 * the importer fills them from the prototype's tested rules (town_title, town_desc and so on).
 * If an SEO plugin is installed, only one of them may print meta tags: define
 * OT_CORE_SEO_OUTPUT as false in wp-config.php to switch this output off.
 */

defined( 'ABSPATH' ) || exit;

function ot_seo_enabled() {
	return ! defined( 'OT_CORE_SEO_OUTPUT' ) || OT_CORE_SEO_OUTPUT;
}

/** Title and description for the current view. */
function ot_seo_current() {
	$title = '';
	$desc  = '';
	if ( is_post_type_archive( 'ot_community' ) ) {
		$n     = count( ot_towns() );
		$title = 'Massachusetts Town Guides | The Oberdorfer Team';
		$desc  = 'Local guides to ' . $n . ' Massachusetts communities, from Worcester and Boston to Cape Cod and the Berkshires: housing, commuting, schools and 2025 home prices.';
	} elseif ( is_home() && ! is_front_page() ) {
		$id    = (int) get_option( 'page_for_posts' );
		$title = (string) get_post_meta( $id, 'ot_seo_title', true );
		$desc  = (string) get_post_meta( $id, 'ot_seo_description', true );
	} elseif ( is_singular() ) {
		$id    = get_queried_object_id();
		$title = (string) get_post_meta( $id, 'ot_seo_title', true );
		$desc  = (string) get_post_meta( $id, 'ot_seo_description', true );
		if ( ! $desc ) {
			$desc = wp_trim_words( wp_strip_all_tags( get_the_excerpt( $id ) ), 28, '…' );
		}
	}
	if ( ! $title ) {
		$title = is_front_page() ? 'Worcester & Massachusetts Real Estate | The Oberdorfer Team' : wp_strip_all_tags( wp_get_document_title() );
	}
	return array( 'title' => $title, 'desc' => $desc );
}

add_filter( 'pre_get_document_title', function ( $t ) {
	if ( ! ot_seo_enabled() ) {
		return $t;
	}
	if ( is_singular() || is_post_type_archive( 'ot_community' ) || ( is_home() && ! is_front_page() ) || is_front_page() ) {
		$id = is_home() && ! is_front_page() ? (int) get_option( 'page_for_posts' ) : get_queried_object_id();
		$st = $id ? (string) get_post_meta( $id, 'ot_seo_title', true ) : '';
		if ( is_post_type_archive( 'ot_community' ) ) {
			return 'Massachusetts Town Guides | The Oberdorfer Team';
		}
		if ( $st ) {
			return $st;
		}
	}
	return $t;
}, 20 );

/** Canonicals point at oberdorferteam.com whatever host serves the page (staging included). */
add_filter( 'get_canonical_url', function ( $url ) {
	return ot_seo_enabled() ? ot_prod_url( $url ) : $url;
} );

add_action( 'wp_head', function () {
	if ( ! ot_seo_enabled() ) {
		return;
	}
	$s   = ot_seo_current();
	$url = '';
	if ( is_singular() ) {
		$url = ot_prod_url( get_permalink( get_queried_object_id() ) );
	} elseif ( is_post_type_archive( 'ot_community' ) ) {
		$url = ot_prod_url( get_post_type_archive_link( 'ot_community' ) );
		echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
	} elseif ( is_home() || is_front_page() ) {
		$url = ot_prod_url( is_front_page() ? home_url( '/' ) : get_permalink( (int) get_option( 'page_for_posts' ) ) );
		if ( is_home() && ! is_front_page() ) {
			echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
		}
	}
	if ( $s['desc'] ) {
		echo '<meta name="description" content="' . esc_attr( $s['desc'] ) . '">' . "\n";
	}
	$type  = is_singular( 'ot_agent' ) ? 'profile' : ( is_singular( 'post' ) ? 'article' : 'website' );
	$image = get_theme_file_uri( 'assets/img/share-oberdorfer-team-v1.jpg' );
	$tags  = array(
		'og:type'        => $type,
		'og:site_name'   => 'The Oberdorfer Team',
		'og:title'       => $s['title'],
		'og:description' => $s['desc'],
		'og:url'         => $url,
		'og:image'       => $image,
	);
	foreach ( $tags as $k => $v ) {
		if ( $v ) {
			echo '<meta property="' . esc_attr( $k ) . '" content="' . esc_attr( $v ) . '">' . "\n";
		}
	}
	echo '<meta property="og:image:width" content="1200"><meta property="og:image:height" content="630"><meta name="twitter:card" content="summary_large_image">' . "\n";

	foreach ( ot_jsonld_graph() as $block ) {
		echo ot_jsonld( $block ) . "\n";
	}
}, 2 );

/** The prototype's JSON-LD: the team on every page, plus a page-specific node and breadcrumbs. */
function ot_jsonld_graph() {
	$team_id   = OT_SITE_URL . '#team';
	$broker_id = OT_SITE_URL . '#brokerage';
	$office    = get_page_by_path( 'worcester', OBJECT, 'ot_office' );
	$street    = $office ? (string) get_post_meta( $office->ID, 'ot_street', true ) : '652 Park Ave, Unit B';
	$postcode  = $office ? (string) get_post_meta( $office->ID, 'ot_postcode', true ) : '01603';
	$out = array( array(
		'@context'  => 'https://schema.org',
		'@type'     => 'RealEstateAgent',
		'@id'       => $team_id,
		'name'      => 'The Oberdorfer Team',
		'url'       => OT_SITE_URL,
		'logo'      => get_theme_file_uri( 'assets/logo/social-avatar.png' ),
		'image'     => get_theme_file_uri( 'assets/img/photo/team-portrait.webp' ),
		'telephone' => OT_PHONE,
		'areaServed' => array( '@type' => 'State', 'name' => 'Massachusetts' ),
		'address'   => array( '@type' => 'PostalAddress', 'streetAddress' => $street, 'addressLocality' => 'Worcester', 'addressRegion' => 'MA', 'postalCode' => $postcode, 'addressCountry' => 'US' ),
		'slogan'    => 'Luxury at every price point.',
		'sameAs'    => array( 'https://www.instagram.com/oberdorferteam/' ),
		'parentOrganization' => array( '@type' => 'Organization', '@id' => $broker_id, 'name' => 'REWAP Brokerage LLC', 'url' => 'https://rewapbrokerage.com/' ),
	) );

	if ( ! is_front_page() ) {
		$items = array();
		foreach ( ot_current_trail() as $i => $t ) {
			$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => html_entity_decode( wp_strip_all_tags( $t[0] ) ), 'item' => ot_prod_url( $t[1] ) );
		}
		if ( count( $items ) > 1 ) {
			$out[] = array( '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items );
		}
	}

	if ( is_singular( 'ot_community' ) ) {
		$x   = ot_cx( get_queried_object_id() );
		$url = ot_prod_url( $x['url'] );
		if ( $x['is_nbhd'] ) {
			$cname = $x['city']['rec']['name'];
			$about = array( '@type' => 'Place', 'name' => $x['name'] . ', ' . $cname . ', Massachusetts',
				'containedInPlace' => array( '@type' => 'City', 'name' => $cname . ', Massachusetts', 'url' => ot_prod_url( $x['city']['url'] ) ) );
			$name  = 'Living in ' . $x['name'] . ', ' . $cname . ', MA';
		} else {
			$about = array( '@type' => 'City', 'name' => $x['name'] . ', Massachusetts',
				'containedInPlace' => array( array( '@type' => 'AdministrativeArea', 'name' => ( $x['r']['county'] ?? '' ) . ', Massachusetts' ), array( '@type' => 'State', 'name' => 'Massachusetts' ) ) );
			if ( ! empty( $x['r']['official_site'] ) ) {
				$about['sameAs'] = array( $x['r']['official_site'] );
			}
			$name = 'Living in ' . $x['name'] . ', MA';
		}
		$page = array( '@context' => 'https://schema.org', '@type' => 'WebPage', '@id' => $url . '#page', 'url' => $url, 'name' => $name,
			'about' => $about, 'dateModified' => OT_UPDATED, 'inLanguage' => 'en-US', 'publisher' => array( '@id' => $team_id ), 'mainEntityOfPage' => $url );
		if ( $x['is_nbhd'] ) {
			$page['isPartOf'] = array( '@id' => ot_prod_url( $x['city']['url'] ) . '#page' );
		}
		$out[] = $page;
	} elseif ( is_singular( 'ot_agent' ) ) {
		$x   = ot_ax( get_queried_object_id() );
		$url = ot_prod_url( $x['url'] );
		if ( $x['team'] ) {
			$out[] = array( '@context' => 'https://schema.org', '@type' => 'AboutPage', 'url' => $url, 'dateModified' => OT_UPDATED,
				'mainEntity' => array( '@id' => $team_id ),
				'mentions'   => array_values( array_map( fn( $a ) => array( '@id' => ot_prod_url( $a['url'] ) . '#person' ), ot_agents() ) ) );
		} else {
			$r      = $x['r'];
			$person = array( '@type' => 'Person', '@id' => $url . '#person', 'name' => $r['name'], 'givenName' => $r['given'] ?? '', 'familyName' => $r['family'] ?? '',
				'url' => $url, 'image' => get_theme_file_uri( $r['photo'] ), 'telephone' => $r['direct'] ?? OT_PHONE,
				'jobTitle' => $r['title'] ?? 'Real estate team leader', 'worksFor' => array( '@id' => $team_id ), 'affiliation' => array( '@id' => $broker_id ),
				'areaServed' => array( '@type' => 'State', 'name' => 'Massachusetts' ),
				'knowsAbout' => array( 'Buying a home in Massachusetts', 'Selling a home in Massachusetts', 'Wakefield, Massachusetts real estate' ) );
			if ( ! empty( $r['email'] ) ) {
				$person['email'] = $r['email'];
			}
			if ( ! empty( $r['same_as'] ) ) {
				$person['sameAs'] = array_values( $r['same_as'] );
			}
			if ( $x['partner'] ) {
				$person['spouse'] = array( '@id' => ot_prod_url( $x['partner']['url'] ) . '#person' );
			}
			$out[] = array( '@context' => 'https://schema.org', '@type' => 'ProfilePage', 'url' => $url, 'dateModified' => OT_UPDATED, 'mainEntity' => $person );
		}
	} elseif ( is_post_type_archive( 'ot_community' ) ) {
		$items = array();
		foreach ( array_values( ot_towns() ) as $i => $t ) {
			$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $t['rec']['name'] . ', Massachusetts', 'url' => ot_prod_url( $t['url'] ) );
		}
		$out[] = array( '@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => 'Massachusetts community guides', 'itemListElement' => $items );
	} elseif ( is_page( 'market' ) ) {
		$out[] = array( '@context' => 'https://schema.org', '@type' => 'Dataset',
			'name' => 'Single-family home sales and median prices by town, Massachusetts, 2025',
			'description' => 'Calendar-year 2025 single-family sales counts and median sale prices for the towns covered by The Oberdorfer Team\'s community guides, as published by The Warren Group.',
			'temporalCoverage' => '2025', 'spatialCoverage' => array( '@type' => 'State', 'name' => 'Massachusetts' ),
			'isBasedOn' => OT_WARREN_URL, 'creator' => array( '@id' => $team_id ) );
	}
	return $out;
}

/* --------------------------------------------------------------------- sitemap */

add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) {
	return 'users' === $name ? false : $provider; // agent profiles are the people pages; author archives would expose usernames
}, 10, 2 );

add_filter( 'wp_sitemaps_taxonomies', function ( $taxonomies ) {
	unset( $taxonomies['ot_region'], $taxonomies['post_format'] );
	return $taxonomies;
} );

/* --------------------------------------------------------------------- llms.txt */

add_action( 'init', function () {
	add_rewrite_rule( '^llms\.txt$', 'index.php?ot_llms=1', 'top' );
} );

add_filter( 'query_vars', function ( $v ) {
	$v[] = 'ot_llms';
	return $v;
} );

// Without this, the trailing-slash permalink structure redirects /llms.txt to /llms.txt/.
add_filter( 'redirect_canonical', function ( $redirect ) {
	return get_query_var( 'ot_llms' ) ? false : $redirect;
} );

add_action( 'template_redirect', function () {
	if ( ! get_query_var( 'ot_llms' ) ) {
		return;
	}
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo ot_llms_txt();
	exit;
} );

function ot_llms_txt() {
	$people = '';
	foreach ( ot_agents() as $a ) {
		$r       = $a['rec'];
		$partner = ! empty( $r['partner'] ) ? ( ot_agents()[ $r['partner'] ]['rec']['name'] ?? '' ) : '';
		$people .= '- [' . $r['name'] . '](' . ot_prod_url( $a['url'] ) . '): ' . $r['role'] . ', The Oberdorfer Team.' . ( $partner ? ' Leads the team with ' . $r['relation'] . ', ' . $partner . '.' : '' ) . "\n";
	}
	$line = function ( $id, $url ) {
		$t = (string) get_post_meta( $id, 'ot_seo_title', true ) ?: get_the_title( $id );
		$d = (string) get_post_meta( $id, 'ot_seo_description', true );
		return '- [' . html_entity_decode( $t ) . '](' . ot_prod_url( $url ) . ')' . ( $d ? ': ' . html_entity_decode( $d ) : '' ) . "\n";
	};
	$pages = '';
	foreach ( get_pages( array( 'sort_column' => 'menu_order' ) ) as $p ) {
		$pages .= $line( $p->ID, get_permalink( $p ) );
	}
	$pages .= '- [Massachusetts Town Guides | The Oberdorfer Team](' . ot_prod_url( get_post_type_archive_link( 'ot_community' ) ) . ")\n";
	foreach ( ot_communities() as $c ) {
		$pages .= $line( $c['id'], $c['url'] );
	}
	return "# The Oberdorfer Team\n\n> A husband-and-wife real estate team led by Brandon and Kaitlyn Oberdorfer. The team works from Worcester, Massachusetts, serves buyers and sellers across the state, and operates under REWAP Brokerage LLC. Phone: " . OT_PHONE . ". Promise: \"Luxury at every price point.\"\n\n"
		. "Facts on this site are sourced and dated. Town market figures are calendar-year 2025 single-family sales and medians from The Warren Group (MA December 2025 Sales, Prices by Town). The Wakefield snapshot is from Redfin, accessed October 10, 2026. None of the figures are live. Community guides describe places, housing, transit and history; school information is limited to links to each district's Massachusetts DESE profile.\n\n"
		. "## People\n" . $people . "\n## Pages\n" . $pages;
}
