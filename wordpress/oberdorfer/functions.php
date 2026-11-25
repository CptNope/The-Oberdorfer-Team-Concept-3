<?php
/**
 * The Oberdorfer Team block theme.
 *
 * Presentation only (docs/wordpress-architecture.md, layer rule): this file loads the design
 * system, registers the pattern category, prints the icon links and adapts two core blocks'
 * front-end markup to the design-system classes. Titles, meta descriptions, canonical URLs,
 * Open Graph and JSON-LD are ot-core's job; nothing here prints or removes them.
 *
 * @package oberdorfer
 */

defined( 'ABSPATH' ) || exit;

/* The theme's version (style.css) tags its CSS and JS, so each release reaches browsers and caches. */
define( 'OBERDORFER_VERSION', (string) wp_get_theme( get_template() )->get( 'Version' ) );

require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/markup.php';
require_once __DIR__ . '/inc/pages.php';

/**
 * The two typefaces are served from the theme (assets/fonts, declared in assets/css/fonts.css;
 * v0.10.26), not Google Fonts. These two are preloaded: body text and the Light display face.
 */
const OBERDORFER_PRELOAD_FONTS = array( 'assets/fonts/manrope-latin-wght-normal.woff2', 'assets/fonts/cormorant-garamond-latin-300-normal.woff2' );

/** The font faces, the design-system stylesheets in cascade order, then the WordPress resets. */
function oberdorfer_stylesheets() {
	return array(
		'oberdorfer-fonts'      => 'assets/css/fonts.css',
		'oberdorfer-tokens'     => 'assets/css/tokens.css',
		'oberdorfer-base'       => 'assets/css/base.css',
		'oberdorfer-components' => 'assets/css/components.css',
		'oberdorfer-site'       => 'assets/css/site.css',
		'oberdorfer-wp'         => 'assets/css/wp.css',
	);
}

add_action( 'after_setup_theme', function () {
	add_theme_support( 'editor-styles' );
	add_editor_style( array_values( oberdorfer_stylesheets() ) );
	// Core's sample patterns do not use the design system; the theme ships its own.
	remove_theme_support( 'core-block-patterns' );
} );

/* The header pattern prints the prototype's own skip link to #main; drop core's duplicate. */
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_block_template_skip_link' );
remove_action( 'wp_footer', 'the_block_template_skip_link' );

/*
 * Priority 20, after core's global styles and block styles are queued, so the design-system
 * rules come later in the cascade and win ties of equal specificity (for example
 * `.stack > * + *` against the layout rule `:root :where(.is-layout-flow) > *`).
 */
add_action( 'wp_enqueue_scripts', function () {
	// One minified file built by tools/build_wp.py from the six below (v0.10.24); the separate
	// files are the fallback and what the editor loads.
	if ( is_readable( get_theme_file_path( 'assets/css/bundle.css' ) ) ) {
		wp_enqueue_style( 'oberdorfer-bundle', get_theme_file_uri( 'assets/css/bundle.css' ), array(), OBERDORFER_VERSION );
	} else {
		$prev = array();
		foreach ( oberdorfer_stylesheets() as $handle => $path ) {
			wp_enqueue_style( $handle, get_theme_file_uri( $path ), $prev, OBERDORFER_VERSION );
			$prev = array( $handle );
		}
	}
	$js = array( 'in_footer' => true, 'strategy' => 'defer' );
	wp_enqueue_script( 'oberdorfer-site', get_theme_file_uri( 'assets/js/site.js' ), array(), OBERDORFER_VERSION, $js );
	wp_enqueue_script( 'oberdorfer-site-demo', get_theme_file_uri( 'assets/js/site-demo.js' ), array( 'oberdorfer-site' ), OBERDORFER_VERSION, $js );
}, 20 );

/* Preload the two most used font files so text draws in its own face sooner. */
add_action( 'wp_head', function () {
	foreach ( OBERDORFER_PRELOAD_FONTS as $font ) {
		printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( get_theme_file_uri( $font ) ) );
	}
}, 2 );

