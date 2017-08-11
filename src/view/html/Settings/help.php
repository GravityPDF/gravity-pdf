<?php

/**
 * Help Settings View
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2017, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2017, Blue Liquid Designs

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

<?php $this->tabs(); ?>


<div id="pdfextended-settings">
	<div class="wrap about-wrap">
		<h1><?php esc_html_e( 'Getting Help With Gravity PDF', 'gravity-forms-pdf-extended' ); ?></h1>

		<div class="about-text">
			<?php esc_html_e( 'This is your portal to find quality help, support and documentation for Gravity PDF', 'gravity-forms-pdf-extended' ); ?>
			<div class="about-text-disclaimer"><?php printf( esc_html__( '(This is not the place to get Gravity Forms support. %sPlease use their official support channel%s for assistance)', 'gravity-forms-pdf-extended' ), '<a href="https://www.gravityhelp.com/support/">', '</a>' ); ?></div>
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

		<h2><?php printf( esc_html__( 'Find the %sanswers%s you need…', 'gravity-forms-pdf-extended' ), '<strong>', '</strong>' ); ?></h2>

		<div id="dashboard-widgets" class="columns-2">
			<div class="postbox-container">
				<a href="https://gravitypdf.com/documentation/v4/five-minute-install/">
					<span><?php esc_html_e( 'Getting Started', 'gravity-forms-pdf-extended' ); ?></span><br>
					<?php esc_html_e( 'Take a look at our quick-start guide and get Gravity PDF up and running in 5 minutes flat!', 'gravity-forms-pdf-extended' ); ?>
				</a>
			</div>

			<div class="postbox-container">
				<a href="https://gravitypdf.com/documentation/v4/user-installation/">
					<span><?php esc_html_e( 'Comprehensive Documentation', 'gravity-forms-pdf-extended' ); ?></span><br>
					<?php esc_html_e( 'We’ve got in-depth articles to help you learn the ins and outs of Gravity PDF. From the basic setup to PDF security.', 'gravity-forms-pdf-extended' ); ?>
				</a>
			</div>

			<div class="postbox-container">
				<a href="https://gravitypdf.com/documentation/v4/user-activation-errors/">
					<span><?php esc_html_e( 'Common Problems', 'gravity-forms-pdf-extended' ); ?></span><br>
					<?php esc_html_e( 'Find out the most common issues user’s experience and ways to resolve them.', 'gravity-forms-pdf-extended' ); ?>
				</a>
			</div>

			<div class="postbox-container">
				<a href="https://gravitypdf.com/documentation/v4/developer-start-customising/">
					<span><?php esc_html_e( 'Developer Documentation', 'gravity-forms-pdf-extended' ); ?></span><br>
					<?php esc_html_e( 'You’ll find all the info and examples you’ll need to create your own custom PDF templates.', 'gravity-forms-pdf-extended' ); ?>
				</a>
			</div>
		</div>

		<div class="center">
			<a href="https://gravitypdf.com/documentation/v4/" class="button button-primary button-large"><?php esc_html_e( 'View All Documentation', 'gravity-forms-pdf-extended' ); ?></a>
			<a href="https://gravitypdf.com/support/#contact-support" class="button button-primary button-large"><?php esc_html_e( 'Contact Support', 'gravity-forms-pdf-extended' ); ?></a>

			<p>
				<?php printf( esc_html__( 'Our support hours are 9:00am-5:00pm Monday to Friday, %sSydney Australia time%s (public holidays excluded).', 'gravity-forms-pdf-extended' ), '<br><a href="http://www.timeanddate.com/worldclock/australia/sydney">', '</a>' ); ?>
			</p>
		</div>

		<?php
		/* See https://gravitypdf.com/documentation/v4/gfpdf_post_help_settings_page/ for more details about this action */
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
