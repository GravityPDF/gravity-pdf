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

<div class="wrap about-wrap gfpdf-update-screen">
	<h1><?php printf( __( 'Discover Gravity PDF %s', 'gravity-forms-pdf-extended' ), $args['display_version'] ); ?></h1>
	
  <div class="about-text"><?php _e( "Gravity PDF has been completely rebuilt with simplicity, stability and security in mind. Our team has spent over ten months making a great product even greater. Discover what's new...", 'gravity-forms-pdf-extended' ); ?></div>
	
	<div class="gfpdf-badge"><?php printf( __( 'Version %s', 'gravity-forms-pdf-extended' ), $args['display_version'] ); ?></div>

	<?php $this->tabs(); ?>


	<!-- @todo -->
	<!--
	<div class="headline-feature feature-video">
		
	</div>

	<hr />
	-->

	<div class="feature-section two-col">

		<div class="col">
			<h3><?php _e( 'Bringing Config Settings to WordPress', 'gravity-forms-pdf-extended' );?></h3>

			<p>
			   <?php _e( "We've done away with the need to edit PHP files to configure Gravity PDF.
			   You'll have a familiar and seamless experience controlling PDF settings direct from your WordPress Admin area.", 'gravity-forms-pdf-extended' ); ?>
			</p>

			<p>
			   <?php _e( "If you've ever configured a Gravity Form add-on you'll feel right at home setting up PDFs.", 'gravity-forms-pdf-extended' ); ?>
			</p>

			<p></p>
		
		</div>

		<div class="col">
			<img class="gfpdf-image" src="<?php echo esc_url( PDF_PLUGIN_URL . 'src/assets/images/updated-new-ui.png' ); ?>">
		</div>

	</div>


	<div class="feature-section two-col">

		<div class="col">
			<img class="gfpdf-image" src="<?php echo esc_url( PDF_PLUGIN_URL . 'src/assets/images/updated-css-ready-class-templates.png' ); ?>">
		</div>

		<div class="col">
			<h3><?php _e( 'All-New Templates with CSS Ready Class Support', 'gravity-forms-pdf-extended' );?></h3>

			<p>
			   <?php printf( __( "We were always getting feedback for templates that look more like their Gravity Forms layouts.
			   %sYou asked and we've delivered!%s", 'gravity-forms-pdf-extended' ), '<strong>', '</strong>' ); ?>
			</p>

			<p>
			   <?php printf( __( "All v4 PDFs will support %sGravity Forms CSS Ready Classes%s. When used in your form the PDF will automatically create two and three column layouts to suit.

			   Plus, Gravity PDF %snow comes with five free PDF templates%s out of the box.", 'gravity-forms-pdf-extended' ), '<a href="https://www.gravityhelp.com/documentation/article/css-ready-classes/">', '</a>', '<strong>', '</strong>' ); ?>
			</p>

		</div>
	</div>


	<div class="feature-section two-col">

		<div class="col">
			<h3><?php _e( 'A Beautiful Font Manager', 'gravity-forms-pdf-extended' );?></h3>

			<p>
			   <?php _e( 'No more playing around with FTP when installing fonts, or "guessing" the font family name to use in your templates.
			   Our all-new font manager makes it a breeze to upload TTF or OTF font files and use them in your PDFs.', 'gravity-forms-pdf-extended' ); ?>

			</p>

			<p>
			   <?php _e( "Once installed, you'll have full control over the font face, size and colour using our powerful settings interface." ,'gravity-forms-pdf-extended' ); ?>
			</p>

			<p></p>
		
		</div>

		<div class="col">
			<img class="gfpdf-image" src="<?php echo esc_url( PDF_PLUGIN_URL . 'src/assets/images/updated-font-manager.png' ); ?>">
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
			<h3><?php _e( 'PDF Conditional Logic', 'gravity-forms-pdf-extended' ); ?></h3>
			<p><?php _e( "Enable or disable PDFs with Gravity Forms powerful conditional logic feature. Control when PDFs are attached to email notifications and disable a PDF from being viewed if your conditions aren't met.", 'gravity-forms-pdf-extended' ); ?></p>
		</div>
		<div class="col last-feature">
			<img class="gfpdf-image" src="<?php echo esc_url( PDF_PLUGIN_URL . 'src/assets/images/updating-header-footer.png' ); ?>">
			<h3><?php _e( 'Headers and Footers', 'gravity-forms-pdf-extended' ); ?></h3>
			<p><?php _e( "We've built in header and footer support in all our v4 templates. You can optionally have a different first page header and footer. Now that's control!", 'gravity-forms-pdf-extended' ); ?></p>
		</div>
	</div>

	<?php $this->more(); ?>

</div>
