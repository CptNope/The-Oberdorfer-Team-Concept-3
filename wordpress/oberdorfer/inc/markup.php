<?php
/**
 * Block-markup builders for the theme's patterns: the prototype's shared partials
 * (page_hero(), section_head(), cta_panel(), link_arrow()) written as serialized core blocks,
 * so every pattern that uses them stays identical and valid in the editor.
 *
 * Each builder returns block markup exactly as the core block's save() outputs it. Class
 * names starting with `ot-` are layout bridges defined in assets/css/wp.css (they stand in
 * for the prototype's inline layout styles, which core blocks cannot carry).
 *
 * @package oberdorfer
 */

defined( 'ABSPATH' ) || exit;

/** A core/paragraph. $html is trusted inline HTML from the pattern source. */
function oberdorfer_p( $html, $class = '' ) {
	$a = $class ? ' {"className":"' . $class . '"}' : '';
	$c = $class ? ' class="' . esc_attr( $class ) . '"' : '';
	return "<!-- wp:paragraph{$a} -->\n<p{$c}>{$html}</p>\n<!-- /wp:paragraph -->\n\n";
}

/** A core/heading. $html is trusted inline HTML; $id becomes the heading's anchor. */
function oberdorfer_h( $html, $level = 2, $class = '', $id = '' ) {
	$attrs = array();
	if ( 2 !== $level ) {
		$attrs[] = '"level":' . (int) $level;
	}
	if ( $class ) {
		$attrs[] = '"className":"' . $class . '"';
	}
	$a   = $attrs ? ' {' . implode( ',', $attrs ) . '}' : '';
	$cls = trim( 'wp-block-heading ' . $class );
	$i   = $id ? ' id="' . esc_attr( $id ) . '"' : '';
	return "<!-- wp:heading{$a} -->\n<h{$level} class=\"{$cls}\"{$i}>{$html}</h{$level}>\n<!-- /wp:heading -->\n\n";
}

/** The two-line answering headline: spans for each line. */
function oberdorfer_lines( $lines ) {
	return implode( '', array_map( fn( $l ) => '<span>' . $l . '</span>', $lines ) );
}

/**
 * A core/group. $inner is block markup. $anchor becomes the element id, $label its
 * aria-label. Class tokens `ot-labelledby-<id>`, `ot-data-<name>`, `ot-role-<role>` and
 * `ot-hidden` become attributes at render time (functions.php).
 */
function oberdorfer_group( $inner, $class = '', $tag = 'div', $anchor = '', $label = '' ) {
	$attrs = array();
	if ( 'div' !== $tag ) {
		$attrs[] = '"tagName":"' . $tag . '"';
	}
	if ( $class ) {
		$attrs[] = '"className":"' . $class . '"';
	}
	if ( $label ) {
		$attrs[] = '"ariaLabel":"' . $label . '"';
	}
	$attrs[] = '"layout":{"type":"default"}';
	$cls     = trim( 'wp-block-group ' . $class );
	$i       = $anchor ? ' id="' . esc_attr( $anchor ) . '"' : '';
	$l       = $label ? ' aria-label="' . esc_attr( $label ) . '"' : '';
	return '<!-- wp:group {' . implode( ',', $attrs ) . "} -->\n<{$tag}{$i} class=\"{$cls}\"{$l}>" . trim( $inner ) . "</{$tag}>\n<!-- /wp:group -->\n\n";
}

/** A section with an id and aria-labelledby, as the prototype's `<section id aria-labelledby>`. */
function oberdorfer_section( $inner, $class = 'section', $anchor = '', $labelledby = '' ) {
	return oberdorfer_group( $inner, trim( $class . ( $labelledby ? ' ot-labelledby-' . $labelledby : '' ) ), 'section', $anchor );
}

/** A core/html fragment (only where core blocks cannot reproduce the prototype's markup). */
function oberdorfer_html( $html ) {
	return "<!-- wp:html -->\n" . trim( $html ) . "\n<!-- /wp:html -->\n\n";
}