/** Icons, manifest and browser color, as the prototype's head(). A Site Icon set in Settings takes over. */
add_action( 'wp_head', function () {
	if ( ! has_site_icon() ) {
		printf( '<link rel="icon" href="%s" sizes="32x32">' . "\n", esc_url( get_theme_file_uri( 'assets/logo/favicon.ico' ) ) );
		printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( get_theme_file_uri( 'assets/logo/favicon.svg' ) ) );
		printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( get_theme_file_uri( 'assets/logo/apple-touch-icon.png' ) ) );
	}
	printf( '<link rel="manifest" href="%s">' . "\n", esc_url( get_theme_file_uri( 'assets/logo/site.webmanifest' ) ) );
	echo '<meta name="theme-color" content="#071D2D">' . "\n";
}, 5 );

/** Pattern category for the theme's patterns. */
add_action( 'init', function () {
	register_block_pattern_category( 'oberdorfer', array(
		'label'       => __( 'The Oberdorfer Team', 'oberdorfer' ),
		'description' => __( 'Page sections of The Oberdorfer Team site.', 'oberdorfer' ),
	) );
} );

/**
 * Buttons as design-system links. A core/button whose "Additional CSS class" holds
 * design-system classes (btn, btn--gold, link-arrow, cta-panel__phone ...) renders on the
 * front end as the prototype's bare link: `<a class="btn btn--gold" href="...">`, without the
 * block wrapper or the wp-element-button classes, so the button colors from theme.json do not
 * mix with .btn. `link-arrow` gets the arrow icon; a class `ot-icon-<name>` picks another icon
 * from the sprite (external, download) and is not printed. The saved block markup is unchanged.
 */
add_filter( 'render_block_core/button', function ( $html, $block ) {
	$tokens = preg_split( '/\s+/', trim( (string) ( $block['attrs']['className'] ?? '' ) ) );
	$keep   = array();
	$icon   = null;
	foreach ( array_filter( $tokens ) as $t ) {
		if ( str_starts_with( $t, 'ot-icon-' ) ) {
			$icon = substr( $t, 8 );
		} elseif ( ! str_starts_with( $t, 'is-style-' ) ) {
			$keep[] = $t;
		}
	}
	if ( ! $keep ) {
		return $html;
	}
	if ( ! $icon && in_array( 'link-arrow', $keep, true ) ) {
		$icon = 'arrow';
	}
	$p = new WP_HTML_Tag_Processor( $html );
	while ( $p->next_tag() ) {
		if ( in_array( $p->get_tag(), array( 'A', 'BUTTON' ), true ) ) {
			$p->set_attribute( 'class', implode( ' ', $keep ) );
			break;
		}
	}
	$out = $p->get_updated_html();
	if ( ! preg_match( '#<(a|button)\b.*</\1>#s', $out, $m ) ) {
		return $html;
	}
	$out = $m[0];
	if ( $icon ) {
		$svg = 'arrow' === $icon ? oberdorfer_arrow() : oberdorfer_icon( sanitize_key( $icon ) );
		$out = preg_replace( '#</(a|button)>$#', ' ' . $svg . '</$1>', $out );
	}
	return $out;
}, 10, 2 );

/**
 * Button groups as design-system wrappers. A core/buttons block with an "Additional CSS
 * class" renders as `<div class="{those classes}">` (for example `cluster` or
 * `section-head__aside`), dropping the flex layout classes the design system does not use.
 * The class `ot-unwrap` removes the wrapper entirely, leaving the links in their parent.
 */
add_filter( 'render_block_core/buttons', function ( $html, $block ) {
	$cls = trim( (string) ( $block['attrs']['className'] ?? '' ) );
	if ( '' === $cls ) {
		return $html;
	}
	$classes = array_filter( preg_split( '/\s+/', $cls ) );
	if ( in_array( 'ot-unwrap', $classes, true ) ) {
		return preg_replace( '#^\s*<div\b[^>]*>(.*)</div>\s*$#s', '$1', $html );
	}
	$p = new WP_HTML_Tag_Processor( $html );
	if ( $p->next_tag() ) {
		$p->set_attribute( 'class', implode( ' ', $classes ) );
		$p->remove_attribute( 'style' );
	}
	return $p->get_updated_html();
}, 10, 2 );

/**
 * Attributes core/group cannot store, written as class tokens in the pattern source:
 * `ot-labelledby-<id>` (aria-labelledby), `ot-data-<name>` (a data attribute that site.js
 * reads, such as data-team-grid), `ot-role-<role>` (role="tabpanel") and `ot-hidden`
 * (the hidden attribute, for inactive tab panels). The tokens are removed from the class list.
 */
