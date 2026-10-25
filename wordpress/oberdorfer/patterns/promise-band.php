<?php
/**
 * Title: Our promise
 * Slug: oberdorfer/promise-band
 * Categories: oberdorfer
 * Inserter: yes
 * Description: Centered promise headline over a dimension rule and three columns: thoughtful service, creative marketing, clear guidance.
 */
$col = fn( $h, $t ) => oberdorfer_group( oberdorfer_h( $h, 3, 'h4' ) . oberdorfer_p( $t, 'body-s ot-mt-2' ) );
$inner = oberdorfer_p( 'Our promise', 'label' )
	. oberdorfer_h( 'The same care for a first condo as for a family estate.', 2, 'display-l', 'promise-title' )
	. "<!-- wp:separator {\"className\":\"dim-rule ot-rule-short\"} -->\n<hr class=\"wp-block-separator has-alpha-channel-opacity dim-rule ot-rule-short\"/>\n<!-- /wp:separator -->\n\n"
	. oberdorfer_group(
		$col( 'Thoughtful service', 'You work with the two people who lead the team, from the first conversation through closing.' )
		. $col( 'Creative marketing', 'Professional photography, a property story and editorial presentation for every listing.' )
		. $col( 'Clear guidance', 'Pricing and offers grounded in comparable closed sales, never headlines.' ),
		'cols cols--3 col-rule ot-text-start ot-w-full'
	);
echo oberdorfer_section( oberdorfer_group( $inner, 'container promise' ), 'section', '', 'promise-title' ); // phpcs:ignore WordPress.Security.EscapeOutput
