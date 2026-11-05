<?php
/**
 * Title: Agent profile: how they can help
 * Slug: oberdorfer/agent-help
 * Categories: oberdorfer
 * Inserter: no
 * Description: The three services on every agent profile (buying, selling, a market review). Shared by both profiles; {first name} is replaced with the agent's first name.
 */
$col  = fn( $h, $t, $label, $url ) => oberdorfer_group( oberdorfer_h( $h, 3, 'h4' ) . oberdorfer_p( $t, 'body-s' ) . oberdorfer_link_arrow( $label, $url ), 'stack' );
$cols = $col( 'Buying a home', 'Preapproval, everyday priorities and the costs beyond the listing price, then showings shaped around your life.', 'Start a home search', oberdorfer_url( '/buy/' ) )
	. $col( 'Selling a home', 'Pricing from comparable closed sales, preparation, photography and a launch planned around your calendar.', 'Plan your sale', oberdorfer_url( '/sell/' ) )
	. $col( 'A market review', 'A property-specific look at recent closed sales before you decide what to offer or where to price.', 'Request a market review', oberdorfer_url( '/sell/#consult' ) );
echo oberdorfer_section( oberdorfer_group( oberdorfer_section_head( 'How {first name} can help', 'Services', array( 'A plan for', 'your next move.' ), '', null, 'help-title' ) . oberdorfer_group( $cols, 'cols cols--3 col-rule' ), 'container' ), 'section', '', 'help-title' ); // phpcs:ignore WordPress.Security.EscapeOutput
