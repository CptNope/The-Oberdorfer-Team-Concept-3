<?php
/**
 * Title: Recruiting conversation
 * Slug: oberdorfer/recruiting-form
 * Categories: oberdorfer
 * Inserter: yes
 * Description: "Start with a conversation." beside the confidential recruiting inquiry form (ot/inquiry-form, context "recruiting"). Anchor #recruit.
 */
$copy = oberdorfer_group(
	oberdorfer_p( 'Confidential conversation', 'label' )
	. oberdorfer_h( oberdorfer_lines( array( 'Start with', 'a conversation.' ) ), 2, 'display-m answer', 'recruit-heading' )
	. oberdorfer_p( 'Your inquiry goes only to Brandon and Kaitlyn and is kept confidential.' ),
	'stack ot-col-1-5'
);
$form = oberdorfer_group( '<!-- wp:ot/inquiry-form {"context":"recruiting","title":"Recruiting inquiry","submit":"Start the conversation","level":"h3","note":"Your inquiry is confidential and goes only to Brandon and Kaitlyn."} /-->', 'panel ot-col-7-6' );
echo oberdorfer_section( oberdorfer_group( $copy . $form, 'container split ot-align-start' ), 'section', 'recruit', 'recruit-heading' ); // phpcs:ignore WordPress.Security.EscapeOutput
