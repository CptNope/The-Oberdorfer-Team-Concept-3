<?php
/**
 * Plugin Name:       Oberdorfer Core
 * Plugin URI:        https://oberdorferteam.com/
 * Description:       The content model, search output and importer for oberdorferteam.com: community guides, agents and offices as post types, their fields, server-rendered blocks, titles, meta descriptions, JSON-LD and llms.txt. Theme-independent by design.
 * Version:           0.1.1
 * Requires at least: 7.1
 * Requires PHP:      8.3
 * Author:            The Oberdorfer Team
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ot-core
 *
 * Layer rule (docs/wordpress-architecture.md): anything that would lose data or break saved
 * content on a theme change lives here, not in the theme. Runs as a normal plugin or as a
 * must-use plugin (see mu-plugin/ot-core-loader.php).
 */

defined( 'ABSPATH' ) || exit;

const OT_CORE_VERSION = '0.1.1';
// Bump when post types, rewrite rules or stored data change; ot_core_maybe_upgrade() then
// flushes rewrites. Must-use plugins never run activation hooks, so this is the only trigger.
const OT_CORE_SCHEMA  = 1;

define( 'OT_CORE_DIR', __DIR__ );
define( 'OT_CORE_URL', plugins_url( '', __FILE__ ) );

/** The production site. Canonicals, sitemaps and JSON-LD use it whatever the host (staging included). */
if ( ! defined( 'OT_SITE_URL' ) ) {
	define( 'OT_SITE_URL', 'https://oberdorferteam.com/' );
}

require_once OT_CORE_DIR . '/includes/helpers.php';
require_once OT_CORE_DIR . '/includes/post-types.php';
require_once OT_CORE_DIR . '/includes/content.php';
require_once OT_CORE_DIR . '/includes/render-community.php';
require_once OT_CORE_DIR . '/includes/render-agent.php';
require_once OT_CORE_DIR . '/includes/render-misc.php';
require_once OT_CORE_DIR . '/includes/blocks.php';
require_once OT_CORE_DIR . '/includes/seo.php';
require_once OT_CORE_DIR . '/includes/importer.php';

/** Flush rewrite rules once after a schema change (works for mu-plugins too). */
add_action( 'init', function () {
	if ( (int) get_option( 'ot_core_schema' ) !== OT_CORE_SCHEMA ) {
		flush_rewrite_rules( false );
		update_option( 'ot_core_schema', OT_CORE_SCHEMA, true );
	}
}, 99 );

register_activation_hook( __FILE__, function () {
	delete_option( 'ot_core_schema' );
} );
