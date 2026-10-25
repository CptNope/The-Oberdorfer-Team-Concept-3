<?php
/**
 * Title: Communities grid
 * Slug: oberdorfer/communities-grid
 * Categories: oberdorfer
 * Inserter: yes
 * Description: Sandstone section with four community cards (Worcester, Shrewsbury, Wakefield, Framingham) from ot/community-cards, a link to all town guides and the photo credit.
 *
 * The guide count comes from ot-core when it is active; without it the lead omits the number.
 */
$n    = function_exists( 'ot_towns' ) ? count( ot_towns() ) : 0;
$lead = ( $n ? $n . ' town guides' : 'Town guides' ) . ', from Worcester to Wakefield, written the way we write <em>At Home</em>: researched, credited and useful.';
echo oberdorfer_section( // phpcs:ignore WordPress.Security.EscapeOutput
	oberdorfer_group(
		oberdorfer_section_head( 'Communities', 'Across Massachusetts', array( 'Close to home,', 'wherever home is.' ), $lead, array( 'All town guides', oberdorfer_url( '/communities/' ) ), 'comm-title' )
		. oberdorfer_group( "<!-- wp:ot/community-cards {\"slugs\":[\"worcester\",\"shrewsbury\",\"wakefield\",\"framingham\"]} /-->", 'card-grid-4' )
		. oberdorfer_p( 'Lake Quannapowitt Panorama, RedSoxFan274 / Wikimedia Commons, CC BY-SA 4.0, cropped. Line drawings stand in for community photography still to be commissioned.', 'caption ot-mt-4' ),
		'container'
	),
	'section surface-muted', '', 'comm-title'
);
