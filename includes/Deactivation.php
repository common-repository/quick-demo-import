<?php
/**
 * Class Deactivation.
 *
 * @since 1.0.0
 * @package QuickDemoImport
 */

namespace QuickDemoImport;

defined( 'ABSPATH' ) || exit;

/**
 * Class Deactivation.
 *
 * @since 1.0.0
 */
class Deactivation {

	/**
	 * Init.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function init() {
		register_deactivation_hook( QUICK_DEMO_IMPORT_PLUGIN_FILE, [ __CLASS__, 'on_deactivate' ] );
	}

	/**
	 * On deactivate.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function on_deactivate() {}
}
