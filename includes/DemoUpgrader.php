<?php
/**
 * Class DemoUpgrader.
 *
 * @since 1.0.0
 * @package 1.0.0
 */

namespace QuickDemoImport;

defined( 'ABSPATH' ) || exit;

use WP_Upgrader;
use WP_Error;

/**
 * Class DemoUpgrader.
 *
 * @since 1.0.0
 */
class DemoUpgrader extends WP_Upgrader {

	/**
	 * Holds result.
	 *
	 * @since 1.0.0
	 * @var $result;
	 */
	public $result;

	/**
	 * @var bool $bulk
	 */
	public $bulk = false;

	public function install_strings() {
		$this->strings['no_package'] = __( 'Install package not available.', 'quick-demo-import' );
		/* translators: %s: package URL */
		$this->strings['downloading_package'] = __( 'Downloading install package from <span class="code">%s</span>&#8230;', 'quick-demo-import' );
		$this->strings['unpack_package']      = __( 'Unpacking the package&#8230;', 'quick-demo-import' );
		$this->strings['remove_old']          = __( 'Removing the old version of the demo&#8230;', 'quick-demo-import' );
		$this->strings['remove_old_failed']   = __( 'Could not remove the old demo.', 'quick-demo-import' );
		$this->strings['installing_package']  = __( 'Installing the demo&#8230;', 'quick-demo-import' );
		$this->strings['no_files']            = __( 'The demo contains no files.', 'quick-demo-import' );
		$this->strings['process_failed']      = __( 'Demo install failed.', 'quick-demo-import' );
		$this->strings['process_success']     = __( 'Demo installed successfully.', 'quick-demo-import' );
	}

	/**
	 * Install.
	 *
	 * @since 1.0.0
	 * @param $package
	 * @return bool|WP_Error
	 */
	public function install( $package ) {

		$this->init();
		$this->install_strings();

		add_filter( 'upgrader_source_selection', [ $this, 'check_package' ] );

		$this->run(
			[
				'package'           => $package,
				'destination'       => QUICK_DEMO_IMPORT_UPLOAD_DIR,
				'clear_destination' => true, // Do overwrite files.
				'clear_working'     => true,
				'hook_extra'        => [
					'type'   => 'demo',
					'action' => 'install',
				],
			]
		);

		remove_filter( 'upgrader_source_selection', [ $this, 'check_package' ] );

		if ( ! $this->result || is_wp_error( $this->result ) ) {
			return $this->result;
		}

		return true;
	}

	/**
	 * Check package.
	 *
	 * @since 1.0.0
	 * @param $source
	 * @return WP_Error
	 */
	public function check_package( $source ) {

		global $wp_filesystem;

		if ( is_wp_error( $source ) ) {
			return $source;
		}

		// Check the folder contains a valid demo.
		$working_directory = str_replace( $wp_filesystem->wp_content_dir(), trailingslashit( WP_CONTENT_DIR ), $source );
		if ( ! is_dir( $working_directory ) ) { // Sanity check, if the above fails, let's not prevent installation.
			return $source;
		}

		$file_pattern = glob( $working_directory . '*.xml' );

		if ( empty( $file_pattern ) ) {
			return new WP_Error( 'incompatible_archive_no_demos', $this->strings['incompatible_archive'], __( 'No valid demos were found.', 'quick-demo-import' ) );
		}

		return $source;
	}
}
