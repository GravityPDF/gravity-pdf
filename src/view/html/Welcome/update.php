<?php

/**
 * Update Screen - Welcome Screen View
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2016, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2016, Blue Liquid Designs

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

<div class="wrap about-wrap gfpdf-update-screen">
	<h1><?php printf( esc_html__( 'Discover Gravity PDF %s', 'gravity-forms-pdf-extended' ), $args['display_version'] ); ?></h1>

	<div class="about-text">
		<?php esc_html_e( 'Managing PDF templates just become a whole lot easier with our Advanced Template Manager! View, Search, Select, Install and Delete PDFs right from our UI.', 'gravity-forms-pdf-extended' ); ?>
	</div>

	<div class="gfpdf-badge"><?php printf( esc_html__( 'Version %s', 'gravity-forms-pdf-extended' ), $args['display_version'] ); ?></div>

	<?php $this->tabs(); ?>

	<div class="feature-section two-col">

		<div class="col">
			<h3><?php esc_html_e( 'Managing PDF Templates in WordPress', 'gravity-forms-pdf-extended' ); ?></h3>

			<p>
				<?php esc_html_e( "Managing PDF templates just became 100% easier thanks to our Advanced Template Manager. Based on the WordPress' Theme Manager, you can easily search through your installed templates, see what your PDF might look like and view supported template features – all without a page reload!", 'gravity-forms-pdf-extended' ); ?>
			</p>

			<p>
				<?php printf( esc_html__( "We've also included the ability to install PDFs via a zip file and easily remove them too. These new feature makes it very simple to install and manage the premium PDF templates %syou might purchased from our PDF Template Shop%s – which launched at the same time as Gravity PDF 4.1.", 'gravity-forms-pdf-extended' ), '<a href="https://gravitypdf.com/shop/">', '</a>' ); ?>
			</p>

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
			<h3><?php esc_html_e( 'Gravity PDF Merge Tags', 'gravity-forms-pdf-extended' ); ?></h3>

			<p>
				<?php printf( esc_html__( "The %s[gravitypdf]%s shortcode is an excellent way to display a PDF download link anywhere on your website. But under certain circumstances it's more useful to display the raw PDF URL, and that's where the new PDF merge tag come into play. The merge tag has the following format %s{Title:pdf:ID}%s and will only display the PDF web address – whereas the shortcode displays a full HTML link to the PDF.", 'gravity-forms-pdf-extended' ), '<code>', '</code>', '<code>', '</code>' ); ?>
			</p>

            <p>
				<?php printf( esc_html__( "Like the %s[gravitypdf]%s shortcode, the PDF merge tag will only show the URL if the PDF is active and the conditional logic for the current entry has been met. Unlike the shortcode, no admin message is shown in those circumstances. The new merge tag is automatically included in Gravity Forms merge tag selector and can be used anywhere Gravity Forms allows other merge tags (provided the entry has been saved).", 'gravity-forms-pdf-extended' ), '<code>', '</code>' ); ?>
            </p>

		</div>
	</div>


	<div class="feature-section two-col" id="gfpdf-mascot-container">

		<div class="col">
			<h3><?php esc_html_e( 'Four Column Support added to Core PDF Templates!', 'gravity-forms-pdf-extended' ); ?></h3>

			<p>
				<?php printf( esc_html__( '%sThis four-column CSS code%s is becoming increasingly popular for Gravity Forms users so we have added full support for these additional classes in Gravity PDF – you will need to manually include these classes with your WordPress theme so your Gravity Form can use four columns.', 'gravity-forms-pdf-extended' ), '<a href="https://gist.github.com/WebEndevSnippets/5555354">', '</a>' ); ?>

			</p>

			<p>
				<?php printf( esc_html__( "To start using in your PDF, add the classes %sgf_first_quarter%s, %sgf_second_quarter%s, %sgf_third_quarter%s and %sgf_fourth_quarter%s to your Gravity Form fields and Gravity PDF will automatically create a four column layout to match.", 'gravity-forms-pdf-extended' ), '<code>', '</code>', '<code>', '</code>', '<code>', '</code>', '<code>', '</code>' ); ?>
			</p>

			<p></p>

		</div>

		<div class="col">
			<img class="gfpdf-image"
			     src="<?php echo esc_url( PDF_PLUGIN_URL . 'src/assets/images/updated-font-manager.png' ); ?>">
		</div>

	</div>

	<?php $this->more(); ?>

</div>
