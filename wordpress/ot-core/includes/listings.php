<?php
/**
 * The team's own listings, entered by hand (Listings > Add listing) while the IDX provider is
 * undecided, and after it too, for coming-soon and off-market homes. They are independent of the
 * Listings (IDX) switch: they show on the home page, the Buy page and the matching town guide.
 *
 * A listing is a post of type ot_listing: the title is the address line, the body is the property
 * story (written in the block editor), the featured image is the main photo, and the fields in the
 * "Listing details" box hold the facts. Only homes the team is authorized to advertise.
 */

defined( 'ABSPATH' ) || exit;

const OT_LISTING_STATUSES = array(
	'coming-soon'     => array( 'Coming soon', 'badge--proposed' ),
	'active'          => array( 'For sale', 'badge--source' ),
	'under-agreement' => array( 'Under agreement', 'badge--demo' ),
	'sold'            => array( 'Sold', 'badge--verify' ),
);

/** Field key => [label, type, help]. Stored as post meta with the key as written. */
function ot_listing_fields() {
	return array(
		'ot_status'     => array( 'Status', 'status', '' ),
		'ot_price'      => array( 'Price ($)', 'int', 'Asking price, or the sale price once sold. Digits only.' ),
		'ot_street'     => array( 'Street address', 'text', 'For example, 12 Elm Street. Shown on the listing.' ),
		'ot_unit'       => array( 'Unit', 'text', 'Optional, for condominiums.' ),
		'ot_town_slug'  => array( 'Town or neighborhood guide', 'town', 'Links the listing to its guide and shows it there.' ),
		'ot_town'       => array( 'Town (as written in the address)', 'text', 'For example, Worcester. Filled from the guide when left empty.' ),
		'ot_zip'        => array( 'ZIP code', 'text', '' ),
		'ot_kind'       => array( 'Home type', 'kind', '' ),
		'ot_style'      => array( 'Style', 'text', 'For example, Colonial, Cape, Triple-decker.' ),
		'ot_beds'       => array( 'Bedrooms', 'text', '' ),
		'ot_baths'      => array( 'Bathrooms', 'text', 'For example, 2.5.' ),
		'ot_sqft'       => array( 'Living area (sq ft)', 'int', '' ),
		'ot_lot'        => array( 'Lot', 'text', 'For example, 0.25 acres.' ),
		'ot_year'       => array( 'Year built', 'text', '' ),
		'ot_open_house' => array( 'Open house', 'text', 'For example, Sunday, November 9, 1 to 3 pm. Leave empty when none is scheduled.' ),
		'ot_mls'        => array( 'MLS number', 'text', 'Optional.' ),
		'ot_courtesy'   => array( 'Listing brokerage', 'text', 'Shown as "Listing courtesy of ...". Defaults to REWAP Brokerage LLC.' ),
		'ot_agent'      => array( 'Listing agent', 'agent', '' ),
		'ot_featured'   => array( 'Feature on the home page', 'bool', 'Featured listings come first on the home page.' ),
		'ot_gallery'    => array( 'More photos', 'gallery', 'The featured image is the main photo; these follow it in the gallery.' ),
	);
}

