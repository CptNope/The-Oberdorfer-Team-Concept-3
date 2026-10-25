<?php
/**
 * Title: Site header
 * Slug: oberdorfer/header
 * Categories: oberdorfer, header
 * Block Types: core/template-part/header
 * Inserter: no
 * Description: Skip link, live region, staging ribbon, the sticky header (logo, primary navigation, search, team phone, menu button) and the mobile menu dialog. As the prototype's header().
 *
 * The navigation, logo and menu are HTML fragments because they carry SVG sprites and the
 * menu dialog's ARIA wiring that site.js drives; the ribbon is an editable paragraph.
 * aria-current on the active link is added at render time (functions.php).
 */
$nav = array(
	array( 'Buy', '/buy/' ),
	array( 'Sell', '/sell/' ),
	array( 'Communities', '/communities/' ),
	array( 'Our Team', '/team/' ),
	array( 'Journal', '/journal/' ),
	array( 'Join Our Team', '/join/' ),
	array( 'Contact', '/contact/' ),
);
$links = '';
$menu  = '';
foreach ( $nav as $item ) {
	if ( 'Join Our Team' !== $item[0] ) {
		$links .= '<a href="' . esc_url( oberdorfer_url( $item[1] ) ) . '">' . esc_html( $item[0] ) . '</a>';
	}
	$sub   = 'Buy' === $item[0] ? ' <span>Search homes</span>' : ( 'Sell' === $item[0] ? ' <span>Market review</span>' : '' );
	$menu .= '<li><a href="' . esc_url( oberdorfer_url( $item[1] ) ) . '">' . esc_html( $item[0] ) . $sub . '</a></li>';
}
?>
<!-- wp:html -->
<a class="skip-link" href="#main">Skip to content</a>
<div id="live" class="visually-hidden" aria-live="polite"></div>
<!-- /wp:html -->

<!-- wp:paragraph {"className":"demo-ribbon"} -->
<p class="demo-ribbon">Staging preview: forms are demonstrations and listings arrive with the IDX feed.</p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<header class="site-header">
  <div class="container site-header__inner">
    <a class="site-header__brand" href="<?php echo esc_url( oberdorfer_url( '/' ) ); ?>"><img src="<?php echo esc_url( oberdorfer_asset( 'assets/logo/logo-compact-ivory.svg' ) ); ?>" alt="The Oberdorfer Team, home" width="320" height="68" style="height:2.1rem"></a>
    <nav class="nav" aria-label="Primary"><?php echo $links; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above ?></nav>
    <div class="site-header__actions">
      <a class="icon-btn" href="<?php echo esc_url( oberdorfer_url( '/buy/#search' ) ); ?>" aria-label="Search properties"><?php echo oberdorfer_icon( 'search' ); // phpcs:ignore ?></a>
      <a class="btn btn--gold btn--small" href="tel:<?php echo esc_attr( OBERDORFER_TEL ); ?>"><?php echo esc_html( OBERDORFER_PHONE ); ?></a>
      <button class="icon-btn nav-toggle" type="button" data-menu-toggle aria-controls="mobile-menu" aria-expanded="false" aria-label="Open menu"><?php echo oberdorfer_icon( 'menu' ); // phpcs:ignore ?></button>
    </div>
  </div>
</header>
<!-- /wp:html -->

<!-- wp:html -->
<div class="mobile-menu" id="mobile-menu" role="dialog" aria-modal="true" aria-label="Menu" aria-hidden="true">
  <button class="icon-btn mobile-menu__close" type="button" data-close aria-label="Close menu" style="color:var(--ot-ivory)"><?php echo oberdorfer_icon( 'close' ); // phpcs:ignore ?></button>
  <ul><?php echo $menu; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above ?></ul>
  <div class="mobile-menu__foot"><a class="btn btn--gold" href="tel:<?php echo esc_attr( OBERDORFER_TEL ); ?>">Call <?php echo esc_html( OBERDORFER_PHONE ); ?></a><p class="caption" style="color:var(--ot-mist)">Brandon &amp; Kaitlyn Oberdorfer · Husband-and-wife team</p></div>
</div>
<!-- /wp:html -->
