<?php
/**
 * Class SupportedThemes.
 *
 * @since 1.0.0
 * @package QuickDemoImport
 */

namespace QuickDemoImport;

defined( 'ABSPATH' ) || exit;

/**
 * Class SupportedThemes.
 *
 * @since 1.0.0
 */
class SupportedThemes {

	/**
	 * Supported themes.
	 *
	 * @var array
	 */
	public static $supported_themes;

	/**
	 * Init.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'setup' ], 3 );
	}

	/**
	 * Setup.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function setup() {
		self::$supported_themes = apply_filters( 'quick-demo-import/supported_themes', [] );
	}

	/**
	 * Check if active theme is supported.
	 *
	 * @since 1.0.0
	 * @return mixed|void
	 */
	public static function is_supported_theme() {
		return apply_filters( 'quick-demo-import/is_supported_theme', in_array( get_option( 'template' ), self::$supported_themes, true ) );
	}
}
