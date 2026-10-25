<?php
/**
 * Title: Property search (IDX placeholder)
 * Slug: oberdorfer/idx-search-results
 * Categories: oberdorfer
 * Inserter: yes
 * Description: The #search section of the Buy page. Until the IDX provider is selected (docs/idx-options.md) it shows the listings placeholder; the demonstration search and listings of the prototype are not part of the WordPress build.
 */
echo oberdorfer_group( // phpcs:ignore WordPress.Security.EscapeOutput
	oberdorfer_group(
		oberdorfer_h( 'Property search', 2, 'visually-hidden' )
		. "<!-- wp:ot/listings-placeholder {\"heading\":\"Property search\"} /-->\n\n"
		. oberdorfer_p( 'The live search will come from the team\'s authorized IDX provider [to be selected], with MLS attribution and update times shown on every listing.', 'form-note ot-mt-7' ),
		'container'
	),
	'section--tight', 'section', 'search', 'Property search'
);
