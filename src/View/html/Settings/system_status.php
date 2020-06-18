<?php

/**
 * System Status Settings View
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 *
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="hr-divider"></div>

<h3>
	<span>
		<i class="fa fa-dashboard"></i>
		<?php esc_html_e( 'Installation Status', 'gravity-forms-pdf-extended' ); ?>
	</span>
</h3>

<table id="pdf-system-status" class="form-table">
	<tr>
		<th scope="row">
			<?php esc_html_e( 'WP Memory Available', 'gravity-forms-pdf-extended' ); ?> <?php gform_tooltip( 'pdf_status_wp_memory' ); ?>
		</th>

		<td>
			<?php
			$ram_icon = 'fa fa-check-circle';
			if ( $args['memory'] < 128 && $args['memory'] !== -1 ) {
				$ram_icon = 'fa fa-exclamation-triangle';
			}
			?>

			<?php if ( $args['memory'] === -1 ): ?>
				<?php echo esc_html__( 'Unlimited', 'gravity-forms-pdf-extended' ); ?>
			<?php else : ?>
				<?php echo $args['memory']; ?>MB
			<?php endif; ?>

			<span class="<?php echo $ram_icon; ?>"></span>

			<?php if ( $args['memory'] < 128 && $args['memory'] !== -1 ): ?>
				<span class="gf_settings_description">
					<?php echo sprintf( esc_html__( 'We strongly recommend you have at least 128MB of available WP Memory (RAM) assigned to your website. %1$sFind out how to increase this limit%2$s.', 'gravity-forms-pdf-extended' ), '<br /><a href="https://gravitypdf.com/documentation/v5/user-increasing-memory-limit/">', '</a>' ); ?>
				</span>
			<?php endif; ?>
		</td>
	</tr>

	<tr>
		<th scope="row">
			<?php esc_html_e( 'WordPress Version', 'gravity-forms-pdf-extended' ); ?>
		</th>

		<td>
			<?php echo $args['wp']; ?>
		</td>
	</tr>

	<tr>
		<th scope="row">
			<?php esc_html_e( 'Gravity Forms Version', 'gravity-forms-pdf-extended' ); ?>
		</th>

		<td>
			<?php echo $args['gf']; ?>
		</td>
	</tr>

	<tr>
		<th scope="row">
			<?php esc_html_e( 'PHP Version', 'gravity-forms-pdf-extended' ); ?>
		</th>

		<td>
			<?php echo $args['php']; ?>
		</td>
	</tr>

	<tr>
		<th scope="row">
			<?php esc_html_e( 'allow_url_fopen', 'gravity-forms-pdf-extended' ); ?> <?php gform_tooltip( 'pdf_allow_url_fopen' ); ?>
		</th>

		<td>
			<?php
			$allow_url_fopen_icon = 'fa fa-check-circle';
			if ( ! $args['allow_url'] ) {
				$allow_url_fopen_icon = 'fa fa-exclamation-triangle';
			}
			?>

			<?= $args['allow_url'] ? esc_html__( 'Enabled', 'gravity-forms-pdf-extended' ) : esc_html__( 'Disabled', 'gravity-forms-pdf-extended' ) ?>

			<span class="<?php echo $allow_url_fopen_icon; ?>"></span>

			<?php if ( ! $args['allow_url'] ): ?>
				<span class="gf_settings_description">
					<?php echo sprintf( esc_html__( 'We detected the PHP runtime configuration setting %1$sallow_url_fopen%2$s is disabled.', 'gravity-forms-pdf-extended' ), '<a href="https://www.php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen"><code>', '</code></a>' ); ?> <br>
					<?php echo esc_html__( 'You may notice image display issues in your PDFs. Contact your web hosting provider for assistance enabling this feature.', 'gravity-forms-pdf-extended' ); ?>
				</span>
			<?php endif; ?>
		</td>
	</tr>

	<tr>
		<th scope="row">
			<?php esc_html_e( 'Direct PDF Protection', 'gravity-forms-pdf-extended' ); ?> <?php gform_tooltip( 'pdf_protection' ); ?>
		</th>

		<td>

			<!-- A placeholder for our JS which will do the check for us, thereby preventing any load time by checking in PHP directly -->
			<div id="gfpdf-direct-pdf-protection-check" data-nonce="<?php echo wp_create_nonce( 'gfpdf-direct-pdf-protection' ); ?>">
				<noscript><?php esc_html_e( 'You need JavaScript enabled to perform this check.', 'gravity-forms-pdf-extended' ); ?></noscript>

				<div id="gfpdf-direct-pdf-check-protected" style="display: none">
					<?php esc_html_e( 'Protected', 'gravity-forms-pdf-extended' ); ?> <span class="fa fa-check-circle"></span>
				</div>

				<div id="gfpdf-direct-pdf-check-unprotected" style="display: none">
					<strong><?php esc_html_e( 'Unprotected', 'gravity-forms-pdf-extended' ); ?></strong> <span class="fa fa-times-circle"></span>

					<span class="gf_settings_description">
						<?php printf( esc_html__( "We've detected the PDFs saved in Gravity PDF's %1\$stmp%2\$s directory can be publically accessed.", 'gravity-forms-pdf-extended' ), '<code>', '</code>' ); ?><br>
						<?php printf( esc_html__( 'We recommend you use our %1$sgfpdf_tmp_location%2$s filter to %3$smove the folder outside your public website directory%4$s.', 'gravity-forms-pdf-extended' ), '<code>', '</code>', '<a href="https://gravitypdf.com/documentation/v5/gfpdf_tmp_location/">', '</a>' ); ?>
					</span>
				</div>
			</div>
		</td>
	</tr>

</table>
