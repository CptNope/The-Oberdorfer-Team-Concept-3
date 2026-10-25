<?php
/**
 * Title: Article body
 * Slug: oberdorfer/article-body
 * Categories: oberdorfer
 * Inserter: no
 * Description: The article column (post content: caption, lede, guide list, sources) beside the aside with the category, the date and a link back to the journal.
 */
$aside = oberdorfer_p( 'The journal', 'label' )
	. "<!-- wp:post-terms {\"term\":\"category\",\"className\":\"body-s\"} /-->\n\n"
	. "<!-- wp:post-date {\"className\":\"body-s\"} /-->\n\n"
	. oberdorfer_link_arrow( 'All articles', oberdorfer_url( '/journal/' ) );
echo oberdorfer_section( // phpcs:ignore WordPress.Security.EscapeOutput
	oberdorfer_group(
		"<!-- wp:post-content {\"tagName\":\"article\",\"className\":\"article__body\",\"layout\":{\"type\":\"default\"}} /-->\n\n"
		. oberdorfer_group( $aside, 'article__aside', 'aside', '', 'About this article' ),
		'container article'
	)
);
