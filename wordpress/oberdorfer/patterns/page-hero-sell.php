<?php
/**
 * Title: Page hero: Sell (navy)
 * Slug: oberdorfer/page-hero-sell
 * Categories: oberdorfer
 * Inserter: yes
 * Description: Navy hero with breadcrumbs, the h1 "Sell your home in Massachusetts", the brand line "Make your home feel exceptional.", the gold "Request a market review" button and the lead.
 */
$aside = oberdorfer_buttons( oberdorfer_button( 'Request a market review', '#consult', 'btn btn--gold' ), 'page-hero__aside' );
echo oberdorfer_page_hero( array( 'Make your home', 'feel exceptional.' ), 'Sell your home in Massachusetts', 'Thoughtful presentation. Clear pricing. A plan that fits your life.', $aside, true ); // phpcs:ignore WordPress.Security.EscapeOutput
