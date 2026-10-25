<?php
/**
 * Title: At Home publication archive
 * Slug: oberdorfer/publication-archive
 * Categories: oberdorfer
 * Inserter: yes
 * Description: Sandstone section "At Home, in print and online." with the At Home sign-up and the Wakefield at Home, Fall 2026 issue (cover and PDF download). Anchor #archive.
 *
 * The sign-up form and the archive card are HTML fragments. The cover image and the PDF are
 * referenced at assets/img/source-pages/page-1.webp and
 * assets/publications/wakefield-at-home-fall-2026.pdf inside the theme.
 */
$copy = oberdorfer_group(
	oberdorfer_p( 'The publication archive', 'label' )
	. oberdorfer_h( oberdorfer_lines( array( 'At Home,', 'in print and online.' ) ), 2, 'display-m answer' )
	. oberdorfer_p( 'Each issue is written for one community and one season. Download past issues, or subscribe to receive the next one.' )
	. oberdorfer_html( '<form class="newsletter" data-newsletter aria-label="Subscribe to the At Home publications"><label class="visually-hidden" for="j-news">Email address</label><input class="input" id="j-news" type="email" autocomplete="email" placeholder="Email address"><button class="btn btn--small" type="submit">Subscribe</button></form>' ),
	'stack ot-col-1-6'
);
$card = oberdorfer_html( '<div class="archive-card ot-col-8-5"><img src="' . esc_url( oberdorfer_asset( 'assets/img/source-pages/page-1.webp' ) ) . '" alt="Wakefield at Home, Fall 2026 cover" width="900" height="1157" loading="lazy"><div class="stack" style="--stack:var(--space-3)"><p class="h4">Wakefield at Home</p><p class="body-s muted">Fall 2026 · 8 pages</p><a class="link-arrow" href="' . esc_url( oberdorfer_asset( 'assets/publications/wakefield-at-home-fall-2026.pdf' ) ) . '" download>Download PDF (3 MB) ' . oberdorfer_icon( 'download' ) . '</a></div></div>' );
echo oberdorfer_section( oberdorfer_group( $copy . $card, 'container split ot-align-center' ), 'section surface-muted', 'archive' ); // phpcs:ignore WordPress.Security.EscapeOutput
