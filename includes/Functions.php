<?php
/**
 * QuickDemoImport fucntions
 */

/**
 * Get attachment ID.
 *
 * @since 1.0.0
 * @param $filename
 * @return int|WP_Post
 */
function qdi_get_attachment_id( $filename ) {

	$attachment_id = 0;

	$file = basename( $filename );

	$query_args = array(
		'post_type'   => 'attachment',
		'post_status' => 'inherit',
		'fields'      => 'ids',
		'meta_query'  => array(
			array(
				'value'   => $file,
				'compare' => 'LIKE',
				'key'     => '_wp_attachment_metadata',
			),
		),
	);

	$query = new WP_Query( $query_args );

	if ( $query->have_posts() ) {

		foreach ( $query->posts as $post_id ) {

			$meta = wp_get_attachment_metadata( $post_id );

			$original_file       = basename( $meta['file'] );
			$cropped_image_files = wp_list_pluck( $meta['sizes'], 'file' );

			if ( $original_file === $file || in_array( $file, $cropped_image_files, true ) ) {
				$attachment_id = $post_id;
				break;
			}
		}
	}

	return $attachment_id;
}

/**
 * Admin notice template.
 *
 * @since 1.0.0
 * @return void
 */
function qdi_print_admin_notice_templates() {
	?>
	<script id="tmpl-wp-installs-admin-notice" type="text/html">
		<div <# if ( data.id ) { #>id="{{ data.id }}"<# } #> class="notice {{ data.className }}"><p>{{{ data.message }}}</p></div>
	</script>
	<script id="tmpl-wp-bulk-installs-admin-notice" type="text/html">
		<div id="{{ data.id }}" class="{{ data.className }} notice <# if ( data.errors ) { #>notice-error<# } else { #>notice-success<# } #>">
			<p>
				<# if ( data.successes ) { #>
				<# if ( 1 === data.successes ) { #>
				<# if ( 'plugin' === data.type ) { #>
				<?php
					/* translators: %s: Number of plugins */
					printf( esc_html__( '%s plugin successfully installed.', 'quick-demo-import' ), '{{ data.successes }}' );
				?>
				<# } #>
				<# } else { #>
				<# if ( 'plugin' === data.type ) { #>
				<?php
					/* translators: %s: Number of plugins */
					printf( esc_html__( '%s plugins successfully installed.', 'quick-demo-import' ), '{{ data.successes }}' );
				?>
				<# } #>
				<# } #>
				<# } #>
				<# if ( data.errors ) { #>
				<button class="button-link bulk-action-errors-collapsed" aria-expanded="false">
					<# if ( 1 === data.errors ) { #>
					<?php
						/* translators: %s: Number of failed installs */
						printf( esc_html__( '%s install failed.', 'quick-demo-import' ), '{{ data.errors }}' );
					?>
					<# } else { #>
					<?php
						/* translators: %s: Number of failed installs */
						printf( esc_html__( '%s installs failed.', 'quick-demo-import' ), '{{ data.errors }}' );
					?>
					<# } #>
					<span class="screen-reader-text"><?php esc_html_e( 'Show more details', 'quick-demo-import' ); ?></span>
					<span class="toggle-indicator" aria-hidden="true"></span>
				</button>
				<# } #>
			</p>
			<# if ( data.errors ) { #>
			<ul class="bulk-action-errors hidden">
				<# _.each( data.errorMessages, function( errorMessage ) { #>
				<li>{{ errorMessage }}</li>
				<# } ); #>
			</ul>
			<# } #>
		</div>
	</script>
	<?php
}