add_action( 'init', function () {
	register_post_type( 'ot_listing', array(
		'labels'        => array(
			'name'          => __( 'Listings', 'ot-core' ),
			'singular_name' => __( 'Listing', 'ot-core' ),
			'add_new_item'  => __( 'Add listing', 'ot-core' ),
			'edit_item'     => __( 'Edit listing', 'ot-core' ),
			'all_items'     => __( 'All listings', 'ot-core' ),
			'featured_image'     => __( 'Main photo', 'ot-core' ),
			'set_featured_image' => __( 'Set main photo', 'ot-core' ),
		),
		'description'   => 'The team\'s own listings, entered by hand.',
		'public'        => true,
		'show_in_rest'  => true,
		'menu_icon'     => 'dashicons-admin-home',
		'menu_position' => 20,
		'has_archive'   => false,
		'rewrite'       => array( 'slug' => 'listings', 'with_front' => false ),
		'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes' ),
		'template'      => array( array( 'core/paragraph', array( 'placeholder' => 'The property story: the light, the layout, the street and what is nearby. Describe the home, not who it would suit.' ) ) ),
	) );
	$auth = fn() => current_user_can( 'edit_posts' );
	foreach ( ot_listing_fields() as $key => $f ) {
		$type = in_array( $f[1], array( 'int' ), true ) ? 'integer' : ( 'bool' === $f[1] ? 'boolean' : 'string' );
		register_post_meta( 'ot_listing', $key, array( 'type' => $type, 'single' => true, 'show_in_rest' => true, 'auth_callback' => $auth ) );
	}
	register_post_meta( 'ot_listing', 'ot_seo_title', array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'auth_callback' => $auth ) );
	register_post_meta( 'ot_listing', 'ot_seo_description', array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'auth_callback' => $auth ) );
} );

/* --------------------------------------------------------------------- data */

/** A listing's facts as an array. */
function ot_listing( $post ) {
	$post = get_post( $post );
	if ( ! $post || 'ot_listing' !== $post->post_type ) {
		return null;
	}
	$l = array( 'id' => $post->ID, 'slug' => $post->post_name, 'url' => get_permalink( $post ), 'title' => get_the_title( $post ) );
	foreach ( array_keys( ot_listing_fields() ) as $k ) {
		$l[ substr( $k, 3 ) ] = get_post_meta( $post->ID, $k, true );
	}
	$l['status'] = isset( OT_LISTING_STATUSES[ $l['status'] ] ) ? $l['status'] : 'active';
	if ( ! $l['town'] && $l['town_slug'] ) {
		$g = get_page_by_path( $l['town_slug'], OBJECT, 'ot_community' );
		if ( $g ) {
			$l['town'] = $g->post_parent ? get_the_title( $g->post_parent ) : get_the_title( $g );
		}
	}
	$l['courtesy'] = $l['courtesy'] ?: 'REWAP Brokerage LLC';
	$l['address']  = trim( $l['street'] . ( $l['unit'] ? ', Unit ' . $l['unit'] : '' ) ) ?: $l['title'];
	$l['photo_id'] = (int) get_post_thumbnail_id( $post );
	$l['gallery']  = array_values( array_filter( array_map( 'intval', explode( ',', (string) $l['gallery'] ) ) ) );
	return $l;
}

/**
 * Listings to show. $args: limit (int), statuses (array), town (guide path like "worcester" or
 * "boston/roxbury"; a city also matches its neighborhoods), exclude (post ID).
 */
function ot_listings( $args = array() ) {
	$args = wp_parse_args( $args, array( 'limit' => 6, 'statuses' => array( 'coming-soon', 'active', 'under-agreement' ), 'town' => '', 'exclude' => 0 ) );
	$q    = array(
		'post_type'      => 'ot_listing',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		'no_found_rows'  => true,
	);
	if ( $args['exclude'] ) {
		$q['post__not_in'] = array( (int) $args['exclude'] );
	}
	$out = array();
	foreach ( get_posts( $q ) as $p ) {
		$l = ot_listing( $p );
		if ( ! in_array( $l['status'], (array) $args['statuses'], true ) ) {
			continue;
		}
		if ( $args['town'] ) {
			$t = trim( $args['town'], '/' );
			if ( $l['town_slug'] !== $t && ! str_starts_with( (string) $l['town_slug'], $t . '/' ) ) {
				continue;
			}
		}
		$out[] = $l;
	}
	usort( $out, fn( $a, $b ) => (int) (bool) $b['featured'] <=> (int) (bool) $a['featured'] );
	return array_slice( $out, 0, max( 1, (int) $args['limit'] ) );
}

function ot_has_listings() {
	static $has = null;
	return $has ?? ( $has = (bool) ot_listings( array( 'limit' => 1 ) ) );
}

