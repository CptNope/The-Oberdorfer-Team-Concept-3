<?php
/**
 * Title: Seller consultation
 * Slug: oberdorfer/review-form
 * Categories: oberdorfer
 * Inserter: yes
 * Description: "Ask for a property-specific review." with the four seller questions (core/details accordion, no FAQPage markup), beside the seller inquiry form (ot/inquiry-form, context "seller").
 */
$faq = array(
	array( 'When should I list?', 'Agree on a pricing strategy before choosing a launch date. Your timing, showing preferences and next move shape the calendar, and a clear preparation plan makes the many decisions easier to manage.' ),
	array( 'How do you price a home?', 'With recent closed sales that resemble your property in location, size, condition and style. Lot size, renovation quality, layout, parking and condominium fees all belong in the comparison. A listing price is a starting point; a closed sale shows what a buyer actually paid.' ),
	array( 'Do I need to stage?', 'Usually a few small changes matter most: clear the entry, simplify the rooms, and show the home in good light. We help you decide what is worth doing and what is not.' ),
	array( 'What does the marketing include?', 'Professional photography, a written property story and editorial presentation across the website, print and social channels. The specific package is confirmed in your consultation.' ),
);
$acc = '';
foreach ( $faq as $i => $qa ) {
	$acc .= oberdorfer_details( $qa[0], oberdorfer_p( $qa[1] ), 0 === $i );
}
$copy = oberdorfer_group(
	oberdorfer_p( 'Seller consultation', 'label' )
	. oberdorfer_h( oberdorfer_lines( array( 'Ask for a', 'property-specific review.' ) ), 2, 'display-m answer', 'consult-title' )
	. oberdorfer_p( 'The townwide median is a starting point. Your best comparisons are recent closed sales that resemble your home. We prepare them before we meet.' )
	. oberdorfer_group( $acc, 'accordion ot-mt-5' ),
	'stack ot-col-1-5'
);
$form = oberdorfer_group( '<!-- wp:ot/inquiry-form {"context":"seller","title":"Request a market review","submit":"Request my market review","level":"h3"} /-->', 'panel ot-col-7-6' );
echo oberdorfer_section( oberdorfer_group( $copy . $form, 'container split ot-align-start' ), 'section', 'consult', 'consult-title' ); // phpcs:ignore WordPress.Security.EscapeOutput
