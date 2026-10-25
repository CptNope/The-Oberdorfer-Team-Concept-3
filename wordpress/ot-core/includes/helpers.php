<?php
/**
 * Small formatting and markup helpers, ported from tools/build_site.py so the WordPress
 * pages render the same markup as the prototype (the design-system CSS depends on it).
 */

defined( 'ABSPATH' ) || exit;

const OT_PHONE    = '406-250-7620';
const OT_TEL      = '+14062507620';
const OT_INSTAGRAM = 'https://www.instagram.com/oberdorferteam/'; // the team asked (2026-10-24) that every Instagram link go here
const OT_UPDATED  = '2026-10-22';
const OT_UPDATED_LABEL = 'October 22, 2026';
const OT_WARREN_URL = 'https://www.thewarrengroup.com/wp-content/uploads/2026/01/MA-December-2025-Sales_Town.pdf';
const OT_WARREN   = 'The Warren Group, MA December 2025 Sales, Prices by Town (single-family, calendar year 2025)';
const OT_ART_CYCLE = array( 'colonial', 'cape', 'triple-decker', 'townscape' );

/** Text that came from a researched record. The records hold a few inline tags (em, a); keep only safe ones. */
function ot_txt( $s ) {
	return wp_kses( (string) $s, array(
		'em' => array(), 'strong' => array(), 'b' => array(), 'i' => array(), 'br' => array(),
		'a'  => array( 'href' => array(), 'rel' => array() ),
		'span' => array( 'class' => array() ), 'time' => array( 'datetime' => array() ),
	) );
}

function ot_money( $n ) {
	return '$' . number_format( (float) $n, 0 );
}

function ot_num( $n ) {
	return number_format( (float) $n, 0 );
}

function ot_pct( $a, $b ) {
	if ( ! $a || ! $b ) {
		return null;
	}
	return ( $b - $a ) / $a * 100;
}

function ot_fmt_pct( $ch ) {
	if ( null === $ch ) {
		return 'n/a';
	}
	if ( abs( $ch ) < 0.05 ) {
		return 'No change';
	}
	return sprintf( '%+.1f%%', $ch );
}

/** Asset in the active theme (the design system ships with the theme). */
function ot_asset( $path ) {
	return get_theme_file_uri( $path );
}

function ot_icon( $name, $cls = 'icon', $vb = null ) {
	$v = $vb ? ' viewBox="' . esc_attr( $vb ) . '"' : '';
	return '<svg class="' . esc_attr( $cls ) . '" aria-hidden="true"' . $v . '><use href="' . esc_url( ot_asset( 'assets/img/icons.svg' ) ) . '#i-' . esc_attr( $name ) . '"/></svg>';
}

function ot_arrow() {
	return ot_icon( 'arrow', 'icon', '0 0 34 12' );
}

function ot_art( $name, $label = null ) {
	$a = $label ? 'role="img" aria-label="' . esc_attr( $label ) . '"' : 'aria-hidden="true"';
	return '<svg viewBox="0 0 400 260" ' . $a . '><use href="' . esc_url( ot_asset( 'assets/img/illustrations.svg' ) ) . '#' . esc_attr( $name ) . '"/></svg>';
}

function ot_link_arrow( $href, $text, $style = '' ) {
	$s = $style ? ' style="' . esc_attr( $style ) . '"' : '';
	return '<a class="link-arrow" href="' . esc_url( $href ) . '"' . $s . '>' . $text . ' ' . ot_arrow() . '</a>';
}

/** Running head, display title in spans, optional aside and lead (prototype section_head()). */
function ot_section_head( $run_left, $run_right, $lines, $lead = null, $aside = null, $level = 'h2', $title_id = null ) {
	$spans = '';
	foreach ( $lines as $l ) {
		$spans .= '<span>' . $l . '</span>';
	}
	$tid = $title_id ? ' id="' . esc_attr( $title_id ) . '"' : '';
	$out = '<div class="section-head"><div class="running-head label"><span>' . $run_left . '</span><span class="label--quiet">' . $run_right . '</span></div>';
	$out .= '<' . $level . ' class="section-head__title display-m answer"' . $tid . '>' . $spans . '</' . $level . '>';
	if ( $aside ) {
		$out .= '<div class="section-head__aside">' . $aside . '</div>';
	}
	if ( $lead ) {
		$out .= '<p class="section-head__lead lead">' . $lead . '</p>';
	}
	return $out . '</div>';
}

/** Breadcrumb trail: list of [label, url]. The last item is the current page. */
function ot_breadcrumbs_html( $trail ) {
	$items = '';
	$last  = count( $trail ) - 1;
	foreach ( $trail as $i => $item ) {
		$items .= $i === $last
			? '<li aria-current="page">' . $item[0] . '</li>'
			: '<li><a href="' . esc_url( $item[1] ) . '">' . $item[0] . '</a></li>';
	}
	return '<nav class="breadcrumbs" aria-label="Breadcrumb"><ol>' . $items . '</ol></nav>';
}

/** Production URL for a site URL: same path on https://oberdorferteam.com/. */
function ot_prod_url( $url ) {
	$home = trailingslashit( home_url( '/' ) );
	if ( str_starts_with( $url, $home ) ) {
		return OT_SITE_URL . ltrim( substr( $url, strlen( $home ) ), '/' );
	}
	return $url;
}

function ot_jsonld( $data ) {
	return '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
}

/** Placeholder for an unconfirmed value (prototype ph()). */
function ot_ph( $text ) {
	return '<span class="placeholder-value">[' . esc_html( $text ) . ']</span>';
}

function ot_first_sentence( $text ) {
	$parts = preg_split( '/(?<=[a-z0-9\)])\. (?=[A-Z])/', (string) $text );
	return rtrim( $parts[0], '.' ) . '.';
}