/* --------------------------------------------------------------------- rendering */

function ot_listing_badge( $l ) {
	$s = OT_LISTING_STATUSES[ $l['status'] ];
	return '<span class="badge ' . $s[1] . '">' . esc_html( $s[0] ) . '</span>';
}

function ot_listing_specs( $l ) {
	$out = '';
	if ( '' !== (string) $l['beds'] ) {
		$out .= '<span><b>' . esc_html( $l['beds'] ) . '</b> beds</span>';
	}
	if ( '' !== (string) $l['baths'] ) {
		$out .= '<span><b>' . esc_html( $l['baths'] ) . '</b> baths</span>';
	}
	if ( $l['sqft'] ) {
		$out .= '<span><b>' . ot_num( $l['sqft'] ) . '</b> sq ft</span>';
	}
	if ( $l['style'] ) {
		$out .= '<span>' . esc_html( $l['style'] ) . '</span>';
	}
	return $out;
}

function ot_listing_card( $l ) {
	$media = $l['photo_id']
		? wp_get_attachment_image( $l['photo_id'], 'large', false, array( 'loading' => 'lazy', 'alt' => 'Front of ' . $l['address'] . ', ' . $l['town'] ) )
		: '<div class="demo-illu" style="position:absolute;inset:0">' . ot_art( 'colonial', 'Photo to come' ) . '</div>';
	$price = $l['price'] ? '<p class="listing-card__price tabular">' . ot_money( $l['price'] ) . '</p>' : '';
	$open  = $l['open_house'] && 'sold' !== $l['status'] ? 'Open house ' . esc_html( $l['open_house'] ) . ' · ' : '';
	return '<article class="listing-card">
  <div class="listing-card__media">' . $media . ot_listing_badge( $l ) . '</div>
  ' . $price . '
  <h3 class="listing-card__address"><a href="' . esc_url( $l['url'] ) . '" style="text-decoration:none;color:inherit">' . esc_html( $l['address'] ) . ( $l['town'] ? ' · ' . esc_html( $l['town'] ) . ', MA' : '' ) . '</a></h3>
  <p class="listing-card__specs">' . ot_listing_specs( $l ) . '</p>
  <p class="listing-card__courtesy">' . $open . 'Listing courtesy of ' . esc_html( $l['courtesy'] ) . '</p>
</article>';
}

/** The grid of team listings, or nothing when there are none. */
function ot_render_team_listings( $limit = 6, $town = '' ) {
	$ls = ot_listings( array( 'limit' => $limit, 'town' => $town ) );
	if ( ! $ls ) {
		return '';
	}
	return '<div class="listing-grid">' . implode( '', array_map( 'ot_listing_card', $ls ) ) . '</div>';
}

/** Listings section on a community guide (only when the town has listings). */
function ot_render_community_homes( $x ) {
	$path = $x['is_nbhd'] ? $x['city']['slug'] . '/' . $x['slug'] : $x['slug'];
	$grid = ot_render_team_listings( 6, $path );
	if ( ! $grid ) {
		return '';
	}
	$name = esc_html( $x['name'] );
	return '<section class="section" id="homes" aria-labelledby="homes-title"><div class="container">' . ot_section_head( 'Homes for sale', 'Brandon &amp; Kaitlyn\'s listings', array( 'Homes in ' . $name . '.' ), null, ot_link_arrow( ot_page_url( 'buy' ), 'All homes' ), 'h2', 'homes-title' ) . $grid . '</div></section>';
}

