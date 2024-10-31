<?php
/**
 * Plugin Name: Quick Demo Import
 * Description: Import demo content, widgets and theme settings with just one click.
 * Version: 1.0.1
 * Author: QuickDemoImport
 * Author URI: https://quickdemoimport.com/
 * Requires at least: 5.4
 * Requires PHP: 7.0
 * License: GPLv3 or later
 * Text Domain: quick-demo-import
 * Domain Path: /languages/
 *
 * @package QuickDemoImport
 */

use QuickDemoImport\QuickDemoImport;

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'QUICK_DEMO_IMPORT_VERSION' ) ) {
	define( 'QUICK_DEMO_IMPORT_VERSION', '1.0.1' );
}

if ( ! defined( 'QUICK_DEMO_IMPORT_PLUGIN_FILE' ) ) {
	define( 'QUICK_DEMO_IMPORT_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'QUICK_DEMO_IMPORT_PLUGIN_DIR' ) ) {
	define( 'QUICK_DEMO_IMPORT_PLUGIN_DIR', dirname( __FILE__ ) );
}

if ( ! defined( 'QUICK_DEMO_IMPORT_PLUGIN_DIR_URL' ) ) {
	define( 'QUICK_DEMO_IMPORT_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'QUICK_DEMO_IMPORT_LANGUAGES' ) ) {
	define( 'QUICK_DEMO_IMPORT_LANGUAGES', dirname( __FILE__ ) . '/languages' );
}

if ( ! defined( 'QUICK_DEMO_IMPORT_UPLOAD_DIR' ) ) {
	$upload_dir = wp_upload_dir( null, false );
	define( 'QUICK_DEMO_IMPORT_UPLOAD_DIR', $upload_dir['basedir'] . '/quick-demo-import/' );
}

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Initialize.
 *
 * @since 1.0.0
 * @return QuickDemoImport|null
 */
function quick_demo_import() {
	return QuickDemoImport::instance();
}

quick_demo_import();
