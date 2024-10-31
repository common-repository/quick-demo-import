<?php
/**
 * Importer HTML.
 *
 * @since 1.0.0
 * @package QuickDemoImport
 */

defined( 'ABSPATH' ) || exit;

use QuickDemoImport\DemoImporter;
use QuickDemoImport\SupportedThemes;

$demo_packages = DemoImporter::$demos_packages;

?>
<div class="wrap demo-importer">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Demo Importer', 'quick-demo-import' ); ?></h1>
	<hr class="wp-header-end">
	<div class="error hide-if-js">
		<p><?php esc_html_e( 'The Demo Importer screen requires JavaScript.', 'quick-demo-import' ); ?></p>
	</div>

	<h2 class="screen-reader-text hide-if-no-js"><?php esc_html_e( 'Filter demos list', 'quick-demo-import' ); ?></h2>

	<div class="wp-filter hide-if-no-js">
		<div class="filter-section">
			<div class="filter-count">
				<span class="count theme-count demo-count"></span>
			</div>

			<?php if ( ! empty( $demo_packages->categories ) ) : ?>
				<ul class="filter-links categories">
					<?php foreach ( $demo_packages->categories as $slug => $label ) : ?>
						<li><a href="#" data-sort="<?php echo esc_attr( $slug ); ?>" class="category-tab"><?php echo esc_html( $label ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<div class="filter-section right">
			<?php if ( ! empty( $demo_packages->pagebuilders ) ) : ?>
				<ul class="filter-links pagebuilders">
					<?php foreach ( $demo_packages->pagebuilders as $slug => $label ) : ?>
						<?php if ( 'default' !== $slug ) : ?>
							<li><a href="#" data-type="<?php echo esc_attr( $slug ); ?>" class="pagebuilder-tab"><?php echo esc_html( $label ); ?></a></li>
						<?php else : ?>
							<li><a href="#" data-type="<?php echo esc_attr( $slug ); ?>" class="pagebuilder-tab tips" data-tip="<?php esc_attr_e( 'Without Page Builder', 'quick-demo-import' ); ?>"><?php echo esc_html( $label ); ?></a></li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<form class="search-form"></form>
		</div>
	</div>
	<h2 class="screen-reader-text hide-if-no-js"><?php esc_html_e( 'Themes list', 'quick-demo-import' ); ?></h2>
	<div class="theme-browser content-filterable"></div>
	<div class="theme-install-overlay wp-full-overlay expanded"></div>

	<p class="no-themes"><?php esc_html_e( 'No demos found. Try a different search.', 'quick-demo-import' ); ?></p>
	<span class="spinner"></span>
</div>

<script id="tmpl-demo" type="text/template">
	<# if ( data.screenshot_url ) { #>
	<div class="theme-screenshot">
		<img src="{{ data.screenshot_url }}" alt="" />
	</div>
	<# } else { #>
	<div class="theme-screenshot blank"></div>
	<# } #>
	<div class="theme-author">
		<?php
			/* translators: %s: Demo author name */
			printf( esc_html__( 'By %s', 'quick-demo-import' ), '{{{ data.author }}}' );
		?>
	</div>

	<div class="theme-id-container">
		<# if ( data.active ) { #>
		<h2 class="theme-name" id="{{ data.id }}-name">
			<?php
				/* translators: %s: Demo name */
				printf( __( '<span>Imported:</span> %s', 'quick-demo-import' ), '{{{ data.name }}}' ); // @codingStandardsIgnoreLine
			?>
		</h2>
		<# } else { #>
		<h2 class="theme-name" id="{{ data.id }}-name">{{{ data.name }}}</h2>
		<# } #>

		<div class="theme-actions">
			<# if ( data.active ) { #>
			<a class="button button-primary live-preview" target="_blank" href="<?php echo esc_url( get_site_url( null, '/' ) ); ?>"><?php esc_html_e( 'Live Preview', 'quick-demo-import' ); ?></a>
			<# } else { #>
			<?php
				/* translators: %s: Demo name */
				$aria_label = sprintf( esc_html_x( 'Import %s', 'demo', 'quick-demo-import' ), '{{ data.name }}' );
			?>
			<a class="button button-primary hide-if-no-js demo-import" href="#" data-name="{{ data.name }}" data-slug="{{ data.id }}" aria-label="<?php echo esc_attr( $aria_label ); ?>" data-plugins="{{ JSON.stringify( data.plugins ) }}"><?php esc_html_e( 'Import', 'quick-demo-import' ); ?></a>
			<# } #>
		</div>
	</div>

	<# if ( data.imported ) { #>
	<div class="notice notice-success notice-alt"><p><?php echo esc_html_x( 'Imported', 'demo', 'quick-demo-import' ); ?></p></div>
	<# } #>
</script>

<script id="tmpl-demo-preview" type="text/template">
	<div class="wp-full-overlay-sidebar">
		<div class="wp-full-overlay-header">
			<button class="close-full-overlay"><span class="screen-reader-text"><?php esc_html_e( 'Close', 'quick-demo-import' ); ?></span></button>
			<button class="previous-theme"><span class="screen-reader-text"><?php echo esc_html_x( 'Previous', 'Button label for a demo', 'quick-demo-import' ); ?></span></button>
			<button class="next-theme"><span class="screen-reader-text"><?php echo esc_html_x( 'Next', 'Button label for a demo', 'quick-demo-import' ); ?></span></button>
			<# if ( data.active ) { #>
			<a class="button button-primary live-preview" target="_blank" href="<?php echo esc_url( get_site_url( null, '/' ) ); ?>"><?php esc_html_e( 'Live Preview', 'quick-demo-import' ); ?></a>
			<# } else { #>
			<a class="button button-primary hide-if-no-js demo-import" href="#" data-name="{{ data.name }}" data-slug="{{ data.id }}"><?php esc_html_e( 'Import Demo', 'quick-demo-import' ); ?></a>
			<# } #>
		</div>
		<div class="wp-full-overlay-sidebar-content">
			<div class="install-theme-info">
				<h3 class="theme-name">
					{{ data.name }}
				</h3>
				<span class="theme-by">
					<?php
						/* translators: %s: Demo author name */
						printf( esc_html__( 'By %s', 'quick-demo-import' ), '{{ data.author }}' );
					?>
				</span>
				<img class="theme-screenshot" src="{{ data.screenshot_url }}" alt="" />
				<div class="theme-details">
					<div class="theme-version">
						<?php
							/* translators: %s: Demo version */
							printf( esc_html__( 'Version: %s', 'quick-demo-import' ), '{{ data.version }}', 'quick-demo-import' );
						?>
					</div>
					<div class="theme-description">{{{ data.description }}}</div>
				</div>

				<div class="plugins-details">
					<h4 class="plugins-info"><?php esc_html_e( 'Plugins Information', 'quick-demo-import' ); ?></h4>

					<table class="plugins-list-table widefat striped">
						<thead>
						<tr>
							<th scope="col" class="manage-column required-plugins" colspan="2"><?php esc_html_e( 'Required Plugins', 'quick-demo-import' ); ?></th>
						</tr>
						</thead>
						<tbody id="the-list">
						<# if ( ! _.isEmpty( data.plugins ) ) { #>
						<# _.each( data.plugins, function( plugin, slug ) { #>
						<tr class="plugin<# if ( ! plugin.is_active ) { #> inactive<# } #>" data-slug="{{ slug }}" data-plugin="{{ plugin.slug }}" data-name="{{ plugin.name }}">
							<td class="plugin-name">
								<a href="<?php printf( esc_url( 'https://wordpress.org/plugins/%s' ), '{{ slug }}' ); ?>" target="_blank">{{ plugin.name }}</a>
							</td>
							<td class="plugin-status">
								<# if ( plugin.is_active && plugin.is_install ) { #>
								<span class="active"></span>
								<# } else if ( plugin.is_install ) { #>
								<span class="activate-now<# if ( ! data.requiredPlugins ) { #> active<# } #>"></span>
								<# } else { #>
								<span class="install-now<# if ( ! data.requiredPlugins ) { #> active<# } #>"></span>
								<# } #>
							</td>
						</tr>
						<# }); #>
						<# } else { #>
						<tr class="no-items">
							<td class="colspanchange" colspan="4"><?php esc_html_e( 'No plugins are required for this demo.', 'quick-demo-import' ); ?></td>
						</tr>
						<# } #>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="wp-full-overlay-footer">
			<div class="demo-import-actions">
				<# if ( data.active ) { #>
				<a class="button button-primary live-preview button-hero hide-if-no-js" target="_blank" href="<?php echo esc_url( get_site_url( null, '/' ) ); ?>"><?php esc_html_e( 'Live Preview', 'quick-demo-import' ); ?></a>
				<# } else { #>
				<a class="button button-hero button-primary hide-if-no-js demo-import" href="#" data-name="{{ data.name }}" data-slug="{{ data.id }}"><?php esc_html_e( 'Import Demo', 'quick-demo-import' ); ?></a>
				<# } #>
			</div>
			<button type="button" class="collapse-sidebar button" aria-expanded="true" aria-label="<?php esc_attr_e( 'Collapse Sidebar', 'quick-demo-import' ); ?>">
				<span class="collapse-sidebar-arrow"></span>
				<span class="collapse-sidebar-label"><?php esc_html_e( 'Collapse', 'quick-demo-import' ); ?></span>
			</button>
			<div class="devices-wrapper">
				<div class="devices">
					<button type="button" class="preview-desktop active" aria-pressed="true" data-device="desktop">
						<span class="screen-reader-text"><?php esc_html_e( 'Enter desktop preview mode', 'quick-demo-import' ); ?></span>
					</button>
					<button type="button" class="preview-tablet" aria-pressed="false" data-device="tablet">
						<span class="screen-reader-text"><?php esc_html_e( 'Enter tablet preview mode', 'quick-demo-import' ); ?></span>
					</button>
					<button type="button" class="preview-mobile" aria-pressed="false" data-device="mobile">
						<span class="screen-reader-text"><?php esc_html_e( 'Enter mobile preview mode', 'quick-demo-import' ); ?></span>
					</button>
				</div>
			</div>
		</div>
	</div>
	<div class="wp-full-overlay-main">
		<iframe src="{{ data.preview_url }}" title="<?php esc_attr_e( 'Preview', 'quick-demo-import' ); ?>"></iframe>
	</div>
</script>
<?php
wp_print_request_filesystem_credentials_modal();
wp_print_admin_notice_templates();
qdi_print_admin_notice_templates();