function ot_listing_gallery_html( $l ) {
	$photos = array_values( array_filter( array_merge( array( $l['photo_id'] ), $l['gallery'] ) ) );
	$gallery = '';
	if ( $photos ) {
		$tiles = '';
		$span = count( $photos ) < 3 ? ' style="grid-row:auto"' : '';
		foreach ( array_slice( $photos, 0, 3 ) as $i => $id ) {
			$tiles .= '<div' . $span . '>' . wp_get_attachment_image( $id, 0 === $i ? 'full' : 'large', false, array( 'loading' => 0 === $i ? 'eager' : 'lazy', 'fetchpriority' => 0 === $i ? 'high' : 'auto' ) ) . '</div>';
		}
		$n       = min( 3, count( $photos ) );
		// One photo fills the frame; two sit side by side; three or more use the prototype's gallery.
		$style   = 1 === $n ? 'aspect-ratio:16/7.5;grid-template-columns:1fr;grid-template-rows:1fr' : ( 2 === $n ? 'aspect-ratio:16/7.5;grid-template-rows:1fr' : 'aspect-ratio:16/7.5' );
		$gallery = '<div class="gallery" style="' . $style . '">' . $tiles . '</div>';
		if ( count( $photos ) > 3 ) {
			$more = '';
			foreach ( array_slice( $photos, 3 ) as $id ) {
				$more .= wp_get_attachment_image( $id, 'large', false, array( 'loading' => 'lazy' ) );
			}
			$gallery .= '<details class="listing-photos"><summary class="btn btn--secondary btn--small">All ' . count( $photos ) . ' photos</summary><div class="card-grid-3" style="margin-top:var(--space-5)">' . $more . '</div></details>';
		}
	} else {
		$gallery = '<div class="demo-illu demo-illu--light" style="aspect-ratio:16/6">' . ot_art( 'colonial', 'Photos to come' ) . '</div>';
	}
	return $gallery;
}

/** Facts row, details table, links and the aside (form and contact) of a listing page. */
function ot_listing_detail_parts( $l, $post_id ) {
	$where = trim( ( $l['town'] ? $l['town'] . ', Massachusetts' : 'Massachusetts' ) . ( $l['zip'] ? ' ' . $l['zip'] : '' ) );
	$facts = '';
	foreach ( array( array( $l['beds'], 'Bedrooms' ), array( $l['baths'], 'Baths' ), array( $l['sqft'] ? ot_num( $l['sqft'] ) : '', 'Sq. ft.' ), array( $l['year'], 'Built' ) ) as $f ) {
		if ( '' !== (string) $f[0] ) {
			$facts .= '<div><b>' . esc_html( $f[0] ) . '</b><span>' . $f[1] . '</span></div>';
		}
	}
	$rows = '';
	foreach ( array( 'Status' => OT_LISTING_STATUSES[ $l['status'] ][0], 'Home type' => $l['kind'], 'Style' => $l['style'], 'Lot' => $l['lot'], 'MLS number' => $l['mls'] ) as $k => $v ) {
		if ( $v ) {
			$rows .= '<tr><th scope="row">' . $k . '</th><td>' . esc_html( $v ) . '</td></tr>';
		}
	}
	$post  = get_post( $post_id );
	$story = trim( (string) $post->post_content ) ? apply_filters( 'the_content', $post->post_content ) : '';
	$guide = '';
	if ( $l['town_slug'] ) {
		$g = get_page_by_path( $l['town_slug'], OBJECT, 'ot_community' );
		if ( $g && ! ot_community_off( $g->ID ) ) {
			$guide = ot_link_arrow( get_permalink( $g ), 'Living in ' . get_the_title( $g ) );
		}
	}
	$maps   = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $l['address'] . ', ' . $where );
	$agent  = $l['agent'] ? get_page_by_path( $l['agent'], OBJECT, 'ot_agent' ) : null;
	$who    = $agent ? get_the_title( $agent ) : 'Brandon &amp; Kaitlyn Oberdorfer';
	$phone  = $agent ? ( get_post_meta( $agent->ID, 'ot_phone', true ) ?: OT_PHONE ) : OT_PHONE;
	$form   = ot_inquiry_form( 'show', 'Request a showing', array( 'This week, weekday', 'This week, weekend', 'Next week', 'Flexible' ), 'Request a showing', 'showing:' . $l['slug'], 'h2', 'Your request is attached to ' . $l['address'] . ' and goes to ' . wp_strip_all_tags( html_entity_decode( $who ) ) . '. [Privacy policy to be published before launch.]' );
	return array(
		'facts' => $facts ? '<div class="facts">' . $facts . '</div>' : '',
		'story' => $story,
		'specs' => ( $rows ? '<div><h2 class="h3" style="margin-bottom:var(--space-4)">Details</h2><table class="spec-table"><tbody>' . $rows . '</tbody></table></div>' : '' )
			. '<div class="cluster">' . ot_link_arrow( $maps, 'See it on a map' ) . $guide . '</div>'
			. '<p class="form-note">Listing courtesy of ' . esc_html( $l['courtesy'] ) . '. Information deemed reliable but not guaranteed; confirm details before you rely on them.</p>',
		'aside' => '<div class="panel">' . $form . '</div>'
			. '<div class="panel" style="grid-template-columns:5rem 1fr;align-items:center;gap:var(--space-4)"><img src="' . esc_url( ot_asset( 'assets/img/photo/team-portrait.webp' ) ) . '" alt="" style="aspect-ratio:1;object-fit:cover;object-position:50% 15%"><div><p class="h5">' . $who . '</p><a href="' . esc_attr( ot_tel_href( $phone ) ) . '" class="body-s">' . esc_html( $phone ) . '</a></div></div>',
	);
}

