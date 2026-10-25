<?php
/**
 * Title: Office hero
 * Slug: oberdorfer/office-hero
 * Categories: oberdorfer
 * Inserter: no
 * Description: Hero of an office page: breadcrumbs, the h1 "Our office in (city), Massachusetts", the brand line "A place to sit down and plan your move.", the "Hours to be confirmed" badge and the lead. The city comes from the office's ot_city field.
 */
$id   = (int) get_queried_object_id();
$city = ( $id ? get_post_meta( $id, 'ot_city', true ) : '' ) ?: OBERDORFER_CITY;
$c    = esc_html( $city );
echo oberdorfer_page_hero( // phpcs:ignore WordPress.Security.EscapeOutput
	array( 'A place to sit down', 'and plan your move.' ),
	'Our office in ' . $c . ', Massachusetts',
	'The Oberdorfer Team is opening its new office in ' . $c . ' and serves clients across Massachusetts. Visits are by appointment.',
	oberdorfer_p( '<span class="badge badge--verify">Hours to be confirmed</span>', 'page-hero__aside' )
);
