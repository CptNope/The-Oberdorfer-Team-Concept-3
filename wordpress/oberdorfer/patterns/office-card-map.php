<?php
/**
 * Title: Office card and map
 * Slug: oberdorfer/office-card-map
 * Categories: oberdorfer
 * Inserter: no
 * Description: The illustrative map plate and the townscape drawing beside the office card (city, address with directions, phone, hours placeholder, brokerage status, agents) and the appointment form (ot/inquiry-form, context "office").
 *
 * The address comes from the office's fields (ot_street, ot_city, ot_postcode, ot_record);
 * without them it falls back to the confirmed Worcester office, 652 Park Ave, Unit B.
 * Unconfirmed items (hours, formal status) stay placeholders. The map is illustrative only.
 */
$id       = (int) get_queried_object_id();
$meta     = fn( $k ) => $id ? (string) get_post_meta( $id, $k, true ) : '';
$rec      = json_decode( $meta( 'ot_record' ) ?: '[]', true ) ?: array();
$street   = $meta( 'ot_street' ) ?: OBERDORFER_STREET;
$city     = $meta( 'ot_city' ) ?: OBERDORFER_CITY;
$postcode = $meta( 'ot_postcode' ) ?: OBERDORFER_POSTCODE;
$maps     = ! empty( $rec['maps'] ) ? $rec['maps'] : oberdorfer_maps_url( $street . ', ' . $city . ', MA ' . $postcode );
$hours    = ! empty( $rec['hours'] ) ? esc_html( $rec['hours'] ) : '<span class="placeholder-value">[Hours to be confirmed]</span>';
$status   = ! empty( $rec['status'] ) ? esc_html( $rec['status'] ) : 'A team office of REWAP Brokerage LLC (formal status to be confirmed)';

$left = oberdorfer_html( '<div class="map-frame"><svg viewBox="0 0 800 500" role="img" aria-label="Illustrative map placeholder"><rect width="800" height="500" fill="#E9E2D5"/><g stroke="#FBF9F5" stroke-width="10" fill="none" stroke-linecap="round"><path d="M0 300C200 280 360 330 800 260"/><path d="M300 0C320 160 280 340 330 500"/><path d="M800 420C600 400 450 440 300 500"/></g><g transform="translate(338 284)"><circle r="20" fill="#071D2D"/><circle r="27" fill="none" stroke="#B49A65"/></g></svg><p class="map-frame__note"><span class="badge badge--demo">Illustrative map · no real location</span></p></div>
<div class="demo-illu demo-illu--light" style="aspect-ratio:16/7;overflow:hidden">' . oberdorfer_art( 'townscape', 'Illustration standing in for office photography' ) . '</div>' );

$card = oberdorfer_group(
	oberdorfer_h( 'The Oberdorfer Team Office', 2, 'h4' )
	. oberdorfer_html( '<dl><dt>City</dt><dd>' . esc_html( $city ) . ', Massachusetts</dd><dt>Address</dt><dd>' . esc_html( $street ) . '<br>' . esc_html( $city ) . ', MA ' . esc_html( $postcode ) . '<br><a href="' . esc_url( $maps ) . '" rel="noopener">Get directions</a></dd><dt>Phone</dt><dd><a href="tel:' . OBERDORFER_TEL . '">' . OBERDORFER_PHONE . '</a></dd><dt>Hours</dt><dd>' . $hours . '</dd><dt>Brokerage</dt><dd>' . $status . '</dd><dt>Agents</dt><dd><a href="' . esc_url( oberdorfer_url( '/agents/brandon-kaitlyn-oberdorfer/' ) ) . '">Brandon &amp; Kaitlyn Oberdorfer</a></dd></dl>' ),
	'office-card'
);
$form = oberdorfer_group( '<!-- wp:ot/inquiry-form {"context":"office","title":"Request an appointment","topics":["Buying","Selling","Joining the team","Other"],"submit":"Request an appointment","level":"h3"} /-->', 'panel' );
echo oberdorfer_section( oberdorfer_group( oberdorfer_group( $left, 'stack ot-col-1-7' ) . oberdorfer_group( $card . $form, 'stack ot-col-9-4' ), 'container split ot-align-start' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
