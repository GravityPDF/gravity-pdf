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
		<?php esc_html_e( 'Managing PDF templates just become a whole lot easier with our Advanced Template Manager! View, Search, Install and Delete PDFs right from our new UI.', 'gravity-forms-pdf-extended' ); ?>
	</div>

	<div class="gfpdf-badge"><?php printf( esc_html__( 'Version %s', 'gravity-forms-pdf-extended' ), $args['display_version'] ); ?></div>

	<?php $this->tabs(); ?>

    <div class="headline-feature feature-video">
        <style>.embed-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; } .embed-container iframe, .embed-container object, .embed-container embed { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }</style><div class='embed-container'><iframe src='https://www.youtube.com/embed/yu070mAf0dg' frameborder='0' allowfullscreen></iframe></div>
    </div>

    <hr />

	<div class="feature-section two-col">

		<div class="col">
			<h3><?php esc_html_e( 'Managing PDF Templates in WordPress', 'gravity-forms-pdf-extended' ); ?></h3>

			<p>
				<?php esc_html_e( "It's 100% easier to work with PDF Templates thanks to our Advanced Template Manager. Based on the WordPress Theme Manager, you can easily search through your installed templates, see what a PDF might look like and view supported features – all without a page reload!", 'gravity-forms-pdf-extended' ); ?>
			</p>

			<p>
				<?php printf( esc_html__( "We've added the ability to install PDFs via a zip file and easily delete them, too. It's now very simple to install and use custom PDF templates – like the ones %syou might purchased from our PDF Template Shop%s.", 'gravity-forms-pdf-extended' ), '<a href="https://gravitypdf.com/shop/">', '</a>' ); ?>
			</p>

		</div>

		<div class="col">
			<img class="gfpdf-image" src="<?php echo esc_url( PDF_PLUGIN_URL . 'src/assets/images/updating-advanced-template-selector.png' ); ?>">
		</div>

	</div>


	<div class="feature-section two-col">

		<div class="col">
			<img class="gfpdf-image" src="<?php echo esc_url( PDF_PLUGIN_URL . 'src/assets/images/updating-merge-tags.png' ); ?>">
		</div>

		<div class="col">
			<h3><?php esc_html_e( 'Gravity PDF Merge Tags', 'gravity-forms-pdf-extended' ); ?></h3>

			<p>
				<?php printf( esc_html__( "The %s[gravitypdf]%s shortcode is an excellent way to display a PDF download link on your website. But sometimes it's more useful to display the raw PDF URL, and that's where the new PDF merge tag %s{Title:pdf:ID}%s comes in.", 'gravity-forms-pdf-extended' ), '<code>', '</code>', '<code>', '</code>' ); ?>
			</p>

            <p>
				<?php printf( esc_html__( "The PDF Merge Tag will be automatically converted to a URL if the PDF is active and the PDF conditional logic has been met. The new merge tag is automatically included in the Gravity Forms merge tag selector and can be used anywhere Gravity Forms allows other merge tags (provided the entry has been created).", 'gravity-forms-pdf-extended' ) ); ?>
            </p>

		</div>
	</div>


	<div class="feature-section two-col" id="gfpdf-mascot-container">

		<div class="col">
			<h3><?php esc_html_e( 'Four Column Support added to Core PDF Templates!', 'gravity-forms-pdf-extended' ); ?></h3>

			<p>
				<?php printf( esc_html__( '%sThis four-column CSS code%s has become increasingly popular for Gravity Forms users and so we have added full support for these classes in Gravity PDF (make sure to include the custom CSS with your theme).', 'gravity-forms-pdf-extended' ), '<a href="https://gist.github.com/WebEndevSnippets/5555354">', '</a>' ); ?>

			</p>

			<p>
				<?php printf( esc_html__( "To start using in your PDF, add the classes %sgf_first_quarter%s, %sgf_second_quarter%s, %sgf_third_quarter%s and %sgf_fourth_quarter%s to your Gravity Form fields and supported PDF templates will automatically create a four column layout to match.", 'gravity-forms-pdf-extended' ), '<code>', '</code>', '<code>', '</code>', '<code>', '</code>', '<code>', '</code>' ); ?>
			</p>

			<p></p>

		</div>

		<div class="col">
			<img class="gfpdf-image"
			     src="<?php echo esc_url( PDF_PLUGIN_URL . 'src/assets/images/updating-four-columns.png' ); ?>">
		</div>

	</div>

	<?php $this->more(); ?>

</div>