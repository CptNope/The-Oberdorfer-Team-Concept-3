<?php
/**
 * The Oberdorfer Team: coming-soon theme.
 *
 * A one-page block theme that holds oberdorferteam.com until the full site launches.
 * It is indexable on purpose: the domain starts building history in Search Console,
 * and a Google Business Profile can link to it. When the full theme ships, this theme
 * is simply switched off; it stores no content or settings of its own.
 *
 * @package oberdorfer-coming-soon
 */

defined( 'ABSPATH' ) || exit;

const OT_CS_VERSION = '1.0.6';
const OT_CS_PHONE   = '406-250-7620';
const OT_CS_HOST    = 'oberdorferteam.com';

/**
 * One domain. The team's other domains (theoberdorferteam.com, oberdorferhomes.com,
 * oberdorferluxury.com, maluxuryhomes.com, maluxury.homes) and every www name point to
 * this app, so any request for a host other than oberdorferteam.com is sent there with
 * a permanent redirect, keeping the path. Let's Encrypt renewals are not affected: nginx
 * serves /.well-known/acme-challenge/ before WordPress runs. The full build moves this
 * rule to ot-core or the server config (docs/coming-soon-launch.md, section 2a).
 */
add_action( 'init', function () {
	if ( is_admin() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return;
	}
	$host = strtolower( preg_replace( '/:\d+$/', '', (string) ( $_SERVER['HTTP_HOST'] ?? '' ) ) );
	if ( '' === $host || OT_CS_HOST === $host ) {
		return;
	}
	$path = (string) ( $_SERVER['REQUEST_URI'] ?? '/' );
	if ( str_starts_with( $path, '/wp-login.php' ) || str_starts_with( $path, '/wp-json/' ) ) {
		return; // never strand a login or an API call mid-request
	}
	wp_redirect( 'https://' . OT_CS_HOST . $path, 301, 'The Oberdorfer Team' );
	exit;
}, 1 );

/**
 * Contact cards. /?vcard=kaitlyn-oberdorfer and /?vcard=brandon-oberdorfer send the theme's
 * .vcf files (built by tools/build_coming_soon.py) as text/vcard, so a phone offers to add the
 * contact. Served through PHP because the server's static-file MIME types may not include .vcf.
 */
add_action( 'init', function () {
	if ( empty( $_GET['vcard'] ) || is_admin() ) {
		return;
	}
	$slug = sanitize_key( wp_unslash( $_GET['vcard'] ) );
	$names = array(
		'kaitlyn-oberdorfer' => 'Kaitlyn Oberdorfer',
		'brandon-oberdorfer' => 'Brandon Oberdorfer',
		'oberdorfer-team'    => 'The Oberdorfer Team',
	);
	$file = get_theme_file_path( 'assets/vcard/' . $slug . '.vcf' );
	if ( ! isset( $names[ $slug ] ) || ! is_readable( $file ) ) {
		return;
	}
	header( 'Content-Type: text/vcard; charset=utf-8' );
	header( 'Content-Disposition: inline; filename="' . $names[ $slug ] . '.vcf"' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Content-Length: ' . filesize( $file ) );
	header( 'Cache-Control: public, max-age=86400' );
	header( 'X-Robots-Tag: noindex' );
	readfile( $file );
	exit;
}, 2 );

/** Styles: the page CSS and the brand fonts (self-hosted in the full theme). */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'ot-cs-fonts',
		'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;1,300&family=Manrope:wght@400;600&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'ot-cs', get_theme_file_uri( 'assets/css/coming-soon.css' ), array(), OT_CS_VERSION );
} );

add_filter( 'wp_resource_hints', function ( $urls, $relation ) {
	if ( 'preconnect' === $relation ) {
		$urls[] = 'https://fonts.googleapis.com';
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
	}
	return $urls;
}, 10, 2 );

/** Title: "The Oberdorfer Team | Worcester & Massachusetts Real Estate" (site title | tagline). */
add_filter( 'document_title_separator', fn() => '|' );

