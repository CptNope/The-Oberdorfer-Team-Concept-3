<?php
/**
 * Title: Article hero
 * Slug: oberdorfer/article-hero
 * Categories: oberdorfer
 * Inserter: no
 * Description: Photo hero for a journal article: the featured image under the navy scrim, breadcrumbs, the post title as the display h1 and the excerpt as the lead. As the prototype's article page.
 */
$inner = "<!-- wp:ot/breadcrumbs /-->\n\n"
	. "<!-- wp:post-title {\"level\":1,\"className\":\"page-hero__title display-l answer\"} /-->\n\n"
	. "<!-- wp:post-excerpt {\"className\":\"page-hero__lead lead\"} /-->\n\n";
echo oberdorfer_group( // phpcs:ignore WordPress.Security.EscapeOutput
	"<!-- wp:post-featured-image {\"className\":\"page-hero__photo\"} /-->\n\n" . oberdorfer_group( $inner, 'container page-hero__grid' ),
	'page-hero page-hero--navy surface-inverse page-hero--photo', 'section'
);