/**
 * A core/button. With design-system classes it renders as the prototype's bare link
 * (functions.php, render_block_core/button): `btn`, `btn btn--gold`, `link-arrow`
 * (gets the arrow icon), `link-arrow ot-icon-external` ...
 */
function oberdorfer_button( $label, $url, $class = 'btn', $extra_attrs = '' ) {
	$a = $class ? ' {"className":"' . $class . '"}' : '';
	$w = trim( 'wp-block-button ' . $class );
	return "<!-- wp:button{$a} -->\n<div class=\"{$w}\"><a class=\"wp-block-button__link wp-element-button\" href=\"" . esc_url( $url ) . "\"{$extra_attrs}>{$label}</a></div>\n<!-- /wp:button -->\n\n";
}

/** A core/buttons group; with a class it renders as `<div class="...">`, and `ot-unwrap` drops the wrapper. */
function oberdorfer_buttons( $inner, $class = 'cluster' ) {
	$a = $class ? ' {"className":"' . $class . '"}' : '';
	$w = trim( 'wp-block-buttons ' . $class );
	return "<!-- wp:buttons{$a} -->\n<div class=\"{$w}\">" . trim( $inner ) . "</div>\n<!-- /wp:buttons -->\n\n";
}

/** A lone link-arrow, as the prototype's link_arrow(). */
function oberdorfer_link_arrow( $label, $url, $icon = '' ) {
	return oberdorfer_buttons( oberdorfer_button( $label, $url, trim( 'link-arrow ' . $icon ) ), 'ot-unwrap' );
}

/** The team phone as the large display link (.cta-panel__phone). */
function oberdorfer_phone_link( $class = 'cta-panel__phone' ) {
	return oberdorfer_buttons( oberdorfer_button( esc_html( OBERDORFER_PHONE ), 'tel:' . OBERDORFER_TEL, $class ), 'ot-unwrap' );
}

/**
 * The prototype's page_hero() for a page: breadcrumbs (ot/breadcrumbs), the search-facing
 * h1 as the kicker, the brand line as the display paragraph, an optional aside and the lead.
 */
function oberdorfer_page_hero( $lines, $h1, $lead, $aside = '', $navy = false ) {
	$inner  = "<!-- wp:ot/breadcrumbs /-->\n\n";
	$inner .= oberdorfer_h( $h1, 1, 'label page-hero__kicker', 'page-title' );
	$inner .= oberdorfer_p( oberdorfer_lines( $lines ), 'page-hero__title display-l answer' );
	$inner .= $aside;
	$inner .= oberdorfer_p( $lead, 'page-hero__lead lead' );
	$cls    = 'page-hero' . ( $navy ? ' page-hero--navy surface-inverse' : '' );
	return oberdorfer_section( oberdorfer_group( $inner, 'container page-hero__grid' ), $cls, '', 'page-title' );
}

/** The prototype's section_head(): running head, answering title, optional aside link and lead. */
function oberdorfer_section_head( $left, $right, $lines, $lead = '', $aside = null, $title_id = '', $level = 2 ) {
	$inner  = oberdorfer_p( '<span>' . $left . '</span><span class="label--quiet">' . $right . '</span>', 'running-head label' );
	$inner .= oberdorfer_h( oberdorfer_lines( $lines ), $level, 'section-head__title display-m answer', $title_id );
	if ( $aside ) {
		$inner .= oberdorfer_buttons( oberdorfer_button( $aside[0], $aside[1], 'link-arrow' ), 'section-head__aside' );
	}
	if ( $lead ) {
		$inner .= oberdorfer_p( $lead, 'section-head__lead lead' );
	}
	return oberdorfer_group( $inner, 'section-head' );
}

