<?php
/**
 * Title: Market teaser
 * Slug: oberdorfer/market-teaser
 * Categories: oberdorfer
 * Inserter: yes
 * Description: "Know the market. Love the home." with the 2025 statewide medians and the Worcester and Wakefield town medians (ot/town-stats), each with source, period and geography.
 *
 * The stat tiles are HTML fragments (value, label and period spans); the town tiles come from
 * the researched records through ot/town-stats.
 */
$copy  = oberdorfer_p( 'Market insight · Massachusetts, 2025', 'label' )
	. oberdorfer_h( oberdorfer_lines( array( 'Know the market.', 'Love the home.' ) ), 2, 'display-m answer', 'mkt-title' )
	. oberdorfer_p( 'The Massachusetts single-family median sale price was $638,000 in 2025. Town by town it ranges widely, which is why every plan starts with comparable closed sales.' )
	. oberdorfer_link_arrow( 'Compare 2025 prices by town', oberdorfer_url( '/market/' ) );
$stats = oberdorfer_group(
	oberdorfer_html( '<div class="stat"><span class="stat__value tabular">$638,000</span><span class="stat__label">Massachusetts single-family median</span><span class="stat__period">2025, up 3.7% from 2024</span></div>
<div class="stat"><span class="stat__value tabular">$537,000</span><span class="stat__label">Massachusetts condominium median</span><span class="stat__period">2025</span></div>' )
	. "<!-- wp:ot/town-stats {\"slugs\":[\"worcester\",\"wakefield\"]} /-->\n\n",
	'stat-panel'
);
$right = $stats . oberdorfer_p( 'Source: The Warren Group, MA December 2025 Sales (statewide) and Prices by Town (single-family, calendar year 2025). Not live data and not a valuation of any home.', 'stat-source' );
echo oberdorfer_section( oberdorfer_group( oberdorfer_group( $copy, 'stack ot-col-1-5' ) . oberdorfer_group( $right, 'ot-col-7-6' ), 'container split ot-align-center' ), 'section', '', 'mkt-title' ); // phpcs:ignore WordPress.Security.EscapeOutput
