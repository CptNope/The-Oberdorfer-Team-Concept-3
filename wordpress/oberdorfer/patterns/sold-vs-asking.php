<?php
/**
 * Title: Sold vs. asking
 * Slug: oberdorfer/sold-vs-asking
 * Categories: oberdorfer
 * Inserter: yes
 * Description: Sandstone band with three ruled notes: sold vs. asking, beyond square feet, and your next step (link to the market review form).
 */
$col = fn( $h, $t, $x = '' ) => oberdorfer_group( oberdorfer_h( $h, 2, 'h4' ) . oberdorfer_p( $t, 'body-s ot-mt-2' ) . $x );
$cols = $col( 'Sold vs. asking', 'A listing price is a seller\'s starting point. A closed sale shows what a buyer actually paid.' )
	. $col( 'Beyond square feet', 'Lot size, renovation quality, layout, parking, and condominium fees all belong in the comparison.' )
	. $col( 'Your next step', 'Ask for a property-specific market review before deciding what to offer or where to price.', oberdorfer_link_arrow( 'Request a review', oberdorfer_url( '/sell/#consult' ) ) );
echo oberdorfer_section( oberdorfer_group( oberdorfer_group( $cols, 'cols cols--3 col-rule' ), 'container' ), 'section surface-muted' ); // phpcs:ignore WordPress.Security.EscapeOutput
