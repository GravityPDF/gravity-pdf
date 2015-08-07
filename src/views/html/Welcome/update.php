<?php

/**
 * Update Screen - Welcome Screen View
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
	<h1><?php printf( __( 'Discover Gravity PDF %s', 'gravitypdf' ), $args['display_version'] ); ?></h1>
	
  <div class="about-text"><?php _e( "Gravity PDF has been completely rebuilt with simplicity, stability and security in mind. Our team has spent six months making a great product even greater. Discover what's new...", 'gravitypdf' ); ?></div>
	
	<div class="gfpdf-badge"><?php printf( __( 'Version %s', 'gravitypdf' ), $args['display_version'] ); ?></div>

	<?php $this->tabs(); ?>


		<div class="headline-feature feature-video">
			<img class="gfpdf-image" src="<?php echo esc_url(PDF_PLUGIN_URL . 'src/assets/images/video-placeholder.png'); ?>">
		</div>

		<hr />

		<div class="feature-section two-col">

			<div class="col">
				<h3><?php _e( 'Bringing Config Settings to WordPress', 'gravitypdf' );?></h3>

				<p>
				   We've done away with the need to edit PHP files to configure Gravity PDF.
				   From now on you'll have a familiar and seamless experience controlling PDF settings direct from your WordPress Admin area.
				</p>

				<p>
				   Ever configured a Gravity Form add on? You'll feel right at home setting up PDFs.
				</p>

				<p></p>
			
			</div>

			<div class="col">
				<img class="gfpdf-image" src="<?php echo esc_url(PDF_PLUGIN_URL . 'src/assets/images/updated-new-ui.png'); ?>">
			</div>

		</div>


		<div class="feature-section two-col">

			<div class="col">
				<img class="gfpdf-image" src="<?php echo esc_url(PDF_PLUGIN_URL . 'src/assets/images/updated-css-ready-class-templates.png'); ?>">
			</div>

			<div class="col">
				<h3><?php _e( 'All-New Templates with CSS Ready Class Support', 'gravitypdf' );?></h3>

				<p>
				   People were always asking for PDFs that look more like their Gravity Forms layouts.
				   <strong>You asked and we've delivered!</strong>
				</p>

				<p>
				   All PDFs shipped now support <a href="#">Gravity Forms CSS Ready Classes</a>, so your PDF can have two or three column layouts that match your form's.

				   Plus, Gravity PDF <strong>now ships with five PDF templates</strong> – two existing designs and three all-new PDFs.
				</p>

			</div>
		</div>


		<div class="feature-section two-col">

			<div class="col">
				<h3><?php _e( 'A Beautiful Font Manager', 'gravitypdf' );?></h3>

				<p>
				   No more playing around with FTP, or "guessing" the font family name to use in your templates.
				   Our all-new font manager makes it a breeze to upload TTF or OTF font files and create your own font type.

				</p>

				<p>
				   Once installed, you'll have full control over the font face, size and colour using our powerful settings interface.
				</p>

				<p></p>
			
			</div>

			<div class="col">
				<img class="gfpdf-image" src="<?php echo esc_url(PDF_PLUGIN_URL . 'src/assets/images/updated-font-manager.png'); ?>">
			</div>

		</div>


		<div id="gfpdf-mascot-container" class="changelog feature-section three-col">
			<div>
				<img class="gfpdf-image" src="<?php echo esc_url(PDF_PLUGIN_URL . 'src/assets/images/welcome-download-shortcode.png'); ?>">
				<h3>Simple PDF Download Links</h3>
				<p>The <code>[gravitypdf]</code> shortcode allows you to <a href="#">easily place a PDF download link</a> on any of the Gravity Forms Confirmation types.</p>
			</div>
			<div>
				<img class="gfpdf-image" src="<?php echo esc_url(PDF_PLUGIN_URL . 'src/assets/images/welcome-notification-conditional.png'); ?>">
				<h3>PDF Conditional Logic</h3>
				<p>Enable or disable PDFs with Gravity Forms powerful conditional logic feature. Control when PDFs are attached to email notifications or disable the PDF from being viewed if your conditions aren't met.</p>
			</div>
			<div class="last-feature">
				<img class="gfpdf-image" src="<?php echo esc_url(PDF_PLUGIN_URL . 'src/assets/images/updating-header-footer.png'); ?>">
				<h3>Headers and Footers</h3>
				<p>We've built in header and footer support in all our PDF templates. You can optionally have a different first page header and footer. Now that's control!</p>
			</div>
		</div>

		<div class="gfpdf-mascot-sitting"></div>

		

		<div class="changelog">
				<h3>Get more out of Gravity PDF</h3>

				<div class="feature-section col three-col">
					<div>

						<h4>PDF Template Shop</h4>
						<p>It's like a theme shop, but for Gravity PDF templates. <a href="#">Head over to our online store</a> and view our growing selection of premium PDF templates.</p>

						<h4>Stay Up To Date</h4>
						<p><a href="#">Sign up to our newsletter</a> to be amongst the first to receive the latest news and details on upcoming feature.</p>

					</div>

					<div>

						<h4>Tailored PDFs</h4>
						<p>If the PDF Shop doesn't have what you're after <a href="#">our friendly team can build a document just for you</a>. With an addon, our devs can even create templates that auto fill existing PDFs – like government and legal documents.</p>

						<h4>Get Support</h4>
						<p>Have trouble using Gravity PDF? <a href="#">Contact our friendly staff</a> who are avaliable 9am to 5pm Monday to Friday, <a href="http://www.timeanddate.com/worldclock/australia/sydney">Australian Eastern Standard Time</a>.</p>
						

					</div>

					<div class="last-feature">

						<h4>Roll your Own</h4>
						<p>If PHP, HTML and CSS come easy to you, you'll find creating your own PDF templates a breeze. With <a href="#">extensive documentation and great examples</a> you'll be up and running in no time.</p>

					</div>

				</div>

		</div>

</div>