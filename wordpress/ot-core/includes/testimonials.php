<?php
/**
 * Testimonials and reviews, kept within the law and the platforms' terms.
 *
 * - Reviews written on other sites (RateMyAgent, Zillow, Google...) belong to those sites, so
 *   the profile links to them ("Reviews on other sites", an Agent details field) instead of
 *   copying them.
 * - A client's words are quoted on the site only as a Testimonial with two boxes ticked: the
 *   client agreed to be quoted on this website, and REWAP approved it. The words are quoted
 *   exactly (FTC rule on consumer reviews and testimonials, 16 CFR 465; NAR Code, Article 12).
 * - No review or rating markup (JSON-LD) is published: Google does not show stars for a
 *   business's reviews of itself, and the rule above makes accuracy the point.
 *
 * Reviews found on other sites are brought in as hidden drafts (title, site and link, never
 * their text) from data/content.json, so the team can ask each client and then paste the words.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	register_post_type( 'ot_testimonial', array(
		'labels'              => array(
			'name'          => 'Testimonials',
			'singular_name' => 'Testimonial',
			'add_new_item'  => 'Add testimonial',
			'edit_item'     => 'Edit testimonial',
			'search_items'  => 'Search testimonials',
			'not_found'     => 'No testimonials yet.',
			'all_items'     => 'All testimonials',
		),
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_rest'        => false,
		'menu_position'       => 27,
		'menu_icon'           => 'dashicons-format-quote',
		'supports'            => array( 'title' ),
		'capability_type'     => 'post',
	) );
} );

/** Meta key => [label, type, help]. */
function ot_testimonial_fields() {
	return array(
		'ot_t_quote'      => array( 'The client\'s words', 'textarea', 'Exactly as the client wrote them. You may shorten with … but never change the meaning or add words.' ),
		'ot_t_name'       => array( 'Name to show', 'text', 'As the client agreed, for example: Sarah M. Leave empty to show no name.' ),
		'ot_t_agent'      => array( 'About', 'agent', '' ),
		'ot_t_context'    => array( 'What we helped with', 'text', 'For example: Sale, Worcester. No details the client has not agreed to share.' ),
		'ot_t_year'       => array( 'Year', 'text', 'When the client wrote it, for example 2021.' ),
		'ot_t_platform'   => array( 'Originally on', 'text', 'For example RateMyAgent, Zillow, Google. Empty if the client sent it to you directly.' ),
		'ot_t_url'        => array( 'Link to the original', 'url', 'Shown as "Read it on …" so visitors can see the original.' ),
		'ot_t_permission' => array( 'The client agreed to be quoted on this website', 'check', 'Keep their reply (an email or text) on file.' ),
		'ot_t_perm_note'  => array( 'How and when they agreed', 'text', 'For example: Email to Brandon, Nov 10, 2026.' ),
		'ot_t_approved'   => array( 'REWAP approved it', 'check', 'The brokerage\'s approval of testimonials in advertising.' ),
	);
}

/** Shown on the site only when published, with words, the client's permission and REWAP's approval. */
function ot_testimonial_is_live( $post ) {
	$post = get_post( $post );
	return $post && 'publish' === $post->post_status
		&& '' !== trim( (string) get_post_meta( $post->ID, 'ot_t_quote', true ) )
		&& get_post_meta( $post->ID, 'ot_t_permission', true )
		&& get_post_meta( $post->ID, 'ot_t_approved', true );
}

/** What still stops a testimonial from showing, in plain words. */
function ot_testimonial_missing( $post ) {
	$miss = array();
	if ( '' === trim( (string) get_post_meta( $post->ID, 'ot_t_quote', true ) ) ) {
		$miss[] = 'the client\'s words';
	}
	if ( ! get_post_meta( $post->ID, 'ot_t_permission', true ) ) {
		$miss[] = 'the client\'s permission';
	}
	if ( ! get_post_meta( $post->ID, 'ot_t_approved', true ) ) {
		$miss[] = 'REWAP\'s approval';
	}
	if ( 'publish' !== $post->post_status ) {
		$miss[] = 'clicking Publish';
	}
	return $miss;
}

