<?php
/**
 * Community guides: one function per section, ported from page_town() and page_neighborhood()
 * in tools/build_site.py. A town is a top-level ot_community; a neighborhood is its child and
 * reads the city's market and tax figures.
 */

defined( 'ABSPATH' ) || exit;

/** Context for the community being rendered: record, URL, city (for a neighborhood), children. */
function ot_cx( $post_id ) {
	$c = ot_community( $post_id );
	if ( ! $c ) {
		return null;
	}
	$all  = ot_communities();
	$city = $c['parent'] && isset( $all[ $c['parent'] ] ) ? $all[ $c['parent'] ] : null;
	return array(
		'c'        => $c,
		'r'        => $c['rec'],
		'name'     => $c['rec']['name'],
		'slug'     => $c['slug'],
		'url'      => $c['url'],
		'is_nbhd'  => (bool) $city,
		'city'     => $city,
		'children' => array_map( fn( $id ) => $all[ $id ], $c['children'] ),
	);
}

function ot_cx_trail( $x ) {
	$trail = array(
		array( 'Home', home_url( '/' ) ),
		array( 'Communities', get_post_type_archive_link( 'ot_community' ) ),
	);
	if ( $x['is_nbhd'] ) {
		$trail[] = array( esc_html( $x['city']['rec']['name'] ), $x['city']['url'] );
	}
	$trail[] = array( esc_html( $x['name'] ), $x['url'] );
	return $trail;
}

function ot_tax_fact( $r ) {
	$t = $r['tax'] ?? array();
	if ( empty( $t['residential_rate'] ) ) {
		return null;
	}
	$s = sprintf( '$%.2f per $1,000 (%s residential)', $t['residential_rate'], $t['fy'] ?? '' );
	$s = str_replace( '( ', '(', $s );
	if ( ! empty( $t['residential_exemption'] ) ) {
		$s .= '; residential exemption available';
	}
	if ( str_contains( strtolower( (string) ( $t['source'] ?? '' ) ), 'dls' ) ) {
		$s .= ' <span class="badge badge--source">Mass. DLS</span>';
	}
	return $s;
}

/** Rail line list ("Line (Station, Station); ...") or the given fallback. */
function ot_rail_txt( $rail, $fallback ) {
	if ( ! $rail ) {
		return $fallback;
	}
	return implode( '; ', array_map( fn( $l ) => esc_html( $l['line'] ) . ' (' . esc_html( implode( ', ', $l['stations'] ) ) . ')', $rail ) );
}

function ot_cols( $items ) {
	$out = '';
	foreach ( $items as $v ) {
		$out .= '<div><h3 class="h4">' . ot_txt( $v['name'] ) . '</h3><p class="body-s" style="margin-top:var(--space-2)">' . ot_txt( $v['line'] ) . '</p></div>';
	}
	return $out;
}

function ot_updated_time() {
	return '<time datetime="' . OT_UPDATED . '">' . OT_UPDATED_LABEL . '</time>';
}

/* --------------------------------------------------------------------- hero */

function ot_render_community_hero( $x ) {
	$r     = $x['r'];
	$h1    = $x['is_nbhd'] ? 'Living in ' . $x['name'] . ', ' . $x['city']['rec']['name'] . ', MA' : 'Living in ' . $x['name'] . ', MA';
	$lines = (array) ( $r['headline'] ?? array() );
	$photo = $x['is_nbhd'] ? null : ( $r['photo'] ?? null );
	return ot_page_hero( ot_cx_trail( $x ), $lines, $h1, $r['answer'] ?? '', null, $photo, $r['photo_alt'] ?? '', $r['focal'] ?? '50% 50%' );
}

/** The prototype's page_hero(): h1 as kicker, brand line as display text, lead, optional photo. */
function ot_page_hero( $trail, $lines, $h1 = null, $lead = null, $aside = null, $photo = null, $photo_alt = '', $focal = '50% 50%', $navy = false ) {
	$spans = '';
	foreach ( $lines as $l ) {
		$spans .= '<span>' . ot_txt( $l ) . '</span>';
	}
	$cls = 'page-hero';
	$img = '';
	if ( $navy || $photo ) {
		$cls .= ' page-hero--navy surface-inverse';
	}
	if ( $photo ) {
		$cls .= ' page-hero--photo';
		$img  = '<img src="' . esc_url( ot_asset( $photo ) ) . '" alt="' . esc_attr( $photo_alt ) . '" style="object-position:' . esc_attr( $focal ) . '" fetchpriority="high">';
	}
	$out = '<section class="' . $cls . '" aria-labelledby="page-title">' . $img . '<div class="container page-hero__grid">';
	$out .= ot_breadcrumbs_html( $trail );
	if ( $h1 ) {
		$out .= '<h1 class="label page-hero__kicker" id="page-title">' . esc_html( $h1 ) . '</h1>';
		$out .= '<p class="page-hero__title display-l answer">' . $spans . '</p>';
	} else {
		$out .= '<h1 class="page-hero__title display-l answer" id="page-title">' . $spans . '</h1>';
	}
	if ( $aside ) {
		$out .= '<div class="page-hero__aside">' . $aside . '</div>';
	}
	if ( $lead ) {
		$out .= '<p class="page-hero__lead lead">' . ot_txt( $lead ) . '</p>';
	}
	return $out . '</div></section>';
}

