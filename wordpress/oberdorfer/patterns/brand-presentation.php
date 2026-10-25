<?php
/**
 * Title: What you would join
 * Slug: oberdorfer/brand-presentation
 * Categories: oberdorfer
 * Inserter: yes
 * Description: Sandstone section "A brand built on presentation." with four columns: the brand, the office (Worcester, Unit B), marketing and technology, training and mentorship. Bracketed placeholders stay until the team confirms; compensation is never stated.
 */
$col  = fn( $h, $t ) => oberdorfer_group( oberdorfer_h( $h, 3, 'h4' ) . oberdorfer_p( $t, 'body-s ot-mt-2' ) );
$cols = $col( 'The brand', 'The Oberdorfer Team identity and the <em>At Home</em> publications. [What agents receive to be confirmed by the team.]' )
	. $col( 'The office', 'The team works from ' . esc_html( oberdorfer_address() ) . ', under REWAP Brokerage.' )
	. $col( 'Marketing and technology', '[Support details to be confirmed by the team before publication.]' )
	. $col( 'Training and mentorship', '[Program details to be confirmed by the team before publication.]' );
echo oberdorfer_section( // phpcs:ignore WordPress.Security.EscapeOutput
	oberdorfer_group(
		oberdorfer_section_head( 'What you would join', 'Join Our Team', array( 'A brand built', 'on presentation.' ), '', null, 'j-title' )
		. oberdorfer_group( $cols, 'cols cols--4 col-rule' )
		. oberdorfer_p( 'Compensation, splits and fees are discussed privately and are never stated on this site.', 'form-note ot-mt-6' ),
		'container'
	),
	'section surface-muted', '', 'j-title'
);