/** The listing page, one section at a time: hero (with gallery), details, more. */
function ot_render_listing_section( $section, $post_id ) {
	$l = ot_listing( $post_id );
	if ( ! $l ) {
		return '';
	}
	$where = trim( ( $l['town'] ? $l['town'] . ', Massachusetts' : 'Massachusetts' ) . ( $l['zip'] ? ' ' . $l['zip'] : '' ) );
	switch ( $section ) {
		// Pieces used by the block template; the address (post title), the main photo and the
		// story (post content) are core blocks there, edited on the page.
		case 'badge':
			return ot_listing_badge( $l );
		case 'location':
			return '<p class="lead" style="max-width:none">' . esc_html( $where ) . '</p>';
		case 'price':
			return ( $l['price'] ? '<p class="display-m tabular" style="font-size:var(--step-h1)">' . ot_money( $l['price'] ) . '</p>' : '' ) . ( $l['open_house'] && 'sold' !== $l['status'] ? '<p class="label">Open house · ' . esc_html( $l['open_house'] ) . '</p>' : '' );
		case 'gallery':
			return ot_listing_gallery_html( $l );
		case 'facts':
		case 'specs':
		case 'aside':
			return ot_listing_detail_parts( $l, $post_id )[ $section ];
		case 'hero':
			$trail  = array( array( 'Home', home_url( '/' ) ), array( 'Buy', ot_page_url( 'buy' ) ), array( esc_html( $l['address'] ), $l['url'] ) );
			$gallery = ot_listing_gallery_html( $l );
			return '<section class="section--tight"><div class="container stack" style="--stack:var(--space-5)">
  ' . ot_breadcrumbs_html( $trail ) . '
  <div class="cluster" style="justify-content:space-between;align-items:end">
    <div class="stack" style="--stack:var(--space-3)">' . ot_listing_badge( $l ) . '<h1 class="display-m" id="page-title">' . esc_html( $l['address'] ) . '</h1><p class="lead" style="max-width:none">' . esc_html( $where ) . '</p></div>
    <div class="stack" style="--stack:var(--space-2);text-align:right">' . ( $l['price'] ? '<p class="display-m tabular" style="font-size:var(--step-h1)">' . ot_money( $l['price'] ) . '</p>' : '' ) . ( $l['open_house'] && 'sold' !== $l['status'] ? '<p class="label">Open house · ' . esc_html( $l['open_house'] ) . '</p>' : '' ) . '</div>
  </div>
  ' . $gallery . '
</div></section>';

		case 'details':
			$d = ot_listing_detail_parts( $l, $post_id );
			return '<section class="section" id="details" style="padding-top:var(--space-6)"><div class="container detail">
  <div class="stack" style="--stack:var(--space-7)">' . $d['facts'] . ( $d['story'] ? '<div class="prose stack"><h2 class="h3">The property</h2>' . $d['story'] . '</div>' : '' ) . $d['specs'] . '</div>
  <aside class="detail__aside" aria-label="Contact about this home">' . $d['aside'] . '</aside>
</div></section>';

		case 'more':
			$more = ot_listings( array( 'limit' => 3, 'exclude' => $post_id ) );
			if ( ! $more ) {
				return '';
			}
			return '<section class="section surface-muted"><div class="container">' . ot_section_head( 'More homes', 'Brandon &amp; Kaitlyn\'s listings', array( 'You may also like' ), null, null, 'h2', 'more-title' ) . '<div class="listing-grid">' . implode( '', array_map( 'ot_listing_card', $more ) ) . '</div></div></section>';
	}
	return '';
}