/* --------------------------------------------------------------------- about */

function ot_render_community_about( $x ) {
	$r    = $x['r'];
	$name = esc_html( $x['name'] );
	$c    = $r['commute'] ?? array();
	$sch  = $r['schools'] ?? ( $x['is_nbhd'] ? ( $x['city']['rec']['schools'] ?? array() ) : array() );
	$rail = $c['rail'] ?? array();
	$facts = array();

	if ( $x['is_nbhd'] ) {
		$city  = $x['city'];
		$cr    = $city['rec'];
		$cname = esc_html( $cr['name'] );
		$facts[] = array( 'City', '<a href="' . esc_url( $city['url'] ) . '">' . $cname . ', Massachusetts</a>' );
		$facts[] = array( 'County', esc_html( $cr['county'] ?? '' ) );
		if ( ! empty( $r['population_2020'] ) ) {
			$facts[] = array( 'Population', ot_num( $r['population_2020'] ) . ( ! empty( $r['population_label'] ) ? ' (' . esc_html( $r['population_label'] ) . ')' : '' ) );
		}
		$facts[] = array( 'Transit', ot_rail_txt( $rail, 'See commuting below' ) );
		if ( ! empty( $sch['district'] ) ) {
			$facts[] = array( 'Schools', '<a href="' . esc_url( $sch['dese_url'] ?? '#' ) . '">' . esc_html( $sch['district'] ) . '</a> (DESE profile)' );
		}
		$nm = $r['market'] ?? array();
		$cm = $cr['market'] ?? array();
		$src = ' <span class="badge badge--source">Warren Group</span>';
		if ( ! empty( $nm['sf_median_2025'] ) ) {
			$facts[] = array( 'Single-family median', ot_money( $nm['sf_median_2025'] ) . ' in 2025 (' . $name . ' row)' . $src );
			if ( ! empty( $nm['condo_median_2025'] ) ) {
				$facts[] = array( 'Condominium median', ot_money( $nm['condo_median_2025'] ) . ' in 2025 (' . $name . ' row)' . $src );
			}
		} elseif ( ! empty( $cm['sf_median_2025'] ) && ( $cm['citywide'] ?? true ) ) {
			$facts[] = array( $cname . ' single-family median', ot_money( $cm['sf_median_2025'] ) . ' in 2025, citywide' . $src );
		} elseif ( ! empty( $cm['sf_median_2025'] ) ) {
			$facts[] = array( $cname . ' row single-family median', ot_money( $cm['sf_median_2025'] ) . ' in 2025 (central neighborhoods)' . $src );
		}
		if ( ot_tax_fact( $cr ) ) {
			$facts[] = array( 'Property tax', ot_tax_fact( $cr ) . ' (citywide)' );
		}
	} else {
		$m = $r['market'] ?? array();
		$facts[] = array( 'County', esc_html( $r['county'] ?? '' ) );
		$facts[] = array( 'Region', esc_html( $r['region'] ?? '' ) );
		if ( ! empty( $r['population_2020'] ) ) {
			$facts[] = array( 'Population', ot_num( $r['population_2020'] ) . ' (2020 Census)' );
		}
		$facts[] = array( 'Rail', ot_rail_txt( $rail, 'No commuter rail station in town' ) );
		if ( ! empty( $c['highways'] ) ) {
			$facts[] = array( 'Highways', esc_html( implode( ', ', array_slice( $c['highways'], 0, 4 ) ) ) );
		}
		if ( ! empty( $sch['district'] ) ) {
			$facts[] = array( 'Schools', '<a href="' . esc_url( $sch['dese_url'] ?? '#' ) . '">' . esc_html( $sch['district'] ) . '</a> (DESE profile)' );
		}
		if ( ! empty( $m['sf_median_2025'] ) ) {
			$label   = ( $m['citywide'] ?? true ) ? 'Single-family median' : 'Single-family median (' . $name . ' row)';
			$facts[] = array( $label, ot_money( $m['sf_median_2025'] ) . ' in 2025 <span class="badge badge--source">Warren Group</span>' );
		}
		if ( ot_tax_fact( $r ) ) {
			$facts[] = array( 'Property tax', ot_tax_fact( $r ) );
		}
	}
	$facts[] = array( 'Guide updated', ot_updated_time() );
	$facts_html = '';
	foreach ( $facts as $f ) {
		$facts_html .= '<dt>' . $f[0] . '</dt><dd>' . $f[1] . '</dd>';
	}

	$where = '';
	if ( $x['is_nbhd'] && ! empty( $r['boundaries']['text'] ) ) {
		$where = '<h3 class="h4">Where it is</h3><p>' . ot_txt( $r['boundaries']['text'] ) . '</p>';
	}
	$overview = '';
	foreach ( (array) ( $r['overview'] ?? array() ) as $p ) {
		$overview .= '<p>' . ot_txt( $p ) . '</p>';
	}
	$housing = ! empty( $r['housing']['text'] ) ? '<h3 class="h4" style="margin-top:var(--space-6)">Homes in ' . $name . '</h3><p>' . ot_txt( $r['housing']['text'] ) . '</p>' : '';
	$credit  = ( ! $x['is_nbhd'] && ! empty( $r['photo_credit'] ) ) ? '<p class="caption caption--serif">' . ot_txt( $r['photo_credit'] ) . '</p>' : '';
	$guides  = $x['is_nbhd'] ? esc_html( $x['city']['rec']['name'] ) : $name;

	return '<section class="section" aria-labelledby="about-title"><div class="container split" style="align-items:start">
  <div class="stack prose" style="grid-column:1/span 7;--stack:var(--space-4)">' . $credit . '
    <h2 class="h2" id="about-title">About ' . $name . '</h2>' . $where . $overview . $housing . '
  </div>
  <aside class="stack" style="grid-column:9/span 4;--stack:var(--space-5)" aria-label="' . esc_attr( $x['name'] ) . ' at a glance">
    <dl class="quick-facts quick-facts--aside">' . $facts_html . '</dl>
    <div class="panel"><p class="h5">Your ' . $guides . ' guides</p><p class="body-s">Brandon &amp; Kaitlyn Oberdorfer · Based in Worcester</p><div class="cluster"><a class="btn btn--small" href="#contact-town">Ask about ' . $name . '</a><a class="link-arrow" href="tel:' . OT_TEL . '">' . OT_PHONE . '</a></div></div>
  </aside>
</div></section>';
}

