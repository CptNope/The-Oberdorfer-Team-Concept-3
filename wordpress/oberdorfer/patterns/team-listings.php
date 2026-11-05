<?php
/**
 * Title: Team listings
 * Slug: oberdorfer/team-listings
 * Categories: oberdorfer
 * Inserter: yes
 * Description: The homes Brandon and Kaitlyn are presenting now, entered in Listings (ot-core). Left out of the page when the team has no listings.
 */
echo oberdorfer_section( // phpcs:ignore WordPress.Security.EscapeOutput
	oberdorfer_group(
		oberdorfer_section_head( 'Our listings', 'Brandon &amp; Kaitlyn', array( 'Homes we are', 'presenting now.' ), '', array(), 'tl-title' )
		. "<!-- wp:ot/team-listings {\"limit\":9} /-->\n\n",
		'container'
	),
	'section surface-muted ot-team-listings', 'listings', 'tl-title'
);
