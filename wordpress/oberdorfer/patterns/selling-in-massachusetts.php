<?php
/**
 * Title: Selling in Massachusetts
 * Slug: oberdorfer/selling-in-massachusetts
 * Categories: oberdorfer
 * Inserter: yes
 * Description: Sandstone section: what sellers need before closing (smoke and CO certificate, Title 5, lead paint notification, deed excise) in a spec table, with sources.
 *
 * The table is an HTML fragment: the design system styles table.spec-table with row headers,
 * and core/table puts its class on a wrapping figure.
 */
$copy = oberdorfer_group(
	oberdorfer_p( 'Selling in Massachusetts', 'label' )
	. oberdorfer_h( oberdorfer_lines( array( 'What sellers here', 'need before closing.' ) ), 2, 'display-m answer', 'ma-sell-title' )
	. oberdorfer_p( 'Massachusetts sellers usually need a smoke and carbon monoxide certificate from the local fire department, a Title 5 report if the home has a septic system, and the state lead paint notification if the home was built before 1978. The deed excise is paid at closing.' ),
	'stack ot-col-1-5'
);
$table = oberdorfer_html( '<table class="spec-table"><tbody>
    <tr><th scope="row">Smoke and CO certificate</th><td>The local fire department inspects the smoke and carbon monoxide alarms and issues a Certificate of Compliance before the sale (M.G.L. c. 148, §§ 26F and 26F½). Book it early; buyers and lenders expect it at closing.</td></tr>
    <tr><th scope="row">Title 5 septic inspection</th><td>For homes on a septic system, an inspection within two years before the sale. If weather prevents it, it can take place up to six months after.</td></tr>
    <tr><th scope="row">Lead paint notification</th><td>For homes built before 1978, give buyers the Property Transfer Lead Paint Notification and any lead reports before the purchase and sale agreement is signed.</td></tr>
    <tr><th scope="row">Deed excise</th><td>Under M.G.L. c. 64D, $2 for each $500 of the price; Barnstable County has its own rate. Your closing attorney confirms the exact amount.</td></tr>
  </tbody></table>' );
$src = oberdorfer_p( 'Sources: <a href="https://www.mass.gov/doc/consumer-guide-to-smoke-detectors-when-selling-home/download">Mass.gov, Consumer guide to smoke detectors when selling a home</a>; <a href="https://www.mass.gov/guides/buying-or-selling-property-with-a-septic-system">Mass.gov, septic systems</a>; <a href="https://www.mass.gov/service-details/property-transfer-lead-paint-notification">Mass.gov, lead paint notification</a>; <a href="https://malegislature.gov/Laws/GeneralLaws/PartI/TitleIX/Chapter64D/Section1">M.G.L. c. 64D, § 1</a>. General information, not legal advice.', 'caption ot-mt-4' );
echo oberdorfer_section( oberdorfer_group( $copy . oberdorfer_group( $table . $src, 'ot-col-7-6' ), 'container split ot-align-start' ), 'section surface-muted', 'massachusetts', 'ma-sell-title' ); // phpcs:ignore WordPress.Security.EscapeOutput
