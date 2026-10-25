<?php
/**
 * Plugin Name: Oberdorfer Core (must-use loader)
 * Description: Loads wp-content/mu-plugins/ot-core/ot-core.php. Copy this file to wp-content/mu-plugins/ and the ot-core folder beside it.
 */

defined( 'ABSPATH' ) || exit;

if ( file_exists( __DIR__ . '/ot-core/ot-core.php' ) ) {
	require_once __DIR__ . '/ot-core/ot-core.php';
}
