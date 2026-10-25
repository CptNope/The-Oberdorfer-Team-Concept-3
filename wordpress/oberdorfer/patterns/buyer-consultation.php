<?php
/**
 * Title: Buyer consultation
 * Slug: oberdorfer/buyer-consultation
 * Categories: oberdorfer
 * Inserter: yes
 * Description: "Tell us what home should feel like." with links to communities, the market insight and the journal, beside the buyer inquiry form (ot/inquiry-form, context "buyer").
 */
$u    = fn( $p ) => esc_url( oberdorfer_url( $p ) );
$list = '<!-- wp:list {"className":"toc-mini"} -->
<ul class="wp-block-list toc-mini"><!-- wp:list-item -->
<li><a href="' . $u( '/communities/' ) . '">Explore communities</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="' . $u( '/market/' ) . '">Read the market insight</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="' . $u( '/journal/' ) . '">Buyer guides in the journal</a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

';
$copy = oberdorfer_group(
	oberdorfer_p( 'Buyer consultation', 'label' )
	. oberdorfer_h( oberdorfer_lines( array( 'Tell us what home', 'should feel like.' ) ), 2, 'display-m answer', 'buyer-form-title' )
	. oberdorfer_p( 'A short conversation about timing, budget and priorities, then a search shaped around you. No account needed.' )
	. $list,
	'stack ot-col-1-5 ot-stack-5'
);
$form = oberdorfer_group( '<!-- wp:ot/inquiry-form {"context":"buyer","title":"Request a buyer consultation","submit":"Request my consultation","level":"h3"} /-->', 'panel ot-col-7-6' );
echo oberdorfer_section( oberdorfer_group( $copy . $form, 'container split ot-align-start' ), 'section', '', 'buyer-form-title' ); // phpcs:ignore WordPress.Security.EscapeOutput
