<?php
/**
 * Title: Home cover
 * Slug: oberdorfer/home-cover
 * Categories: oberdorfer
 * Inserter: yes
 * Description: Front-page cover: the team portrait, the search-facing h1, the brand line, the lead and the home search bar. As the prototype's home hero.
 *
 * The portrait and the search form are HTML fragments: the image sits full-bleed under a
 * gradient scrim (.hero-cover__media) and the search form submits to the Buy page.
 */
$search = oberdorfer_icon( 'search' );
$media  = oberdorfer_html( '<div class="hero-cover__media"><img src="' . esc_url( oberdorfer_asset( 'assets/img/photo/cover-portrait.webp' ) ) . '" alt="Brandon and Kaitlyn Oberdorfer outside a Massachusetts home in autumn" width="1103" height="1426" fetchpriority="high"></div>' );
$copy   = oberdorfer_h( 'Massachusetts real estate team · Based in Worcester', 1, 'label home-hero__issue', 'home-title' )
	. oberdorfer_p( oberdorfer_lines( array( 'Luxury at', 'every price point.' ) ), 'display-xl answer' )
	. oberdorfer_p( 'Brandon and Kaitlyn Oberdorfer are a husband-and-wife real estate team based in Worcester, helping buyers and sellers across Massachusetts. Thoughtful service. Creative marketing. A personal connection to every move.', 'lead' );
$form   = oberdorfer_html( '<form class="search-bar" role="search" action="' . esc_url( oberdorfer_url( '/buy/' ) ) . '" method="get" aria-label="Search homes">
      <div class="field"><label class="field__label" for="h-q">Location</label><input class="input" id="h-q" name="q" type="text" placeholder="Town, neighborhood or ZIP" autocomplete="off"></div>
      <div class="field"><label class="field__label" for="h-price">Price</label><select class="select" id="h-price" name="price"><option value="">Any price</option><option value="0-600000">Up to $600k</option><option value="600000-900000">$600k – $900k</option><option value="900000-99999999">$900k +</option></select></div>
      <div class="field"><label class="field__label" for="h-beds">Beds</label><select class="select" id="h-beds" name="beds"><option value="">Any</option><option value="2">2+</option><option value="3">3+</option><option value="4">4+</option></select></div>
      <div class="field"><label class="field__label" for="h-type">Home type</label><select class="select" id="h-type" name="type"><option value="">All types</option><option value="single-family">Single-family</option><option value="condominium">Condominium</option><option value="multi-family">Multi-family</option></select></div>
      <button class="btn" type="submit">' . $search . 'Search</button>
    </form>' );
echo oberdorfer_section( $media . oberdorfer_group( oberdorfer_group( $copy, 'home-hero__copy' ) . $form, 'hero-cover__inner' ), 'hero-cover home-hero folio-frame', '', 'home-title' ); // phpcs:ignore WordPress.Security.EscapeOutput
