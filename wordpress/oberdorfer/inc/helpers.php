<?php
/**
 * Markup helpers for the theme's PHP patterns, ported from the prototype's atoms in
 * tools/build_site.py (icon(), arrow(), art()). Presentation only: no data lives here except
 * the confirmed team phone and office address used as fallbacks (CLAUDE.md, ground rule 2).
 *
 * @package oberdorfer
 */

defined( 'ABSPATH' ) || exit;

/* Confirmed by the client (2026-10-15 and 2026-10-22). */
const OBERDORFER_PHONE    = '406-250-7620';
const OBERDORFER_TEL      = '+14062507620';
const OBERDORFER_STREET   = '652 Park Ave, Unit B';
const OBERDORFER_CITY     = 'Worcester';
const OBERDORFER_POSTCODE = '01603';

/** URL of a file shipped in the theme (assets are copied in by tools/build_wp.py). */
function oberdorfer_asset( $path ) {
	return get_theme_file_uri( $path );
}

/** URL of a page on this site, e.g. oberdorfer_url( '/sell/' ). */
function oberdorfer_url( $path = '/' ) {
	return home_url( $path );
}

/** An icon from the sprite, as the prototype's icon(). */
function oberdorfer_icon( $name, $cls = 'icon', $vb = null ) {
	$v = $vb ? ' viewBox="' . esc_attr( $vb ) . '"' : '';
	return '<svg class="' . esc_attr( $cls ) . '" aria-hidden="true"' . $v . '><use href="' . esc_url( oberdorfer_asset( 'assets/img/icons.svg' ) ) . '#i-' . esc_attr( $name ) . '"/></svg>';
}

/** The long editorial arrow used by .link-arrow. */
function oberdorfer_arrow() {
	return oberdorfer_icon( 'arrow', 'icon', '0 0 34 12' );
}

/** A house illustration from the illustrations sprite, as the prototype's art(). */
function oberdorfer_art( $name, $label = null ) {
	$a = $label ? 'role="img" aria-label="' . esc_attr( $label ) . '"' : 'aria-hidden="true"';
	return '<svg viewBox="0 0 400 260" ' . $a . '><use href="' . esc_url( oberdorfer_asset( 'assets/img/illustrations.svg' ) ) . '#' . esc_attr( $name ) . '"/></svg>';
}

/** The office address in one line, as the prototype's ADDRESS. */
function oberdorfer_address() {
	return OBERDORFER_STREET . ', ' . OBERDORFER_CITY . ', MA ' . OBERDORFER_POSTCODE;
}

/** Google Maps search link for an address, as the prototype's MAPS. */
function oberdorfer_maps_url( $address = null ) {
	$address = $address ?: oberdorfer_address();
	return 'https://www.google.com/maps/search/?api=1&query=' . str_replace( array( ' ', ',' ), array( '+', '%2C' ), $address );
}
