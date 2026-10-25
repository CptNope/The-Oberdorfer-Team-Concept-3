<?php
/**
 * Title: Latest from the journal
 * Slug: oberdorfer/journal-latest
 * Categories: oberdorfer
 * Inserter: yes
 * Description: "Local living. Beautiful homes." with the three latest journal posts as article cards (core/query), and an empty state until the first posts are published.
 */
echo oberdorfer_section( // phpcs:ignore WordPress.Security.EscapeOutput
	oberdorfer_group(
		oberdorfer_section_head( 'The journal', 'At Home', array( 'Local living.', 'Beautiful homes.' ), '', array( 'Read the journal', oberdorfer_url( '/journal/' ) ), 'journal-title' )
		. oberdorfer_article_query( 3, false ),
		'container'
	),
	'section', '', 'journal-title'
);