add_filter( 'render_block_core/group', function ( $html, $block ) {
	$cls = (string) ( $block['attrs']['className'] ?? '' );
	if ( ! str_contains( $cls, 'ot-labelledby-' ) && ! str_contains( $cls, 'ot-data-' ) && ! str_contains( $cls, 'ot-role-' ) && ! str_contains( $cls, 'ot-hidden' ) ) {
		return $html;
	}
	$p = new WP_HTML_Tag_Processor( $html );
	if ( ! $p->next_tag() ) {
		return $html;
	}
	foreach ( array_filter( preg_split( '/\s+/', $cls ) ) as $t ) {
		if ( str_starts_with( $t, 'ot-labelledby-' ) ) {
			$p->set_attribute( 'aria-labelledby', substr( $t, 14 ) );
		} elseif ( str_starts_with( $t, 'ot-data-' ) ) {
			$p->set_attribute( 'data-' . substr( $t, 8 ), true );
		} elseif ( str_starts_with( $t, 'ot-role-' ) ) {
			$p->set_attribute( 'role', substr( $t, 8 ) );
		} elseif ( 'ot-hidden' === $t ) {
			$p->set_attribute( 'hidden', true );
		} else {
			continue;
		}
		$p->remove_class( $t );
	}
	return $p->get_updated_html();
}, 10, 2 );

/** The site URL path of the primary-nav item that matches the current view, as the prototype's `active`. */
function oberdorfer_active_nav() {
	if ( is_page( 'buy' ) ) {
		return '/buy/';
	}
	if ( is_page( 'sell' ) ) {
		return '/sell/';
	}
	if ( is_post_type_archive( 'ot_community' ) || is_singular( 'ot_community' ) ) {
		return '/communities/';
	}
	if ( is_page( array( 'team', 'brandon-kaitlyn-oberdorfer' ) ) || is_singular( 'ot_agent' ) ) {
		return '/team/';
	}
	if ( is_home() || is_singular( 'post' ) || is_category() || is_tag() ) {
		return '/journal/';
	}
	if ( is_page( 'contact' ) ) {
		return '/contact/';
	}
	return null;
}

/**
 * The header navigation. Its links are a paragraph inside the `nav` group, so the team edits
 * them as text. At render time: aria-current="page" goes on the active link, and the links
 * are remembered so the mobile menu (`<ul data-ot-menu>` in the header's HTML) lists the same
 * pages, plus "Join Our Team" before Contact, with the prototype's sub-labels.
 */
add_filter( 'render_block_core/group', function ( $html, $block ) {
	if ( ! in_array( 'nav', preg_split( '/\s+/', (string) ( $block['attrs']['className'] ?? '' ) ), true ) ) {
		return $html;
	}
	$GLOBALS['oberdorfer_nav_links'] = array();
	if ( preg_match_all( '#<a\b[^>]*\bhref="([^"]*)"[^>]*>(.*?)</a>#s', $html, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $a ) {
			$GLOBALS['oberdorfer_nav_links'][] = array( html_entity_decode( $a[1] ), wp_strip_all_tags( $a[2] ) );
		}
	}
	$path = oberdorfer_active_nav();
	if ( ! $path ) {
		return $html;
	}
	$want = untrailingslashit( wp_make_link_relative( home_url( $path ) ) );
	$p    = new WP_HTML_Tag_Processor( $html );
	while ( $p->next_tag( 'a' ) ) {
		if ( untrailingslashit( wp_make_link_relative( (string) $p->get_attribute( 'href' ) ) ) === $want ) {
			$p->set_attribute( 'aria-current', 'page' );
		}
	}
	return $p->get_updated_html();
}, 20, 2 );

