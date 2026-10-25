<?php
/**
 * Title: Marketing and launch
 * Slug: oberdorfer/presentation-feature
 * Categories: oberdorfer
 * Inserter: yes
 * Description: "Every home, presented like a feature." with four ruled columns: pricing strategy, photography, property story, launch.
 */
$col = fn( $h, $t ) => oberdorfer_group( oberdorfer_h( $h, 3, 'h4' ) . oberdorfer_p( $t, 'body-s ot-mt-2' ) );
$cols = $col( 'Pricing strategy', 'Comparable closed sales, not headlines, with the reasoning shared in writing.' )
	. $col( 'Photography', 'Professional photography in natural light, with straight lines and lights on.' )
	. $col( 'Property story', 'A concrete, written story of how the home lives, with no generic superlatives.' )
	. $col( 'Launch', 'A launch date chosen around your calendar, with showing preferences agreed up front.' );
echo oberdorfer_section( // phpcs:ignore WordPress.Security.EscapeOutput
	oberdorfer_group( oberdorfer_section_head( 'Marketing and launch', 'Sell', array( 'Every home,', 'presented like a feature.' ), 'The same editorial care that goes into our <em>At Home</em> publications goes into every listing.', null, 'mkt-title' ) . oberdorfer_group( $cols, 'cols cols--4 col-rule' ), 'container' ),
	'section', '', 'mkt-title'
);
