<?php
/**
 * The market comparison table, the inquiry form, the listings placeholder and breadcrumbs
 * for ordinary pages.
 */

defined( 'ABSPATH' ) || exit;

function ot_render_market_table() {
	$towns = array_values( ot_towns() );
	usort( $towns, fn( $a, $b ) => ( $b['rec']['market']['sf_median_2025'] ?? 0 ) <=> ( $a['rec']['market']['sf_median_2025'] ?? 0 ) );
	$rows    = '';
	$village = '';
	foreach ( $towns as $t ) {
		$r = $t['rec'];
		$m = $r['market'] ?? array();
		$a = '<a href="' . esc_url( $t['url'] ) . '">' . esc_html( $r['name'] ) . '</a>';
		if ( empty( $m['sf_median_2025'] ) ) {
			if ( ! empty( $m['village_rows'] ) ) {
				$village .= '<tr><th scope="row">' . $a . '</th><td>' . esc_html( $r['region'] ?? '' ) . '</td><td colspan="3">Reported by village, with no town-wide figure; see the guide</td></tr>';
			}
			continue;
		}
		$label = ( $m['citywide'] ?? true ) ? '' : ' <span class="caption">(' . esc_html( $r['name'] ) . ' row)</span>';
		$rows .= '<tr><th scope="row">' . $a . $label . '</th><td>' . esc_html( $r['region'] ?? '' ) . '</td><td class="tabular">' . ( ! empty( $m['sf_sales_2025'] ) ? ot_num( $m['sf_sales_2025'] ) : 'n/a' ) . '</td><td class="tabular">' . ot_money( $m['sf_median_2025'] ) . '</td><td class="tabular">' . ot_fmt_pct( ot_pct( $m['sf_median_2024'] ?? null, $m['sf_median_2025'] ) ) . '</td></tr>';
	}
	return '<div class="table-scroll" tabindex="0" role="region" aria-label="Single-family prices by town, 2025"><table class="spec-table market-table"><thead><tr><th scope="col">Town</th><th scope="col">Region</th><th scope="col">Sales, 2025</th><th scope="col">Median, 2025</th><th scope="col">Median vs. 2024</th></tr></thead><tbody>' . $rows . $village . '</tbody></table></div>
<p class="stat-source">Source: <a href="' . OT_WARREN_URL . '">' . OT_WARREN . '</a>. Single-family homes; year-to-date figures through December. Not live data and not a valuation of any home.</p>';
}

/* --------------------------------------------------------------------- forms (demo until ot-leads) */

function ot_field( $fid, $label, $type = 'text', $req = false, $auto = null, $opt = false, $pattern = null, $pattern_msg = null ) {
	$name  = substr( $fid, strpos( $fid, '-' ) + 1 );
	$attrs = array( 'id="' . esc_attr( $fid ) . '"', 'name="' . esc_attr( $name ) . '"' );
	if ( 'textarea' !== $type ) {
		$attrs[] = 'type="' . esc_attr( $type ) . '"';
	}
	if ( $req ) {
		$attrs[] = 'required';
		$attrs[] = 'data-label="' . esc_attr( $label ) . '"';
	}
	if ( $auto ) {
		$attrs[] = 'autocomplete="' . esc_attr( $auto ) . '"';
	}
	if ( $pattern ) {
		$attrs[] = 'pattern="' . esc_attr( $pattern ) . '"';
		$attrs[] = 'data-pattern-msg="' . esc_attr( $pattern_msg ) . '"';
	}
	$attrs[] = 'aria-describedby="' . esc_attr( $fid ) . '-e"';
	$marker  = $req ? ' <span class="req">(required)</span>' : ( $opt ? ' <span class="opt">(optional)</span>' : '' );
	$ctl     = 'textarea' === $type ? '<textarea class="textarea" ' . implode( ' ', $attrs ) . '></textarea>' : '<input class="input" ' . implode( ' ', $attrs ) . '>';
	return '<div class="field"><label class="field__label" for="' . esc_attr( $fid ) . '">' . esc_html( $label ) . $marker . '</label>' . $ctl . '<p class="field__error" id="' . esc_attr( $fid ) . '-e" hidden>' . ot_icon( 'alert' ) . '<span></span></p></div>';
}

function ot_select( $fid, $label, $options ) {
	$o = '';
	foreach ( $options as $x ) {
		$o .= '<option>' . esc_html( $x ) . '</option>';
	}
	$name = substr( $fid, strpos( $fid, '-' ) + 1 );
	return '<div class="field"><label class="field__label" for="' . esc_attr( $fid ) . '">' . esc_html( $label ) . '</label><select class="select" id="' . esc_attr( $fid ) . '" name="' . esc_attr( $name ) . '">' . $o . '</select></div>';
}

