<?php

/**
 * Getting Started - Welcome Screen View
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if (! defined('ABSPATH')) {
    exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF Copyright (C) 2015 Blue Liquid Designs

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

global $gfpdf;

?>

<div class="wrap about-wrap gfpdf-welcome-screen">
	<h1><?php _e( 'Welcome to Gravity PDF', 'gravity-forms-pdf-extended' ); ?></h1>
	<div class="about-text"><?php _e( "You're just minutes away from producing your first highly-customisable PDF document using Gravity Forms data.", 'gravity-forms-pdf-extended' ); ?></div>
	
	<div class="gfpdf-badge"><?php printf( __( 'Version %s', 'gravity-forms-pdf-extended' ), $args['display_version'] ); ?></div>

	<?php $this->tabs(); ?>
		

		<div class="feature-section two-col">

			<div class="col">
				<h3><?php _e( 'Where to Start?', 'gravity-forms-pdf-extended' );?></h3>

				<p>
				   <?php printf( __( "Your first step is to review %sGravity PDF's General Settings%s which can be found by navigating to %sForms -> Settings -> PDF%s in your WordPress admin area.
				   From here you'll be able to set defaults for paper size, font face, font colour, and select a PDF template – %swe ship with five completely-free layouts%s – which will be used for all new PDFs.
				   There's even an easy-to-use interface for installing custom fonts.", 'gravity-forms-pdf-extended' ), '<a href="' . esc_url($gfpdf->data->settings_url) . '">', '</a>', '<code>', '</code>', '<strong>', '</strong>' ); ?>
				</p>
			
				<a href="<?php echo esc_url( $gfpdf->data->settings_url ); ?>" class="button"><?php _e( 'Configure Settings', 'gravity-forms-pdf-extended' ); ?></a>
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
				<h3><?php _e( 'Setting up a PDF', 'gravity-forms-pdf-extended' );?></h3>

				<p>
				   <?php printf( __( 'You can setup individual PDF documents from the %sGravity Form "Forms" page%s in your admin area – located at %sForms -> Forms%s in your navigation.
				   A new %sPDF%s option will be avaliable in the forms\' settings section.
				   The only required fields are %sName%s – an internal identifier – and %sFilename%s – the name used when saving and emailing the PDF.', 'gravity-forms-pdf-extended' ), '<a href="' . esc_url( admin_url( 'admin.php?page=gf_edit_forms' ) ) . '">', '</a>', '<code>', '</code>', '<code>', '</code>', '<em>', '</em>', '<em>', '</em>' ); ?>
				</p>

				<!-- Output a quick Gravity Forms selector so we can let users get redirected to a PDF form of their choice -->
				<?php if(sizeof($args['forms']) > 0): ?>
					<form action="<?php echo admin_url( 'admin.php' ); ?>">
						<input type="hidden" name="page" value="gf_edit_forms" />
						<input type="hidden" name="view" value="settings" />
						<input type="hidden" name="subview" value="pdf" />
						<input type="hidden" name="pid" value="0" />

						<p><strong><?php _e( 'Select which Form you want to setup first:', 'gravity-forms-pdf-extended' ); ?></strong><br>
						<select name="id" class="">
							<?php foreach($args['forms'] as $form): ?>
								<option value="<?php echo $form['id']; ?>"><?php echo $form['title']; ?></option>
							<?php endforeach; ?>
						</select>

						<button class="button" style="vertical-align: middle"><?php _e( 'Create a PDF', 'gravity-forms-pdf-extended' ); ?></button>

						</p>

					</form>
				<?php endif; ?>
			</div>
		</div>

		<div id="gfpdf-mascot-container" class="changelog feature-section three-col">
			<div class="col">
				<img class="gfpdf-image" src="<?php echo esc_url( PDF_PLUGIN_URL . 'src/assets/images/welcome-download-shortcode.png' ); ?>">
				<h3><?php _e( 'Simple PDF Download Links', 'gravity-forms-pdf-extended' ); ?></h3>
				<p><?php printf( __( 'The %s[gravitypdf]%s shortcode allows you to %seasily place a PDF download link%s on any of the Gravity Forms Confirmation types.', 'gravity-forms-pdf-extended' ), '<code>', '</code>', '<a href="#">', '</a>' ); ?></p>
			</div>
			<div class="col">
				<img class="gfpdf-image" src="<?php echo esc_url( PDF_PLUGIN_URL . 'src/assets/images/welcome-notification-conditional.png' ); ?>">
				<h3><?php _e( 'Automated PDF Emails', 'gravity-forms-pdf-extended' ); ?></h3>
				<p><?php printf( __( 'Select a Gravity Form Notification and your PDF %swill automatically be sent as an attachment%s. Powerful conditional logic can also be used to determine if a PDF will be included.', 'gravity-forms-pdf-extended' ), '<a href="#">', '</a>' ); ?></p>
			</div>
			<div class="col last-feature">
				<img class="gfpdf-image" src="<?php echo esc_url( PDF_PLUGIN_URL . 'src/assets/images/welcome-manage-fonts.png' ); ?>">
				<h3><?php _e( 'Custom Fonts', 'gravity-forms-pdf-extended' ); ?></h3>
				<p><?php printf( __( 'Make your documents stand out by including your favourite fonts with our %ssimple font manager%s.', 'gravity-forms-pdf-extended' ), '<a href="#">', '</a>' ); ?></p>
			</div>
		</div>

		<div class="gfpdf-mascot-sitting"></div>

		

		<div class="changelog">
				<h3><?php _e( 'Get more out of Gravity PDF', 'gravity-forms-pdf-extended' ); ?></h3>

				<div class="feature-section three-col">
					<div class="col gfpdf-breakdown">

						<h4><?php _e( 'PDF Template Shop', 'gravity-forms-pdf-extended' ); ?></h4>
						<p><?php printf( __( "It's like a theme shop, but for Gravity PDF templates. %sHead over to our online store%s and view our growing selection of premium PDF templates.", 'gravity-forms-pdf-extended' ), '<a href="#">', '</a>' ); ?></p>

						<h4><?php _e( 'Stay Up To Date', 'gravity-forms-pdf-extended' ); ?></h4>
						<p><?php printf( __( '%sSign up to our newsletter%s to be amongst the first to receive the latest news and details on upcoming feature.', 'gravity-forms-pdf-extended' ), '<a href="#">', '</a>' ); ?></p>

					</div>

					<div class="col gfpdf-breakdown">

						<h4><?php _e( 'Tailored PDFs', 'gravity-forms-pdf-extended' ); ?></h4>
						<p><?php printf( __( "If the PDF Shop doesn't have what you're after %sour friendly team can build a document just for you%s. With an addon, our devs can even create templates that auto fill existing PDFs – like government and legal documents.", 'gravity-forms-pdf-extended' ), '<a href="#">', '</a>' ); ?></p>

						<h4><?php _e( 'Get Support', 'gravity-forms-pdf-extended' ); ?></h4>
						<p><?php printf( __( 'Have trouble using Gravity PDF? %sContact our friendly staff%s who are avaliable 9am to 5pm Monday to Friday, %sAustralian Eastern Standard Time%s.', 'gravity-forms-pdf-extended' ), '<a href="#">', '</a>', '<a href="http://www.timeanddate.com/worldclock/australia/sydney">', '</a>' ); ?></p>
						

					</div>

					<div class="col gfpdf-breakdown last-feature">

						<h4><?php _e( 'Roll your Own', 'gravity-forms-pdf-extended' ); ?></h4>
						<p><?php printf( __( "If PHP, HTML and CSS come easy to you, you'll find creating your own PDF templates a breeze. With %sextensive documentation and great examples%s you'll be up and running in no time.", 'gravity-forms-pdf-extended' ), '<a href="#">', '</a>' ); ?></p>

					</div>

				</div>

		</div>

</div>