/* --------------------------------------------------------------------- neighborhoods / areas */

function ot_render_community_neighborhoods( $x ) {
	$r    = $x['r'];
	$name = esc_html( $x['name'] );
	if ( $x['is_nbhd'] ) {
		if ( empty( $r['areas'] ) ) {
			return '';
		}
		return '<section class="section surface-muted" id="areas" aria-labelledby="ar-title"><div class="container">
  ' . ot_section_head( 'Within the neighborhood', $name, array( 'Corners of', $name . '.' ), null, null, 'h2', 'ar-title' ) . '
  <div class="cols cols--3 col-rule">' . ot_cols( $r['areas'] ) . '</div>
</div></section>';
	}
	$kids = $x['children'];
	if ( $kids ) {
		$have  = array_map( fn( $k ) => strtolower( $k['rec']['name'] ), $kids );
		$items = '';
		foreach ( $kids as $k ) {
			$kn     = esc_html( $k['rec']['name'] );
			$items .= '<div><h3 class="h4"><a href="' . esc_url( $k['url'] ) . '">' . $kn . '</a></h3><p class="body-s" style="margin-top:var(--space-2)">' . ot_txt( ot_first_sentence( $k['rec']['answer'] ?? '' ) ) . '</p><p class="body-s" style="margin-top:var(--space-2)">' . ot_link_arrow( $k['url'], 'The ' . $kn . ' guide' ) . '</p></div>';
		}
		$items .= ot_cols( array_values( array_filter( (array) ( $r['villages'] ?? array() ), fn( $v ) => ! in_array( strtolower( $v['name'] ), $have, true ) ) ) );
		return '<section class="section surface-muted" id="neighborhoods" aria-labelledby="nb-title"><div class="container">
  ' . ot_section_head( 'Neighborhood guides', count( $kids ) . ' ' . $name . ' neighborhoods', array( 'Where you might', 'live in ' . $name . '.' ), null, null, 'h2', 'nb-title' ) . '
  <div class="cols cols--3 col-rule">' . $items . '</div>
</div></section>';
	}
	if ( empty( $r['villages'] ) ) {
		return '';
	}
	return '<section class="section surface-muted" id="neighborhoods" aria-labelledby="nb-title"><div class="container">
  ' . ot_section_head( 'Neighborhoods and villages', $name, array( 'Where you might', 'live in ' . $name . '.' ), null, null, 'h2', 'nb-title' ) . '
  <div class="cols cols--3 col-rule">' . ot_cols( $r['villages'] ) . '</div>
</div></section>';
}

/* --------------------------------------------------------------------- commute and schools */

function ot_render_community_commute( $x ) {
	$r    = $x['r'];
	$name = esc_html( $x['name'] );
	$c    = $r['commute'] ?? array();
	$sch  = $r['schools'] ?? ( $x['is_nbhd'] ? ( $x['city']['rec']['schools'] ?? array() ) : array() );
	$rail = $c['rail'] ?? array();
	$rail_block = '';
	if ( $rail ) {
		$li = '';
		foreach ( $rail as $l ) {
			$li .= '<li><b>' . esc_html( $l['line'] ) . '</b>: ' . esc_html( implode( ', ', $l['stations'] ) ) . '</li>';
		}
		$rail_block = '<ul class="body-s" style="padding-left:1.1rem;display:grid;gap:var(--space-2)">' . $li . '</ul>';
	}
	$district = ! empty( $sch['district'] ) ? esc_html( $sch['district'] ) : '[district to confirm]';
	if ( $x['is_nbhd'] ) {
		$wrta = 'worcester' === $x['city']['slug'] ? ' and the <a href="https://therta.com/">WRTA</a>' : '';
		$check = 'Check current schedules with the <a href="https://www.mbta.com/">MBTA</a>' . $wrta . '; compare the trip from each address before you buy.';
		$school_p = $name . ' is served by ' . $district . '. School assignment depends on the address and the district\'s own rules; confirm it with the district, and review its profile with the Massachusetts Department of Elementary and Secondary Education.';
		$links = ot_link_arrow( $sch['dese_url'] ?? '#', 'DESE district profile' );
	} else {
		$check = 'Check current schedules with the <a href="https://www.mbta.com/schedules/commuter-rail">MBTA</a>; compare the trip from each address before you buy.';
		$school_p = $name . ' is served by ' . $district . '. Review the district\'s profile with the Massachusetts Department of Elementary and Secondary Education, and confirm school assignment for any address with the district.';
		$links = ot_link_arrow( $sch['dese_url'] ?? '#', 'DESE district profile' ) . ( ! empty( $sch['dese_url_regional'] ) ? ot_link_arrow( $sch['dese_url_regional'], 'DESE regional district profile' ) : '' );
	}
	return '<section class="section" id="commute" aria-labelledby="commute-title"><div class="container split" style="align-items:start">
  <div class="stack" style="grid-column:1/span 6;--stack:var(--space-4)"><p class="label">Getting around</p><h2 class="h2" id="commute-title">Commuting from ' . $name . '</h2><p>' . ot_txt( $c['text'] ?? '' ) . '</p>' . $rail_block . '<p class="caption">' . $check . '</p></div>
  <div class="stack" style="grid-column:8/span 5;--stack:var(--space-4)"><p class="label">Schools</p><h2 class="h2">Public schools</h2><p>' . $school_p . '</p>' . $links . '</div>
</div></section>';
}

