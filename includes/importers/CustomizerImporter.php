<?php
/**
 * Class CustomizerImporter.
 *
 * @since 1.0.0
 * @package QuickDemoImport
 */

namespace QuickDemoImport\Importers;

defined( 'ABSPATH' ) || exit;

use WP_Error;
use stdClass;

/**
 * Class CustomizerImporter.
 *
 * @since 1.0.0
 */
class CustomizerImporter {

	/**
	 * Import customizer.
	 *
	 * @param $file
	 * @param $id
	 * @param $demo_data
	 * @return void|WP_Error
	 */
	public static function import( $file, $id, $demo_data ) {

		global $wp_customize;

		$data = maybe_unserialize( file_get_contents( $file ) ); // phpcs:ignore

		// Data checks.
		if ( ! is_array( $data ) &&
			( ! isset( $data['template'] ) ||
			! isset( $data['mods'] ) )
		) {
			return new WP_Error(
				'quick_demo_import_customizer_import_data_error',
				__(
					'The customizer import file is not in a correct format. Please make sure to use the correct customizer import file.',
					'quick-demo-import'
				)
			);
		}

		if (
			! empty( $demo_data['template'] ) &&
			! in_array( $data['template'], $demo_data['template'], true )
		) {
			return new WP_Error(
				'quick_demo_import_customizer_import_wrong_theme',
				__(
					'The customizer import file is not suitable for current theme. You can only import customizer settings for the same theme or a child theme.',
					'quick-demo-import'
				)
			);
		}

		// Import Images.
		if ( apply_filters( 'quick-demo-import/customizer_import_images', true ) ) {
			$data['mods'] = self::import_customizer_images( $data['mods'] );
		}

		// Modify settings array.
		$data = apply_filters( 'quick-demo-import/customizer_import_settings', $data, $data, $id );

		// Import custom options.
		if ( isset( $data['options'] ) ) {

			// Load WordPress Customize Setting Class.
			if ( ! class_exists( 'WP_Customize_Setting' ) ) {
				require_once ABSPATH . WPINC . '/class-wp-customize-setting.php';
			}

			foreach ( $data['options'] as $option_key => $option_value ) {
				$option = new CustomizeImporterSetting(
					$wp_customize,
					$option_key,
					[
						'default'    => '',
						'type'       => 'option',
						'capability' => 'edit_theme_options',
					]
				);

				$option->import( $option_value );
			}
		}

		// If wp_css is set then import it.
		if ( function_exists( 'wp_update_custom_css_post' ) && isset( $data['wp_css'] ) && '' !== $data['wp_css'] ) {
			wp_update_custom_css_post( $data['wp_css'] );
		}

		// Loop through theme mods and update them.
		if ( ! empty( $data['mods'] ) ) {
			foreach ( $data['mods'] as $key => $value ) {
				set_theme_mod( $key, $value );
			}
		}
	}

	/**
	 * Imports images for settings saved as mods.
	 *
	 * @param array $mods An array of customizer mods.
	 * @return array The mods array with any new import data.
	 */
	private static function import_customizer_images( array $mods ): array {
		foreach ( $mods as $key => $value ) {
			if ( self::is_image_url( $value ) ) {
				$data = self::media_handle_sideload( $value );
				if ( ! is_wp_error( $data ) ) {
					$mods[ $key ] = $data->url;

					// Handle header image controls.
					if ( isset( $mods[ $key . '_data' ] ) ) {
						$mods[ $key . '_data' ] = $data;
						update_post_meta( $data->attachment_id, '_wp_attachment_is_custom_header', get_stylesheet() );
					}
				}
			}
		}

		return $mods;
	}

	/**
	 * Checks to see whether an url is an image url or not.
	 *
	 * @param string $url The url to check.
	 * @return bool Whether the url is an image url or not.
	 */
	private static function is_image_url( $url ): bool {
		if ( is_string( $url ) ) {
			if ( preg_match( '/\.(jpg|jpeg|png|gif)/i', $url ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Taken from the core media_sideload_image function and
	 * modified to return an array of data instead of html.
	 *
	 * @param string $file The image file path.
	 * @return bool|int|stdClass|string|WP_Error An array of image data.
	 */
	private static function media_handle_sideload( string $file ) {
		$data = new stdClass();

		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		if ( ! empty( $file ) ) {
			// Set variables for storage, fix file filename for query strings.
			preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
			$file_array         = array();
			$file_array['name'] = basename( $matches[0] );

			// Download file to temp location.
			$file_array['tmp_name'] = download_url( $file );

			// If error storing temporarily, return the error.
			if ( is_wp_error( $file_array['tmp_name'] ) ) {
				return $file_array['tmp_name'];
			}

			// Do the validation and storage stuff.
			$id = media_handle_sideload( $file_array, 0 );

			// If error storing permanently, unlink.
			if ( is_wp_error( $id ) ) {
				@unlink( $file_array['tmp_name'] ); // phpcs:ignore
				return $id;
			}

			// Build the object to return.
			$meta                = wp_get_attachment_metadata( $id );
			$data->attachment_id = $id;
			$data->url           = wp_get_attachment_url( $id );
			$data->thumbnail_url = wp_get_attachment_thumb_url( $id );
			$data->height        = $meta['height'];
			$data->width         = $meta['width'];
		}

		return $data;
	}
}
