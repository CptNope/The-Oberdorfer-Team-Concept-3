<?php
/**
 * The main pages (Home, Buy, Sell, Market, Team, Join, Contact) are edited in the page itself.
 *
 * Each page's template shows only its body (core/post-content). The body starts as the
 * theme's sections for that page, listed here: ot-core's importer copies them into the page
 * once (and never over the team's edits), and while a body is still empty the sections render
 * from the patterns, so a page is never blank between a theme update and an import.
 *
 * @package oberdorfer
 */

defined( 'ABSPATH' ) || exit;

/** Page slug => the patterns that make up its body, in order. */
function oberdorfer_page_sections() {
	return array(
		'home' => array( 'home-cover', 'team-intro', 'featured-listings', 'buy-sell-paths', 'promise-band', 'communities-grid', 'market-teaser', 'team-standard', 'journal-latest', 'closing-cta' ),
		'buy' => array( 'page-hero-buy', 'team-listings', 'idx-search-results', 'buyer-steps', 'buying-in-massachusetts', 'buyer-consultation' ),
		'sell' => array( 'page-hero-sell', 'seller-steps', 'fall-edit', 'presentation-feature', 'selling-in-massachusetts', 'review-form', 'closing-cta' ),
		'market' => array( 'page-hero-market', 'market-stats', 'town-market-table', 'market-spotlight', 'sold-vs-asking', 'closing-cta-market' ),
		'team' => array( 'page-hero-team', 'agent-directory', 'join-teaser' ),
		'join' => array( 'page-hero-join', 'leadership-letter', 'brand-presentation', 'recruiting-form' ),
		'contact' => array( 'page-hero-contact', 'contact-tabs', 'privacy-notes' ),
	);
}

/** The starting block markup of a page's body, or '' for a page with no sections. */
function oberdorfer_page_body( $slug ) {
	$sections = oberdorfer_page_sections()[ $slug ] ?? array();
	$reg      = WP_Block_Patterns_Registry::get_instance();
	$out      = array();
	foreach ( $sections as $name ) {
		$p = $reg->get_registered( 'oberdorfer/' . $name );
		if ( $p && ! empty( $p['content'] ) ) {
			$out[] = trim( $p['content'] );
		}
	}
	// Links and images on this site are written root-relative, so the body moves between
	// Local, staging and oberdorferteam.com without a search-and-replace.
	$home = untrailingslashit( home_url() );
	return str_replace( array( $home . '/', '"' . $home . '"' ), array( '/', '"/"' ), implode( "\n\n", $out ) );
}

/** The slug a page's body is keyed on: the front page is "home". */
function oberdorfer_page_slug( $post_id ) {
	if ( (int) get_option( 'page_on_front' ) === (int) $post_id ) {
		return 'home';
	}
	return (string) get_post_field( 'post_name', $post_id );
}

/* A page whose body is still empty shows its sections from the patterns. */
add_filter( 'render_block_core/post-content', function ( $html, $block ) {
	if ( is_admin() || ! str_contains( ' ' . ( $block['attrs']['className'] ?? '' ) . ' ', ' ot-page-body ' ) ) {
		return $html;
	}
	$id = get_queried_object_id();
	if ( ! $id || '' !== trim( (string) get_post_field( 'post_content', $id ) ) ) {
		return $html;
	}
	$body = oberdorfer_page_body( oberdorfer_page_slug( $id ) );
	return '' === $body ? $html : '<div class="entry-content wp-block-post-content ot-page-body">' . do_blocks( $body ) . '</div>';
}, 10, 2 );