/* --------------------------------------------------------------------- sections that depend on listings */

/**
 * Theme sections tagged `ot-team-listings` show only when the team has listings; sections tagged
 * `ot-homes` (the home page's Featured homes) show when the team has listings or IDX is on.
 */
add_filter( 'render_block', function ( $html, $block ) {
	if ( is_admin() ) {
		return $html;
	}
	$classes = ' ' . ( $block['attrs']['className'] ?? '' ) . ' ';
	if ( str_contains( $classes, ' ot-team-listings ' ) && ! ot_has_listings() ) {
		return '';
	}
	if ( str_contains( $classes, ' ot-homes ' ) && ! ot_has_listings() && ! ot_idx_enabled() ) {
		return '';
	}
	return $html;
}, 9, 2 );

/* --------------------------------------------------------------------- search output */

add_filter( 'pre_get_document_title', function ( $t ) {
	if ( is_singular( 'ot_listing' ) && ! get_post_meta( get_queried_object_id(), 'ot_seo_title', true ) ) {
		$l = ot_listing( get_queried_object_id() );
		return $l['address'] . ( $l['town'] ? ', ' . $l['town'] . ', MA' : '' ) . ' | The Oberdorfer Team';
	}
	return $t;
}, 21 );

add_action( 'wp_head', function () {
	if ( ! is_singular( 'ot_listing' ) || ! ot_seo_enabled() ) {
		return;
	}
	$l     = ot_listing( get_queried_object_id() );
	$photo = $l['photo_id'] ? wp_get_attachment_image_url( $l['photo_id'], 'large' ) : null;
	$data  = array(
		'@context' => 'https://schema.org', '@type' => 'RealEstateListing', 'url' => ot_prod_url( $l['url'] ),
		'name' => $l['address'] . ( $l['town'] ? ', ' . $l['town'] . ', MA' : '' ),
		'datePosted' => get_the_date( 'Y-m-d', $l['id'] ),
		'about' => array_filter( array(
			'@type' => 'SingleFamilyResidence' === $l['kind'] ? 'SingleFamilyResidence' : 'Residence',
			'address' => array_filter( array( '@type' => 'PostalAddress', 'streetAddress' => $l['address'], 'addressLocality' => $l['town'], 'addressRegion' => 'MA', 'postalCode' => $l['zip'], 'addressCountry' => 'US' ) ),
			'numberOfRooms' => $l['beds'] ?: null,
			'floorSize' => $l['sqft'] ? array( '@type' => 'QuantitativeValue', 'value' => (int) $l['sqft'], 'unitCode' => 'FTK' ) : null,
		) ),
		'image' => $photo,
		'offers' => $l['price'] && 'sold' !== $l['status'] ? array( '@type' => 'Offer', 'price' => (int) $l['price'], 'priceCurrency' => 'USD', 'availability' => 'https://schema.org/InStock' ) : null,
		'provider' => array( '@id' => OT_SITE_URL . '#team' ),
	);
	echo ot_jsonld( array_filter( $data ) ) . "\n";
}, 3 );

