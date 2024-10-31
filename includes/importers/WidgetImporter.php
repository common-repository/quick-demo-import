<?php
/**
 * Class WidgetImporter.
 *
 * @since 1.0.0
 * @package QuickDemoImport
 */

namespace QuickDemoImport\Importers;

defined( 'ABSPATH' ) || exit;

use WP_Error;

/**
 * Class WidgetImporter.
 *
 * @since 1.0.0
 */
class WidgetImporter {

	/**
	 * Import widget JSON data.
	 *
	 * @since 1.0.0
	 * @param  string $import_file Path to the import file.
	 * @param string $demo_id The ID of demo being imported.
	 * @param array $demo_data The data of demo being imported.
	 * @return WP_Error|array WP_Error on failure, $results on success.
	 * @global array $wp_registered_sidebars
	 */
	public static function import( string $import_file, string $demo_id, array $demo_data ) {

		global $wp_registered_sidebars;

		$data = json_decode( file_get_contents( $import_file ) ); // phpcs:ignore

		// Have valid data? If no data or could not decode.
		if ( empty( $data ) || ! is_object( $data ) ) {
			return new WP_Error(
				'quick_demo_import_widget_import_data_error',
				__(
					'Widget import data could not be read. Please try a different file.',
					'quick-demo-import'
				)
			);
		}

		// Hook before import.
		do_action( 'quick-demo-import/widget_importer_before_widgets_import' );

		$data = apply_filters( 'quick-demo-import/before_widgets_import_data', $data );

		// Get all available widgets site supports.
		$available_widgets = self::available_widgets();

		// Get all existing widget instances.
		$widget_instances = array();
		foreach ( $available_widgets as $widget_data ) {
			$widget_instances[ $widget_data['id_base'] ] = get_option( 'widget_' . $widget_data['id_base'] );
		}

		// Begin results.
		$results = array();

		// Loop import data's sidebars.
		foreach ( $data as $sidebar_id => $widgets ) {

			// Skip inactive widgets (should not be in export file).
			if ( 'wp_inactive_widgets' === $sidebar_id ) {
				continue;
			}

			// Check if sidebar is available on this site. Otherwise, add widgets to inactive, and say so.
			if ( isset( $wp_registered_sidebars[ $sidebar_id ] ) ) {
				$sidebar_available    = true;
				$use_sidebar_id       = $sidebar_id;
				$sidebar_message_type = 'success';
				$sidebar_message      = '';
			} else {
				$sidebar_available    = false;
				$use_sidebar_id       = 'wp_inactive_widgets'; // Add to inactive if sidebar does not exist in theme.
				$sidebar_message_type = 'error';
				$sidebar_message      = __( 'Sidebar does not exist in theme (moving widget to Inactive)', 'quick-demo-import' );
			}

			// Result for sidebar.
			$results[ $sidebar_id ]['name']         = $wp_registered_sidebars[ $sidebar_id ]['name'] ?? $sidebar_id; // Sidebar name if theme supports it; otherwise ID.
			$results[ $sidebar_id ]['message_type'] = $sidebar_message_type;
			$results[ $sidebar_id ]['message']      = $sidebar_message;
			$results[ $sidebar_id ]['widgets']      = array();

			// Loop widgets.
			foreach ( $widgets as $widget_instance_id => $widget ) {

				$fail = false;

				// Get id_base (remove -# from end) and instance ID number.
				$id_base            = preg_replace( '/-[0-9]+$/', '', $widget_instance_id );
				$instance_id_number = str_replace( $id_base . '-', '', $widget_instance_id );

				// Does site support this widget?
				if ( ! isset( $available_widgets[ $id_base ] ) ) {
					$fail                = true;
					$widget_message_type = 'error';
					$widget_message      = __( 'Site does not support widget', 'quick-demo-import' ); // Explain why widget not imported.
				}

				/**
				 * Convert multidimensional objects to multidimensional arrays.
				 *
				 * Some plugins like Jetpack Widget Visibility store settings as multidimensional arrays.
				 * Without this, they are imported as objects and cause fatal error on Widgets page.
				 * If this creates problems for plugins that do actually intend settings in objects then may need to consider other approach: https://wordpress.org/support/topic/problem-with-array-of-arrays.
				 * It is probably much more likely that arrays are used than objects, however.
				 */
				$widget = json_decode( wp_json_encode( $widget ), true );

				/**
				 * Filter to modify settings array.
				 *
				 * Do before identical check because changes may make it identical to end result (such as URL replacements).
				 */
				$widget = apply_filters( 'quick-demo-import/widget_import_settings', $widget, $id_base, $instance_id_number, $demo_data, $demo_id );

				// Does widget with identical settings already exist in same sidebar?
				if ( ! $fail && isset( $widget_instances[ $id_base ] ) ) {

					// Get existing widgets in this sidebar.
					$sidebars_widgets = get_option( 'sidebars_widgets' );
					$sidebar_widgets  = $sidebars_widgets[ $use_sidebar_id ] ?? array(); // Check Inactive if that's where will go.

					// Loop widgets with ID base.
					$single_widget_instances = ! empty( $widget_instances[ $id_base ] ) ? $widget_instances[ $id_base ] : array();
					foreach ( $single_widget_instances as $check_id => $check_widget ) {

						// Is widget in same sidebar and has identical settings?
						if ( in_array( "$id_base-$check_id", $sidebar_widgets, true ) && (array) $widget === $check_widget ) {
							$fail                = true;
							$widget_message_type = 'warning';
							$widget_message      = __( 'Widget already exists', 'quick-demo-import' ); // Explain why widget not imported.

							break;
						}
					}
				}

				// No failure.
				if ( ! $fail ) {

					// Add widget instance.
					$single_widget_instances   = get_option( 'widget_' . $id_base ); // All instances for that widget ID base, get fresh every time.
					$single_widget_instances   = ! empty( $single_widget_instances ) ? $single_widget_instances : array( '_multiwidget' => 1 ); // Start fresh if have to.
					$single_widget_instances[] = $widget; // Add it.

					// Get the key it was given.
					end( $single_widget_instances );
					$new_instance_id_number = key( $single_widget_instances );

					// If key is 0, make it 1.
					// When 0, an issue can occur where adding a widget causes data from other widget to load, and the widget doesn't stick (reload wipes it).
					if ( '0' === strval( $new_instance_id_number ) ) {
						$new_instance_id_number                             = 1;
						$single_widget_instances[ $new_instance_id_number ] = $single_widget_instances[0];
						unset( $single_widget_instances[0] );
					}

					// Move _multiwidget to end of array for uniformity.
					if ( isset( $single_widget_instances['_multiwidget'] ) ) {
						$multiwidget = $single_widget_instances['_multiwidget'];
						unset( $single_widget_instances['_multiwidget'] );
						$single_widget_instances['_multiwidget'] = $multiwidget;
					}

					// Update option with new widget.
					update_option( 'widget_' . $id_base, $single_widget_instances );

					// Assign widget instance to sidebar.
					$sidebars_widgets                      = get_option( 'sidebars_widgets' ); // Which sidebars have which widgets, get fresh every time.
					$new_instance_id                       = $id_base . '-' . $new_instance_id_number; // Use ID number from new widget instance.
					$sidebars_widgets[ $use_sidebar_id ][] = $new_instance_id; // Add new instance to sidebar.
					update_option( 'sidebars_widgets', $sidebars_widgets ); // Save the amended data.

					// After widget import action.
					$after_widget_import = array(
						'sidebar'           => $use_sidebar_id,
						'sidebar_old'       => $sidebar_id,
						'widget'            => $widget,
						'widget_type'       => $id_base,
						'widget_id'         => $new_instance_id,
						'widget_id_old'     => $widget_instance_id,
						'widget_id_num'     => $new_instance_id_number,
						'widget_id_num_old' => $instance_id_number,
					);
					do_action( 'quick-demo-import/widget_importer_after_single_widget_import', $after_widget_import );

					// Success message.
					if ( $sidebar_available ) {
						$widget_message_type = 'success';
						$widget_message      = __( 'Imported', 'quick-demo-import' );
					} else {
						$widget_message_type = 'warning';
						$widget_message      = __( 'Imported to Inactive', 'quick-demo-import' );
					}
				}

				// Result for widget instance.
				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['name']         = $available_widgets[ $id_base ]['name'] ?? $id_base; // Widget name or ID if name not available (not supported by site).
				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['title']        = $widget['title'] ?? __( 'No Title', 'quick-demo-import' ); // Show "No Title" if widget instance is untitled.
				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['message_type'] = $widget_message_type;
				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['message']      = $widget_message;
			}
		}

		// Hook after import.
		do_action( 'quick-demo-import/widget_importer_after_widgets_import' );

		// Return results.
		return apply_filters( 'quick-demo-import/widget_import_results', $results );
	}

	/**
	 * Available widgets.
	 *
	 * Gather site's widgets into array with ID base, name, etc.
	 *
	 * @since 1.0.0
	 * @global array $wp_registered_widget_controls
	 * @return array Widget information
	 */
	private static function available_widgets(): array {
		global $wp_registered_widget_controls;

		$widget_controls   = $wp_registered_widget_controls;
		$available_widgets = array();

		foreach ( $widget_controls as $widget ) {
			if ( ! empty( $widget['id_base'] ) && ! isset( $available_widgets[ $widget['id_base'] ] ) ) {
				$available_widgets[ $widget['id_base'] ]['id_base'] = $widget['id_base'];
				$available_widgets[ $widget['id_base'] ]['name']    = $widget['name'];
			}
		}

		return apply_filters( 'quick-demo-import/available_widgets', $available_widgets );
	}
}
