<?php
/**
 * Title: Market spotlight: Wakefield
 * Slug: oberdorfer/market-spotlight
 * Categories: oberdorfer
 * Inserter: yes
 * Description: The Wakefield figures from Wakefield at Home (Redfin, accessed October 10, 2026; data through August 2026; all home types), with the note on comparing sources and a link to the Wakefield guide.
 *
 * The stat tiles are an HTML fragment. These figures are a dated snapshot for Wakefield only,
 * never live or statewide (CLAUDE.md, ground rule 4).
 */
$left = oberdorfer_p( 'Spotlight · Wakefield · Data through August 2026 · All home types', 'label ot-mb-3' )
	. oberdorfer_h( 'Wakefield, from <em>Wakefield at Home</em>', 2, 'h3 ot-mb-4', 'wk-title' )
	. oberdorfer_html( '<div class="stat-panel">
      <div class="stat"><span class="stat__value tabular">$786,979</span><span class="stat__label">Median sale price</span><span class="stat__period">3 months ending August 2026</span></div>
      <div class="stat"><span class="stat__value tabular">21</span><span class="stat__label">Median days on market</span><span class="stat__period">August 2026</span></div>
      <div class="stat"><span class="stat__value tabular">$483</span><span class="stat__label">Median sold price / sq. ft.</span><span class="stat__period">Redfin rolling 3-month snapshot</span></div>
      <div class="stat"><span class="stat__value tabular">103.3%</span><span class="stat__label">Sale-to-list price</span><span class="stat__period">August 2026</span></div>
    </div>' )
	. oberdorfer_p( 'Source: Redfin, Wakefield Housing Market, accessed October 10, 2026. Geography: Wakefield town; all home types. Figures may be revised; this is not a valuation of an individual home.', 'stat-source' );
$right = oberdorfer_p( 'Different sources measure different things: the Warren Group table counts single-family sales for a full calendar year, while this Redfin snapshot covers all home types over recent months. Compare like with like.', 'body-s' )
	. oberdorfer_link_arrow( 'The Wakefield guide', oberdorfer_url( '/communities/wakefield/' ) );
echo oberdorfer_section( oberdorfer_group( oberdorfer_group( $left, 'ot-col-1-7' ) . oberdorfer_group( $right, 'stack ot-col-9-4' ), 'container split ot-align-start' ), 'section', 'wakefield', 'wk-title' ); // phpcs:ignore WordPress.Security.EscapeOutput