/* --------------------------------------------------------------------- places and history */

function ot_render_community_places( $x ) {
	$r    = $x['r'];
	$name = esc_html( $x['name'] );
	$hist = '';
	if ( ! empty( $r['history'] ) ) {
		$hist = '<div class="history">';
		foreach ( array_slice( $r['history'], 0, $x['is_nbhd'] ? 4 : 3 ) as $h ) {
			$hist .= '<div><b>' . esc_html( $h['year'] ) . '</b><p class="body-s">' . ot_txt( $h['text'] ) . '</p></div>';
		}
		$hist .= '</div>';
		$hist = '<h2 class="h3" style="margin-top:var(--space-8);margin-bottom:var(--space-5)">History</h2>' . $hist;
	}
	return '<section class="section surface-muted" id="places" aria-labelledby="places-title"><div class="container">
  ' . ot_section_head( 'Things to do', $name, array( 'Places to know', 'in ' . $name . '.' ), null, null, 'h2', 'places-title' ) . '
  <div class="cols cols--3 col-rule">' . ot_cols( (array) ( $r['places'] ?? array() ) ) . '</div>
  ' . $hist . '
</div></section>';
}

/* --------------------------------------------------------------------- market */

function ot_market_row( $label, $a, $b, $money = true ) {
	$f = $money ? 'ot_money' : 'ot_num';
	return '<tr><th scope="row">' . $label . '</th><td class="tabular">' . ( $a ? $f( $a ) : 'n/a' ) . '</td><td class="tabular">' . $f( $b ) . '</td><td class="tabular">' . ot_fmt_pct( ot_pct( $a, $b ) ) . '</td></tr>';
}