/* --------------------------------------------------------------------- admin: the details box */

add_action( 'add_meta_boxes_ot_listing', function () {
	add_meta_box( 'ot-listing-details', 'Listing details', 'ot_listing_box', 'ot_listing', 'normal', 'high' );
} );

function ot_listing_box( $post ) {
	wp_nonce_field( 'ot_listing_save', 'ot_listing_nonce' );
	wp_enqueue_media();
	$guides = get_posts( array( 'post_type' => 'ot_community', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) );
	$agents = get_posts( array( 'post_type' => 'ot_agent', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
	echo '<p>The title is the address line people see (for example, 12 Elm Street). Write the property story in the main editor; set the main photo with "Main photo" in the sidebar. Only homes the team is authorized to advertise. Describe the home and the place, never who it would suit.</p><table class="form-table" role="presentation"><tbody>';
	foreach ( ot_listing_fields() as $key => $f ) {
		$v    = get_post_meta( $post->ID, $key, true );
		$id   = esc_attr( $key );
		$help = $f[2] ? '<p class="description">' . esc_html( $f[2] ) . '</p>' : '';
		echo '<tr><th scope="row"><label for="' . $id . '">' . esc_html( $f[0] ) . '</label></th><td>';
		switch ( $f[1] ) {
			case 'status':
				echo '<select id="' . $id . '" name="' . $id . '">';
				foreach ( OT_LISTING_STATUSES as $k => $s ) {
					echo '<option value="' . esc_attr( $k ) . '"' . selected( $v ?: 'active', $k, false ) . '>' . esc_html( $s[0] ) . '</option>';
				}
				echo '</select>';
				break;
			case 'kind':
				echo '<select id="' . $id . '" name="' . $id . '"><option value="">Choose</option>';
				foreach ( array( 'Single-family', 'Condominium', 'Multi-family', 'Townhouse', 'Land' ) as $k ) {
					echo '<option' . selected( $v, $k, false ) . '>' . esc_html( $k ) . '</option>';
				}
				echo '</select>';
				break;
			case 'town':
				echo '<select id="' . $id . '" name="' . $id . '"><option value="">None</option>';
				foreach ( $guides as $g ) {
					$path = $g->post_parent ? get_post_field( 'post_name', $g->post_parent ) . '/' . $g->post_name : $g->post_name;
					$label = $g->post_parent ? '— ' . get_the_title( $g->post_parent ) . ': ' . $g->post_title : $g->post_title;
					echo '<option value="' . esc_attr( $path ) . '"' . selected( $v, $path, false ) . '>' . esc_html( $label ) . '</option>';
				}
				echo '</select>';
				break;
			case 'agent':
				echo '<select id="' . $id . '" name="' . $id . '"><option value="">Brandon &amp; Kaitlyn (the team)</option>';
				foreach ( $agents as $a ) {
					if ( 'brandon-kaitlyn-oberdorfer' === $a->post_name ) {
						continue;
					}
					echo '<option value="' . esc_attr( $a->post_name ) . '"' . selected( $v, $a->post_name, false ) . '>' . esc_html( $a->post_title ) . '</option>';
				}
				echo '</select>';
				break;
			case 'bool':
				echo '<label><input type="checkbox" id="' . $id . '" name="' . $id . '" value="1"' . checked( (bool) $v, true, false ) . '> Yes</label>';
				break;
			case 'gallery':
				$ids = array_filter( array_map( 'intval', explode( ',', (string) $v ) ) );
				echo '<input type="hidden" id="' . $id . '" name="' . $id . '" value="' . esc_attr( implode( ',', $ids ) ) . '"><div id="ot-gallery-preview" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px">';
				foreach ( $ids as $aid ) {
					echo wp_get_attachment_image( $aid, array( 80, 80 ) );
				}
				echo '</div><button type="button" class="button" id="ot-gallery-pick">Choose photos</button> <button type="button" class="button-link" id="ot-gallery-clear">Clear</button>';
				break;
			default:
				$type = 'int' === $f[1] ? 'number' : 'text';
				echo '<input class="regular-text" type="' . $type . '" id="' . $id . '" name="' . $id . '" value="' . esc_attr( $v ) . '"' . ( 'number' === $type ? ' min="0" step="1"' : '' ) . '>';
		}
		echo $help . '</td></tr>';
	}
	echo '</tbody></table>';
	?>
	<script>
	( function () {
		var pick = document.getElementById( 'ot-gallery-pick' ), field = document.getElementById( 'ot_gallery' ), prev = document.getElementById( 'ot-gallery-preview' ), frame;
		if ( ! pick ) { return; }
		pick.addEventListener( 'click', function () {
			if ( ! frame ) {
				frame = wp.media( { title: 'Listing photos', button: { text: 'Use these photos' }, multiple: 'add', library: { type: 'image' } } );
				frame.on( 'open', function () {
					var sel = frame.state().get( 'selection' );
					field.value.split( ',' ).filter( Boolean ).forEach( function ( id ) { sel.add( wp.media.attachment( id ) ); } );
				} );
				frame.on( 'select', function () {
					var items = frame.state().get( 'selection' ).toJSON();
					field.value = items.map( function ( a ) { return a.id; } ).join( ',' );
					prev.innerHTML = '';
					items.forEach( function ( a ) {
						var img = document.createElement( 'img' );
						img.src = ( a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url ); img.width = 80; img.height = 80; img.style.objectFit = 'cover';
						prev.appendChild( img );
					} );
				} );
			}
			frame.open();
		} );
		document.getElementById( 'ot-gallery-clear' ).addEventListener( 'click', function () { field.value = ''; prev.innerHTML = ''; } );
	} )();
	</script>
	<?php
}

add_action( 'save_post_ot_listing', function ( $post_id ) {
	if ( ! isset( $_POST['ot_listing_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ot_listing_nonce'] ), 'ot_listing_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( ot_listing_fields() as $key => $f ) {
		$raw = wp_unslash( $_POST[ $key ] ?? '' );
		switch ( $f[1] ) {
			case 'int':
				$val = '' === trim( (string) $raw ) ? '' : (int) preg_replace( '/\D/', '', (string) $raw );
				break;
			case 'bool':
				$val = ! empty( $raw ) ? 1 : '';
				break;
			case 'status':
				$val = isset( OT_LISTING_STATUSES[ $raw ] ) ? $raw : 'active';
				break;
			case 'gallery':
				$val = implode( ',', array_filter( array_map( 'intval', explode( ',', (string) $raw ) ) ) );
				break;
			default:
				$val = sanitize_text_field( (string) $raw );
		}
		if ( '' === $val ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $val );
		}
	}
} );

/** Admin list columns: status, price, town. */
add_filter( 'manage_ot_listing_posts_columns', function ( $cols ) {
	$new = array();
	foreach ( $cols as $k => $v ) {
		$new[ $k ] = $v;
		if ( 'title' === $k ) {
			$new['ot_status'] = 'Status';
			$new['ot_price']  = 'Price';
			$new['ot_town']   = 'Town';
		}
	}
	return $new;
} );
add_action( 'manage_ot_listing_posts_custom_column', function ( $col, $id ) {
	$l = ot_listing( $id );
	if ( 'ot_status' === $col ) {
		echo esc_html( OT_LISTING_STATUSES[ $l['status'] ][0] ) . ( $l['featured'] ? ' · Featured' : '' );
	} elseif ( 'ot_price' === $col ) {
		echo $l['price'] ? esc_html( ot_money( $l['price'] ) ) : '—';
	} elseif ( 'ot_town' === $col ) {
		echo esc_html( $l['town'] ?: '—' );
	}
}, 10, 2 );
