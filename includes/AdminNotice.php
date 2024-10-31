<?php
/**
 * Class AdminNotice.
 *
 * @since 1.0.0
 * @package QuickDemoImport
 */

namespace QuickDemoImport;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdminNotice.
 *
 * @since 1.0.0
 */
class AdminNotice {

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
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function init_hooks() {
		add_action( 'admin_notices', [ __CLASS__, 'notice' ] );
	}

	/**
	 * Notice markup.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function notice() {
		if ( SupportedThemes::is_supported_theme() ) {
			return;
		}
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<?php
					printf(
						/* Translators: %s: Integration guide link. */
						esc_html__( 'Your active theme is not supported by Quick Demo Import. Visit this %s to integrate the Quick Demo Import plugin with your theme.', 'quick-demo-import' ),
						'<a href="https://quickdemoimport.com/#integration-guide" target="_blank" rel="nofollow">' . esc_html__( 'link', 'quick-demo-import' ) . '</a>'
					);
				?>
			</p>
		</div>
		<?php
	}
}
