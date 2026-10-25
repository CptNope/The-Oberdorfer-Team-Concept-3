<?php
/**
 * Title: Contact details and inquiry tabs
 * Slug: oberdorfer/contact-tabs
 * Categories: oberdorfer
 * Inserter: yes
 * Description: The team phone, the contact lines (call or text, email, contact cards, Instagram, office by appointment, service area) and four inquiry forms in tabs: general, buying, selling, joining the team.
 *
 * The contact lines (icons) and the tab list (ARIA tabs driven by site-demo.js) are HTML
 * fragments; each tab panel is a group holding an ot/inquiry-form.
 */
$i     = fn( $n ) => oberdorfer_icon( $n );
$lines = oberdorfer_html( '<div>
      <div class="contact-line">' . $i( 'phone' ) . '<div><p class="h5">Call or text</p><p class="body-s">Kaitlyn Oberdorfer · <a href="tel:+19782574938">978-257-4938</a><br>Brandon Oberdorfer · <a href="tel:' . OBERDORFER_TEL . '">' . OBERDORFER_PHONE . '</a> (also the team line)</p></div></div>
      <div class="contact-line">' . $i( 'mail' ) . '<div><p class="h5">Email</p><p class="body-s">Kaitlyn · <a href="mailto:kaitlynoberdorfer@gmail.com">kaitlynoberdorfer@gmail.com</a><br>Brandon · <a href="mailto:brandonoberdorfer@gmail.com">brandonoberdorfer@gmail.com</a></p></div></div>
      <div class="contact-line">' . $i( 'download' ) . '<div><p class="h5">Save our contacts</p><p class="body-s"><a href="' . esc_url( home_url( '/?vcard=kaitlyn-oberdorfer' ) ) . '">Kaitlyn</a> · <a href="' . esc_url( home_url( '/?vcard=brandon-oberdorfer' ) ) . '">Brandon</a> · <a href="' . esc_url( home_url( '/?vcard=oberdorfer-team' ) ) . '">Both</a> (vCard, for your phone\'s contacts)</p></div></div>
      <div class="contact-line">' . $i( 'external' ) . '<div><p class="h5">Follow along</p><p class="body-s"><a href="https://www.instagram.com/oberdorferteam/" rel="noopener">Instagram @oberdorferteam</a></p></div></div>
      <div class="contact-line">' . $i( 'door' ) . '<div><p class="h5">Team office</p><p class="body-s">' . esc_html( oberdorfer_address() ) . '. By appointment. <a href="' . esc_url( oberdorfer_url( '/office/worcester/' ) ) . '">Office details</a></p></div></div>
      <div class="contact-line">' . $i( 'pin' ) . '<div><p class="h5">Service area</p><p class="body-s">Massachusetts</p></div></div>
    </div>' );
$left = oberdorfer_group( oberdorfer_phone_link( 'cta-panel__phone ot-text-navy ot-fs-display-m' ) . $lines, 'stack ot-stack-6' );

$tabs   = array(
	array( 'general', 'General', '{"context":"general","title":"Send a message","submit":"Send my message"}' ),
	array( 'buyer', 'Buying', '{"context":"buyer","title":"Buyer consultation","submit":"Request my consultation"}' ),
	array( 'seller', 'Selling', '{"context":"seller","title":"Seller consultation","submit":"Request my market review"}' ),
	array( 'recruit', 'Joining the team', '{"context":"recruiting","title":"Recruiting inquiry","submit":"Start the conversation","note":"Confidential. Goes only to Brandon and Kaitlyn."}' ),
);
$btns   = '';
$panels = '';
foreach ( $tabs as $n => $t ) {
	$btns   .= '<button role="tab" id="tab-' . $t[0] . '" aria-controls="panel-' . $t[0] . '" aria-selected="' . ( 0 === $n ? 'true' : 'false' ) . '" tabindex="' . ( 0 === $n ? 0 : -1 ) . '" type="button">' . $t[1] . '</button>';
	$panels .= oberdorfer_group( '<!-- wp:ot/inquiry-form ' . $t[2] . ' /-->', 'tabpanel ot-role-tabpanel ot-labelledby-tab-' . $t[0] . ( 0 === $n ? '' : ' ot-hidden' ), 'div', 'panel-' . $t[0] );
}
$right = oberdorfer_group( oberdorfer_html( '<div class="tabs" role="tablist" aria-label="Type of inquiry">' . $btns . '</div>' ) . $panels, 'panel' );
echo oberdorfer_section( oberdorfer_group( $left . $right, 'container contact-grid' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
