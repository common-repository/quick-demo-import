<?php
/**
 * Class CustomizeImporterSetting.
 *
 * @since 1.0.0
 * @package QuickDemoImport
 */

namespace QuickDemoImport\Importers;

defined( 'ABSPATH' ) || exit;

use WP_Customize_Setting;

/**
 * Class CustomizeImporterSetting.
 *
 * @since 1.0.0
 */
class CustomizeImporterSetting extends WP_Customize_Setting {

	/**
	 * Import
	 *
	 * @since 1.0.0
	 * @param $value
	 * @return void
	 */
	public function import( $value ) {
		$this->update( $value );
	}
}
