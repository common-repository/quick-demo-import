<?php
/**
 * Class AdminMenu.
 *
 * @since 1.0.0
 */

namespace QuickDemoImport;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdminMenu.
 *
 * @since 1.0.0
 */
class AdminMenu {

	/**
	 * Init.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function init() {
		self::init_hooks();
	}

	/**
	 * Init hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function init_hooks() {
		add_action( 'admin_menu', [ __CLASS__, 'admin_menu' ], 9 );
		add_action( 'admin_head', [ __CLASS__, 'add_menu_classes' ] );
	}

	/**
	 * Admin menu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function admin_menu() {
		if ( ! SupportedThemes::is_supported_theme() ) {
			return;
		}

		add_theme_page(
			__( 'Demo Importer', 'quick-demo-import' ),
			__( 'Demo Importer', 'quick-demo-import' ),
			'switch_themes',
			'demo-importer',
			[ __CLASS__, 'demo_importer' ]
		);
	}

	/**
	 * Demo importer template.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function demo_importer() {
		include_once dirname( __FILE__ ) . '/views/html-admin-page-importer.php';
	}

	/**
	 * Admin menu classes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function add_menu_classes() {
		if ( ! SupportedThemes::is_supported_theme() ) {
			return;
		}

		global $submenu;

		if ( isset( $submenu['themes.php'] ) ) {
			$submenu_class = 'demo-importer hide-if-no-js';

			// Add menu classes if user has access.
			if ( apply_filters( 'quick-demo-import/include_class_in_menu', true ) ) {
				foreach ( $submenu['themes.php'] as $order => $menu_item ) {
					if ( 0 === strpos( $menu_item[0], _x( 'Demo Importer', 'Admin menu name', 'quick-demo-import' ) ) ) {
						$submenu['themes.php'][ $order ][4] = empty( $menu_item[4] ) ? $submenu_class : $menu_item[4] . ' ' . $submenu_class; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
						break;
					}
				}
			}
		}
	}
}