add_filter( 'render_block_core/html', function ( $html ) {
	if ( ! str_contains( $html, 'data-ot-menu' ) ) {
		return $html;
	}
	$links = $GLOBALS['oberdorfer_nav_links'] ?? array();
	if ( ! $links ) {
		foreach ( array( 'Buy' => '/buy/', 'Sell' => '/sell/', 'Communities' => '/communities/', 'Our Team' => '/team/', 'Journal' => '/journal/', 'Contact' => '/contact/' ) as $label => $path ) {
			$links[] = array( oberdorfer_url( $path ), $label );
		}
	}
	$rel  = fn( $u ) => trailingslashit( (string) wp_parse_url( $u, PHP_URL_PATH ) );
	$join = oberdorfer_url( '/join/' );
	if ( ! in_array( $rel( $join ), array_map( fn( $l ) => $rel( $l[0] ), $links ), true ) ) {
		$at = count( $links );
		foreach ( $links as $i => $l ) {
			if ( '/contact/' === $rel( $l[0] ) ) {
				$at = $i;
			}
		}
		array_splice( $links, $at, 0, array( array( $join, 'Join Our Team' ) ) );
	}
	$items = '';
	foreach ( $links as $l ) {
		// "Search homes" follows the listings (IDX) switch in ot-core.
		$idx    = ! function_exists( 'ot_idx_enabled' ) || ot_idx_enabled();
		$sub    = array( '/buy/' => $idx ? ' <span data-ot-idx>Search homes</span>' : '', '/sell/' => ' <span>Market review</span>' )[ $rel( $l[0] ) ] ?? '';
		$items .= '<li><a href="' . esc_url( $l[0] ) . '">' . esc_html( $l[1] ) . $sub . '</a></li>';
	}
	return str_replace( '<ul data-ot-menu></ul>', '<ul>' . $items . '</ul>', $html );
}, 5 );

/* The cover photo is the page's largest image: load it first, never lazily. */
add_filter( 'render_block_core/image', function ( $html, $block ) {
	if ( ! str_contains( ' ' . ( $block['attrs']['className'] ?? '' ) . ' ', ' hero-cover__media ' ) ) {
		return $html;
	}
	$p = new WP_HTML_Tag_Processor( $html );
	if ( $p->next_tag( 'img' ) ) {
		$p->remove_attribute( 'loading' );
		$p->set_attribute( 'fetchpriority', 'high' );
		$html = $p->get_updated_html();
	}
	return $html;
}, 10, 2 );

/* --------------------------------------------------------------------- responsive photos */

/**
 * Give every theme photo (assets/img/photo/*.webp) a srcset of its smaller copies (made by
 * tools/build_photo_sizes.py: name-640, -800 and -960.webp), sizes, and width and height, so phones
 * download a fraction of the full image and the browser can reserve its space (v0.10.24).
 */
function oberdorfer_photo_srcset( $html ) {
	if ( false === strpos( $html, '/assets/img/photo/' ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		return $html;
	}
	static $dims = array();
	// Patterns print theme URLs root-relative (/wp-content/...), so compare paths, and build the
	// smaller copies' URLs on the same prefix as the src.
	$base = trailingslashit( (string) wp_parse_url( get_theme_file_uri( 'assets/img/photo' ), PHP_URL_PATH ) );
	$dir  = trailingslashit( get_theme_file_path( 'assets/img/photo' ) );
	$p    = new WP_HTML_Tag_Processor( $html );
	while ( $p->next_tag( 'img' ) ) {
		$src  = (string) $p->get_attribute( 'src' );
		$path = (string) wp_parse_url( $src, PHP_URL_PATH );
		if ( 0 !== strpos( $path, $base ) || $p->get_attribute( 'srcset' ) || ! preg_match( '/^([\w-]+)\.webp$/', substr( $path, strlen( $base ) ), $m ) ) {
			continue;
		}
		$prefix = substr( $src, 0, strrpos( $src, '/' ) + 1 );
		$name = $m[1];
		if ( ! isset( $dims[ $name ] ) ) {
			$size           = is_readable( $dir . $name . '.webp' ) ? wp_getimagesize( $dir . $name . '.webp' ) : false;
			$dims[ $name ]  = $size ? array( (int) $size[0], (int) $size[1] ) : array( 0, 0 );
		}
		list( $w, $h ) = $dims[ $name ];
		if ( ! $w ) {
			continue;
		}
		$set = array();
		foreach ( array( 640, 800, 960 ) as $v ) {
			if ( is_readable( $dir . $name . '-' . $v . '.webp' ) ) {
				$set[] = $prefix . $name . '-' . $v . '.webp ' . $v . 'w';
			}
		}
		if ( $set ) {
			$set[] = $src . ' ' . $w . 'w';
			$p->set_attribute( 'srcset', implode( ', ', $set ) );
			$p->set_attribute( 'sizes', $w / $h > 1.6 ? '100vw' : '(max-width: 48rem) 100vw, 50vw' );
		}
		if ( ! $p->get_attribute( 'width' ) ) {
			$p->set_attribute( 'width', (string) $w );
			$p->set_attribute( 'height', (string) $h );
		}
	}
	return $p->get_updated_html();
}
add_filter( 'render_block', 'oberdorfer_photo_srcset', 20 );
