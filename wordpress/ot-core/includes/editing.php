<?php
/**
 * Making the site easy to edit for people new to WordPress.
 *
 * - Guides, agents, listings and offices open in the editor showing the whole page
 *   ("template-locked" mode), so the team edits on the page: the headline, summary and
 *   biography are ordinary blocks bound to the post's fields (Block Bindings, core/post-meta)
 *   and can be typed into directly. Sections drawn from research data show as previews with
 *   a note saying where to change them (assets/editor.js).
 * - An empty biography shows its placeholder on the site, and a prompt in the editor.
 * - A "Start here" panel on the dashboard links the common tasks; unused menus are tidied.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	foreach ( array( 'page', 'ot_community', 'ot_agent', 'ot_listing', 'ot_office' ) as $type ) {
		add_post_type_support( $type, 'editor', array( 'default-mode' => 'template-locked' ) );
	}
}, 20 );

/** A prompt in the empty biography of a new or unwritten agent profile. */
add_filter( 'register_post_type_args', function ( $args, $type ) {
	if ( 'ot_agent' === $type ) {
		$args['template'] = array( array( 'core/paragraph', array( 'placeholder' => 'Write the biography here: 120 to 250 words, in the first person. How you came to real estate, the part of each move you lead for clients, and a Massachusetts place you know well.' ) ) );
	}
	return $args;
}, 10, 2 );

/**
 * A profile with no biography yet opens with an empty paragraph that says what to write, so the
 * biography's place on the page is obvious. An empty paragraph still shows the placeholder text
 * on the site (see below). Done once per plugin version, only for profiles with no body.
 */