function ot_render_community_market( $x ) {
	$r    = $x['r'];
	$name = esc_html( $x['name'] );
	$warren_link = '<a href="' . OT_WARREN_URL . '">The Warren Group, MA December 2025 Sales, Prices by Town</a>';
	$thead = '<thead><tr><th scope="col">Calendar year</th><th scope="col">2024</th><th scope="col">2025</th><th scope="col">Change</th></tr></thead>';

	if ( $x['is_nbhd'] ) {
		$nm    = $r['market'] ?? array();
		$city  = $x['city'];
		$cm    = $city['rec']['market'] ?? array();
		$cname = esc_html( $city['rec']['name'] );
		if ( ! empty( $nm['sf_median_2025'] ) ) {
			$rows = ot_market_row( 'Single-family median sale price', $nm['sf_median_2024'] ?? null, $nm['sf_median_2025'] );
			if ( ! empty( $nm['condo_median_2025'] ) ) {
				$rows .= ot_market_row( 'Condominium median sale price', $nm['condo_median_2024'] ?? null, $nm['condo_median_2025'] );
			}
			$market = '<table class="spec-table market-table">' . $thead . '<tbody>' . $rows . '</tbody></table>
<p class="stat-source">Source: ' . $warren_link . '. Geography: ' . $name . ', as the report defines it. Calendar years 2024 and 2025. Not live data and not a valuation of any home.</p>';
		} else {
			if ( ! empty( $cm['sf_median_2025'] ) && ! ( $cm['citywide'] ?? true ) ) {
				$lead_line = 'The Warren Group does not report ' . $name . ' on its own row; it falls within ' . ot_txt( $cm['geography'] ?? 'the city row' ) . '.';
				$city_line = 'That row\'s single-family median was ' . ot_money( $cm['sf_median_2025'] ) . ' in 2025, across ' . ot_num( $cm['sf_sales_2025'] ?? 0 ) . ' sales' . ( ! empty( $cm['condo_median_2025'] ) ? ', and its condominium median was ' . ot_money( $cm['condo_median_2025'] ) : '' );
			} else {
				$lead_line = 'Sale prices are reported for the city of ' . $cname . ' as a whole, not by neighborhood.';
				$city_line = ! empty( $cm['sf_median_2025'] ) ? $cname . '\'s single-family median was ' . ot_money( $cm['sf_median_2025'] ) . ' in 2025' : $cname . '\'s figures are in the city guide';
			}
			$market = '<p>' . $lead_line . ' ' . $city_line . ', according to <a href="' . OT_WARREN_URL . '">The Warren Group</a>. Prices within ' . $name . ' depend on the street, the building and the type of home, so the best guide is recent closed sales nearby.</p>' . ot_link_arrow( $city['url'] . '#market', $cname . ' prices in the city guide' );
		}
		return '<section class="section" id="market" aria-labelledby="market-title"><div class="container split" style="align-items:start">
  <div class="stack" style="grid-column:1/span 7;--stack:var(--space-4)"><p class="label">Home prices</p><h2 class="h2" id="market-title">Buying in ' . $name . '</h2>' . $market . '</div>
  <div class="stack" style="grid-column:9/span 4;--stack:var(--space-4)"><h3 class="h4">Read it for your own home</h3><p class="body-s">A citywide median is a starting point. Your best comparisons are recent closed sales that resemble the home in location, size, condition and style.</p>' . ot_link_arrow( ot_page_url( 'sell', 'consult' ), 'Request a market review' ) . '</div>
</div></section>';
	}

	$m    = $r['market'] ?? array();
	$rows = '';
	if ( ! empty( $m['sf_median_2025'] ) ) {
		$rows .= ot_market_row( 'Single-family median sale price', $m['sf_median_2024'] ?? null, $m['sf_median_2025'] );
	}
	if ( ! empty( $m['sf_sales_2025'] ) ) {
		$rows .= ot_market_row( 'Single-family sales', $m['sf_sales_2024'] ?? null, $m['sf_sales_2025'], false );
	}
	if ( ! empty( $m['condo_median_2025'] ) ) {
		$rows .= ot_market_row( 'Condominium median sale price', $m['condo_median_2024'] ?? null, $m['condo_median_2025'] );
	}
	$market = '';
	$vrows  = $m['village_rows'] ?? array();
	if ( ! $rows && $vrows ) {
		usort( $vrows, fn( $a, $b ) => $a['sf_median_2025'] <=> $b['sf_median_2025'] );
		$body = '';
		foreach ( $vrows as $v ) {
			$body .= '<tr><th scope="row">' . esc_html( $v['name'] ) . '</th><td class="tabular">' . ot_num( $v['sf_sales_2025'] ) . '</td><td class="tabular">' . ot_money( $v['sf_median_2024'] ) . '</td><td class="tabular">' . ot_money( $v['sf_median_2025'] ) . '</td><td class="tabular">' . ot_fmt_pct( ot_pct( $v['sf_median_2024'], $v['sf_median_2025'] ) ) . '</td></tr>';
		}
		$market = '<div class="table-scroll" tabindex="0" role="region" aria-label="' . esc_attr( $x['name'] ) . ' single-family prices by village"><table class="spec-table market-table"><thead><tr><th scope="col">Village</th><th scope="col">Sales, 2025</th><th scope="col">Median, 2024</th><th scope="col">Median, 2025</th><th scope="col">Change</th></tr></thead><tbody>' . $body . '</tbody></table></div>
<p class="stat-source">Source: ' . $warren_link . ', single-family. The report lists ' . $name . ' by village, with no town-wide figure, so none is given here. Calendar years 2024 and 2025. Not live data and not a valuation of any home.</p>';
	}
	if ( $rows ) {
		$geo    = ! empty( $m['geography'] ) ? ot_txt( $m['geography'] ) : 'town of ' . $name;
		$market = '<table class="spec-table market-table">' . $thead . '<tbody>' . $rows . '</tbody></table>
<p class="stat-source">Source: ' . $warren_link . '. Geography: ' . $geo . '. Year-to-date figures through December, so calendar years 2024 and 2025. Not live data and not a valuation of any home.</p>';
	} elseif ( ! $market ) {
		$market = '<p class="placeholder-block">Town-level sale prices to be added from a dated, sourced report.</p>';
	}
	return '<section class="section" id="market" aria-labelledby="market-title"><div class="container split" style="align-items:start">
  <div class="stack" style="grid-column:1/span 7;--stack:var(--space-4)"><p class="label">Home prices</p><h2 class="h2" id="market-title">The ' . $name . ' housing market</h2>' . $market . '</div>
  <div class="stack" style="grid-column:9/span 4;--stack:var(--space-4)"><h3 class="h4">Read it for your own home</h3><p class="body-s">A townwide median is a starting point. Your best comparisons are recent closed sales that resemble your home in location, size, condition and style.</p>' . ot_link_arrow( ot_page_url( 'sell', 'consult' ), 'Request a market review' ) . ot_link_arrow( ot_page_url( 'market' ), 'Compare all towns' ) . '</div>
</div></section>';
}

/* --------------------------------------------------------------------- questions and nearby */

function ot_faq_html( $faq ) {
	$out = '';
	foreach ( array_values( (array) $faq ) as $i => $f ) {
		$out .= '<details' . ( 0 === $i ? ' open' : '' ) . '><summary>' . ot_txt( $f['q'] ) . '</summary><div class="accordion__body body-s"><p>' . ot_txt( $f['a'] ) . '</p></div></details>';
	}
	return $out;
}

function ot_toc_links( $items ) {
	$li = '';
	foreach ( $items as $it ) {
		$li .= '<li><a href="' . esc_url( $it['url'] ) . '">' . esc_html( $it['rec']['name'] ) . '</a></li>';
	}
	return '<ul class="toc-mini">' . $li . '</ul>';
}

