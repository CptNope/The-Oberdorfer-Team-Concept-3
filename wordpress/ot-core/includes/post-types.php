<?php
/**
 * Post types, the region taxonomy and registered post meta.
 * Names are fixed in docs/wordpress-build.md; the theme's templates depend on them.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	register_post_type( 'ot_community', array(
		'labels'        => array(
			'name'          => __( 'Communities', 'ot-core' ),
			'singular_name' => __( 'Community', 'ot-core' ),
			'add_new_item'  => __( 'Add community guide', 'ot-core' ),
			'edit_item'     => __( 'Edit community guide', 'ot-core' ),
			'all_items'     => __( 'All community guides', 'ot-core' ),
			'parent_item_colon' => __( 'City:', 'ot-core' ),
		),
		'description'   => 'Researched, sourced guides to Massachusetts towns and city neighborhoods. A neighborhood is a child of its city.',
		'public'        => true,
		'hierarchical'  => true,
		'show_in_rest'  => true,
		'menu_icon'     => 'dashicons-location-alt',
		'menu_position' => 21,
		'has_archive'   => 'communities',
		'rewrite'       => array( 'slug' => 'communities', 'with_front' => false ),
		'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'custom-fields', 'revisions' ),
	) );

	register_post_type( 'ot_agent', array(
		'labels'        => array(
			'name'          => __( 'Agents', 'ot-core' ),
			'singular_name' => __( 'Agent', 'ot-core' ),
			'add_new_item'  => __( 'Add agent profile', 'ot-core' ),
			'edit_item'     => __( 'Edit agent profile', 'ot-core' ),
		),
		'public'        => true,
		'show_in_rest'  => true,
		'menu_icon'     => 'dashicons-id',
		'menu_position' => 22,
		'has_archive'   => false,
		'rewrite'       => array( 'slug' => 'agents', 'with_front' => false ),
		'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions' ),
	) );

	register_post_type( 'ot_office', array(
		'labels'        => array(
			'name'          => __( 'Offices', 'ot-core' ),
			'singular_name' => __( 'Office', 'ot-core' ),
		),
		'public'        => true,
		'show_in_rest'  => true,
		'menu_icon'     => 'dashicons-building',
		'menu_position' => 23,
		'has_archive'   => false,
		'rewrite'       => array( 'slug' => 'office', 'with_front' => false ),
		'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions' ),
	) );

	register_taxonomy( 'ot_region', array( 'ot_community' ), array(
		'labels'            => array(
			'name'          => __( 'Regions', 'ot-core' ),
			'singular_name' => __( 'Region', 'ot-core' ),
		),
		'public'            => true,
		'hierarchical'      => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'rewrite'           => false, // regions are sections of /communities/, not archives of their own
	) );

	register_term_meta( 'ot_region', 'ot_order', array( 'type' => 'integer', 'single' => true, 'show_in_rest' => true ) );
	register_term_meta( 'ot_region', 'ot_blurb', array( 'type' => 'string', 'single' => true, 'show_in_rest' => true ) );

	$string = array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'default' => '' );
	$auth   = function () {
		return current_user_can( 'edit_posts' );
	};
	$meta = array(
		'ot_community' => array( 'ot_record', 'ot_headline', 'ot_answer', 'ot_kicker', 'ot_seo_title', 'ot_seo_description', 'ot_county' ),
		'ot_agent'     => array( 'ot_record', 'ot_headline', 'ot_answer', 'ot_kicker', 'ot_seo_title', 'ot_seo_description', 'ot_role', 'ot_job_title', 'ot_phone', 'ot_email', 'ot_instagram', 'ot_card_url', 'ot_photo' ),
		'ot_office'    => array( 'ot_record', 'ot_seo_title', 'ot_seo_description', 'ot_street', 'ot_city', 'ot_postcode' ),
		'page'         => array( 'ot_seo_title', 'ot_seo_description' ),
		'post'         => array( 'ot_seo_title', 'ot_seo_description' ),
	);
	foreach ( $meta as $type => $keys ) {
		foreach ( $keys as $key ) {
			register_post_meta( $type, $key, $string + array( 'auth_callback' => $auth ) );
		}
	}
	register_post_meta( 'ot_community', 'ot_population', array(
		'type' => 'integer', 'single' => true, 'show_in_rest' => true, 'default' => 0, 'auth_callback' => $auth,
	) );
} );

/** Communities list in menu order, then title, everywhere (archive and admin). */
add_action( 'pre_get_posts', function ( $q ) {
	if ( $q->is_main_query() && ! is_admin() && $q->is_post_type_archive( 'ot_community' ) ) {
		$q->set( 'posts_per_page', -1 );
		$q->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
	}
} );
