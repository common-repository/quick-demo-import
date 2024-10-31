<?php
/**
 * Class Activation.
 *
 * @since 1.0.0
 * @package QuickDemoImport
 */

namespace QuickDemoImport;

defined( 'ABSPATH' ) || exit;

/**
 * Class Activation.
 *
 * @since 1.0.0
 */
class Activation {

	/**
	 * Init.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function init() {
		register_activation_hook( QUICK_DEMO_IMPORT_PLUGIN_FILE, [ __CLASS__, 'on_activate' ] );
	}

	/**
	 * On activate.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function on_activate() {

		$files = [
			[
				'base'    => QUICK_DEMO_IMPORT_UPLOAD_DIR,
				'file'    => 'index.html',
				'content' => '',
			],
		];

		if ( ! is_blog_installed() || apply_filters( 'quick-demo-import/create_files', false ) ) {
			return;
		}

		// phpcs:disable
		foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				$file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' );
				if ( $file_handle ) {
					fwrite( $file_handle, $file['content'] );
					fclose( $file_handle );
				}
			}
		}
		// phpcs:enable

		set_transient( '_quick_demo_import_activation_redirect', 1, 30 );
	}
}