function ot_render_community_questions( $x ) {
	$r    = $x['r'];
	$name = esc_html( $x['name'] );
	$near_html = '';
	if ( $x['is_nbhd'] ) {
		$city  = $x['city'];
		$all   = ot_communities();
		$sibs  = array();
		foreach ( $city['children'] as $id ) {
			$sibs[ $all[ $id ]['slug'] ] = $all[ $id ];
		}
		$near = array();
		foreach ( (array) ( $r['nearby'] ?? array() ) as $k ) {
			if ( isset( $sibs[ $k ] ) && $k !== $x['slug'] && count( $near ) < 4 ) {
				$near[ $k ] = $sibs[ $k ];
			}
		}
		$others = array_filter( $sibs, fn( $s ) => $s['slug'] !== $x['slug'] && ! isset( $near[ $s['slug'] ] ) );
		if ( $near ) {
			$near_html .= '<p class="label" style="margin-top:var(--space-6)">Nearby neighborhoods</p>' . ot_toc_links( $near );
		}
		if ( $others ) {
			$near_html .= '<p class="label" style="margin-top:var(--space-6)">More ' . esc_html( $city['rec']['name'] ) . ' neighborhoods</p>' . ot_toc_links( $others );
		}
		$near_html .= '<p class="body-s" style="margin-top:var(--space-3)"><a href="' . esc_url( $city['url'] ) . '">The ' . esc_html( $city['rec']['name'] ) . ' city guide</a></p>';
	} else {
		$near = array();
		foreach ( (array) ( $r['nearby'] ?? array() ) as $n ) {
			$k = str_replace( ' ', '-', strtolower( $n ) );
			$t = ot_town_by_slug( $k );
			if ( $t && $k !== $x['slug'] && count( $near ) < 4 ) {
				$near[ $k ] = $t;
			}
		}
		if ( $near ) {
			$near_html = '<p class="label" style="margin-top:var(--space-6)">Nearby guides</p>' . ot_toc_links( $near );
		}
		$rk   = $r['region_key'] ?? '';
		$more = array_slice( array_filter( ot_towns_in( $rk ), fn( $t ) => $t['slug'] !== $x['slug'] && ! isset( $near[ $t['slug'] ] ) ), 0, 6 );
		if ( $more ) {
			$region = (string) ( $r['region'] ?? '' );
			$rname  = str_starts_with( $region, 'The ' ) ? 'the ' . substr( $region, 4 ) : $region;
			$near_html .= '<p class="label" style="margin-top:var(--space-6)">More guides in ' . esc_html( $rname ) . '</p>' . ot_toc_links( $more ) . '<p class="body-s" style="margin-top:var(--space-3)"><a href="' . esc_url( get_post_type_archive_link( 'ot_community' ) . '#' . $rk ) . '">Every guide in ' . esc_html( $rname ) . '</a></p>';
		}
	}
	return '<section class="section surface-muted" id="questions" aria-labelledby="faq-title"><div class="container split" style="align-items:start">
  <div class="stack" style="grid-column:1/span 5"><p class="label">Questions</p><h2 class="display-m answer" id="faq-title"><span>Asked about</span><span>' . $name . '.</span></h2>' . $near_html . '</div>
  <div class="accordion" style="grid-column:7/span 6">' . ot_faq_html( $r['faq'] ?? array() ) . '</div>
</div></section>';
}

/* --------------------------------------------------------------------- contact */

function ot_render_community_contact( $x ) {
	$name = esc_html( $x['name'] );
	if ( $x['is_nbhd'] ) {
		$cname  = esc_html( $x['city']['rec']['name'] );
		$p      = 'Buying in ' . $name . ', selling here, or comparing it with other parts of ' . $cname . ': tell us what you are planning and we will reply by your preferred method.';
		$topics = array( 'Buying in ' . $x['name'], 'Selling in ' . $x['name'], 'Both', 'Comparing ' . $x['city']['rec']['name'] . ' neighborhoods' );
		$ctx    = 'community:' . $x['city']['slug'] . '/' . $x['slug'];
	} else {
		$p      = 'Buying in ' . $name . ', selling here, or comparing it with nearby towns: tell us what you are planning and we will reply by your preferred method.';
		$topics = array( 'Buying in ' . $x['name'], 'Selling in ' . $x['name'], 'Both', 'Comparing towns' );
		$ctx    = 'community:' . $x['slug'];
	}
	$form = ot_inquiry_form( 'town', 'Ask about ' . $x['name'], $topics, 'Send my question', $ctx, 'h3' );
	return '<section class="section" id="contact-town" aria-labelledby="ct-title"><div class="container split" style="align-items:start">
  <div class="stack" style="grid-column:1/span 5;--stack:var(--space-5)"><p class="label">Thinking about ' . $name . '?</p><h2 class="display-m answer" id="ct-title"><span>Let\'s talk about</span><span>your move.</span></h2><p>' . $p . '</p><a class="cta-panel__phone" href="tel:' . OT_TEL . '" style="color:var(--ot-navy);font-size:var(--step-h2)">' . OT_PHONE . '</a></div>
  <div class="panel" style="grid-column:7/span 6">' . $form . '</div>
</div></section>';
}

/* --------------------------------------------------------------------- sources */

