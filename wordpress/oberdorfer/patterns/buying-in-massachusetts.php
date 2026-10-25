<?php
/**
 * Title: Buying in Massachusetts
 * Slug: oberdorfer/buying-in-massachusetts
 * Categories: oberdorfer
 * Inserter: yes
 * Description: What is different about buying here: the offer, inspection, purchase and sale agreement, attorney-led closing, lead paint and Title 5 steps, the MHP ONE+ panel and the sources.
 *
 * The numbered steps are an HTML fragment: each item holds a heading and a paragraph, which a
 * list item's rich text cannot keep as separate elements.
 */
$steps = oberdorfer_html( '<ol class="steps ot-col-7-6">
      <li><div><h3>The offer to purchase</h3><p class="body-s">A short standard form sets the price, deposit and contingencies, such as the home inspection and financing. Because the signed offer is usually binding on its own, the contingencies belong in it from the start.</p></div></li>
      <li><div><h3>The home inspection</h3><p class="body-s">The inspection period is set in the offer, often about a week to ten days. Repairs or credits are negotiated before the next contract is signed.</p></div></li>
      <li><div><h3>The purchase and sale agreement</h3><p class="body-s">The longer, detailed contract, usually drafted or reviewed by attorneys. It becomes the final agreement between buyer and seller.</p></div></li>
      <li><div><h3>An attorney-led closing</h3><p class="body-s">Massachusetts closings are conducted by attorneys. The closing attorney searches the title, prepares the closing documents and records the deed and mortgage at the county registry of deeds.</p></div></li>
      <li><div><h3>Homes built before 1978</h3><p class="body-s">Before you sign a purchase and sale agreement, the seller gives you the state\'s Property Transfer Lead Paint Notification and any lead reports for the home.</p></div></li>
      <li><div><h3>Homes on a septic system</h3><p class="body-s">A Title 5 inspection is generally required within two years before the sale. Ask for the report early.</p></div></li>
    </ol>' );
$intro = oberdorfer_group(
	oberdorfer_p( 'Buying in Massachusetts', 'label' )
	. oberdorfer_h( oberdorfer_lines( array( 'What is different', 'about buying here.' ) ), 2, 'display-m answer', 'ma-buy-title' )
	. oberdorfer_p( 'In Massachusetts, a signed offer to purchase is usually a binding contract, a longer purchase and sale agreement follows the home inspection, and an attorney conducts the closing. Older homes and homes on septic systems come with their own required paperwork.' ),
	'stack ot-col-1-5 ot-stack-5'
);
$panel = oberdorfer_group(
	oberdorfer_p( 'First-time buyers', 'label' )
	. oberdorfer_p( 'The Massachusetts Housing Partnership\'s ONE+ Mortgage offers discounted fixed rates, no private mortgage insurance and up to $50,000 in down payment and closing cost assistance to eligible first-time buyers in the 26 Gateway Cities, Framingham, Randolph and Boston. Income, asset and credit limits apply, and a homebuyer class is required.', 'body-s ot-mt-2' )
	. oberdorfer_p( 'Source: <a href="https://www.mhp.net/news/2024/one-plus-launch">Massachusetts Housing Partnership</a>. Check current terms with MHP and a participating lender.', 'caption ot-mt-3' ),
	'panel'
);
$sources = oberdorfer_p( 'Sources: <a href="https://www.nolo.com/legal-encyclopedia/relocating-what-know-before-buying-home-massachusetts.html">Nolo, Relocating to Massachusetts</a>; <a href="https://www.mass.gov/service-details/property-transfer-lead-paint-notification">Mass.gov, Property Transfer Lead Paint Notification</a>; <a href="https://www.mass.gov/guides/buying-or-selling-property-with-a-septic-system">Mass.gov, Buying or selling property with a septic system</a>. General information, not legal advice; your attorney confirms the details for your purchase.', 'caption ot-mt-5' );
echo oberdorfer_section( // phpcs:ignore WordPress.Security.EscapeOutput
	oberdorfer_group( $intro . $steps, 'container split ot-align-start' ) . oberdorfer_group( $panel . $sources, 'container ot-mt-7' ),
	'section', 'massachusetts', 'ma-buy-title'
);
