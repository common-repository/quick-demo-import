<?php
/**
 * Class Hooks.
 *
 * @since 1.0.0
 * @package QuickDemoImport
 */

namespace QuickDemoImport;

defined( 'ABSPATH' ) || exit;

use Exception;

/**
 * Class Hooks.
 *
 * @since 1.0.0
 */
class Hooks {

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
		add_action( 'quick-demo-import/ajax_before_demo_import', [ __CLASS__, 'reset' ] );
		add_action( 'quick-demo-import/ajax_after_demo_import', [ __CLASS__, 'update' ], 10, 2 );
	}

	/**
	 * Reset.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function reset() {
		self::reset_widgets();
		self::reset_menu();
		self::reset_theme_mods();
	}

	/**
	 * Update.
	 *
	 * @since 1.0.0
	 * @param $id
	 * @param $data
	 * @return void
	 * @throws Exception
	 */
	public static function update( $id, $data ) {
		self::update_elementor_data( $data );
		self::update_siteorigin_data( $data );
		self::wc_page_setup( $id );
		self::masteriyo_page_setup( $id );
		self::update_masteriyo_settings( $data );
	}

	/**
	 * Reset widgets.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function reset_widgets() {
		$sidebars_widgets = wp_get_sidebars_widgets();

		// Reset active widgets.
		foreach ( $sidebars_widgets as $key => $widgets ) {
			$sidebars_widgets[ $key ] = array();
		}

		wp_set_sidebars_widgets( $sidebars_widgets );
	}

	/**
	 * Reset menu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function reset_menu() {
		$nav_menus = wp_get_nav_menus();

		// Delete navigation menus.
		if ( ! empty( $nav_menus ) ) {
			foreach ( $nav_menus as $nav_menu ) {
				wp_delete_nav_menu( $nav_menu->slug );
			}
		}
	}

	/**
	 * Reset theme mods.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function reset_theme_mods() {
		remove_theme_mods();
	}

	/**
	 * Update Elementor data.
	 *
	 * @since 1.0.0
	 * @param $data
	 * @return void
	 */
	private static function update_elementor_data( $data ) {
		if ( ! empty( $data['elementor_data_update'] ) ) {
			foreach ( $data['elementor_data_update'] as $data_type => $value ) {
				if ( ! empty( $value['post_title'] ) ) {
					$page = get_page_by_title( $value['post_title'] );

					if ( is_object( $page ) && $page->ID ) {
						$elementor_data = get_post_meta( $page->ID, '_elementor_data', true );

						if ( ! empty( $elementor_data ) ) {
							$elementor_data = self::elementor_recursive_update( $elementor_data, $value );
						}

						// Update elementor data.
						update_post_meta( $page->ID, '_elementor_data', $elementor_data );
					}
				}
			}
		}
	}

	/**
	 * Update Elementor data recursively.
	 *
	 * @since 1.0.0
	 * @param $data
	 * @param $value
	 * @return false|string
	 */
	private static function elementor_recursive_update( $data, $value ) {
		$elementor_data = json_decode( stripslashes( $data ), true );

		// Recursively update elementor data.
		foreach ( $elementor_data as $element_id => $element_data ) {
			if ( ! empty( $element_data['elements'] ) ) {
				foreach ( $element_data['elements'] as $el_key => $el_data ) {
					if ( ! empty( $el_data['elements'] ) ) {
						foreach ( $el_data['elements'] as $el_child_key => $child_el_data ) {
							if ( 'widget' === $child_el_data['elType'] ) {
								$settings   = $child_el_data['settings'] ?? array();
								$widgetType = $child_el_data['widgetType'] ?? ''; // phpcs:ignore

								if ( isset( $settings['display_type'] ) && 'categories' === $settings['display_type'] ) {
									$categories_selected = $settings['categories_selected'] ?? '';

									if ( ! empty( $value['data_update'] ) ) {
										foreach ( $value['data_update'] as $taxonomy => $taxonomy_data ) {
											if ( ! taxonomy_exists( $taxonomy ) ) {
												continue;
											}

											foreach ( $taxonomy_data as $widget_id => $widget_data ) {
												if ( ! empty( $widget_data ) && $widget_id === $widgetType ) { // phpcs:ignore
													if ( is_array( $categories_selected ) ) {
														foreach ( $categories_selected as $cat_key => $cat_id ) {
															if ( isset( $widget_data[ $cat_id ] ) ) {
																$term = get_term_by( 'name', $widget_data[ $cat_id ], $taxonomy );

																if ( is_object( $term ) && $term->term_id ) {
																	$categories_selected[ $cat_key ] = $term->term_id;
																}
															}
														}
													} elseif ( isset( $widget_data[ $categories_selected ] ) ) {
														$term = get_term_by( 'name', $widget_data[ $categories_selected ], $taxonomy );

														if ( is_object( $term ) && $term->term_id ) {
															$categories_selected = $term->term_id;
														}
													}
												}
											}
										}
									}

									// Update the elementor data.
									$elementor_data[ $element_id ]['elements'][ $el_key ]['elements'][ $el_child_key ]['settings']['categories_selected'] = $categories_selected;
								}
							}
						}
					}
				}
			}
		}

		return wp_json_encode( $elementor_data );
	}

	/**
	 * Update SiteOrigin Panels data.
	 *
	 * @since 1.0.0
	 * @param $data
	 * @return void
	 * @throws Exception
	 */
	private static function update_siteorigin_data( $data ) {
		if ( ! empty( $data['siteorigin_panels_data_update'] ) ) {
			foreach ( $data['siteorigin_panels_data_update'] as $data_type => $data_value ) {
				if ( ! empty( $data_value['post_title'] ) ) {
					$page = get_page_by_title( $data_value['post_title'] );

					if ( is_object( $page ) && $page->ID ) {
						$panels_data = get_post_meta( $page->ID, 'panels_data', true );

						if ( ! empty( $panels_data ) ) {
							$panels_data = self::siteorigin_recursive_update( $panels_data, $data_type, $data_value );
						}

						// Update siteorigin panels data.
						update_post_meta( $page->ID, 'panels_data', $panels_data );
					}
				}
			}
		}
	}

	/**
	 * Update SiteOrigin Panels data recursively.
	 *
	 * @since 1.0.0
	 * @param $data
	 * @param $type
	 * @param $value
	 * @return array Panels data
	 * @throws Exception
	 */
	private static function siteorigin_recursive_update( $data, $type, $value ): array {
		static $instance = 0;

		foreach ( $data as $panel_type => $panel_data ) {
			// Format the value based on panel type.
			switch ( $panel_type ) {
				case 'grids':
					foreach ( $panel_data as $instance_id => $grid_instance ) {
						if ( ! empty( $value['data_update']['grids_data'] ) ) {
							foreach ( $value['data_update']['grids_data'] as $grid_id => $grid_data ) {
								if ( ! empty( $grid_data['style'] ) && $instance_id === $grid_id ) {
									$level = $grid_data['level'] ?? 0;
									if ( $level === $instance ) {
										foreach ( $grid_data['style'] as $style_key => $style_value ) {
											if ( empty( $style_value ) ) {
												continue;
											}

											// Format the value based on style key.
											switch ( $style_key ) {
												case 'background_image_attachment':
													$attachment_id = qdi_get_attachment_id( $style_value );

													if ( 0 !== $attachment_id ) {
														$grid_instance['style'][ $style_key ] = $attachment_id;
													}
													break;
												default:
													$grid_instance['style'][ $style_key ] = $style_value;
													break;
											}
										}
									}
								}
							}
						}

						// Update panel grids data.
						$data['grids'][ $instance_id ] = $grid_instance;
					}
					break;

				case 'widgets':
					foreach ( $panel_data as $instance_id => $widget_instance ) {
						if ( isset( $widget_instance['panels_data']['widgets'] ) ) {
							++$instance;
							$child_panels_data                              = $widget_instance['panels_data'];
							$data['widgets'][ $instance_id ]['panels_data'] = self::siteorigin_recursive_update( $child_panels_data, $type, $value );
							--$instance;
							continue;
						}

						if ( isset( $widget_instance['nav_menu'] ) && isset( $widget_instance['title'] ) ) {
							$nav_menu = wp_get_nav_menu_object( $widget_instance['title'] );

							if ( is_object( $nav_menu ) && $nav_menu->term_id ) {
								$widget_instance['nav_menu'] = $nav_menu->term_id;
							}
						} elseif ( ! empty( $value['data_update']['widgets_data'] ) ) {
							$instance_class = $widget_instance['panels_info']['class'];

							foreach ( $value['data_update']['widgets_data'] as $dropdown_type => $dropdown_data ) {
								if ( ! in_array( $dropdown_type, array( 'dropdown_pages', 'dropdown_categories' ), true ) ) {
									continue;
								}

								// Format the value based on data type.
								switch ( $dropdown_type ) {
									case 'dropdown_pages':
										foreach ( $dropdown_data as $widget_id => $widget_data ) {
											if ( ! empty( $widget_data[ $instance_id ] ) && $widget_id === $instance_class ) {
												$level = $widget_data['level'] ?? 0;

												if ( $level === $instance ) {
													foreach ( $widget_data[ $instance_id ] as $widget_key => $widget_value ) {
														$page = get_page_by_title( $widget_value );

														if ( is_object( $page ) && $page->ID ) {
															$widget_instance[ $widget_key ] = $page->ID;
														}
													}
												}
											}
										}
										break;
									default:
									case 'dropdown_categories':
										foreach ( $dropdown_data as $taxonomy => $taxonomy_data ) {
											if ( ! taxonomy_exists( $taxonomy ) ) {
												continue;
											}

											foreach ( $taxonomy_data as $widget_id => $widget_data ) {
												if ( ! empty( $widget_data[ $instance_id ] ) && $widget_id === $instance_class ) {
													$level = $widget_data['level'] ?? 0;

													if ( $level === $instance ) {
														foreach ( $widget_data[ $instance_id ] as $widget_key => $widget_value ) {
															$term = get_term_by( 'name', $widget_value, $taxonomy );

															if ( is_object( $term ) && $term->term_id ) {
																$widget_instance[ $widget_key ] = $term->term_id;
															}
														}
													}
												}
											}
										}
										break;
								}
							}
						}

						$data['widgets'][ $instance_id ] = $widget_instance;
					}
					break;
				default:
					throw new Exception( 'Unexpected value' );
			}
		}

		return $data;
	}

	private static function wc_page_setup( $id ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		$pages    = apply_filters(
			"quick-demo-import/wc_{$id}_pages",
			array(
				'shop'      => array(
					'name'  => 'shop',
					'title' => 'Shop',
				),
				'cart'      => array(
					'name'  => 'cart',
					'title' => 'Cart',
				),
				'checkout'  => array(
					'name'  => 'checkout',
					'title' => 'Checkout',
				),
				'myaccount' => array(
					'name'  => 'my-account',
					'title' => 'My Account',
				),
			)
		);
		$callback = function( $key, $page, $page_id ) {
			update_option( 'woocommerce_' . $key . '_page_id', $page_id );
		};
		self::set_up_pages( $pages, $callback );
	}

	private static function masteriyo_page_setup( $id ) {
		if ( ! function_exists( 'masteriyo' ) ) {
			return;
		}
		$pages    = apply_filters(
			"quick-demo-import/masteriyo_{$id}_pages",
			array(
				'courses'                 => array(
					'name'         => 'courses',
					'title'        => 'Courses',
					'setting_name' => 'courses_page_id',
				),
				'account'                 => array(
					'name'         => 'account',
					'title'        => 'Account',
					'setting_name' => 'account_page_id',
				),
				'checkout'                => array(
					'name'         => 'checkout',
					'title'        => 'Checkout',
					'setting_name' => 'checkout_page_id',
				),
				'learn'                   => array(
					'name'         => 'learn',
					'title'        => 'Learn',
					'setting_name' => 'learn_page_id',
				),
				'instructor-registration' => array(
					'name'         => 'instructor-registration',
					'title'        => 'Instructor Registration',
					'setting_name' => 'instructor_registration_page_id',
				),
			)
		);
		$callback = function( $key, $page, $page_id ) {
			$setting_name = $page['setting_name'];
			$version      = masteriyo_get_version();
			$tab          = version_compare( '1.5.4', $version, '<=' ) ? 'general' : 'advance';
			function_exists( 'masteriyo_set_setting' ) && masteriyo_set_setting( "$tab.pages.{$setting_name}", $page_id );
		};
		self::set_up_pages( $pages, $callback );
	}

	/**
	 * Setup pages.
	 *
	 * @param array $pages Pages array.
	 * @param callable $callback Callback.
	 * @return void
	 */
	private static function set_up_pages( array $pages, callable $callback ) {
		global $wpdb;

		foreach ( $pages as $key => $plugin_page ) {
			$page_ids = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE (post_name = %s OR post_title = %s) AND post_type = 'page' AND post_status = 'publish'", $plugin_page['name'], $plugin_page['title'] ) );
			if ( ! is_null( $page_ids ) ) {
				$page_id    = 0;
				$delete_ids = [];

				if ( count( $page_ids ) > 1 ) {
					foreach ( $page_ids as $page ) {
						if ( $page->ID > $page_id ) {
							if ( $page_id ) {
								$delete_ids[] = $page_id;
							}

							$page_id = $page->ID;
						} else {
							$delete_ids[] = $page->ID;
						}
					}
				} else {
					$page_id = $page_ids[0]->ID;
				}

				// Delete posts.
				foreach ( $delete_ids as $delete_id ) {
					wp_delete_post( $delete_id, true );
				}

				if ( $page_id > 0 ) {
					wp_update_post(
						array(
							'ID'        => $page_id,
							'post_name' => sanitize_title( $plugin_page['name'] ),
						)
					);

					call_user_func( $callback, $key, $plugin_page, $page_id );
				}
			}
		}
	}

	/**
	 * Update Masteriyo settings.
	 *
	 * @param $data
	 * @return void
	 */
	private static function update_masteriyo_settings( $data ) {
		if ( ! function_exists( 'masteriyo' ) ) {
			return;
		}
		if ( empty( $data['masteriyo_settings'] ) ) {
			return;
		}
		foreach ( $data['masteriyo_settings'] as $key => $value ) {
			function_exists( 'masteriyo_set_setting' ) && masteriyo_set_setting( $key, $value );
		}
	}
}
