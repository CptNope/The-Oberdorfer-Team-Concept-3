<?php
/**
 * Title: The 30-minute fall edit
 * Slug: oberdorfer/fall-edit
 * Categories: oberdorfer
 * Inserter: yes
 * Description: Sandstone section with the seasonal preparation triad: the welcome, the light, the calm.
 */
$t = fn( $l, $h ) => oberdorfer_group( oberdorfer_p( $l, 'label' ) . oberdorfer_p( $h, 'h5' ) );
$copy = oberdorfer_group(
	oberdorfer_p( 'Preparation', 'label' )
	. oberdorfer_h( oberdorfer_lines( array( 'The 30-minute', 'fall edit.' ) ), 2, 'display-m answer' )
	. oberdorfer_p( 'Small, seasonal changes before photography and showings.' ),
	'stack ot-col-1-6'
);
$triad = oberdorfer_group(
	$t( 'The welcome', 'Sweep the entry and style one simple seasonal planter.' )
	. $t( 'The light', 'Open the shades and give the windows a quick clean.' )
	. $t( 'The calm', 'Clear one surface and add a warm, soft layer.' ),
	'triad ot-col-7-6'
);
echo oberdorfer_section( oberdorfer_group( $copy . $triad, 'container split ot-align-center' ), 'section surface-muted', 'prepare' ); // phpcs:ignore WordPress.Security.EscapeOutput
