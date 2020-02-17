<?php

/**
 * Common welcome and update screen content
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="gfpdf-mascot-sitting"></div>

<div class="gfpdf-changelog">
	<h2><?php esc_html_e( 'Get more out of Gravity PDF', 'gravity-forms-pdf-extended' ); ?></h2>

	<div class="feature-section gfpdf-three-col">
		<div class="col gfpdf-breakdown">

			<h4><?php esc_html_e( 'PDF Template Shop', 'gravity-forms-pdf-extended' ); ?></h4>

			<p><?php printf( esc_html__( "It's like a theme shop, but for Gravity PDF templates. %1\$sHead over to our online store%2\$s and view our growing selection of premium PDF templates.", 'gravity-forms-pdf-extended' ), '<a href="https://gravitypdf.com/template-shop/">', '</a>' ); ?></p>

			<h4><?php esc_html_e( 'Roll your Own', 'gravity-forms-pdf-extended' ); ?></h4>

			<p><?php printf( esc_html__( "If PHP, HTML and CSS come easy to you, you'll find creating your own PDF templates a breeze. With %1\$sextensive documentation and great examples%2\$s you'll be up and running in no time.", 'gravity-forms-pdf-extended' ), '<a href="https://gravitypdf.com/documentation/v5/developer-start-customising/">', '</a>' ); ?></p>

		</div>

		<div class="col gfpdf-breakdown">

			<h4>PDF Extension Shop</h4>

			<p><?php printf( esc_html__( "If you're looking to enhance Gravity PDF %1\$sour Extension Shop is your first port of call%2\$s. Advanced functionality will be at your fingertips with a premium Gravity PDF extension.", 'gravity-forms-pdf-extended' ), '<a href="https://gravitypdf.com/extension-shop/">', '</a>' ); ?></p>

			<h4><?php esc_html_e( 'Stay Up To Date', 'gravity-forms-pdf-extended' ); ?></h4>

			<p><?php printf( esc_html__( '%1$sSign up to our newsletter%2$s to be amongst the first to receive the latest news and details on upcoming feature.', 'gravity-forms-pdf-extended' ), '<a href="https://gravitypdf.com/#signup-top">', '</a>' ); ?></p>

		</div>

		<div class="col gfpdf-breakdown gfpdf-last-feature">

			<h4><?php esc_html_e( 'Tailored PDFs', 'gravity-forms-pdf-extended' ); ?></h4>

			<p><?php printf( esc_html__( "If the PDF Shop doesn't have what you're after %1\$sour friendly team can build a document just for you%2\$s. With an addon, our devs can even create templates that auto fill existing PDFs – like government and legal documents.", 'gravity-forms-pdf-extended' ), '<a href="https://gravitypdf.com/integration-services/">', '</a>' ); ?></p>

			<h4><?php esc_html_e( 'Get Support', 'gravity-forms-pdf-extended' ); ?></h4>

			<p><?php printf( esc_html__( 'Have trouble using Gravity PDF? %1$sContact our friendly staff%2$s who are avaliable 9am to 5pm Monday to Friday, %3$sAustralian Eastern Standard Time%4$s.', 'gravity-forms-pdf-extended' ), '<a href="https://gravitypdf.com/support/">', '</a>', '<a href="http://www.timeanddate.com/worldclock/australia/sydney">', '</a>' ); ?></p>

		</div>

	</div>
</div>
