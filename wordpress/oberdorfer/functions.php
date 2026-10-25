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

const OBERDORFER_VERSION = '0.1.0';

require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/markup.php';

/** Google Fonts, as the prototype's head(). Self-hosting the fonts is a launch task. */
const OBERDORFER_FONTS = 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Manrope:wght@300;400;500;600;700&display=swap';

/** The design-system stylesheets, in cascade order, then the WordPress resets. */
function oberdorfer_stylesheets() {
	return array(
		'oberdorfer-tokens'     => 'assets/css/tokens.css',
		'oberdorfer-base'       => 'assets/css/base.css',
		'oberdorfer-components' => 'assets/css/components.css',
		'oberdorfer-site'       => 'assets/css/site.css',
		'oberdorfer-wp'         => 'assets/css/wp.css',
	);
}

add_action( 'after_setup_theme', function () {
	add_theme_support( 'editor-styles' );
	add_editor_style( array_merge( array( OBERDORFER_FONTS ), array_values( oberdorfer_stylesheets() ) ) );
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
	wp_enqueue_style( 'oberdorfer-fonts', OBERDORFER_FONTS, array(), null );
	$prev = array( 'oberdorfer-fonts' );
	foreach ( oberdorfer_stylesheets() as $handle => $path ) {
		wp_enqueue_style( $handle, get_theme_file_uri( $path ), $prev, OBERDORFER_VERSION );
		$prev = array( $handle );
	}
	$js = array( 'in_footer' => true, 'strategy' => 'defer' );
	wp_enqueue_script( 'oberdorfer-site', get_theme_file_uri( 'assets/js/site.js' ), array(), OBERDORFER_VERSION, $js );
	wp_enqueue_script( 'oberdorfer-site-demo', get_theme_file_uri( 'assets/js/site-demo.js' ), array( 'oberdorfer-site' ), OBERDORFER_VERSION, $js );
}, 20 );

add_filter( 'wp_resource_hints', function ( $urls, $relation ) {
	if ( 'preconnect' === $relation ) {
		$urls[] = 'https://fonts.googleapis.com';
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
	}
	return $urls;
}, 10, 2 );

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
	if ( is_page( 'team' ) || is_singular( 'ot_agent' ) ) {
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
 * aria-current="page" on the active header link. Runs at render time on the header's
 * navigation fragment, so it also works after the header is edited in the Site Editor.
 */
add_filter( 'render_block_core/html', function ( $html ) {
	if ( ! str_contains( $html, 'class="nav"' ) || ! str_contains( $html, 'aria-label="Primary"' ) ) {
		return $html;
	}
	$path = oberdorfer_active_nav();
	if ( ! $path ) {
		return $html;
	}
	$want = untrailingslashit( home_url( $path ) );
	$p    = new WP_HTML_Tag_Processor( $html );
	$in   = false;
	while ( $p->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
		if ( 'NAV' === $p->get_tag() ) {
			$in = ! $p->is_tag_closer();
			continue;
		}
		if ( $in && 'A' === $p->get_tag() && ! $p->is_tag_closer() && untrailingslashit( (string) $p->get_attribute( 'href' ) ) === $want ) {
			$p->set_attribute( 'aria-current', 'page' );
		}
	}
	return $p->get_updated_html();
} );
