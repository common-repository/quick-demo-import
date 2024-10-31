<?php
/**
 * Class ScriptStyle.
 *
 * @since 1.0.0
 * @package QuickDemoImport
 */

namespace QuickDemoImport;

defined( 'ABSPATH' ) || exit;

/**
 * Class ScriptStyle.
 *
 * @since 1.0.0
 */
class ScriptStyle {

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
		add_action( 'init', [ __CLASS__, 'register_script_style' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_script_style' ] );
	}

	/**
	 * Register scripts and styles.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register_script_style() {
		$suffix      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$assets_path = plugins_url( '/', QUICK_DEMO_IMPORT_PLUGIN_FILE ) . '/assets/';

		wp_register_style( 'jquery-confirm', $assets_path . 'css/jquery-confirm/jquery-confirm.css', [], QUICK_DEMO_IMPORT_VERSION );
		wp_register_style( 'quick-demo-import', $assets_path . 'css/demo-importer.css', [ 'jquery-confirm' ], QUICK_DEMO_IMPORT_VERSION );
		wp_style_add_data( 'quick-demo-import', 'rtl', 'replace' );

		wp_register_script( 'jquery-tiptip', $assets_path . 'js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', [ 'jquery' ], '1.3', true );
		wp_register_script( 'jquery-confirm', $assets_path . 'js/jquery-confirm/jquery-confirm' . $suffix . '.js', [ 'jquery' ], QUICK_DEMO_IMPORT_VERSION, true );
		wp_register_script( 'quick-demo-import-importer', $assets_path . 'js/importer' . $suffix . '.js', [ 'jquery', 'updates', 'wp-i18n' ], QUICK_DEMO_IMPORT_VERSION, true );
		wp_register_script( 'quick-demo-import', $assets_path . 'js/quick-demo-import' . $suffix . '.js', [ 'jquery', 'jquery-tiptip', 'wp-backbone', 'wp-a11y', 'quick-demo-import-importer', 'jquery-confirm' ], QUICK_DEMO_IMPORT_VERSION, true );
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function enqueue_script_style() {
		$current_screen    = get_current_screen();
		$current_screen_id = $current_screen ? $current_screen->id : '';

		if ( 'appearance_page_demo-importer' === $current_screen_id ) {
			wp_enqueue_style( 'quick-demo-import' );
			wp_enqueue_script( 'quick-demo-import' );

			wp_localize_script(
				'quick-demo-import',
				'_demoImporterSettings',
				[
					'demos'    => DemoImporter::query_demos(),
					'settings' => [
						'isNew'         => false,
						'ajaxURL'       => admin_url( 'admin-ajax.php' ),
						'adminURL'      => wp_parse_url( self_admin_url(), PHP_URL_PATH ),
						'confirmImport' => sprintf(
						/* Translators: Before import warnings. */
							__(
								'Importing demo data will ensure that you site will look similar as theme demo. It makes you easy to modify the content instead of creating them from scratch. Also, consider before importing the demo: %1$s %2$s %3$s %4$s %5$s %6$s',
								'quick-demo-import'
							),
							'<ol><li class="warning">' . __( 'Importing the demo on the site if you have already added the content is highly discouraged.', 'quick-demo-import' ) . '</li>',
							'<li>' . __( 'You need to import demo on fresh WordPress install to exactly replicate the theme demo.', 'quick-demo-import' ) . '</li>',
							'<li>' . __( 'It will install the required plugins as well as activate them for installing the required theme demo within your site.', 'quick-demo-import' ) . '</li>',
							'<li>' . __( 'Copyright images will get replaced with other placeholder images.', 'quick-demo-import' ) . '</li>',
							'<li>' . __( 'None of the posts, pages, attachments or any other data already existing in your site will be deleted or modified.', 'quick-demo-import' ) . '</li>',
							'<li>' . __( 'It will take some time to import the theme demo.', 'quick-demo-import' ) . '</li></ol>'
						),
					],
					'l10n'     => [
						'search'              => __( 'Search Demos', 'quick-demo-import' ),
						'searchPlaceholder'   => __( 'Search demos...', 'quick-demo-import' ),
						'error'               => sprintf(
							/* translators: %s: support forums URL */
							__( 'An unexpected error occurred. Something may be wrong with Quick Demo Import server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.', 'quick-demo-import' ),
							'https://wordpress.org/support/plugin/quick-demo-import'
						),
						'tryAgain'            => __( 'Try Again', 'quick-demo-import' ),
						'suggestNew'          => __( 'Please suggest us!', 'quick-demo-import' ),
						/* translators: %d: Number of demos. */
						'demosFound'          => __( 'Number of Demos found: %d', 'quick-demo-import' ),
						'noDemosFound'        => __( 'No demos found. Try a different search.', 'quick-demo-import' ),
						'collapseSidebar'     => __( 'Collapse Sidebar', 'quick-demo-import' ),
						'expandSidebar'       => __( 'Expand Sidebar', 'quick-demo-import' ),
						/* translators: accessibility text */
						'selectFeatureFilter' => __( 'Select one or more Demo features to filter by', 'quick-demo-import' ),
						'confirmMsg'          => __( 'Confirm!', 'quick-demo-import' ),
					],
				]
			);

			wp_set_script_translations( 'quick-demo-import', 'quick-demo-import' );
		}
	}
}
