<?php
/**
 * Server-rendered blocks. They read the content model and print the prototype's markup;
 * they store nothing in post content except their attributes, so a theme change never
 * breaks saved content. The editor previews them with ServerSideRender (assets/editor.js).
 */

defined( 'ABSPATH' ) || exit;

/** The post a block renders for: the block's context in a query loop or editor, else the viewed post. */
function ot_block_post_id( $block ) {
	if ( $block instanceof WP_Block && ! empty( $block->context['postId'] ) ) {
		return (int) $block->context['postId'];
	}
	if ( ! empty( $_GET['post_id'] ) && defined( 'REST_REQUEST' ) && REST_REQUEST ) { // editor preview
		return (int) $_GET['post_id'];
	}
	return (int) get_queried_object_id();
}

function ot_block_definitions() {
	$str_list = array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'default' => array() );
	return array(
		'ot/breadcrumbs'          => array(
			'title'    => 'Breadcrumbs',
			'attrs'    => array(),
			'render'   => fn( $a, $b ) => ot_breadcrumbs_html( ot_current_trail() ),
		),
		'ot/page-hero'            => array(
			'title'    => 'Guide or profile hero',
			'attrs'    => array(),
			'render'   => function ( $a, $b ) {
				$id = ot_block_post_id( $b );
				return 'ot_community' === get_post_type( $id ) ? ot_render_community_section( 'hero', $id ) : ot_render_agent_section( 'hero', $id );
			},
		),
		'ot/community-section'    => array(
			'title'    => 'Community guide section',
			'attrs'    => array( 'section' => array( 'type' => 'string', 'default' => 'about', 'enum' => array( 'hero', 'about', 'neighborhoods', 'commute', 'places', 'market', 'questions', 'contact', 'sources' ) ) ),
			'render'   => fn( $a, $b ) => ot_render_community_section( $a['section'] ?? 'about', ot_block_post_id( $b ) ),
		),
		'ot/community-index'      => array(
			'title'    => 'Community index',
			'attrs'    => array( 'part' => array( 'type' => 'string', 'default' => 'body', 'enum' => array( 'hero', 'body' ) ) ),
			'render'   => fn( $a, $b ) => 'hero' === ( $a['part'] ?? 'body' ) ? ot_render_community_archive_hero() : ot_render_community_index(),
		),
		'ot/community-cards'      => array(
			'title'    => 'Community cards',
			'attrs'    => array( 'slugs' => $str_list ),
			'render'   => fn( $a, $b ) => ot_render_community_cards( $a['slugs'] ?? array() ),
		),
		'ot/market-table'         => array(
			'title'    => 'Market table, all towns',
			'attrs'    => array(),
			'render'   => fn( $a, $b ) => ot_render_market_table(),
		),
		'ot/town-stats'           => array(
			'title'    => 'Town median stats',
			'attrs'    => array( 'slugs' => $str_list ),
			'render'   => fn( $a, $b ) => ot_render_town_stats( $a['slugs'] ?? array() ),
		),
		'ot/agent-profile'        => array(
			'title'    => 'Agent profile section',
			'attrs'    => array( 'section' => array( 'type' => 'string', 'default' => 'hero', 'enum' => array( 'hero', 'about', 'help', 'stories', 'questions', 'contact' ) ) ),
			'render'   => fn( $a, $b ) => ot_render_agent_section( $a['section'] ?? 'hero', ot_block_post_id( $b ) ),
		),
		'ot/agent-cards'          => array(
			'title'    => 'Agent cards',
			'attrs'    => array( 'seat' => array( 'type' => 'boolean', 'default' => true ) ),
			'render'   => fn( $a, $b ) => ot_render_agent_cards( $a['seat'] ?? true ),
		),
		'ot/inquiry-form'         => array(
			'title'    => 'Inquiry form',
			'attrs'    => array(
				'context' => array( 'type' => 'string', 'default' => 'contact' ),
				'title'   => array( 'type' => 'string', 'default' => 'Send a message' ),
				'topics'  => $str_list,
				'submit'  => array( 'type' => 'string', 'default' => 'Send my message' ),
				'level'   => array( 'type' => 'string', 'default' => 'h2' ),
				'note'    => array( 'type' => 'string', 'default' => '' ),
			),
			'render'   => fn( $a, $b ) => ot_inquiry_form( $a['context'] ?? 'contact', $a['title'] ?? '', $a['topics'] ?? array(), $a['submit'] ?? 'Send', $a['context'] ?? 'contact', $a['level'] ?? 'h2', ( $a['note'] ?? '' ) ?: null ),
		),
		'ot/listings-placeholder' => array(
			'title'    => 'Listings (IDX placeholder)',
			'attrs'    => array( 'heading' => array( 'type' => 'string', 'default' => 'Homes for sale' ) ),
			'render'   => fn( $a, $b ) => ot_render_listings_placeholder( $a['heading'] ?? '' ),
		),
	);
}

add_action( 'init', function () {
	wp_register_script(
		'ot-core-editor',
		OT_CORE_URL . '/assets/editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		OT_CORE_VERSION,
		true
	);
	foreach ( ot_block_definitions() as $name => $def ) {
		register_block_type( $name, array(
			'api_version'          => 3,
			'title'                => $def['title'],
			'category'             => 'oberdorfer',
			'attributes'           => $def['attrs'],
			'uses_context'         => array( 'postId', 'postType' ),
			'supports'             => array( 'html' => false, 'reusable' => false ),
			'editor_script_handles' => array( 'ot-core-editor' ),
			'render_callback'      => $def['render'],
		) );
	}
} );

add_filter( 'block_categories_all', function ( $cats ) {
	foreach ( $cats as $c ) {
		if ( 'oberdorfer' === $c['slug'] ) {
			return $cats;
		}
	}
	$cats[] = array( 'slug' => 'oberdorfer', 'title' => 'The Oberdorfer Team' );
	return $cats;
} );
