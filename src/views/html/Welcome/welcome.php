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

<div class="wrap about-wrap">
	<h1><?php _e( 'Welcome to Gravity PDF', 'gravitypdf' ); ?></h1>
	<div class="about-text"><?php _e( "You're just minutes away from producing your first highly-customisable PDF document using Gravity Forms data.", 'gravitypdf' ); ?></div>
	
	<div class="gfpdf-badge"><?php printf( __( 'Version %s', 'gravitypdf' ), $args['display_version'] ); ?></div>

	<?php $this->tabs(); ?>
		

		<div class="feature-section two-col">

			<div class="col">
				<h3><?php _e( 'Where to Start?', 'gravitypdf' );?></h3>

				<p>
				   Your first step is to review <a href="<?php echo esc_url($gfpdf->data->settings_url); ?>">Gravity PDF's General Settings</a> which can be found by navigating to <code>Forms -> Settings -> PDF</code> in your WordPress admin area.
				   From here you'll be able to set defaults for paper size, font face, font colour, and select a PDF template – <strong>we ship with five completely-free layouts</strong> – which will be used for all new PDFs.
				   There's even an easy-to-use interface for installing custom fonts.
				</p>
			
				<a href="<?php echo esc_url($gfpdf->data->settings_url); ?>" class="button">Configure Settings</a>
			</div>

			<div class="col">
				<img class="gfpdf-image" src="<?php echo esc_url(PDF_PLUGIN_URL . 'src/assets/images/welcome-pdf-settings-page.png'); ?>">
			</div>

		</div>

		<div class="feature-section two-col">

			<div class="col">
				<img class="gfpdf-image" src="<?php echo esc_url(PDF_PLUGIN_URL . 'src/assets/images/welcome-individual-pdf-settings.png'); ?>">
			</div>

			<div class="col">
				<h3><?php _e( 'Setting up a PDF', 'gravitypdf' );?></h3>

				<p>
				   You can setup individual PDF documents from the <a href="<?php echo esc_url( admin_url( "admin.php?page=gf_edit_forms" ) ); ?>">Gravity Form "Forms" page</a> in your admin area – located at <code>Forms -> Forms</code> in your navigation.
				   A new <code>PDF</code> option will be avaliable in the forms' settings section.
				   The only required fields are <em>Name</em> – an internal identifier – and <em>Filename</em> – the name used when saving and emailing the PDF.
				
				</p>

				<!-- Output a quick Gravity Forms selector so we can let users get redirected to a PDF form of their choice -->
				<?php if(sizeof($args['forms']) > 0): ?>
					<form action="<?php echo admin_url( 'admin.php' ); ?>">
						<input type="hidden" name="page" value="gf_edit_forms" />
						<input type="hidden" name="view" value="settings" />
						<input type="hidden" name="subview" value="pdf" />
						<input type="hidden" name="pid" value="0" />

						<p><strong>Select which Form you want to setup first:</strong><br>
						<select name="id" class="">
							<?php foreach($args['forms'] as $form): ?>
								<option value="<?php echo $form['id']; ?>"><?php echo $form['title']; ?></option>
							<?php endforeach; ?>
						</select>

						<button class="button" style="vertical-align: middle">Create a PDF</button>

						</p>

					</form>
				<?php endif; ?>
			</div>
		</div>

		<div id="gfpdf-mascot-container" class="changelog feature-section three-col">
			<div class="col">
				<img class="gfpdf-image" src="<?php echo esc_url(PDF_PLUGIN_URL . 'src/assets/images/welcome-download-shortcode.png'); ?>">
				<h3>Simple PDF Download Links</h3>
				<p>The <code>[gravitypdf]</code> shortcode allows you to <a href="#">easily place a PDF download link</a> on any of the Gravity Forms Confirmation types.</p>
			</div>
			<div class="col">
				<img class="gfpdf-image" src="<?php echo esc_url(PDF_PLUGIN_URL . 'src/assets/images/welcome-notification-conditional.png'); ?>">
				<h3>Automated PDF Emails</h3>
				<p>Select a Gravity Form Notification and your PDF <a href="#">will automatically be sent as an attachment</a>. Powerful conditional logic can also be used to determine if a PDF will be included.</p>
			</div>
			<div class="col last-feature">
				<img class="gfpdf-image" src="<?php echo esc_url(PDF_PLUGIN_URL . 'src/assets/images/welcome-manage-fonts.png'); ?>">
				<h3>Custom Fonts</h3>
				<p>Make your documents stand out by including your favourite fonts with our <a href="#">simple font manager</a>.</p>
			</div>
		</div>

		<div class="gfpdf-mascot-sitting"></div>

		

		<div class="changelog">
				<h3>Get more out of Gravity PDF</h3>

				<div class="feature-section three-col">
					<div class="col gfpdf-breakdown">

						<h4>PDF Template Shop</h4>
						<p>It's like a theme shop, but for Gravity PDF templates. <a href="#">Head over to our online store</a> and view our growing selection of premium PDF templates.</p>

						<h4>Stay Up To Date</h4>
						<p><a href="#">Sign up to our newsletter</a> to be amongst the first to receive the latest news and details on upcoming feature.</p>

					</div>

					<div class="col gfpdf-breakdown">

						<h4>Tailored PDFs</h4>
						<p>If the PDF Shop doesn't have what you're after <a href="#">our friendly team can build a document just for you</a>. With an addon, our devs can even create templates that auto fill existing PDFs – like government and legal documents.</p>

						<h4>Get Support</h4>
						<p>Have trouble using Gravity PDF? <a href="#">Contact our friendly staff</a> who are avaliable 9am to 5pm Monday to Friday, <a href="http://www.timeanddate.com/worldclock/australia/sydney">Australian Eastern Standard Time</a>.</p>
						

					</div>

					<div class="col gfpdf-breakdown last-feature">

						<h4>Roll your Own</h4>
						<p>If PHP, HTML and CSS come easy to you, you'll find creating your own PDF templates a breeze. With <a href="#">extensive documentation and great examples</a> you'll be up and running in no time.</p>

					</div>

				</div>

		</div>

</div>