function ot_render_community_sources( $x ) {
	$r    = $x['r'];
	$srcs = array();
	$add  = function ( $label, $u ) use ( &$srcs ) {
		if ( $u && ! in_array( $u, array_column( $srcs, 1 ), true ) ) {
			$srcs[] = array( $label, $u );
		}
	};
	$c   = $r['commute'] ?? array();
	if ( $x['is_nbhd'] ) {
		$cr  = $x['city']['rec'];
		$sch = $r['schools'] ?? ( $cr['schools'] ?? array() );
		$add( 'City of ' . $cr['name'], $cr['official_site'] ?? null );
		$add( 'Population', $r['population_source'] ?? null );
		$add( 'Boundaries', $r['boundaries']['source'] ?? null );
		$add( 'Transit', $c['rail_source'] ?? null );
		$add( 'Massachusetts DESE district profile', $sch['dese_url'] ?? null );
		$add( 'The Warren Group, 2025 sales by town', OT_WARREN_URL );
		$add( $cr['name'] . ' property tax rates', $cr['tax']['source'] ?? null );
		$add( 'Housing', $r['housing']['source'] ?? null );
		foreach ( (array) ( $r['areas'] ?? array() ) as $a ) {
			$add( $a['name'], $a['source'] ?? null );
		}
	} else {
		$sch  = $r['schools'] ?? array();
		$site = (string) ( $r['official_site'] ?? '' );
		$add( ( str_contains( $site, 'city' ) ? 'City of ' : 'Town of ' ) . $r['name'], $site ?: null );
		$add( '2020 Census population', $r['population_source'] ?? null );
		$add( 'MBTA commuter rail', $c['rail_source'] ?? null );
		$add( 'Massachusetts DESE district profile', $sch['dese_url'] ?? null );
		$add( 'Massachusetts DESE regional district profile', $sch['dese_url_regional'] ?? null );
		$add( 'The Warren Group, 2025 sales by town', OT_WARREN_URL );
		$add( 'Housing', $r['housing']['source'] ?? null );
		if ( ! empty( $r['tax'] ) ) {
			$add( trim( 'Property tax rates, ' . ( $r['tax']['fy'] ?? '' ), ', ' ), $r['tax']['source'] ?? null );
		}
		foreach ( (array) ( $r['villages'] ?? array() ) as $v ) {
			$add( $v['name'], $v['source'] ?? null );
		}
	}
	foreach ( (array) ( $r['places'] ?? array() ) as $p ) {
		$add( $p['name'], $p['source'] ?? null );
	}
	foreach ( (array) ( $r['history'] ?? array() ) as $h ) {
		$add( 'History (' . $h['year'] . ')', $h['source'] ?? null );
	}
	$li = '';
	foreach ( $srcs as $s ) {
		$li .= '<li><a href="' . esc_url( $s[1] ) . '">' . esc_html( wp_strip_all_tags( $s[0] ) ) . '</a></li>';
	}
	return '<section class="section--tight" aria-labelledby="src-title"><div class="container"><h2 class="label" id="src-title">Sources for this guide</h2><ul class="source-list caption">' . $li . '</ul><p class="caption" style="margin-top:var(--space-3)">Researched by The Oberdorfer Team. Updated ' . ot_updated_time() . '. Places and facts can change; check with the source before you rely on them.</p></div></section>';
}

function ot_render_community_section( $section, $post_id ) {
	$x = ot_cx( $post_id );
	if ( ! $x ) {
		return '';
	}
	return match ( $section ) {
		'hero'          => ot_render_community_hero( $x ),
		'about'         => ot_render_community_about( $x ),
		'neighborhoods' => ot_render_community_neighborhoods( $x ),
		'commute'       => ot_render_community_commute( $x ),
		'places'        => ot_render_community_places( $x ),
		'market'        => ot_render_community_market( $x ),
		'questions'     => ot_render_community_questions( $x ),
		'contact'       => ot_render_community_contact( $x ),
		'sources'       => ot_render_community_sources( $x ),
		default         => '',
	};
}

/* --------------------------------------------------------------------- archive, cards, market, stats */

function ot_town_art( $rec, $i = 0 ) {
	return $rec['art'] ?? OT_ART_CYCLE[ $i % count( OT_ART_CYCLE ) ];
}

function ot_community_card( $c, $i = 0 ) {
	$r   = $c['rec'];
	$m   = $r['market'] ?? array();
	$med = ( ! empty( $m['sf_median_2025'] ) && ( $m['citywide'] ?? true ) ) ? ' · 2025 median ' . ot_money( $m['sf_median_2025'] ) : '';
	if ( ! empty( $r['photo'] ) ) {
		$media = '<img src="' . esc_url( ot_asset( $r['photo'] ) ) . '" alt="" loading="lazy" style="object-position:' . esc_attr( $r['card_focal'] ?? '30% 50%' ) . '">';
	} else {
		$media = '<div class="demo-illu" style="position:absolute;inset:0">' . ot_art( ot_town_art( $r, $i ) ) . '</div>';
	}
	return '<a class="community-card" href="' . esc_url( $c['url'] ) . '">' . $media . '<div class="community-card__body"><span class="community-card__meta">' . esc_html( $r['county'] ?? '' ) . $med . '</span><span class="community-card__name">' . esc_html( $r['name'] ) . '</span></div></a>';
}

