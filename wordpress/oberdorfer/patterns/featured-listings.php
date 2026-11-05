<?php
/**
 * Title: Featured homes
 * Slug: oberdorfer/featured-listings
 * Categories: oberdorfer
 * Inserter: yes
 * Description: Sandstone section for featured homes. Shows the team's own listings (ot/team-listings) first, then the IDX feed; the whole section is left out when the team has no listings and IDX is switched off (ot-core: Settings > Listings (IDX)). Until the IDX provider is chosen and connected it shows the listings placeholder (ot/listings-placeholder); demonstration listings are not part of the WordPress build.
 */
echo oberdorfer_section( // phpcs:ignore WordPress.Security.EscapeOutput
	oberdorfer_group(
		oberdorfer_section_head( 'Featured homes', 'Brandon &amp; Kaitlyn\'s listings', array( 'Homes presented', 'like a feature.' ), '', array( 'All homes', oberdorfer_url( '/buy/' ) ), 'feat-title' )
		. "<!-- wp:ot/team-listings {\"limit\":3} /-->\n\n"
		. "<!-- wp:ot/listings-placeholder {\"heading\":\"Featured homes\"} /-->\n\n"
		. oberdorfer_p( 'Listings appear here through the team\'s authorized IDX feed once it is connected.', 'form-note ot-mt-7 ot-idx' ),
		'container'
	),
	'section surface-muted ot-homes', '', 'feat-title'
);
