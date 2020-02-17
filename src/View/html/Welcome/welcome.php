<?php

/**
 * Getting Started - Welcome Screen View
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

<div class="wrap about-wrap gfpdf-welcome-screen">
	<h1><?php esc_html_e( 'Welcome to Gravity PDF', 'gravity-forms-pdf-extended' ); ?></h1>

	<div class="about-text">
		<?php esc_html_e( "You're just minutes away from producing your first highly-customizable PDF document using Gravity Forms data.", 'gravity-forms-pdf-extended' ); ?>
	</div>

	<div class="gfpdf-badge"><?php printf( esc_html__( 'Version %s', 'gravity-forms-pdf-extended' ), $args['display_version'] ); ?></div>

	<!-- Include Divider -->
	<h2 class="nav-tab-wrapper wp-clearfix"></h2>

	<div class="feature-section gfpdf-two-col">

		<div class="col">
			<h3><?php esc_html_e( 'Where to Start?', 'gravity-forms-pdf-extended' ); ?></h3>

			<p>
				<?php esc_html_e( "Before using the software, the Core PDF Fonts will need to be installed. Don't worry though: this is a one-time action that won't need to be repeated.", 'gravity-forms-pdf-extended' ); ?>
				<a href="<?php echo esc_url( $gfpdf->data->settings_url ); ?>&tab=tools#/downloadCoreFonts"><?php esc_html_e( 'Run this step now', 'gravity-forms-pdf-extended' ); ?></a>.
			</p>

			<p>
				<?php printf( esc_html__( "Next, you'll want to review %1\$sGravity PDF's General Settings%2\$s which can be found by navigating to %3\$sForms -> Settings -> PDF%4\$s in your WordPress admin area. From here you'll be able to set defaults for paper size, font face, font color, and select a PDF template – %5\$swe ship with four completely-free layouts%6\$s – which will be used for all new PDFs. There's even an easy-to-use interface for installing custom fonts.", 'gravity-forms-pdf-extended' ), '<a href="' . esc_url( $gfpdf->data->settings_url ) . '">', '</a>', '<code>', '</code>', '<strong>', '</strong>' ); ?>
			</p>

			<a href="<?php echo esc_url( $gfpdf->data->settings_url ); ?>" class="button"><?php esc_html_e( 'Configure Settings', 'gravity-forms-pdf-extended' ); ?></a>
		</div>

		<div class="col">
			<img class="gfpdf-image" src="https://resources.gravitypdf.com/uploads/2017/11/general-pdf-settings-page-full-v5-1.png">
		</div>

	</div>

	<div class="feature-section gfpdf-two-col">

		<div class="col">
			<img class="gfpdf-image" src="https://resources.gravitypdf.com/uploads/2017/11/add-new-pdf-page-full-v5-1.png">
		</div>

		<div class="col">
			<h3><?php esc_html_e( 'Setting up a PDF', 'gravity-forms-pdf-extended' ); ?></h3>

			<p>
				<?php printf( esc_html__( 'You can setup individual PDF documents from the %1$sGravity Form "Forms" page%2$s in your admin area – located at %3$sForms -> Forms%4$s in your navigation. A new %5$sPDF%6$s option will be avaliable in each forms\' settings section. The only required fields are %7$sName%8$s – an internal identifier – and %9$sFilename%10$s – the name used when saving and emailing the PDF.', 'gravity-forms-pdf-extended' ), '<a href="' . esc_url( admin_url( 'admin.php?page=gf_edit_forms' ) ) . '">', '</a>', '<code>', '</code>', '<code>', '</code>', '<em>', '</em>', '<em>', '</em>' ); ?>
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

	<div id="gfpdf-mascot-container" class="changelog feature-section gfpdf-three-col">
		<div class="col">
			<img class="gfpdf-image" src="https://resources.gravitypdf.com/uploads/2017/11/pdf-list-page-v5.png">

			<h3><?php esc_html_e( 'Simple PDF Download Links', 'gravity-forms-pdf-extended' ); ?></h3>

			<p><?php printf( esc_html__( 'The %1$s[gravitypdf]%2$s shortcode allows you to %3$seasily place a PDF download link%4$s on any of the Gravity Forms Confirmation types.', 'gravity-forms-pdf-extended' ), '<code>', '</code>', '<a href="https://gravitypdf.com/documentation/v5/user-shortcodes/">', '</a>' ); ?></p>
		</div>
		<div class="col">
			<img class="gfpdf-image" src="https://resources.gravitypdf.com/uploads/2017/11/pdf-notifications-v5.png">

			<h3><?php esc_html_e( 'Automated PDF Emails', 'gravity-forms-pdf-extended' ); ?></h3>

			<p><?php esc_html_e( 'Select a Gravity Form Notification and your PDF will automatically be sent as an attachment. Powerful conditional logic can also be used to determine if a PDF will be included.', 'gravity-forms-pdf-extended' ); ?></p>
		</div>
		<div class="col last-feature">
			<img class="gfpdf-image" src="https://resources.gravitypdf.com/uploads/2017/11/welcome-manage-fonts.png">

			<h3><?php esc_html_e( 'Custom Fonts', 'gravity-forms-pdf-extended' ); ?></h3>

			<p><?php printf( esc_html__( 'Make your documents stand out by including your favorite fonts with our %1$ssimple font manager%2$s.', 'gravity-forms-pdf-extended' ), '<a href="https://gravitypdf.com/documentation/v5/user-custom-fonts/">', '</a>' ); ?></p>
		</div>
	</div>

	<?php $this->more(); ?>

</div>
