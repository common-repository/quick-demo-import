<?php
/**
 * Class DemoImporter.
 *
 * @since 1.0.0
 * @package QuickDemoImport
 */

namespace QuickDemoImport;

defined( 'ABSPATH' ) || exit;

use QuickDemoImport\Importers\CustomizerImporter;
use QuickDemoImport\Importers\WXRImporter;
use QuickDemoImport\Importers\WidgetImporter;

/**
 * Class DemoImporter.
 *
 * @since 1.0.0
 */
class DemoImporter {

	/**
	 * Holds demo data.
	 *
	 * @var $demos_packages
	 */
	public static $demos_packages;

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
		add_filter( 'woocommerce_enable_setup_wizard', '__return_false', 1 );
		add_filter( 'masteriyo_enable_setup_wizard', '__return_false' );
		add_filter( 'blockart_activation_redirect', '__return_false' );
		add_action( 'init', [ __CLASS__, 'setup' ], 5 );
		add_action( 'wp_ajax_query-demos', [ __CLASS__, 'query_demos' ] );
		add_action( 'wp_ajax_import-demo', [ __CLASS__, 'import_demo' ] );
		add_action( 'wp_ajax_install-required-plugins', [ __CLASS__, 'install_plugins' ] );
	}

	/**
	 * Setup.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function setup() {
		if ( ! SupportedThemes::is_supported_theme() ) {
			return;
		}
		self::$demos_packages = self::get_demo_packages();
	}

	/**
	 * Get demo packages.
	 *
	 * @since 1.0.0
	 * @return mixed|void
	 */
	private static function get_demo_packages() {
		$package             = apply_filters( 'quick-demo-import/demo_packages', [] );
		$active_theme        = wp_get_theme();
		$package['name']     = $active_theme->get( 'Name' );
		$package['slug']     = $active_theme->get( 'TextDomain' );
		$package['version']  = $active_theme->get( 'Version' );
		$package['homepage'] = $active_theme->get( 'ThemeURI' );
		$package['author']   = $active_theme->display( 'Author', false );

		if ( isset( $package['categories'] ) && is_array( $package['categories'] ) ) {
			if ( ! array_key_exists( 'all', $package['categories'] ) ) {
				$package['categories']['all'] = __( 'All', 'quick-demo-import' );
			}
		} else {
			$package['categories'] = [ 'all' => __( 'All', 'quick-demo-import' ) ];
		}

		if ( isset( $package['pagebuilders'] ) && is_array( $package['pagebuilders'] ) ) {
			if ( ! array_key_exists( 'all', $package['pagebuilders'] ) ) {
				$package['pagebuilders']['all'] = __( 'All', 'quick-demo-import' );
			}
		} else {
			$package['pagebuilders'] = [ 'all' => __( 'All', 'quick-demo-import' ) ];
		}

		if ( isset( $package['demos'] ) && is_array( $package['demos'] ) ) {
			foreach ( $package['demos'] as $key => $demo ) {
				if ( isset( $demo['category'] ) && is_array( $demo['category'] ) ) {
					if ( ! in_array( 'all', $demo['category'], true ) ) {
						$package['demos'][ $key ]['category'][] = 'all';
					}
				} else {
					$package['demos'][ $key ]['category'] = [ 'all' ];
				}

				if ( isset( $demo['pagebuilder'] ) && is_array( $demo['pagebuilder'] ) ) {
					if ( ! in_array( 'all', $demo['pagebuilder'], true ) ) {
						$package['demos'][ $key ]['pagebuilder'][] = 'all';
					}
				} else {
					$package['demos'][ $key ]['pagebuilder'] = [ 'all' ];
				}

				if ( isset( $demo['template'] ) && is_array( $demo['template'] ) ) {
					if ( ! in_array( $active_theme->get( 'TextDomain' ), $demo['template'], true ) ) {
						$package['demos'][ $key ]['template'][] = $active_theme->get( 'TextDomain' );
					}
				} else {
					$package['demos'][ $key ]['template'] = [ $active_theme->get( 'TextDomain' ) ];
				}
			}
		}

		$package['categories']   = array_change_key_case( $package['categories'], CASE_LOWER );
		$package['pagebuilders'] = array_change_key_case( $package['pagebuilders'], CASE_LOWER );
		ksort( $package['categories'] );
		ksort( $package['pagebuilders'] );

		return json_decode( wp_json_encode( $package ) );
	}

	/**
	 * Get import file path from local.
	 *
	 * @param $extension
	 * @return string
	 */
	private static function get_import_file_path( $extension ): string {
		$filepath_pattern = QUICK_DEMO_IMPORT_UPLOAD_DIR . "*.$extension";
		$filepath         = glob( $filepath_pattern );

		if ( empty( $filepath ) ) {
			return false;
		}

		return $filepath[0];
	}

	/**
	 * Query demos.
	 *
	 * @param bool $return
	 * @return array|void
	 */
	public static function query_demos( bool $return = true ) {
		$current_template   = get_option( 'template' );
		$imported_demo_id   = get_option( '_quick_demo_import_imported_demo_id' );
		$available_packages = self::$demos_packages;

		$prepared_demos = (array) apply_filters( 'quick-demo-import/pre_prepare_demos_for_js', [], $available_packages, $imported_demo_id );

		if ( ! empty( $prepared_demos ) ) {
			return $prepared_demos;
		}

		if ( ! $return ) {
			$request = wp_parse_args(
				wp_unslash( $_REQUEST['request'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				[ 'browse' => 'all' ]
			);
		} else {
			$request = [ 'browse' => 'all' ];
		}

		if ( isset( $available_packages->demos ) ) {
			foreach ( $available_packages->demos as $slug => $data ) {
				$plugins_list   = $data->plugins_list ?? [];
				$screenshot_url = $data->screenshot;

				if (
					isset( $request['browse'], $data->category ) &&
					! in_array( $request['browse'], $data->category, true )
				) {
					continue;
				}

				if (
					isset( $request['builder'], $data->pagebuilder ) &&
					! in_array( $request['builder'], $data->pagebuilder, true )
				) {
					continue;
				}

				foreach ( $plugins_list as $plugin => $plugin_data ) {

					$plugin_data->is_active = is_plugin_active( $plugin_data->slug );

					// Looks like a plugin is installed, but not active.
					if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin ) ) {
						$plugins = get_plugins( '/' . $plugin );
						if ( ! empty( $plugins ) ) {
							$plugin_data->is_install = true;
						}
					} else {
						$plugin_data->is_install = false;
					}
				}

				$prepared_demos[ $slug ] = [
					'slug'            => $slug,
					'name'            => $data->title,
					/* Translators: %s: Theme name. */
					'theme'           => $available_packages->name,
					'active'          => $slug === $imported_demo_id,
					'author'          => $data->author ?? $available_packages->author,
					'version'         => $data->version ?? $available_packages->version,
					'description'     => $data->description ?? '',
					'homepage'        => $available_packages->homepage,
					'preview_url'     => set_url_scheme( $data->preview ),
					'screenshot_url'  => $screenshot_url,
					'plugins'         => $plugins_list,
					'requiredTheme'   => isset( $data->template ) && ! in_array( $current_template, $data->template, true ),
					'requiredPlugins' => (bool) wp_list_filter( json_decode( wp_json_encode( $plugins_list ), true ), [ 'is_active' => false ] ),
				];
			}
		}

		$prepared_demos = apply_filters( 'quick-demo-import/prepare_demos_for_js', $prepared_demos );
		$prepared_demos = array_values( $prepared_demos );

		if ( $return ) {
			return $prepared_demos;
		}

		wp_send_json_success(
			[
				'info'  => [
					'page'    => 1,
					'pages'   => 1,
					'results' => count( $prepared_demos ),
				],
				'demos' => array_filter( $prepared_demos ),
			]
		);
	}

	/**
	 * Import demo.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function import_demo() {
		check_ajax_referer( 'updates' );

		if ( empty( $_POST['slug'] ) ) {
			wp_send_json_error(
				[
					'slug'         => '',
					'errorCode'    => 'no_demo_specified',
					'errorMessage' => __( 'No demo specified.', 'quick-demo-import' ),
				]
			);
		}

		$slug   = sanitize_key( wp_unslash( $_POST['slug'] ) );
		$status = [
			'import' => 'demo',
			'slug'   => $slug,
		];

		if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
			define( 'WP_LOAD_IMPORTERS', true );
		}

		if ( ! current_user_can( 'import' ) ) {
			$status['errorMessage'] = __( 'Sorry, you are not allowed to import content.', 'quick-demo-import' );
			wp_send_json_error( $status );
		}

		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		$skin         = new \WP_Ajax_Upgrader_Skin();
		$upgrader     = new DemoUpgrader( $skin );
		$packages     = isset( self::$demos_packages->demos ) ? json_decode( wp_json_encode( self::$demos_packages->demos ), true ) : [];
		$package_path = $packages[ $slug ]['zip'];
		$result       = $upgrader->install( $package_path );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$status['debug'] = $skin->get_upgrade_messages();
		}

		if ( is_wp_error( $result ) ) {
			$status['errorCode']    = $result->get_error_code();
			$status['errorMessage'] = $result->get_error_message();
			wp_send_json_error( $status );
		} elseif ( is_wp_error( $skin->result ) ) {
			$status['errorCode']    = $skin->result->get_error_code();
			$status['errorMessage'] = $skin->result->get_error_message();
			wp_send_json_error( $status );
		} elseif ( $skin->get_errors()->get_error_code() ) {
			$status['errorMessage'] = $skin->get_error_messages();
			wp_send_json_error( $status );
		} elseif ( is_null( $result ) ) {
			global $wp_filesystem;

			$status['errorCode']    = 'unable_to_connect_to_filesystem';
			$status['errorMessage'] = __( 'Unable to connect to the filesystem. Please confirm your credentials.', 'quick-demo-import' );

			// Pass through the error from WP_Filesystem if one was raised.
			if ( $wp_filesystem instanceof \WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
				$status['errorMessage'] = esc_html( $wp_filesystem->errors->get_error_message() );
			}

			wp_send_json_error( $status );
		}

		$demo_data            = $packages[ $slug ];
		$status['demoName']   = $demo_data['title'];
		$status['previewUrl'] = get_site_url( null, '/' );

		do_action( 'quick-demo-import/ajax_before_demo_import' );

		if ( ! empty( $demo_data ) ) {
			self::import_xml( $slug, $demo_data, $status );
			self::import_core_options( $demo_data );
			self::import_elementor_schemes( $demo_data );
			self::import_dat( $slug, $demo_data, $status );
			self::import_wie( $slug, $demo_data, $status );

			update_option( '_quick_demo_import_imported_demo_id', $slug );
			do_action( 'quick-demo-import/ajax_after_demo_import', $slug, $demo_data );
		}

		wp_send_json_success( $status );
	}

	/**
	 * Import XML.
	 *
	 * @param $id
	 * @param $data
	 * @param $status
	 * @return void
	 */
	private static function import_xml( $id, $data, $status ) {
		$import_file = self::get_import_file_path( 'xml' );

		do_action( 'quick-demo-import/ajax_before_xml_import', $data, $id );

		require_once ABSPATH . '/wp-admin/includes/class-wp-importer.php';

		if ( is_file( $import_file ) ) {
			$importer                    = new WXRImporter();
			$importer->fetch_attachments = true;

			ob_start();
			$importer->import( $import_file );
			ob_get_clean();

			do_action( 'quick-demo-import/ajax_after_xml_import', $data, $id );
		} else {
			$status['errorMessage'] = __( 'The XML file dummy content is missing.', 'quick-demo-import' );
			wp_send_json_error( $status );
		}
	}

	/**
	 * Import core options.
	 *
	 * @since 1.0.0
	 * @param $data
	 * @return void
	 */
	private static function import_core_options( $data ) {
		if ( ! empty( $data['core_options'] ) ) {
			foreach ( $data['core_options'] as $key => $value ) {
				if ( ! in_array( $key, [ 'blogname', 'blogdescription', 'show_on_front', 'page_on_front', 'page_for_posts' ], true ) ) {
					continue;
				}

				switch ( $key ) {
					case 'show_on_front':
						if ( in_array( $value, [ 'posts', 'page' ], true ) ) {
							update_option( 'show_on_front', $value );
						}
						break;
					case 'page_on_front':
					case 'page_for_posts':
						$page = get_page_by_title( $value );
						if ( is_object( $page ) && $page->ID ) {
							update_option( $key, $page->ID );
							update_option( 'show_on_front', 'page' );
						}
						break;
					default:
						update_option( $key, sanitize_text_field( $value ) );
						break;
				}
			}
		}
	}

	/**
	 * Import DAT customizer file.
	 *
	 * @since 1.0.0
	 * @param $id
	 * @param $data
	 * @param $status
	 * @return void
	 */
	private static function import_dat( $id, $data, $status ) {
		$import_file = self::get_import_file_path( 'dat' );

		do_action( 'quick-demo-import/aja_before_dat_import' );

		if ( is_file( $import_file ) ) {
			$results = CustomizerImporter::import( $import_file, $id, $data );

			do_action( 'quick-demo-import/aja_after_dat_import' );

			if ( is_wp_error( $results ) ) {
				wp_send_json_error( $results );
			}
		} else {
			$status['errorMessage'] = __( 'The DAT file customizer data is missing.', 'quick-demo-import' );
			wp_send_json_error( $status );
		}

	}

	/**
	 * Import WIE widgets file.
	 *
	 * @since 1.0.0
	 * @param $id
	 * @param $data
	 * @param $status
	 * @return void
	 */
	private static function import_wie( $id, $data, $status ) {
		$import_file = self::get_import_file_path( 'wie' );

		do_action( 'quick-demo-import/ajax_before_wie_import' );

		if ( is_file( $import_file ) ) {
			$results = WidgetImporter::import( $import_file, $id, $data );

			do_action( 'quick-demo-import/ajax_after_wie_import' );

			if ( is_wp_error( $results ) ) {
				wp_send_json_error( $results );
			}
		} else {
			$status['errorMessage'] = __( 'The WIE file widget content is missing.', 'quick-demo-import' );
			wp_send_json_error( $status );
		}
	}

	/**
	 * Install plugins.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function install_plugins() {

		check_ajax_referer( 'updates' );

		if ( empty( $_POST['plugin'] ) || empty( $_POST['slug'] ) ) {
			wp_send_json_error(
				array(
					'slug'         => '',
					'errorCode'    => 'no_plugin_specified',
					'errorMessage' => __( 'No plugin specified.', 'quick-demo-import' ),
				)
			);
		}

		$slug   = sanitize_key( wp_unslash( $_POST['slug'] ) );
		$plugin = plugin_basename( sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) );

		$status = [
			'install' => 'plugin',
			'slug'    => sanitize_key( wp_unslash( $_POST['slug'] ) ),
		];

		if ( ! current_user_can( 'install_plugins' ) ) {
			$status['errorMessage'] = __( 'Sorry, you are not allowed to install plugins on this site.', 'quick-demo-import' );
			wp_send_json_error( $status );
		}

		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		// Looks like a plugin is installed, but not active.
		if ( file_exists( WP_PLUGIN_DIR . '/' . $slug ) ) {
			$plugin_data          = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
			$status['plugin']     = $plugin;
			$status['pluginName'] = $plugin_data['Name'];

			if ( current_user_can( 'activate_plugin', $plugin ) && is_plugin_inactive( $plugin ) ) {
				$result = activate_plugin( $plugin );

				if ( is_wp_error( $result ) ) {
					$status['errorCode']    = $result->get_error_code();
					$status['errorMessage'] = $result->get_error_message();
					wp_send_json_error( $status );
				}

				wp_send_json_success( $status );
			}
		}

		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => sanitize_key( wp_unslash( $_POST['slug'] ) ),
				'fields' => array(
					'sections' => false,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			$status['errorMessage'] = $api->get_error_message();
			wp_send_json_error( $status );
		}

		$status['pluginName'] = $api->name;

		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $api->download_link );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$status['debug'] = $skin->get_upgrade_messages();
		}

		if ( is_wp_error( $result ) ) {
			$status['errorCode']    = $result->get_error_code();
			$status['errorMessage'] = $result->get_error_message();
			wp_send_json_error( $status );
		} elseif ( is_wp_error( $skin->result ) ) {
			$status['errorCode']    = $skin->result->get_error_code();
			$status['errorMessage'] = $skin->result->get_error_message();
			wp_send_json_error( $status );
		} elseif ( $skin->get_errors()->get_error_code() ) {
			$status['errorMessage'] = $skin->get_error_messages();
			wp_send_json_error( $status );
		} elseif ( is_null( $result ) ) {
			global $wp_filesystem;

			$status['errorCode']    = 'unable_to_connect_to_filesystem';
			$status['errorMessage'] = __( 'Unable to connect to the filesystem. Please confirm your credentials.', 'quick-demo-import' );

			// Pass through the error from WP_Filesystem if one was raised.
			if ( $wp_filesystem instanceof \WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
				$status['errorMessage'] = esc_html( $wp_filesystem->errors->get_error_message() );
			}

			wp_send_json_error( $status );
		}

		$install_status = install_plugin_install_status( $api );

		if ( current_user_can( 'activate_plugin', $install_status['file'] ) && is_plugin_inactive( $install_status['file'] ) ) {
			$result = activate_plugin( $install_status['file'] );

			if ( is_wp_error( $result ) ) {
				$status['errorCode']    = $result->get_error_code();
				$status['errorMessage'] = $result->get_error_message();
				wp_send_json_error( $status );
			}
		}

		wp_send_json_success( $status );
	}

	/**
	 * Import Elementor schemes.
	 *
	 * @since 1.0.0
	 * @param $data
	 * @return void
	 */
	private static function import_elementor_schemes( $data ) {

		$elementor_version = defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : false;

		if ( version_compare( $elementor_version, '3.0.0', '<=' ) ) {

			if ( ! empty( $data['elementor_schemes'] ) ) {
				foreach ( $data['elementor_schemes'] as $scheme_key => $scheme_value ) {
					if ( ! in_array( $scheme_key, [ 'color', 'typography', 'color-picker' ], true ) ) {
						continue;
					}

					// Change scheme index to start from 1 instead.
					$scheme_value = array_combine( range( 1, count( $scheme_value ) ), $scheme_value );

					if ( ! empty( $scheme_value ) ) {
						update_option( 'elementor_scheme_' . $scheme_key, $scheme_value );
					}
				}
			}
		}
	}
}
