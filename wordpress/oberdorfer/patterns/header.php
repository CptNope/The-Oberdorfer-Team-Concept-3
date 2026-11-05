<?php
/**
 * Title: Site header
 * Slug: oberdorfer/header
 * Categories: oberdorfer, header
 * Block Types: core/template-part/header
 * Inserter: no
 * Description: Skip link, live region, staging ribbon, the sticky header (logo, primary navigation, search, team phone, menu button) and the mobile menu dialog. As the prototype's header().
 *
 * The navigation links are a paragraph inside a <nav> group and the phone number is a button,
 * so the team edits them as text (Appearance > Editor > Patterns > Header). The logo, search
 * icon, menu button and mobile menu dialog are HTML fragments: they carry SVG sprites and the
 * ARIA wiring site.js drives. The mobile menu's links are built at render time from the
 * navigation paragraph (functions.php), so they always match; aria-current is added there too.
 */
$nav = array(
	array( 'Buy', '/buy/' ),
	array( 'Sell', '/sell/' ),
	array( 'Communities', '/communities/' ),
	array( 'Our Team', '/team/' ),
	array( 'Journal', '/journal/' ),
	array( 'Contact', '/contact/' ),
);
$links = array();
foreach ( $nav as $item ) {
	$links[] = '<a href="' . esc_url( oberdorfer_url( $item[1] ) ) . '">' . esc_html( $item[0] ) . '</a>';
}
$links = implode( ' ', $links );
?>
<!-- wp:html -->
<a class="skip-link" href="#main">Skip to content</a>
<div id="live" class="visually-hidden" aria-live="polite"></div>
<!-- /wp:html -->

<!-- wp:paragraph {"className":"demo-ribbon"} -->
<p class="demo-ribbon">Staging preview: forms are demonstrations and listings arrive with the IDX feed.</p>
<!-- /wp:paragraph -->

<!-- wp:group {"tagName":"header","className":"site-header","layout":{"type":"default"}} -->
<header class="wp-block-group site-header"><!-- wp:group {"className":"container site-header__inner","layout":{"type":"default"}} -->
<div class="wp-block-group container site-header__inner"><!-- wp:html -->
<a class="site-header__brand" href="<?php echo esc_url( oberdorfer_url( '/' ) ); ?>"><img src="<?php echo esc_url( oberdorfer_asset( 'assets/logo/logo-compact-ivory.svg' ) ); ?>" alt="The Oberdorfer Team, home" width="320" height="68" style="height:2.1rem"></a>
<!-- /wp:html -->

<!-- wp:group {"tagName":"nav","className":"nav","ariaLabel":"Primary","layout":{"type":"default"}} -->
<nav class="wp-block-group nav" aria-label="Primary"><!-- wp:paragraph {"className":"nav__links"} -->
<p class="nav__links"><?php echo $links; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above ?></p>
<!-- /wp:paragraph --></nav>
<!-- /wp:group -->

<!-- wp:group {"className":"site-header__actions","layout":{"type":"default"}} -->
<div class="wp-block-group site-header__actions"><!-- wp:html -->
<a class="icon-btn" data-ot-idx href="<?php echo esc_url( oberdorfer_url( '/buy/#search' ) ); ?>" aria-label="Search properties"><?php echo oberdorfer_icon( 'search' ); // phpcs:ignore ?></a>
<!-- /wp:html -->

<!-- wp:buttons {"className":"ot-unwrap"} -->
<div class="wp-block-buttons ot-unwrap"><!-- wp:button {"className":"btn btn--gold btn--small"} -->
<div class="wp-block-button btn btn--gold btn--small"><a class="wp-block-button__link wp-element-button" href="tel:<?php echo esc_attr( OBERDORFER_TEL ); ?>"><?php echo esc_html( OBERDORFER_PHONE ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:html -->
<button class="icon-btn nav-toggle" type="button" data-menu-toggle aria-controls="mobile-menu" aria-expanded="false" aria-label="Open menu"><?php echo oberdorfer_icon( 'menu' ); // phpcs:ignore ?></button>
<!-- /wp:html --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></header>
<!-- /wp:group -->

<!-- wp:html -->
<div class="mobile-menu" id="mobile-menu" role="dialog" aria-modal="true" aria-label="Menu" aria-hidden="true">
  <button class="icon-btn mobile-menu__close" type="button" data-close aria-label="Close menu" style="color:var(--ot-ivory)"><?php echo oberdorfer_icon( 'close' ); // phpcs:ignore ?></button>
  <ul data-ot-menu></ul>
  <div class="mobile-menu__foot"><a class="btn btn--gold" href="tel:<?php echo esc_attr( OBERDORFER_TEL ); ?>">Call <?php echo esc_html( OBERDORFER_PHONE ); ?></a><p class="caption" style="color:var(--ot-mist)">Brandon &amp; Kaitlyn Oberdorfer · Husband-and-wife team</p></div>
</div>
<!-- /wp:html -->
