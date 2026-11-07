<?php
/**
 * Plugin Name:       Oberdorfer Leads
 * Plugin URI:        https://oberdorferteam.com/
 * Description:       The team's built-in CRM: every form on the site saves here; leads are routed to the right agent with instant alerts, worked in an inbox and pipeline, forwarded to an outside CRM if chosen, and nurtured by email (Brevo) with AI drafts a person approves. Spec: docs/leads-crm-nurture-spec.md.
 * Version:           0.6.2
 * Requires at least: 7.1
 * Requires PHP:      8.3
 * Requires Plugins:  ot-core
 * Author:            The Oberdorfer Team
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ot-leads
 *
 * Layer rule (docs/wordpress-architecture.md): forms, leads and nurture live here, never in the
 * theme. Leads are stored in their own tables, not as posts, so personal data stays out of the
 * posts REST API, search and content exports.
 */

defined( 'ABSPATH' ) || exit;

const OT_LEADS_VERSION = '0.6.2';
const OT_LEADS_DB      = 1; // bump when a table changes; ot_leads_install() runs dbDelta again

define( 'OT_LEADS_DIR', __DIR__ );
define( 'OT_LEADS_URL', plugins_url( '', __FILE__ ) );

foreach ( array( 'schema', 'model', 'mail', 'capture', 'routing', 'connectors', 'nurture', 'ai', 'cron', 'privacy' ) as $f ) {
	require_once OT_LEADS_DIR . '/includes/' . $f . '.php';
}
if ( is_admin() ) {
	foreach ( array( 'admin', 'admin-inbox', 'admin-lead', 'admin-board', 'admin-queue', 'admin-sequences', 'admin-newsletter', 'admin-settings' ) as $f ) {
		require_once OT_LEADS_DIR . '/includes/' . $f . '.php';
	}
}

register_activation_hook( __FILE__, 'ot_leads_activate' );
register_deactivation_hook( __FILE__, 'ot_leads_deactivate' );

function ot_leads_activate() {
	ot_leads_install();
	ot_leads_roles();
	ot_leads_schedule();
}

function ot_leads_deactivate() {
	wp_clear_scheduled_hook( 'ot_leads_tick' );
	wp_clear_scheduled_hook( 'ot_leads_daily' );
}

/** Tables, roles and schedules are checked on every load, so an upload-and-replace update is enough. */
add_action( 'plugins_loaded', function () {
	if ( (int) get_option( 'ot_leads_db' ) !== OT_LEADS_DB ) {
		ot_leads_install();
		ot_leads_roles();
	}
	ot_leads_schedule();
} );
