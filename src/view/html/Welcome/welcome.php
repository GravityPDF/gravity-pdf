<?php

/**
 * Getting Started - Welcome Screen View
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2018, Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

?>

<div class="wrap about-wrap gfpdf-welcome-screen">
	<h1><?php esc_html_e( 'Welcome to Gravity PDF', 'gravity-forms-pdf-extended' ); ?></h1>

	<div class="about-text">
		<?php esc_html_e( "You're just minutes away from producing your first highly-customizable PDF document using Gravity Forms data.", 'gravity-forms-pdf-extended' ); ?>
	</div>

	<div class="gfpdf-badge"><?php printf( esc_html__( 'Version %s', 'gravity-forms-pdf-extended' ), $args['display_version'] ); ?></div>

	<div class="feature-section two-col">

		<div class="col">
			<h3><?php esc_html_e( 'Where to Start?', 'gravity-forms-pdf-extended' ); ?></h3>

			<p>
				<?php printf( esc_html__( "Your first step is to review %sGravity PDF's General Settings%s which can be found by navigating to %sForms -> Settings -> PDF%s in your WordPress admin area. From here you'll be able to set defaults for paper size, font face, font colour, and select a PDF template – %swe ship with four completely-free layouts%s – which will be used for all new PDFs. There's even an easy-to-use interface for installing custom fonts.", 'gravity-forms-pdf-extended' ), '<a href="' . esc_url( $gfpdf->data->settings_url ) . '">', '</a>', '<code>', '</code>', '<strong>', '</strong>' ); ?>
			</p>

			<a href="<?php echo esc_url( $gfpdf->data->settings_url ); ?>" class="button"><?php esc_html_e( 'Configure Settings', 'gravity-forms-pdf-extended' ); ?></a>
		</div>

		<div class="col">
			<img class="gfpdf-image" src="<?php echo esc_url( PDF_PLUGIN_URL . 'src/assets/images/welcome-pdf-settings-page.png' ); ?>">
		</div>

	</div>

	<div class="feature-section two-col">

		<div class="col">
			<img class="gfpdf-image" src="<?php echo esc_url( PDF_PLUGIN_URL . 'src/assets/images/welcome-individual-pdf-settings.png' ); ?>">
		</div>

		<div class="col">
			<h3><?php esc_html_e( 'Setting up a PDF', 'gravity-forms-pdf-extended' ); ?></h3>

			<p>
				<?php printf( esc_html__( 'You can setup individual PDF documents from the %sGravity Form "Forms" page%s in your admin area – located at %sForms -> Forms%s in your navigation. A new %sPDF%s option will be avaliable in each forms\' settings section. The only required fields are %sName%s – an internal identifier – and %sFilename%s – the name used when saving and emailing the PDF.', 'gravity-forms-pdf-extended' ), '<a href="' . esc_url( admin_url( 'admin.php?page=gf_edit_forms' ) ) . '">', '</a>', '<code>', '</code>', '<code>', '</code>', '<em>', '</em>', '<em>', '</em>' ); ?>
			</p>

			<!-- Output a quick Gravity Forms selector so we can let users get redirected to a PDF form of their choice -->
			<?php if ( sizeof( $args['forms'] ) > 0 ): ?>
				<form action="<?php echo admin_url( 'admin.php' ); ?>">
					<input type="hidden" name="page" value="gf_edit_forms"/>
					<input type="hidden" name="view" value="settings"/>
					<input type="hidden" name="subview" value="pdf"/>
					<input type="hidden" name="pid" value="0"/>

					<p>
						<strong><?php esc_html_e( 'Select which Form you want to setup first:', 'gravity-forms-pdf-extended' ); ?></strong><br>
						<select name="id" class="">
							<?php foreach ( $args['forms'] as $form ): ?>
								<option value="<?php echo $form['id']; ?>"><?php echo $form['title']; ?></option>
							<?php endforeach; ?>
						</select>

						<button class="button" style="vertical-align: middle"><?php esc_html_e( 'Create a PDF', 'gravity-forms-pdf-extended' ); ?></button>
					</p>
				</form>
			<?php endif; ?>
		</div>
	</div>

	<div id="gfpdf-mascot-container" class="changelog feature-section three-col">
		<div class="col">
			<img class="gfpdf-image" src="<?php echo esc_url( PDF_PLUGIN_URL . 'src/assets/images/welcome-download-shortcode.png' ); ?>">

			<h3><?php esc_html_e( 'Simple PDF Download Links', 'gravity-forms-pdf-extended' ); ?></h3>

			<p><?php printf( esc_html__( 'The %s[gravitypdf]%s shortcode allows you to %seasily place a PDF download link%s on any of the Gravity Forms Confirmation types.', 'gravity-forms-pdf-extended' ), '<code>', '</code>', '<a href="https://gravitypdf.com/documentation/v4/user-shortcodes/">', '</a>' ); ?></p>
		</div>
		<div class="col">
			<img class="gfpdf-image" src="<?php echo esc_url( PDF_PLUGIN_URL . 'src/assets/images/welcome-notification-conditional.png' ); ?>">

			<h3><?php esc_html_e( 'Automated PDF Emails', 'gravity-forms-pdf-extended' ); ?></h3>

			<p><?php esc_html_e( 'Select a Gravity Form Notification and your PDF will automatically be sent as an attachment. Powerful conditional logic can also be used to determine if a PDF will be included.', 'gravity-forms-pdf-extended' ); ?></p>
		</div>
		<div class="col last-feature">
			<img class="gfpdf-image" src="<?php echo esc_url( PDF_PLUGIN_URL . 'src/assets/images/welcome-manage-fonts.png' ); ?>">

			<h3><?php esc_html_e( 'Custom Fonts', 'gravity-forms-pdf-extended' ); ?></h3>

			<p><?php printf( esc_html__( 'Make your documents stand out by including your favorite fonts with our %ssimple font manager%s.', 'gravity-forms-pdf-extended' ), '<a href="https://gravitypdf.com/documentation/v4/user-custom-fonts/">', '</a>' ); ?></p>
		</div>
	</div>

	<?php $this->more(); ?>

</div>
