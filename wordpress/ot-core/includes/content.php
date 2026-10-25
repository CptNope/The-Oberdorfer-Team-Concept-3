<?php
/**
 * Read access to the content model: every community with its decoded record, regions in
 * order, agents. One query per request, cached in memory.
 */

defined( 'ABSPATH' ) || exit;

/**
 * All published communities, keyed by post ID:
 * [ 'id', 'slug', 'parent' (post ID or 0), 'url', 'title', 'order', 'rec' (array), 'children' (IDs) ].
 */
function ot_communities() {
	static $all = null;
	if ( null !== $all ) {
		return $all;
	}
	$all   = array();
	$posts = get_posts( array(
		'post_type'      => 'ot_community',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'no_found_rows'  => true,
	) );
	foreach ( $posts as $p ) {
		$rec = json_decode( (string) get_post_meta( $p->ID, 'ot_record', true ), true );
		if ( ! is_array( $rec ) ) {
			$rec = array();
		}
		$rec['name']  = $rec['name'] ?? $p->post_title;
		$rec['slug']  = $p->post_name;
		$all[ $p->ID ] = array(
			'id'       => $p->ID,
			'slug'     => $p->post_name,
			'parent'   => (int) $p->post_parent,
			'url'      => get_permalink( $p ),
			'title'    => $p->post_title,
			'order'    => (int) $p->menu_order,
			'rec'      => $rec,
			'children' => array(),
		);
	}
	foreach ( $all as $id => $c ) {
		if ( $c['parent'] && isset( $all[ $c['parent'] ] ) ) {
			$all[ $c['parent'] ]['children'][] = $id;
		}
	}
	return $all;
}

/** Top-level communities (towns and cities), in order. */
function ot_towns() {
	return array_filter( ot_communities(), fn( $c ) => 0 === $c['parent'] );
}

function ot_town_by_slug( $slug ) {
	foreach ( ot_towns() as $c ) {
		if ( $c['slug'] === $slug ) {
			return $c;
		}
	}
	return null;
}

function ot_community( $post_id ) {
	$all = ot_communities();
	return $all[ $post_id ] ?? null;
}

/** Regions in display order: [ key, name, blurb ]. */
function ot_regions() {
	static $regions = null;
	if ( null !== $regions ) {
		return $regions;
	}
	$terms = get_terms( array( 'taxonomy' => 'ot_region', 'hide_empty' => false ) );
	$regions = array();
	if ( is_array( $terms ) ) {
		foreach ( $terms as $t ) {
			$regions[] = array(
				'key'   => $t->slug,
				'name'  => $t->name,
				'blurb' => (string) get_term_meta( $t->term_id, 'ot_blurb', true ),
				'order' => (int) get_term_meta( $t->term_id, 'ot_order', true ),
			);
		}
		usort( $regions, fn( $a, $b ) => $a['order'] <=> $b['order'] );
	}
	return $regions;
}

function ot_towns_in( $region_key ) {
	return array_values( array_filter( ot_towns(), fn( $c ) => ( $c['rec']['region_key'] ?? '' ) === $region_key ) );
}

/** URL of a page by its slug, or the home URL plus the slug when the page does not exist yet. */
function ot_page_url( $slug, $fragment = '' ) {
	$p   = get_page_by_path( $slug );
	$url = $p ? get_permalink( $p ) : home_url( '/' . trim( $slug, '/' ) . '/' );
	return $url . ( $fragment ? '#' . $fragment : '' );
}

/** Agents (not the team page), in menu order: [ id, slug, url, rec ]. */
function ot_agents( $include_team = false ) {
	$out   = array();
	$posts = get_posts( array(
		'post_type'      => 'ot_agent',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'no_found_rows'  => true,
	) );
	foreach ( $posts as $p ) {
		$rec = json_decode( (string) get_post_meta( $p->ID, 'ot_record', true ), true ) ?: array();
		if ( ! $include_team && ( $rec['kind'] ?? '' ) === 'team' ) {
			continue;
		}
		$out[ $p->post_name ] = array( 'id' => $p->ID, 'slug' => $p->post_name, 'url' => get_permalink( $p ), 'rec' => $rec );
	}
	return $out;
}

function ot_agent_url( $slug ) {
	$p = get_page_by_path( $slug, OBJECT, 'ot_agent' );
	return $p ? get_permalink( $p ) : home_url( '/agents/' . $slug . '/' );
}
