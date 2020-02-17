<?php

/**
 * Help Settings View
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

<?php $this->tabs(); ?>


<div id="pdfextended-settings">
	<div class="wrap about-wrap">
		<h1><?php esc_html_e( 'Getting Help With Gravity PDF', 'gravity-forms-pdf-extended' ); ?></h1>

		<div class="about-text">
			<?php esc_html_e( 'This is your portal to find quality help, support and documentation for Gravity PDF', 'gravity-forms-pdf-extended' ); ?>
			<div class="about-text-disclaimer"><?php printf( esc_html__( '(This is not the place to get Gravity Forms support. %1$sPlease use their official support channel%2$s for assistance. Using the search box below will search gravitypdf.com for results.)', 'gravity-forms-pdf-extended' ), '<a href="https://www.gravityforms.com/support/">', '</a>' ); ?></div>
		</div>

		<div id="search-knowledgebase">
			<div id="search-results">
				<div id="dashboard_primary" class="metabox-holder">

					<div id="documentation-api" class="postbox">
						<h3 class="hndle">
							<span><?php esc_html_e( 'Gravity PDF Documentation', 'gravity-forms-pdf-extended' ); ?></span>
							<span class="spinner"></span>
						</h3>

						<div class="inside rss-widget">
							<ul></ul>
						</div>
					</div>

				</div><!-- close #dashboard_primary -->
			</div><!-- close #search-results -->
		</div><!-- close #search-knowledgebase -->

		<div class="hr-divider"></div>

		<h2><?php printf( esc_html__( 'Find the %1$sanswers%2$s you need…', 'gravity-forms-pdf-extended' ), '<strong>', '</strong>' ); ?></h2>

		<div id="dashboard-widgets" class="columns-2">
			<div class="postbox-container">
				<a href="https://gravitypdf.com/documentation/v5/five-minute-install/">
					<span><?php esc_html_e( 'Getting Started', 'gravity-forms-pdf-extended' ); ?></span><br>
					<?php esc_html_e( 'Take a look at our quick-start guide and get Gravity PDF up and running in 5 minutes flat!', 'gravity-forms-pdf-extended' ); ?>
				</a>
			</div>

			<div class="postbox-container">
				<a href="https://gravitypdf.com/documentation/v5/user-installation/">
					<span><?php esc_html_e( 'Comprehensive Documentation', 'gravity-forms-pdf-extended' ); ?></span><br>
					<?php esc_html_e( 'We’ve got in-depth articles to help you learn the ins and outs of Gravity PDF. From the basic setup to PDF security.', 'gravity-forms-pdf-extended' ); ?>
				</a>
			</div>

			<div class="postbox-container">
				<a href="https://gravitypdf.com/documentation/v5/developer-start-customising/">
					<span><?php esc_html_e( 'Developer Documentation', 'gravity-forms-pdf-extended' ); ?></span><br>
					<?php esc_html_e( 'You’ll find all the info and examples you’ll need to create your own custom PDF templates.', 'gravity-forms-pdf-extended' ); ?>
				</a>
			</div>

			<div class="postbox-container">
				<strong><?php esc_html_e( 'Common Questions', 'gravity-forms-pdf-extended' ); ?></strong><br>
				<ul>
					<li><a href="https://gravitypdf.com/documentation/v5/user-setup-pdf/">How do I setup a PDF on my form?</a></li>
					<li><a href="https://gravitypdf.com/documentation/v5/user-setup-pdf/#notifications">How do you attach the PDF to a notification email?</a></li>
					<li><a href="https://gravitypdf.com/documentation/v5/user-shortcodes/">How do you add a PDF download link to the form's confirmation page?</a></li>
					<li><a href="https://gravitypdf.com/documentation/v5/user-managing-pdfs/">How can I add two or more PDFs to a single form?</a></li>
					<li><a href="https://gravitypdf.com/documentation/v5/gravityview-support/">How can I allow logged in users to download their past PDFs?</a></li>
				</ul>
			</div>
		</div>

		<div class="center">
			<a href="https://gravitypdf.com/documentation/v5/" class="button button-primary button-large"><?php esc_html_e( 'View All Documentation', 'gravity-forms-pdf-extended' ); ?></a>
			<a href="https://gravitypdf.com/support/#contact-support" class="button button-primary button-large"><?php esc_html_e( 'Contact Support', 'gravity-forms-pdf-extended' ); ?></a>

			<p>
				<?php printf( esc_html__( 'Our support hours are 9:00am-5:00pm Monday to Friday, %1$sSydney Australia time%2$s (public holidays excluded).', 'gravity-forms-pdf-extended' ), '<br><a href="http://www.timeanddate.com/worldclock/australia/sydney">', '</a>' ); ?>
			</p>
		</div>

		<?php
		/* See https://gravitypdf.com/documentation/v5/gfpdf_post_help_settings_page/ for more details about this action */
		do_action( 'gfpdf_post_help_settings_page' );
		?>

	</div><!-- close wrap about-wrap -->
</div><!-- close #pdfextended-settings -->

<script type="text/template" id="GravityPDFSearchResultsDocumentation">
	{{ if(collection.length === 0) { }}
		<li><?php esc_html_e( "It doesn't look like there are any topics related to your issue.", 'gravity-forms-pdf-extended' ); ?></li>
	{{ } else { }}
		<li><h3><?php esc_html_e( 'Maybe one of these articles will help...', 'gravity-forms-pdf-extended' ); ?></h3></li>
	{{ } }}

	{{ _.each(collection, function (model) { }}
		<li>
			<a href="{{= model.link }}">{{= model.title.rendered }}</a>
			<div class="except">{{= model.excerpt.rendered }}</div>
		</li>
	{{ }); }}
</script>
