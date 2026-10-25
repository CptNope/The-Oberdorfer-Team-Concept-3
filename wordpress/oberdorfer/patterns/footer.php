<?php
/**
 * Title: Site footer
 * Slug: oberdorfer/footer
 * Categories: oberdorfer, footer
 * Block Types: core/template-part/footer
 * Inserter: no
 * Description: Logo, brand line, team phone and the At Home sign-up; Explore, Communities, Team and Read links; the REWAP plate, the team disclosure with the Worcester office address and Equal Housing Opportunity. As the prototype's footer().
 *
 * The REWAP logo appears only on its own ivory plate and is never redrawn (CLAUDE.md, ground
 * rule 6), so the plate is an HTML fragment. The disclosure keeps the prototype's bracketed
 * placeholders until the principal broker supplies and approves the wording.
 */
$towns = array(
	array( 'worcester', 'Worcester' ),
	array( 'shrewsbury', 'Shrewsbury' ),
	array( 'framingham', 'Framingham' ),
	array( 'wakefield', 'Wakefield' ),
	array( 'boston', 'Boston' ),
	array( 'springfield', 'Springfield' ),
);
$u = fn( $p ) => esc_url( oberdorfer_url( $p ) );
?>
<!-- wp:group {"tagName":"footer","className":"site-footer","layout":{"type":"default"}} -->
<footer class="wp-block-group site-footer"><!-- wp:group {"className":"container","layout":{"type":"default"}} -->
<div class="wp-block-group container"><!-- wp:group {"className":"site-footer__grid","layout":{"type":"default"}} -->
<div class="wp-block-group site-footer__grid"><!-- wp:group {"className":"stack ot-stack-5","layout":{"type":"default"}} -->
<div class="wp-block-group stack ot-stack-5"><!-- wp:html -->
<img src="<?php echo esc_url( oberdorfer_asset( 'assets/logo/logo-primary-gold.svg' ) ); ?>" alt="The Oberdorfer Team" width="300" height="40" style="width:17rem">
<!-- /wp:html -->

<!-- wp:paragraph {"className":"body-s ot-measure-30"} -->
<p class="body-s ot-measure-30">Luxury at every price point. A husband-and-wife real estate team based in Worcester, serving all of Massachusetts.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"ot-unwrap"} -->
<div class="wp-block-buttons ot-unwrap"><!-- wp:button {"className":"cta-panel__phone ot-fs-2"} -->
<div class="wp-block-button cta-panel__phone ot-fs-2"><a class="wp-block-button__link wp-element-button" href="tel:<?php echo esc_attr( OBERDORFER_TEL ); ?>"><?php echo esc_html( OBERDORFER_PHONE ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:html -->
<form class="newsletter" style="color:var(--ot-ivory)" data-newsletter aria-label="Seasonal publication sign-up"><label class="visually-hidden" for="f-news">Email address</label><input class="input" id="f-news" type="email" autocomplete="email" placeholder="Email for the At Home issues" style="color:var(--ot-ivory)"><button class="btn btn--small" type="submit">Subscribe</button></form>
<!-- /wp:html --></div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading -->
<h2 class="wp-block-heading">Explore</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li><a href="<?php echo $u( '/buy/' ); ?>">Buy a home</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo $u( '/sell/' ); ?>">Sell your home</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo $u( '/market/' ); ?>">Market insights</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo $u( '/communities/' ); ?>">Communities</a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading -->
<h2 class="wp-block-heading">Communities</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><?php foreach ( $towns as $t ) : ?><!-- wp:list-item -->
<li><a href="<?php echo $u( '/communities/' . $t[0] . '/' ); ?>"><?php echo esc_html( $t[1] ); ?>, MA</a></li>
<!-- /wp:list-item -->

<?php endforeach; ?><!-- wp:list-item -->
<li><a href="<?php echo $u( '/communities/' ); ?>">All town guides</a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading -->
<h2 class="wp-block-heading">Team</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li><a href="<?php echo $u( '/team/' ); ?>">Meet the team</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo $u( '/agents/brandon-kaitlyn-oberdorfer/' ); ?>">Brandon &amp; Kaitlyn</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo $u( '/agents/brandon-oberdorfer/' ); ?>">Brandon Oberdorfer</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo $u( '/agents/kaitlyn-oberdorfer/' ); ?>">Kaitlyn Oberdorfer</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo $u( '/join/' ); ?>">Join our team</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo $u( '/office/worcester/' ); ?>">Team office</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="https://www.instagram.com/oberdorferteam/" rel="noopener">Instagram</a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading -->
<h2 class="wp-block-heading">Read</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li><a href="<?php echo $u( '/journal/' ); ?>">Journal</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo $u( '/journal/#archive' ); ?>">At Home archive</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo $u( '/contact/' ); ?>">Contact</a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"attribution","layout":{"type":"default"}} -->
<div class="wp-block-group attribution"><!-- wp:html -->
<div class="attribution__plate"><img src="<?php echo esc_url( oberdorfer_asset( 'assets/img/rewap-logo.webp' ) ); ?>" alt="REWAP Brokerage" width="900" height="300"><small>Real Estate Wealth Acquisition and Preservation Brokerage</small></div>
<!-- /wp:html -->

<!-- wp:group {"className":"stack ot-stack-3","layout":{"type":"default"}} -->
<div class="wp-block-group stack ot-stack-3"><!-- wp:paragraph {"className":"disclosure"} -->
<p class="disclosure"><strong>The Oberdorfer Team</strong> is a real estate team operating under <strong>REWAP Brokerage LLC</strong>, Massachusetts. Office: <?php echo esc_html( oberdorfer_address() ); ?>. Brokerage license number and required team disclosures [to be supplied and approved by the principal broker]. Listing information is provided by [MLS name] and is deemed reliable but not guaranteed.</p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<p class="eho"><?php echo oberdorfer_icon( 'eho' ); // phpcs:ignore ?>Equal Housing Opportunity</p>
<!-- /wp:html -->

<!-- wp:paragraph {"className":"disclosure"} -->
<p class="disclosure">© 2026 The Oberdorfer Team · <a href="<?php echo $u( '/contact/#privacy' ); ?>">Privacy</a> · <a href="<?php echo $u( '/contact/#accessibility' ); ?>">Accessibility</a> · <a href="<?php echo $u( '/contact/#fair-housing' ); ?>">Fair housing</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></footer>
<!-- /wp:group -->
