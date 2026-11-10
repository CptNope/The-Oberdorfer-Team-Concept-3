<?php
/**
 * Title: Privacy policy
 * Slug: oberdorfer/privacy-policy
 * Categories: oberdorfer
 * Inserter: no
 * Description: The privacy policy's body (the page template adds the title). Written from data/privacy-policy.json, copied from tools/data/ by tools/build_wp.py; the importer puts it in the Privacy policy page once, and after that the team edits the page itself.
 */
$file = get_theme_file_path( 'data/privacy-policy.json' );
$pol  = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions
if ( ! $pol ) {
	return;
}
$fill = fn( $t ) => strtr( $t, array( '{phone}' => OBERDORFER_PHONE, '{address}' => oberdorfer_address(), '{contact}' => oberdorfer_url( '/contact/' ) ) );
$out  = oberdorfer_p( 'Last updated ' . esc_html( $pol['updated'] ), 'caption' ) . oberdorfer_p( $fill( $pol['intro'] ) );
foreach ( $pol['sections'] as $sec ) {
	$out .= oberdorfer_h( esc_html( $sec['h'] ), 2 );
	foreach ( $sec['blocks'] as $b ) {
		if ( isset( $b['p'] ) ) {
			$out .= oberdorfer_p( $fill( $b['p'] ) );
		} else {
			$items = '';
			foreach ( $b['ul'] as $li ) {
				$items .= "<!-- wp:list-item -->\n<li>" . $fill( $li ) . "</li>\n<!-- /wp:list-item -->\n";
			}
			$out .= "<!-- wp:list -->\n<ul class=\"wp-block-list\">" . $items . "</ul>\n<!-- /wp:list -->\n\n";
		}
	}
}
echo $out; // phpcs:ignore WordPress.Security.EscapeOutput
