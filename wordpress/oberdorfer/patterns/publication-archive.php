<?php
/**
 * Title: At Home publication archive
 * Slug: oberdorfer/publication-archive
 * Categories: oberdorfer
 * Inserter: yes
 * Description: Sandstone section "At Home, in print and online." with the At Home sign-up and the Wakefield at Home, Fall 2026 issue (cover, read online and PDF download). Anchor #archive.
 *
 * The sign-up form and the archive card are HTML fragments. The cover image and the PDF are
 * referenced at assets/img/source-pages/page-1.webp and
 * assets/publications/wakefield-at-home-fall-2026.pdf inside the theme.
 */
$copy = oberdorfer_group(
	oberdorfer_p( 'The publication archive', 'label' )
	. oberdorfer_h( oberdorfer_lines( array( 'At Home,', 'in print and online.' ) ), 2, 'display-m answer' )
	. oberdorfer_p( 'Each issue is written for one community and one season. Read past issues online or download them, or subscribe to receive the next one.' )
	. oberdorfer_html( '<form class="newsletter" data-newsletter aria-label="Subscribe to the At Home publications"><label class="visually-hidden" for="j-news">Email address</label><input class="input" id="j-news" type="email" autocomplete="email" placeholder="Email address"><button class="btn btn--small" type="submit">Subscribe</button></form>' ),
	'stack ot-col-1-6'
);
// Read online (the browser's own PDF viewer, in a new tab) or download, from the same file.
$pdf  = esc_url( oberdorfer_asset( 'assets/publications/wakefield-at-home-fall-2026.pdf' ) );
$card = oberdorfer_html( '<div class="archive-card ot-col-8-5"><a href="' . $pdf . '" target="_blank" rel="noopener" aria-label="Read Wakefield at Home, Fall 2026 (PDF, opens in a new tab)"><img src="' . esc_url( oberdorfer_asset( 'assets/img/source-pages/page-1.webp' ) ) . '" alt="Wakefield at Home, Fall 2026 cover" width="900" height="1157" loading="lazy"></a><div class="stack" style="--stack:var(--space-3)"><p class="h4">Wakefield at Home</p><p class="body-s muted">Fall 2026 · 8 pages · PDF, 3 MB</p><p><a class="link-arrow" href="' . $pdf . '" target="_blank" rel="noopener">Read it online ' . oberdorfer_arrow() . '<span class="visually-hidden"> (PDF, opens in a new tab)</span></a></p><p><a class="link-arrow" href="' . $pdf . '" download="Wakefield-at-Home-Fall-2026.pdf">Download the PDF ' . oberdorfer_icon( 'download' ) . '</a></p></div></div>' );
echo oberdorfer_section( oberdorfer_group( $copy . $card, 'container split ot-align-center' ), 'section surface-muted', 'archive' ); // phpcs:ignore WordPress.Security.EscapeOutput
