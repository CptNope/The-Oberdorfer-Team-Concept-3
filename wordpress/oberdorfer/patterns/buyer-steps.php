<?php
/**
 * Title: The buying process
 * Slug: oberdorfer/buyer-steps
 * Categories: oberdorfer
 * Inserter: yes
 * Description: Navy section "Three steps before the first showing.": preapproval, priorities and true cost, with the CFPB preapproval link.
 */
$col = fn( $label, $h, $t, $extra = '' ) => oberdorfer_group( oberdorfer_p( $label, 'label' ) . oberdorfer_h( $h, 3, 'h4' ) . oberdorfer_p( $t, 'body-s' ) . $extra, 'stack' );
$cols = $col( '01 · Preapproval', 'Get ready before you find the one.', 'Connect with a lender for preapproval and review a comfortable monthly budget. We can help you get that conversation started.', oberdorfer_link_arrow( 'CFPB: Get a preapproval letter', 'https://www.consumerfinance.gov/owning-a-home/', 'ot-icon-external ot-text-ivory' ) )
	. $col( '02 · Priorities', 'Know your everyday priorities.', 'Decide what matters most: a layout that works, outdoor space, a commute, or access to the places you enjoy. Test the routine as well as the floor plan.' )
	. $col( '03 · True cost', 'Look beyond the listing price.', 'Consider taxes, insurance, condominium fees, and maintenance. Talk through inspections and the documents you need to understand the property.' );
echo oberdorfer_section( // phpcs:ignore WordPress.Security.EscapeOutput
	oberdorfer_group( oberdorfer_section_head( 'The buying process', 'Buy', array( 'Three steps', 'before the first showing.' ), '', null, 'proc-title' ) . oberdorfer_group( $cols, 'cols cols--3 col-rule' ), 'container' ),
	'section surface-inverse', 'process', 'proc-title'
);