/** The prototype's cta_panel(): the navy closing panel with the team phone and one action. */
function oberdorfer_cta_panel(
	$heading_html = 'For the home you have. <em>For the life you are building.</em>',
	$label = 'A personal standard of service',
	$text = 'Buying, selling, or simply curious about your next move? We would love to be a resource.',
	$cta = array( 'Request a consultation', '/contact/' )
) {
	$left   = oberdorfer_p( $label, 'label ot-text-gold' )
		. oberdorfer_h( $heading_html, 2, 'h1' )
		. oberdorfer_p( $text, 'body-l ot-measure-44' );
	$right  = oberdorfer_phone_link()
		. oberdorfer_p( 'Brandon &amp; Kaitlyn Oberdorfer · Husband-and-wife team · Top Producer', 'caption ot-text-mist' )
		. oberdorfer_buttons( oberdorfer_button( $cta[0], oberdorfer_url( $cta[1] ), 'btn' ), 'cluster' );
	$split  = oberdorfer_group( oberdorfer_group( $left, 'stack ot-col-1-7 ot-stack-5' ) . oberdorfer_group( $right, 'stack ot-col-9-4 ot-stack-4' ), 'split ot-align-end' );
	return oberdorfer_group( oberdorfer_group( oberdorfer_group( $split, 'cta-panel folio-frame ot-frame-tight' ), 'container' ), 'section', 'section' );
}

/**
 * A query loop of journal posts rendered as the prototype's article cards
 * (.card-grid-3 > .article-card: image, category label, title, dek, date), with an empty state.
 * $inherit = true follows the main query (the journal page, archives) and adds pagination.
 */
function oberdorfer_article_query( $per_page = 3, $inherit = false ) {
	$q   = '{"queryId":' . ( $inherit ? 1 : 2 ) . ',"query":{"perPage":' . (int) $per_page . ',"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":' . ( $inherit ? 'true' : 'false' ) . '}}';
	$out = "<!-- wp:query {$q} -->\n<div class=\"wp-block-query\">";
	$out .= "<!-- wp:post-template {\"className\":\"card-grid-3\",\"layout\":{\"type\":\"default\"}} -->\n";
	$out .= oberdorfer_group(
		"<!-- wp:post-featured-image {\"isLink\":true,\"className\":\"article-card__media\"} /-->\n\n"
		. "<!-- wp:post-terms {\"term\":\"category\",\"className\":\"label\"} /-->\n\n"
		. "<!-- wp:post-title {\"level\":3,\"isLink\":true,\"className\":\"article-card__title\"} /-->\n\n"
		. "<!-- wp:post-excerpt {\"className\":\"body-s muted\",\"excerptLength\":24} /-->\n\n"
		. "<!-- wp:post-date {\"className\":\"article-card__meta\"} /-->",
		'article-card'
	);
	$out .= "<!-- /wp:post-template -->\n\n";
	if ( $inherit ) {
		$out .= "<!-- wp:query-pagination {\"className\":\"ot-mt-7\",\"layout\":{\"type\":\"flex\",\"justifyContent\":\"space-between\"}} -->\n<!-- wp:query-pagination-previous /-->\n\n<!-- wp:query-pagination-numbers /-->\n\n<!-- wp:query-pagination-next /-->\n<!-- /wp:query-pagination -->\n\n";
	}
	$out .= "<!-- wp:query-no-results -->\n" . oberdorfer_p( 'No articles yet. New issues add to the journal each season.', 'body-s muted' ) . "<!-- /wp:query-no-results --></div>\n<!-- /wp:query -->\n\n";
	return $out;
}

/** One accordion item: core/details whose body is the prototype's .accordion__body. */
function oberdorfer_details( $summary, $body_blocks, $open = false ) {
	$a = $open ? ' {"showContent":true}' : '';
	$o = $open ? ' open' : '';
	return "<!-- wp:details{$a} -->\n<details class=\"wp-block-details\"{$o}><summary>{$summary}</summary>" . oberdorfer_group( $body_blocks, 'accordion__body body-s' ) . "</details>\n<!-- /wp:details -->\n\n";
}