/** Head: description, Open Graph, icons, preload and structured data. */
add_action( 'wp_head', function () {
	$desc  = 'Brandon and Kaitlyn Oberdorfer bring strategic marketing, skilled negotiation and attentive guidance to buyers and sellers across Massachusetts. Call ' . OT_CS_PHONE . '.';
	$title = wp_get_document_title();
	$home  = home_url( '/' );
	$img   = get_theme_file_uri( 'assets/img/' );

	printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
	if ( is_front_page() ) {
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $home ) );
	}
	// Link previews (iMessage, Facebook, LinkedIn, X): a 1200 x 630 image from tools/build_share_image.py.
	// Apps cache previews by image URL, so a new design gets a new file name (v2, v3 ...).
	$share = $img . 'share-oberdorfer-team-v1.jpg';
	printf( '<meta property="og:type" content="website"><meta property="og:site_name" content="The Oberdorfer Team"><meta property="og:url" content="%s"><meta property="og:title" content="%s"><meta property="og:description" content="%s">' . "\n",
		esc_url( $home ), esc_attr( $title ), esc_attr( $desc ) );
	printf( '<meta property="og:image" content="%1$s"><meta property="og:image:secure_url" content="%1$s"><meta property="og:image:type" content="image/jpeg"><meta property="og:image:width" content="1200"><meta property="og:image:height" content="630"><meta property="og:image:alt" content="%2$s">' . "\n",
		esc_url( $share ), esc_attr( 'Kaitlyn and Brandon Oberdorfer, The Oberdorfer Team: Luxury at every price point.' ) );
	printf( '<meta name="twitter:card" content="summary_large_image"><meta name="twitter:image" content="%s">' . "\n", esc_url( $share ) );
	printf( '<link rel="preload" as="image" href="%s" type="image/svg+xml">' . "\n", esc_url( $img . 'logo-primary-gold.svg' ) );
	printf( '<link rel="preload" as="image" href="%s" type="image/webp" fetchpriority="high">' . "\n", esc_url( $img . 'team-portrait.webp' ) );
	// Icons (tools/build_icons.py). A Site Icon set in Settings > General takes over if one is added.
	if ( ! has_site_icon() ) {
		printf( '<link rel="icon" href="%s" sizes="32x32">' . "\n", esc_url( home_url( '/favicon.ico' ) ) );
		printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( $img . 'favicon.svg' ) );
		printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( $img . 'apple-touch-icon.png' ) );
	}
	printf( '<link rel="manifest" href="%s">' . "\n", esc_url( $img . 'site.webmanifest' ) );
	echo '<meta name="theme-color" content="#071D2D">' . "\n";

	// Same entity as the prototype's graph (@id), with only confirmed facts (office address confirmed 2026-10-22).
	$ld = array(
		'@context'           => 'https://schema.org',
		'@type'              => 'RealEstateAgent',
		'@id'                => $home . '#team',
		'name'               => 'The Oberdorfer Team',
		'url'                => $home,
		'logo'               => $img . 'social-avatar.png',
		'image'              => $img . 'team-portrait.webp',
		'telephone'          => OT_CS_PHONE,
		'slogan'             => 'Luxury at every price point.',
		'areaServed'         => array( '@type' => 'State', 'name' => 'Massachusetts' ),
		'address'            => array( '@type' => 'PostalAddress', 'streetAddress' => '652 Park Ave, Unit B', 'addressLocality' => 'Worcester', 'addressRegion' => 'MA', 'postalCode' => '01603', 'addressCountry' => 'US' ),
		'parentOrganization' => array( '@type' => 'Organization', 'name' => 'REWAP Brokerage LLC', 'url' => 'https://rewapbrokerage.com/' ),
		// Team leaders and their direct contact details (supplied by the team, 2026-10-22).
		'member'             => array(
			array( '@type' => 'Person', 'name' => 'Kaitlyn Oberdorfer', 'jobTitle' => 'MA Licensed Realtor', 'telephone' => '978-257-4938', 'email' => 'kaitlynoberdorfer@gmail.com', 'sameAs' => array( 'https://instagram.com/_ma_real_estate', 'https://blinq.me/WXgsLzUBTC2V' ) ),
			array( '@type' => 'Person', 'name' => 'Brandon Oberdorfer', 'jobTitle' => 'MA Licensed Realtor', 'telephone' => '406-250-7620', 'email' => 'brandonoberdorfer@gmail.com', 'sameAs' => array( 'https://www.instagram.com/oberdorferrealtygroup/', 'https://blinq.me/cmere9vg301rxs60mq7nu5g5g' ) ),
		),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $ld, JSON_UNESCAPED_SLASHES ) . "</script>\n";
}, 1 );

/**
 * /favicon.ico. Browsers and Google ask for it at the site root. Without a Site Icon,
 * WordPress answers with a redirect to its own "W" logo; serve the brand icon instead.
 */
add_action( 'do_faviconico', function () {
	if ( has_site_icon() ) {
		return; // WordPress redirects to the Site Icon
	}
	header( 'Content-Type: image/x-icon' );
	header( 'Cache-Control: public, max-age=604800' );
	readfile( get_theme_file_path( 'assets/img/favicon.ico' ) );
	exit;
}, 1 ); // before core's do_favicon (priority 10), which redirects

/** WordPress prints its own canonical on singular views; this page prints one for the home. */
remove_action( 'wp_head', 'rel_canonical' );

/** Skip link to the page content. */
add_action( 'wp_body_open', function () {
	echo '<a class="skip-link" href="#main">Skip to content</a>';
} );

/**
 * A one-page site: no comments, feeds, author archives or user sitemap.
 * Everything that is not the home page gets the 404 template (which repeats the page).
 */
add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );
remove_action( 'wp_head', 'feed_links', 2 );
remove_action( 'wp_head', 'feed_links_extra', 3 );
add_action( 'template_redirect', function () {
	if ( is_feed() || is_author() || is_attachment() || is_search() || is_date() ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
} );
add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) {
	return in_array( $name, array( 'users', 'taxonomies' ), true ) ? false : $provider;
}, 10, 2 );
add_filter( 'wp_sitemaps_post_types', function ( $types ) {
	return array_intersect_key( $types, array( 'page' => true ) );
} );

/** robots.txt: the sitemap plus the AI-crawler policy (see the decisions page; recommended: allow). */
add_filter( 'robots_txt', function ( $output, $public ) {
	if ( ! $public ) {
		return $output;
	}
	$output .= "\n# AI crawlers: allowed until the team decides otherwise (decisions page).\n";
	foreach ( array( 'GPTBot', 'OAI-SearchBot', 'ClaudeBot', 'PerplexityBot', 'Google-Extended' ) as $bot ) {
		$output .= "User-agent: {$bot}\nAllow: /\n";
	}
	return $output;
}, 10, 2 );