/** Live testimonials about an agent (or about the whole team), newest first. */
function ot_testimonials_for( $slug, $limit = 3 ) {
	$posts = get_posts( array(
		'post_type'      => 'ot_testimonial',
		'post_status'    => 'publish',
		'posts_per_page' => 30,
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		'meta_query'     => array( array( 'key' => 'ot_t_agent', 'value' => array( $slug, 'team' ), 'compare' => 'IN' ) ),
	) );
	return array_slice( array_values( array_filter( $posts, 'ot_testimonial_is_live' ) ), 0, $limit );
}

/** "Label | URL | note" lines => [ [label, url, note], ... ]. */
function ot_parse_review_links( $text ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		$p = array_map( 'trim', explode( '|', $line, 3 ) );
		if ( count( $p ) >= 2 && '' !== $p[0] && filter_var( $p[1], FILTER_VALIDATE_URL ) && preg_match( '#^https?://#i', $p[1] ) ) {
			$out[] = array( $p[0], $p[1], $p[2] ?? '' );
		}
	}
	return $out;
}

/* --------------------------------------------------------------------- front end */

/** The "Client stories" section of a profile. Empty (and so left out) until there is something real to show. */
function ot_render_agent_stories( $x ) {
	$slug  = $x['p']->post_name;
	$items = ot_testimonials_for( $slug );
	$links = ot_parse_review_links( $x['r']['review_links'] ?? '' );
	if ( ! $items && ! $links ) {
		return '';
	}
	$body = '';
	foreach ( $items as $i => $t ) {
		$m    = fn( $k ) => trim( (string) get_post_meta( $t->ID, $k, true ) );
		$cite = implode( ' · ', array_filter( array( $m( 'ot_t_name' ), $m( 'ot_t_context' ), $m( 'ot_t_year' ) ) ) );
		$src  = $m( 'ot_t_url' ) ? '<a href="' . esc_url( $m( 'ot_t_url' ) ) . '" rel="noopener">Read it on ' . esc_html( $m( 'ot_t_platform' ) ?: 'the original site' ) . '</a>' : ( $m( 'ot_t_platform' ) ? esc_html( 'Review on ' . $m( 'ot_t_platform' ) ) : '' );
		$body .= '<figure class="testimonial' . ( $i ? ' testimonial--compact' : '' ) . '"><span class="testimonial__mark" aria-hidden="true"></span><blockquote><p>' . nl2br( esc_html( $m( 'ot_t_quote' ) ) ) . '</p></blockquote>'
			. ( $cite || $src ? '<figcaption class="caption">' . esc_html( $cite ) . ( $cite && $src ? ' · ' : '' ) . $src . '</figcaption>' : '' ) . '</figure>';
	}
	if ( $links ) {
		$li = '';
		foreach ( $links as $l ) {
			$li .= '<li><a class="link-arrow" href="' . esc_url( $l[1] ) . '" rel="noopener">' . esc_html( $l[0] ) . ' ' . ot_arrow() . '</a>' . ( $l[2] ? '<span class="caption">' . esc_html( $l[2] ) . '</span>' : '' ) . '</li>';
		}
		$body .= '<div class="review-links"><p class="label">' . ( $items ? 'More reviews' : 'Reviews on other sites' ) . '</p><ul>' . $li . '</ul></div>';
	}
	return '<section class="section surface-inverse" aria-labelledby="stories-title"><div class="container split" style="align-items:center">
  <div class="stack" style="grid-column:1/span 5"><p class="label">Client stories</p><h2 class="display-m answer" id="stories-title"><span>In their</span><span>own words.</span></h2></div>
  <div class="testimonial-list" style="grid-column:7/span 6">' . $body . '</div>
</div></section>';
}

/* --------------------------------------------------------------------- the edit screen */

