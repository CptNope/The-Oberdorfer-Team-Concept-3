<?php
/**
 * Title: Town by town market table
 * Slug: oberdorfer/town-market-table
 * Categories: oberdorfer
 * Inserter: yes
 * Description: Sandstone section "Single-family prices in our guide towns." with the all-towns comparison table and its source (ot/market-table, from the researched records).
 */
echo oberdorfer_section( // phpcs:ignore WordPress.Security.EscapeOutput
	oberdorfer_group( oberdorfer_section_head( 'Town by town', 'Calendar year 2025', array( 'Single-family prices', 'in our guide towns.' ), '', null, 'towns-title' ) . "<!-- wp:ot/market-table /-->\n\n", 'container' ),
	'section surface-muted', 'towns', 'towns-title'
);
