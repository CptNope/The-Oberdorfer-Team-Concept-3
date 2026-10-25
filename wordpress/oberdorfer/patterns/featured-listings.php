<?php
/**
 * Title: Featured homes
 * Slug: oberdorfer/featured-listings
 * Categories: oberdorfer
 * Inserter: yes
 * Description: Sandstone section for featured homes. Until the IDX provider is chosen and connected it shows the listings placeholder (ot/listings-placeholder); demonstration listings are not part of the WordPress build.
 */
echo oberdorfer_section( // phpcs:ignore WordPress.Security.EscapeOutput
	oberdorfer_group(
		oberdorfer_section_head( 'Featured homes', 'IDX feed to be connected', array( 'Homes presented', 'like a feature.' ), '', array( 'All homes', oberdorfer_url( '/buy/' ) ), 'feat-title' )
		. "<!-- wp:ot/listings-placeholder {\"heading\":\"Featured homes\"} /-->\n\n"
		. oberdorfer_p( 'Listings appear here through the team\'s authorized IDX feed once it is connected.', 'form-note ot-mt-7' ),
		'container'
	),
	'section surface-muted', '', 'feat-title'
);
