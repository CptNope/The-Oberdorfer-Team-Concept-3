<?php
/**
 * Title: Statewide market stats
 * Slug: oberdorfer/market-stats
 * Categories: oberdorfer
 * Inserter: yes
 * Description: Calendar year 2025 statewide medians (single-family and condominium) with source, period and geography, beside "What these numbers can tell you".
 *
 * The stat tiles are an HTML fragment (value, label and period spans).
 */
$left = oberdorfer_p( 'Calendar year 2025 · Massachusetts statewide', 'label ot-mb-3' )
	. oberdorfer_html( '<div class="stat-panel">
      <div class="stat"><span class="stat__value tabular">$638,000</span><span class="stat__label">Single-family median sale price</span><span class="stat__period">2025, up 3.7% from $615,000 in 2024</span></div>
      <div class="stat"><span class="stat__value tabular">$537,000</span><span class="stat__label">Condominium median sale price</span><span class="stat__period">2025, $535,000 in 2024</span></div>
    </div>' )
	. oberdorfer_p( 'Source: <a href="https://www.thewarrengroup.com/wp-content/uploads/2026/01/MA-December-2025-Sales.pdf">The Warren Group, MA Median Home Sale Price Reaches $638K in 2025</a>, January 20, 2026. Geography: Massachusetts statewide. Not live data.', 'stat-source' );
$right = oberdorfer_h( 'What these numbers can tell you', 2, 'h3' )
	. oberdorfer_p( 'A statewide or townwide median is a starting point. Your best comparisons are recent closed sales that resemble your property in location, size, condition, and style. A condo, an older single-family home, and a newly built residence can tell very different stories.', 'body-s' );
echo oberdorfer_section( oberdorfer_group( oberdorfer_group( $left, 'ot-col-1-7' ) . oberdorfer_group( $right, 'stack ot-col-9-4' ), 'container split ot-align-start' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