add_action( 'add_meta_boxes_ot_testimonial', function () {
	add_meta_box( 'ot-testimonial', 'Testimonial', 'ot_testimonial_box', 'ot_testimonial', 'normal', 'high' );
} );

function ot_testimonial_box( $post ) {
	wp_nonce_field( 'ot_testimonial_save', 'ot_testimonial_nonce' );
	$miss = ot_testimonial_missing( $post );
	echo '<div class="notice inline ' . ( $miss ? 'notice-warning' : 'notice-success' ) . '" style="margin:8px 0 12px"><p>'
		. ( $miss ? '<strong>Not on the site yet.</strong> Still needed: ' . esc_html( implode( ', ', $miss ) ) . '.' : '<strong>Shown on the site</strong>, in the profile\'s "Client stories" section.' )
		. '</p></div>';
	echo '<p>A client\'s words appear on the site only when they agreed to be quoted <em>on this website</em> and REWAP approved it. Reviews on Zillow, Google or RateMyAgent belong to those sites: ask the client first (for example, "May we quote your review on our website?"), then paste their exact words here. The title above is for the team only.</p>';
	echo '<table class="form-table" role="presentation"><tbody>';
	$agents = get_posts( array( 'post_type' => 'ot_agent', 'post_status' => 'any', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
	foreach ( ot_testimonial_fields() as $k => $f ) {
		$v = (string) get_post_meta( $post->ID, $k, true );
		echo '<tr><th scope="row"><label for="' . esc_attr( $k ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
		switch ( $f[1] ) {
			case 'textarea':
				echo '<textarea class="large-text" rows="6" id="' . esc_attr( $k ) . '" name="' . esc_attr( $k ) . '">' . esc_textarea( $v ) . '</textarea>';
				break;
			case 'check':
				echo '<input type="checkbox" id="' . esc_attr( $k ) . '" name="' . esc_attr( $k ) . '" value="1"' . checked( (bool) $v, true, false ) . '>';
				break;
			case 'url':
				echo '<input class="large-text" type="url" id="' . esc_attr( $k ) . '" name="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '" placeholder="https://">';
				break;
			case 'agent':
				echo '<select id="' . esc_attr( $k ) . '" name="' . esc_attr( $k ) . '">';
				foreach ( $agents as $a ) {
					$val   = 'brandon-kaitlyn-oberdorfer' === $a->post_name ? 'team' : $a->post_name;
					$label = 'team' === $val ? 'Both (shown on each profile)' : $a->post_title;
					echo '<option value="' . esc_attr( $val ) . '"' . selected( $v, $val, false ) . '>' . esc_html( $label ) . '</option>';
				}
				echo '</select>';
				break;
			default:
				echo '<input class="regular-text" type="text" id="' . esc_attr( $k ) . '" name="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '">';
		}
		echo $f[2] ? '<p class="description">' . esc_html( $f[2] ) . '</p>' : '';
		echo '</td></tr>';
	}
	echo '</tbody></table>';
}

add_action( 'save_post_ot_testimonial', function ( $post_id ) {
	if ( ! isset( $_POST['ot_testimonial_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ot_testimonial_nonce'] ), 'ot_testimonial_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( ot_testimonial_fields() as $k => $f ) {
		$raw = isset( $_POST[ $k ] ) ? wp_unslash( $_POST[ $k ] ) : '';
		switch ( $f[1] ) {
			case 'textarea':
				$val = sanitize_textarea_field( $raw );
				break;
			case 'check':
				$val = $raw ? 1 : '';
				break;
			case 'url':
				$val = esc_url_raw( $raw );
				break;
			default:
				$val = sanitize_text_field( $raw );
		}
		update_post_meta( $post_id, $k, $val );
	}
} );

/* List screen: what each one is about and what it still needs. */
add_filter( 'manage_ot_testimonial_posts_columns', function ( $cols ) {
	return array(
		'cb'       => $cols['cb'] ?? '',
		'title'    => 'Testimonial',
		'ot_about' => 'About',
		'ot_from'  => 'Originally on',
		'ot_state' => 'On the site?',
		'date'     => $cols['date'] ?? 'Date',
	);
} );
add_action( 'manage_ot_testimonial_posts_custom_column', function ( $col, $id ) {
	$post = get_post( $id );
	if ( 'ot_about' === $col ) {
		$a = (string) get_post_meta( $id, 'ot_t_agent', true );
		$p = 'team' === $a ? null : get_page_by_path( $a, OBJECT, 'ot_agent' );
		echo esc_html( 'team' === $a ? 'Both' : ( $p ? $p->post_title : '—' ) );
	} elseif ( 'ot_from' === $col ) {
		$u = (string) get_post_meta( $id, 'ot_t_url', true );
		$n = (string) get_post_meta( $id, 'ot_t_platform', true ) ?: '—';
		echo $u ? '<a href="' . esc_url( $u ) . '" target="_blank" rel="noopener">' . esc_html( $n ) . '</a>' : esc_html( $n );
	} elseif ( 'ot_state' === $col ) {
		$miss = ot_testimonial_missing( $post );
		echo $miss ? 'No. Needs ' . esc_html( implode( ', ', $miss ) ) . '.' : '<strong>Yes</strong>';
	}
}, 10, 2 );

/* --------------------------------------------------------------------- reviews found elsewhere */

/**
 * Once per plugin version: profiles get their "Reviews on other sites" links unless the team
 * has set or emptied that field, and each review found elsewhere becomes a hidden draft
 * testimonial (never re-created once the team deletes it).
 */
add_action( 'admin_init', function () {
	if ( get_option( 'ot_reviews_seeded' ) === OT_CORE_VERSION || ! current_user_can( 'edit_posts' ) || ! function_exists( 'ot_import_file' ) ) {
		return;
	}
	$data = json_decode( (string) @file_get_contents( ot_import_file() ), true );
	if ( ! is_array( $data ) ) {
		return;
	}
	// Newly confirmed profile facts (review links; MA license numbers, confirmed 2026-11-10) fill
	// empty fields once, never over what the team typed or deliberately emptied.
	foreach ( $data['agents'] ?? array() as $a ) {
		$post = get_page_by_path( $a['slug'], OBJECT, 'ot_agent' );
		foreach ( array( 'ot_review_links', 'ot_license' ) as $key ) {
			$val = (string) ( $a['meta'][ $key ] ?? '' );
			if ( $post && '' !== $val && '' === (string) get_post_meta( $post->ID, $key, true ) && ! get_post_meta( $post->ID, $key . '_cleared', true ) ) {
				update_post_meta( $post->ID, $key, $val );
			}
		}
	}
	$done = (array) get_option( 'ot_testimonial_keys', array() );
	foreach ( $data['testimonials'] ?? array() as $t ) {
		if ( empty( $t['key'] ) || in_array( $t['key'], $done, true ) ) {
			continue;
		}
		$id = wp_insert_post( array(
			'post_type'   => 'ot_testimonial',
			'post_status' => 'draft',
			'post_title'  => sprintf( '%s (%s review, %s)', $t['title'], $t['platform'], $t['context'] ),
			'meta_input'  => array(
				'ot_t_agent'    => $t['agent'],
				'ot_t_context'  => $t['context'],
				// "about 2021" is only an estimate from the site's "almost 5 years ago": confirm with the client.
				'ot_t_year'     => str_starts_with( (string) $t['written'], 'about' ) ? '' : (string) $t['written'],
				'ot_t_platform' => $t['platform'],
				'ot_t_url'      => $t['url'],
				'ot_t_key'      => $t['key'],
			),
		), true );
		if ( ! is_wp_error( $id ) ) {
			$done[] = $t['key'];
		}
	}
	update_option( 'ot_testimonial_keys', $done, false );
	update_option( 'ot_reviews_seeded', OT_CORE_VERSION, false );
} );
