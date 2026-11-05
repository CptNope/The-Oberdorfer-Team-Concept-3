<?php
/**
 * Listings (IDX) switch. Until the IDX provider is chosen (docs/idx-options.md), everything that
 * searches or shows listings can be turned off without removing it:
 *   - blocks whose class list includes `ot-idx` (the theme's Featured homes and Buy-page search sections);
 *   - HTML elements marked `data-ot-idx` inside core/html blocks (the header search icon, the home
 *     search bar, the "Search homes" label in the mobile menu);
 *   - the ot/listings-placeholder block.
 * Hidden items are removed from the page on the server, not hidden with CSS.
 *
 * Settings > Listings (IDX) holds the switch and the provider's shortcodes, so going live is:
 * install the provider's plugin, paste its shortcodes, tick the box, save. A constant in
 * wp-config.php overrides the setting (define( 'OT_IDX_ENABLED', true ); or false).
 */

defined( 'ABSPATH' ) || exit;

function ot_idx_enabled() {
	if ( defined( 'OT_IDX_ENABLED' ) ) {
		return (bool) OT_IDX_ENABLED;
	}
	return (bool) get_option( 'ot_idx_enabled', false );
}

/** The provider's shortcode for a placement ('search' on the Buy page, 'featured' on the home page). */
function ot_idx_shortcode( $placement ) {
	return trim( (string) get_option( 'featured' === $placement ? 'ot_idx_featured_shortcode' : 'ot_idx_search_shortcode', '' ) );
}

/* --------------------------------------------------------------------- front end */

add_filter( 'render_block', function ( $html, $block ) {
	if ( ot_idx_enabled() || is_admin() ) {
		return $html;
	}
	$classes = ' ' . ( $block['attrs']['className'] ?? '' ) . ' ';
	if ( str_contains( $classes, ' ot-idx ' ) ) {
		return '';
	}
	if ( 'core/html' === ( $block['blockName'] ?? '' ) && str_contains( $html, 'data-ot-idx' ) ) {
		$html = preg_replace( '#<(form|a|span|div|section)\b[^>]*\bdata-ot-idx\b[^>]*>.*?</\1>#s', '', $html );
	}
	return $html;
}, 10, 2 );

add_filter( 'body_class', function ( $classes ) {
	$classes[] = ot_idx_enabled() ? 'ot-idx-on' : 'ot-idx-off';
	return $classes;
} );

/* --------------------------------------------------------------------- settings */

add_action( 'admin_init', function () {
	register_setting( 'ot_idx', 'ot_idx_enabled', array( 'type' => 'boolean', 'default' => false, 'sanitize_callback' => 'rest_sanitize_boolean' ) );
	register_setting( 'ot_idx', 'ot_idx_provider', array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
	register_setting( 'ot_idx', 'ot_idx_search_shortcode', array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'ot_idx_sanitize_shortcode' ) );
	register_setting( 'ot_idx', 'ot_idx_featured_shortcode', array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'ot_idx_sanitize_shortcode' ) );
} );

/** Shortcodes only: square-bracket text, no HTML or scripts. */
function ot_idx_sanitize_shortcode( $v ) {
	return trim( wp_strip_all_tags( (string) $v ) );
}

add_action( 'admin_menu', function () {
	add_options_page( 'Listings (IDX)', 'Listings (IDX)', 'manage_options', 'ot-idx', 'ot_idx_screen' );
} );

function ot_idx_screen() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$locked = defined( 'OT_IDX_ENABLED' );
	echo '<div class="wrap"><h1>Listings (IDX)</h1>';
	echo '<p>While listings are off, the site hides everything that searches or shows homes for sale: the home page search bar and Featured homes section, the header search icon, the "Search homes" label in the mobile menu, and the search section of the Buy page. Nothing is deleted; switching listings on brings it all back.</p>';
	if ( $locked ) {
		echo '<div class="notice notice-warning inline"><p>The switch is set in wp-config.php (<code>OT_IDX_ENABLED</code> is ' . ( OT_IDX_ENABLED ? 'true' : 'false' ) . '), so the checkbox below has no effect until that line is removed.</p></div>';
	}
	echo '<form method="post" action="options.php">';
	settings_fields( 'ot_idx' );
	echo '<table class="form-table" role="presentation"><tbody>';
	printf(
		'<tr><th scope="row">Show listings</th><td><label><input type="checkbox" name="ot_idx_enabled" value="1"%s%s> Show home search and listings on the site</label><p class="description">Currently <strong>%s</strong>.</p></td></tr>',
		checked( (bool) get_option( 'ot_idx_enabled', false ), true, false ),
		$locked ? ' disabled' : '',
		ot_idx_enabled() ? 'on' : 'off'
	);
	printf(
		'<tr><th scope="row"><label for="ot_idx_provider">IDX provider</label></th><td><input class="regular-text" id="ot_idx_provider" name="ot_idx_provider" value="%s" placeholder="For example, the plugin name"><p class="description">For reference only. Options and costs: docs/idx-options.md.</p></td></tr>',
		esc_attr( get_option( 'ot_idx_provider', '' ) )
	);
	printf(
		'<tr><th scope="row"><label for="ot_idx_search_shortcode">Buy page search shortcode</label></th><td><textarea class="large-text code" rows="2" id="ot_idx_search_shortcode" name="ot_idx_search_shortcode" placeholder="[provider_search ...]">%s</textarea><p class="description">The provider\'s search and results shortcode. Shown in the Buy page\'s search section; empty shows the "IDX to be decided" placeholder.</p></td></tr>',
		esc_textarea( get_option( 'ot_idx_search_shortcode', '' ) )
	);
	printf(
		'<tr><th scope="row"><label for="ot_idx_featured_shortcode">Featured homes shortcode</label></th><td><textarea class="large-text code" rows="2" id="ot_idx_featured_shortcode" name="ot_idx_featured_shortcode" placeholder="[provider_listings ...]">%s</textarea><p class="description">The provider\'s featured or office listings shortcode, shown on the home page. Only listings the team is authorized to advertise, with MLS PIN attribution.</p></td></tr>',
		esc_textarea( get_option( 'ot_idx_featured_shortcode', '' ) )
	);
	echo '</tbody></table>';
	submit_button();
	echo '</form>';
	echo '<p class="description">After changing this, purge the page caches (Breeze, then Cloudflare) so visitors see the change.</p></div>';
}

/** A reminder in the admin bar while listings are off, so nobody forgets the switch exists. */
add_action( 'admin_bar_menu', function ( $bar ) {
	if ( ! current_user_can( 'manage_options' ) || ot_idx_enabled() ) {
		return;
	}
	$bar->add_node( array(
		'id'    => 'ot-idx-off',
		'title' => 'Listings: off',
		'href'  => admin_url( 'options-general.php?page=ot-idx' ),
	) );
}, 90 );