/**
 * The prototype's inquiry form: name, email, phone, topic, message, preferred contact.
 * Until ot-leads ships, site-demo.js validates it and shows a demo confirmation; nothing is sent or stored.
 */
function ot_inquiry_form( $id, $title, $topics, $submit, $context, $level = 'h3', $note = null ) {
	static $n = 0;
	$n++;
	$fid  = sanitize_html_class( $id ) . ( $n > 1 ? '-' . $n : '' );
	$p    = substr( preg_replace( '/[^a-z]/', '', strtolower( $fid ) ), 0, 2 ) . $n;
	$note = $note ?: 'Your inquiry goes to Brandon and Kaitlyn and is used only to respond to your request. [Privacy policy to be published before launch.]';
	$inner  = '<div class="form__row">' . ot_field( $p . '-name', 'Full name', 'text', true, 'name' ) . ot_field( $p . '-email', 'Email', 'email', true, 'email' ) . '</div>';
	$inner .= '<div class="form__row">' . ot_field( $p . '-phone', 'Phone', 'tel', false, 'tel', true, '[0-9()+\-\s.]{7,}', 'Use digits, spaces or dashes, for example 406-250-7620.' ) . '</div>';
	if ( $topics ) {
		$inner .= ot_select( $p . '-topic', "I'm interested in", $topics );
	}
	$inner .= ot_field( $p . '-msg', 'Message', 'textarea', false, null, true );
	$inner .= '<fieldset class="choice-group"><legend class="field__label">Best way to reach you</legend><div class="choices">'
		. '<label class="choice"><input type="radio" name="' . $p . '-pref" value="call" checked><span>Call</span></label>'
		. '<label class="choice"><input type="radio" name="' . $p . '-pref" value="text"><span>Text</span></label>'
		. '<label class="choice"><input type="radio" name="' . $p . '-pref" value="email"><span>Email</span></label></div></fieldset>';
	$level = in_array( $level, array( 'h2', 'h3', 'h4' ), true ) ? $level : 'h3';
	return '<form class="form" id="' . esc_attr( $fid ) . '" data-demo-form data-icons="' . esc_url( ot_asset( 'assets/img/icons.svg' ) ) . '" data-lead-type="' . esc_attr( $context ) . '" aria-labelledby="' . esc_attr( $fid ) . '-title">
<' . $level . ' class="h3" id="' . esc_attr( $fid ) . '-title">' . esc_html( $title ) . '</' . $level . '>
' . $inner . '
<p class="form-note">' . esc_html( $note ) . '</p>
<div><button class="btn" type="submit">' . esc_html( $submit ) . '</button></div>
<div data-form-status hidden tabindex="-1" role="status"></div>
</form>';
}

function ot_render_listings_placeholder( $heading ) {
	return '<div class="placeholder-block"><p class="label">' . esc_html( $heading ?: 'Homes for sale' ) . '</p><p>Listings appear here from the MLS PIN feed once the IDX provider is chosen and connected, with each listing brokerage credited. <span class="badge badge--proposed">IDX to be decided</span></p></div>';
}

/** Breadcrumbs for whatever is being viewed: pages (with their ancestors), posts, agents, offices. */
function ot_current_trail() {
	$trail = array( array( 'Home', home_url( '/' ) ) );
	if ( is_front_page() ) {
		return $trail;
	}
	if ( is_post_type_archive( 'ot_community' ) ) {
		$trail[] = array( 'Communities', get_post_type_archive_link( 'ot_community' ) );
		return $trail;
	}
	if ( is_home() ) {
		$trail[] = array( 'Journal', get_permalink( (int) get_option( 'page_for_posts' ) ) ?: home_url( '/journal/' ) );
		return $trail;
	}
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return $trail;
	}
	if ( 'ot_agent' === $post->post_type ) {
		$trail[] = array( 'Our Team', ot_page_url( 'team' ) );
	} elseif ( 'ot_office' === $post->post_type ) {
		$trail[] = array( 'Team office', get_permalink( $post ) );
		return $trail;
	} elseif ( 'post' === $post->post_type ) {
		$trail[] = array( 'Journal', get_permalink( (int) get_option( 'page_for_posts' ) ) ?: home_url( '/journal/' ) );
	} elseif ( 'ot_community' === $post->post_type ) {
		$trail[] = array( 'Communities', get_post_type_archive_link( 'ot_community' ) );
	}
	foreach ( array_reverse( get_post_ancestors( $post ) ) as $a ) {
		$trail[] = array( esc_html( get_the_title( $a ) ), get_permalink( $a ) );
	}
	$trail[] = array( esc_html( get_the_title( $post ) ), get_permalink( $post ) );
	return $trail;
}
