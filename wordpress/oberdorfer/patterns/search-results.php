<?php
/**
 * Title: Search results
 * Slug: oberdorfer/search-results
 * Categories: oberdorfer
 * Inserter: no
 * Description: Search hero (the search term as the h1, the search form) and the matching items as article cards, with an empty state.
 */
$hero = "<!-- wp:ot/breadcrumbs /-->\n\n"
	. "<!-- wp:query-title {\"type\":\"search\",\"level\":1,\"className\":\"label page-hero__kicker\"} /-->\n\n"
	. oberdorfer_p( oberdorfer_lines( array( 'Search', 'the site.' ) ), 'page-hero__title display-l answer' )
	. "<!-- wp:search {\"label\":\"Search\",\"showLabel\":false,\"buttonText\":\"Search\",\"className\":\"page-hero__lead\"} /-->\n\n";
echo oberdorfer_group( oberdorfer_group( $hero, 'container page-hero__grid' ), 'page-hero', 'section' ) // phpcs:ignore WordPress.Security.EscapeOutput
	. oberdorfer_section( oberdorfer_group( oberdorfer_article_query( 9, true ), 'container' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
