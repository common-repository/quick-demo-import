<?php
/**
 * Class QuickDemoImport.
 *
 * @since 1.0.0
 * @package QuickDemoImport
 */

namespace QuickDemoImport;

defined( 'ABSPATH' ) || exit;

/**
 * Class QuickDemoImport.
 *
 * @since 1.0.0
 */
final class QuickDemoImport {

	/**
	 * Instance.
	 *
	 * @since 1.0.0
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Initiator.
	 *
	 * @return QuickDemoImport|null
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'quick-demo-import' ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'quick-demo-import' ), '1.0.0' );
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		Activation::init();
		Deactivation::init();
		self::init_hooks();
		SupportedThemes::init();
		AdminMenu::init();
		AdminNotice::init();
		ScriptStyle::init();
		Hooks::init();
		DemoImporter::init();
	}

	/**
	 * Init hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function init_hooks() {
		add_action( 'init', [ __CLASS__, 'init' ], 0 );
	}

	/**
	 * Init.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function init() {
		self::load_text_domain();
		do_action( 'quick-demo-import/init' );
	}

	/**
	 * Load text domain.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function load_text_domain() {
		load_plugin_textdomain( 'blockart', false, QUICK_DEMO_IMPORT_LANGUAGES );
	}
}