function ot_render_community_cards( $slugs ) {
	$out = '';
	foreach ( array_values( (array) $slugs ) as $i => $s ) {
		$t = ot_town_by_slug( $s );
		if ( $t ) {
			$out .= ot_community_card( $t, $i );
		}
	}
	return $out;
}

/** The /communities/ archive body below its hero: region index, region grids, neighborhoods, closing note. */
function ot_render_community_index() {
	$regions_html = '';
	$toc          = '';
	foreach ( ot_regions() as $i => $reg ) {
		$ts = ot_towns_in( $reg['key'] );
		if ( ! $ts ) {
			continue;
		}
		$cards = '';
		foreach ( $ts as $j => $t ) {
			$cards .= ot_community_card( $t, $j );
		}
		$n     = count( $ts );
		$muted = $i % 2 ? ' surface-muted' : '';
		$key   = esc_attr( $reg['key'] );
		$regions_html .= '<section class="section' . $muted . '" id="' . $key . '" aria-labelledby="' . $key . '-title"><div class="container">
  ' . ot_section_head( esc_html( $reg['name'] ), $n . ' guide' . ( 1 === $n ? '' : 's' ), array( esc_html( $reg['name'] ) . '.' ), ot_txt( $reg['blurb'] ), null, 'h2', $reg['key'] . '-title' ) . '
  <div class="card-grid-3" style="gap:var(--gutter)">' . $cards . '</div>
</div></section>';
		$toc .= '<li><a href="#' . $key . '">' . esc_html( $reg['name'] ) . '</a></li>';
	}
	$cities = array_filter( ot_towns(), fn( $t ) => ! empty( $t['children'] ) );
	if ( $cities ) {
		$all   = ot_communities();
		$cols  = '';
		$count = 0;
		foreach ( $cities as $t ) {
			$kids   = array_map( fn( $id ) => $all[ $id ], $t['children'] );
			$count += count( $kids );
			$cols  .= '<div class="stack" style="--stack:var(--space-3)"><h3 class="h4"><a href="' . esc_url( $t['url'] ) . '">' . esc_html( $t['rec']['name'] ) . '</a></h3>' . ot_toc_links( $kids ) . '</div>';
		}
		$regions_html .= '<section class="section" id="neighborhoods" aria-labelledby="nbh-title"><div class="container">
  ' . ot_section_head( 'Neighborhood guides', $count . ' neighborhoods', array( 'Street by street', 'in the cities.' ), 'Closer looks at the neighborhoods of Worcester, home base for the team, and Boston, each with its own streets, homes, transit and history.', null, 'h2', 'nbh-title' ) . '
  <div class="cols cols--' . max( 2, count( $cities ) ) . ' col-rule">' . $cols . '</div>
</div></section>';
		$toc .= '<li><a href="#neighborhoods">Neighborhoods</a></li>';
	}
	return '<section class="section--tight"><div class="container"><nav aria-label="Regions"><ul class="toc-mini" style="display:flex;flex-wrap:wrap;gap:var(--space-3) var(--space-6)">' . $toc . '</ul></nav></div></section>
' . $regions_html . '
<section class="section"><div class="container split" style="align-items:start">
  <div class="stack" style="grid-column:1/span 6"><p class="label">Elsewhere in Massachusetts</p><h2 class="h2">Not seeing your town?</h2><p>The team helps clients across the state. Guides are added one at a time as the team works in a town, each researched and sourced, never generated in bulk.</p>' . ot_link_arrow( ot_page_url( 'contact' ), 'Ask about your town' ) . '</div>
  <div class="stack" style="grid-column:8/span 5"><p class="caption">Line drawings stand in for community photography still to be commissioned. Wakefield photo: Lake Quannapowitt Panorama, RedSoxFan274 / Wikimedia Commons, CC BY-SA 4.0, cropped.</p><p class="caption">Home prices: ' . OT_WARREN . '. <a href="' . esc_url( ot_page_url( 'market' ) ) . '">All towns compared</a>.</p></div>
</div></section>';
}

/** The archive hero (its lead counts the guides). */
function ot_render_community_archive_hero() {
	$n = count( ot_towns() );
	return ot_page_hero(
		array( array( 'Home', home_url( '/' ) ), array( 'Communities', get_post_type_archive_link( 'ot_community' ) ) ),
		array( 'Close to home,', 'wherever home is.' ),
		'Massachusetts community guides',
		'The Oberdorfer Team works from Worcester and helps buyers and sellers across Massachusetts. These ' . $n . ' guides cover housing, neighborhoods, commuting, schools and 2025 home prices, each with its sources.',
		'<span class="badge badge--proposed">Proposed list · team to confirm</span>'
	);
}

function ot_render_town_stats( $slugs ) {
	$out = '';
	foreach ( (array) $slugs as $k ) {
		$t = ot_town_by_slug( $k );
		$m = $t ? ( $t['rec']['market'] ?? array() ) : array();
		if ( ! empty( $m['sf_median_2025'] ) ) {
			$n    = esc_html( $t['rec']['name'] );
			$out .= '<div class="stat"><span class="stat__value tabular">' . ot_money( $m['sf_median_2025'] ) . '</span><span class="stat__label">' . $n . ' single-family median</span><span class="stat__period">2025 · <a href="' . esc_url( $t['url'] ) . '">' . $n . ' guide</a></span></div>';
		}
	}
	return $out;
}