add_action( 'admin_init', function () {
	if ( get_option( 'ot_bio_seeded' ) === OT_CORE_VERSION || ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	foreach ( get_posts( array( 'post_type' => 'ot_agent', 'post_status' => 'any', 'posts_per_page' => -1 ) ) as $a ) {
		// The joint profile's page has no biography block (its story comes from the record).
		if ( 'brandon-kaitlyn-oberdorfer' !== $a->post_name && '' === trim( (string) $a->post_content ) ) {
			wp_update_post( array( 'ID' => $a->ID, 'post_content' => ot_bio_prompt_block( $a ) ) );
		}
	}
	update_option( 'ot_bio_seeded', OT_CORE_VERSION, false );
} );

function ot_bio_prompt_block( $post ) {
	$who = strtok( $post->post_title, ' ' ) . '\'s biography';
	$tip = '120 to 250 words, in the first person: how you came to real estate, the part of each move you lead for clients, and a Massachusetts place you know well.';
	return '<!-- wp:paragraph {"placeholder":' . wp_json_encode( 'Click here and write ' . $who . '. ' . $tip ) . '} -->' . "\n" . '<p></p>' . "\n" . '<!-- /wp:paragraph -->';
}

/* --------------------------------------------------------------------- front end */

add_filter( 'render_block', function ( $html, $block ) {
	if ( is_admin() ) {
		return $html;
	}
	$name = $block['blockName'] ?? '';

	// An agent's biography is the post content; while it is empty, show the placeholder.
	if ( 'core/post-content' === $name && is_singular( 'ot_agent' ) && '' === trim( wp_strip_all_tags( (string) get_post_field( 'post_content', get_queried_object_id() ) ) ) ) {
		return ot_render_agent_section( 'bio-placeholder', get_queried_object_id() );
	}

	// Text shared by every profile (Appearance > Editor > Templates > agent) writes {first name}
	// where the agent's first name goes.
	if ( is_singular( 'ot_agent' ) && str_contains( $html, '{first name}' ) ) {
		$rec  = function_exists( 'ot_agent_rec' ) ? ot_agent_rec( get_queried_object_id() ) : array();
		$html = str_replace( '{first name}', esc_html( $rec['given'] ?? get_the_title( get_queried_object_id() ) ), $html );
	}

	// A listing without a description drops its "The property" heading.
	$classes = ' ' . ( $block['attrs']['className'] ?? '' ) . ' ';
	if ( str_contains( $classes, ' ot-listing-story ' ) && is_singular( 'ot_listing' ) && '' === trim( wp_strip_all_tags( (string) get_post_field( 'post_content', get_queried_object_id() ) ) ) ) {
		return '';
	}

	// A guide with a photo (Wakefield) gets the photo hero: navy surface and the image behind the text.
	if ( str_contains( $classes, ' ot-hero ' ) && is_singular( 'ot_community' ) ) {
		$c = ot_community( get_queried_object_id() );
		$r = $c ? $c['rec'] : array();
		if ( ! empty( $r['photo'] ) && empty( $c['parent'] ) ) {
			$p = new WP_HTML_Tag_Processor( $html );
			if ( $p->next_tag() ) {
				foreach ( array( 'page-hero--navy', 'surface-inverse', 'page-hero--photo' ) as $cls ) {
					$p->add_class( $cls );
				}
				$html = $p->get_updated_html();
				$img  = '<img src="' . esc_url( ot_asset( $r['photo'] ) ) . '" alt="' . esc_attr( $r['photo_alt'] ?? '' ) . '" style="object-position:' . esc_attr( $r['focal'] ?? '50% 50%' ) . '" fetchpriority="high">';
				$html = preg_replace( '/>/', '>' . $img, $html, 1 );
			}
		}
	}
	return $html;
}, 10, 2 );

/* --------------------------------------------------------------------- admin */

add_action( 'wp_dashboard_setup', function () {
	wp_add_dashboard_widget( 'ot_start_here', 'Start here: The Oberdorfer Team site', 'ot_start_here_widget' );
	// Put it first.
	global $wp_meta_boxes;
	$normal = $wp_meta_boxes['dashboard']['normal']['core'] ?? array();
	if ( isset( $normal['ot_start_here'] ) ) {
		$wp_meta_boxes['dashboard']['normal']['core'] = array( 'ot_start_here' => $normal['ot_start_here'] ) + $normal;
	}
} );

function ot_start_here_widget() {
	$agents = get_posts( array( 'post_type' => 'ot_agent', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
	$links  = array();
	foreach ( $agents as $a ) {
		$links[] = '<a href="' . esc_url( get_edit_post_link( $a->ID ) ) . '">' . esc_html( $a->post_title ) . '</a>';
	}
	$items = array(
		array( 'Edit a profile', 'Open a profile, click the text you want to change and type. The biography is the large text area; the details below the page (phone, title, specialties, headshot) are in the "Agent details" box.', implode( ' · ', $links ) ),
		array( 'Add a listing', 'Type the address as the title, write the description, set the main photo in the sidebar, then fill in "Listing details" and click Publish.', '<a href="' . esc_url( admin_url( 'post-new.php?post_type=ot_listing' ) ) . '">Add listing</a> · <a href="' . esc_url( admin_url( 'edit.php?post_type=ot_listing' ) ) . '">All listings</a>' ),
		array( 'Show or hide town guides', 'Untick a town to take its guide off the site; tick it to bring it back. Nothing is deleted.', '<a href="' . esc_url( admin_url( 'edit.php?post_type=ot_community&page=ot-guides' ) ) . '">Show or hide guides</a>' ),
		array( 'Edit a town guide\'s headline', 'Open a guide and click its headline or summary to change it. The research below it (facts, prices, sources) is kept separate so it stays accurate.', '<a href="' . esc_url( admin_url( 'edit.php?post_type=ot_community' ) ) . '">All guides</a>' ),
		array( 'Listings search (IDX)', 'Off until the IDX provider is chosen. One checkbox turns the home search and listings feed on.', '<a href="' . esc_url( admin_url( 'options-general.php?page=ot-idx' ) ) . '">Listings (IDX)</a>' ),
	);
	echo '<ol style="margin:0;padding-left:1.2em">';
	foreach ( $items as $it ) {
		echo '<li style="margin-bottom:.9em"><strong>' . esc_html( $it[0] ) . '.</strong> ' . esc_html( $it[1] ) . '<br>' . $it[2] . '</li>';
	}
	echo '</ol><p style="margin-bottom:0">Changes show on the site after you click <strong>Save</strong> (or <strong>Update</strong>). If a page still looks old, use <strong>Breeze &gt; Purge All Cache</strong> in the top bar.</p>';
}

/** The site has no comments; hide their menus so there is less to wade through. */
add_action( 'admin_menu', function () {
	remove_menu_page( 'edit-comments.php' );
}, 99 );
add_action( 'admin_bar_menu', function ( $bar ) {
	$bar->remove_node( 'comments' );
}, 99 );

/** Posts are the team's journal articles; call them that. */
add_filter( 'post_type_labels_post', function ( $labels ) {
	$labels->name          = 'Journal';
	$labels->menu_name     = 'Journal';
	$labels->all_items     = 'All articles';
	$labels->add_new_item  = 'Add article';
	$labels->singular_name = 'Article';
	$labels->edit_item     = 'Edit article';
	$labels->new_item      = 'New article';
	$labels->view_item     = 'View article';
	$labels->search_items  = 'Search articles';
	$labels->not_found     = 'No articles found.';
	return $labels;
} );
