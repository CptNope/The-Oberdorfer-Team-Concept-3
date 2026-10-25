<?php
/**
 * Title: Journal posts
 * Slug: oberdorfer/journal-feature-grid
 * Categories: oberdorfer
 * Inserter: yes
 * Description: The journal's posts as article cards (core/query following the main query, with pagination) and an empty state until the first posts are published.
 */
echo oberdorfer_section( oberdorfer_group( oberdorfer_article_query( 9, true ), 'container' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
