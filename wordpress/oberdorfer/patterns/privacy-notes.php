<?php
/**
 * Title: Privacy, accessibility and fair housing notes
 * Slug: oberdorfer/privacy-notes
 * Categories: oberdorfer
 * Inserter: yes
 * Description: Sandstone band with the #privacy, #accessibility and #fair-housing notes the footer links to. The privacy column links to the Privacy policy page.
 */
$col  = fn( $id, $h, $t ) => oberdorfer_group( oberdorfer_h( $h, 2, 'h4' ) . oberdorfer_p( $t, 'ot-mt-2' ), '', 'div', $id );
$cols = $col( 'privacy', 'Privacy', 'Form details are used only to respond to your request and are never sold. <a href="' . esc_url( oberdorfer_url( '/privacy/' ) ) . '">Read our privacy policy</a>.' )
	. $col( 'accessibility', 'Accessibility', 'This site is designed to meet WCAG 2.2 AA. If anything is hard to use, call ' . OBERDORFER_PHONE . ' and we will help.' )
	. $col( 'fair-housing', 'Fair housing', 'The Oberdorfer Team and REWAP Brokerage LLC are committed to the Fair Housing Act and Equal Housing Opportunity.' );
echo oberdorfer_section( oberdorfer_group( $cols, 'container cols cols--3 col-rule body-s' ), 'section surface-muted' ); // phpcs:ignore WordPress.Security.EscapeOutput
