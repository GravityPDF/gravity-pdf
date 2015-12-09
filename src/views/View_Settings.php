<?php

namespace GFPDF\View;

use GFPDF\Helper\Helper_Abstract_View;
use GFPDF_Major_Compatibility_Checks;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Misc;

use Psr\Log\LoggerInterface;

/**
 * Settings View
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
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

/**
 * View_Settings
 *
 * A general class for About / Intro Screen
 *
 * @since 4.0
 */
class View_Settings extends Helper_Abstract_View {

	/**
	 * Set the view's name
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $view_type = 'Settings';

	/**
	 * Holds abstracted functions related to the forms plugin
	 *
	 * @var \GFPDF\Helper\Helper_Form
	 *
	 * @since 4.0
	 */
	protected $form;

	/**
	 * Holds our log class
	 *
	 * @var \Monolog\Logger|LoggerInterface
	 *
	 * @since 4.0
	 */
	protected $log;

	/**
	 * Holds our Helper_Abstract_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 *
	 * @var \GFPDF\Helper\Helper_Options_Fields
	 *
	 * @since 4.0
	 */
	protected $options;

	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var \GFPDF\Helper\Helper_Data
	 *
	 * @since 4.0
	 */
	protected $data;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 *
	 * @var \GFPDF\Helper\Helper_Misc
	 *
	 * @since 4.0
	 */
	protected $misc;

	/**
	 * Setup our class by injecting all our dependancies
	 *
	 * @param array                                          $data_cache An array of data to pass to the view
	 * @param \GFPDF\Helper\Helper_Form|Helper_Abstract_Form $form       Our abstracted Gravity Forms helper functions
	 * @param \Monolog\Logger|LoggerInterface                $log        Our logger class
	 * @param \GFPDF\Helper\Helper_Abstract_Options          $options    Our options class which allows us to access any settings
	 * @param \GFPDF\Helper\Helper_Data                      $data       Our plugin data store
	 * @param \GFPDF\Helper\Helper_Misc                      $misc       Our miscellaneous class
	 *
	 * @since 4.0
	 */
	public function __construct( $data_cache = array(), Helper_Abstract_Form $form, LoggerInterface $log, Helper_Abstract_Options $options, Helper_Data $data, Helper_Misc $misc ) {

		/* Call our parent constructor */
		parent::__construct( $data_cache );

		/* Assign our internal variables */
		$this->form    = $form;
		$this->log     = $log;
		$this->options = $options;
		$this->data    = $data;
		$this->misc    = $misc;
	}

	/**
	 * Load the Welcome Tab tabs
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function tabs() {

		/* Set up any variables we need for the view and display */
		$vars = array(
			'selected' => isset( $_GET['tab'] ) ? $_GET['tab'] : 'general',
			'tabs'     => $this->get_avaliable_tabs(),
			'data'     => $this->data,
		);

		/* load the tabs view */
		$this->load( 'tabs', $vars );
	}

	/**
	 * Set up our settings navigation
	 *
	 * @return array The navigation array
	 *
	 * @since 4.0
	 */
	public function get_avaliable_tabs() {
		/**
		 * Store the setting navigation
		 * The array key is the settings order
		 *
		 * @var array
		 */
		$navigation = array(
			5 => array(
				'name' => __( 'General', 'gravity-forms-pdf-extended' ),
				'id'   => 'general',
			),

			100 => array(
				'name' => __( 'Tools', 'gravity-forms-pdf-extended' ),
				'id'   => 'tools',
			),

			120 => array(
				'name' => __( 'Help', 'gravity-forms-pdf-extended' ),
				'id'   => 'help',
			),
		);

		/**
		 * Allow additional navigation to be added to the settings page
		 *
		 * @since 3.8
		 */
		return apply_filters( 'gfpdf_settings_navigation', $navigation );
	}

	/**
	 * Pull the system status details and show
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function system_status() {
		global $wp_version;

		$status = new GFPDF_Major_Compatibility_Checks();

		$vars = array(
			'memory' => $status->get_ram( $this->data->memory_limit ),
			'wp'     => $wp_version,
			'php'    => phpversion(),
			'gf'     => $this->form->get_version(),
		);

		$this->log->addNotice( 'System Status', array( 'status' => $vars ) );

		/* load the system status view */
		$this->load( 'system_status', $vars );
	}

	/**
	 * Pull the general details and display
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function general() {

		$vars = array(
			'edit_cap' => $this->form->has_capability( 'gravityforms_edit_settings' ),
		);

		/* load the system status view */
		$this->load( 'general', $vars );
	}

	/**
	 * Pull the tools details and show
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function tools() {

		/* prevent unauthorized access */
		if ( ! $this->form->has_capability( 'gravityforms_edit_settings' ) ) {
			$this->log->addWarning( 'Lack of User Capabilities.' );

			wp_die( __( 'You do not have permission to access this page', 'gravity-forms-pdf-extended' ) );
		}

		$template_directory = ( is_multisite() ) ? $this->data->multisite_template_location : $this->data->template_location;

		$vars = array(
			'template_directory'            => $this->misc->relative_path( $template_directory, '/' ),
			'template_files'                => $this->options->get_plugin_pdf_templates(),
			'custom_template_setup_warning' => $this->options->get_option( 'custom_pdf_template_files_installed' ),
		);

		/* load the system status view */
		$this->load( 'tools', $vars );
	}

	/**
	 * Add Gravity Forms Tooltips
	 *
	 * @param array $tooltips The existing tooltips
	 *
	 * @since 4.0
	 *
	 * @return string
	 */
	public function add_tooltips( $tooltips ) {

		$tooltips['pdf_status_wp_memory'] = '<h6>' . __( 'WP Memory Available', 'gravity-forms-pdf-extended' ) . '</h6>' . sprintf( __( 'Producing PDF documents is hard work and Gravity PDF requires more resources than most plugins. We strongly recommend you have at least 128MB, but you may need more.', 'gravity-forms-pdf-extended' ) );

		return apply_filters( 'gravitypdf_registered_tooltips', $tooltips );
	}

	/**
	 * Add Knowledebase meta box
	 *
	 * @param object $object The metabox object
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function add_meta_pdf_knowledgebase( $object ) {
		?>
		<i class="fa fa-file-text-o fa-5x"></i>
		<h4>
			<a href="https://developer.gravitypdf.com/documentation/"><?php _e( 'Knowledge Base', 'gravity-forms-pdf-extended' ); ?></a>
		</h4>
		<p><?php _e( 'Gravity PDF has extensive online documentation to help you get started.', 'gravity-forms-pdf-extended' ); ?></p>
		<?php
	}

	/**
	 * Add support forum meta box
	 *
	 * @param object $object The metabox object
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function add_meta_pdf_support_forum( $object ) {
		?>
		<i class="fa fa-comments-o fa-5x"></i>
		<h4><a href="https://support.gravitypdf.com/"><?php _e( 'Support Forum', 'gravity-forms-pdf-extended' ); ?></a>
		</h4>
		<p><?php _e( 'Our community support forum is a great resource if you have a problem.', 'gravity-forms-pdf-extended' ); ?></p>
		<?php
	}

	/**
	 * Add direct contact meta box
	 *
	 * @param object $object The metabox object
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function add_meta_pdf_direct( $object ) {
		?>
		<i class="fa fa-envelope-o fa-5x"></i>
		<h4>
			<a href="https://developer.gravitypdf.com/contact/"><?php _e( 'Contact Us', 'gravity-forms-pdf-extended' ); ?></a>
		</h4>
		<p><?php _e( 'You can also get in touch with Gravity PDF staff directly via email or phone.', 'gravity-forms-pdf-extended' ); ?></p>
		<?php
	}

	/**
	 * Add Key Documentation meta box
	 *
	 * @param object $object The metabox object
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function add_meta_pdf_popular_articles( $object ) {

		$articles = array(

			array(
				'title' => __( 'Getting Started Guide', 'gravity-forms-pdf-extended' ),
				'url'   => 'https://developer.gravitypdf.com/documentation/getting-started-with-gravity-pdf-configuration/',
			),

			array(
				'title' => __( 'Creating a Custom PDF Template', 'gravity-forms-pdf-extended' ),
				'url'   => 'https://developer.gravitypdf.com/documentation/custom-templates-introduction/',
			),

		);

		?>
		<ul>
			<?php foreach ( $articles as $a ) : ?>
				<li><a href="<?php echo $a['url']; ?>" class="rsswidget"><?php echo $a['title']; ?></a></li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	/**
	 * Add Recent forum articles meta box
	 *
	 * @param object $object The metabox object
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function add_meta_pdf_recent_forum_articles( $object, $topics ) {

		if ( ! $topics || ! is_array( $topics ) ) {
			_e( 'Latest forum topics could not be loaded.', 'gravity-forms-pdf-extended' );

			return;
		}

		?>
		<?php foreach ( $topics as $topic ) : ?>
			<li><a href="https://support.gravitypdf.com/t/<?php echo $topic['slug']; ?>/<?php echo $topic['id']; ?>"
			       class="rsswidget"><?php echo $topic['fancy_title']; ?></a></li>
		<?php endforeach; ?>
		<?php
	}

	/**
	 * Add Support hour meta box
	 *
	 * @param object $object The metabox object
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function add_meta_pdf_support_hours( $object ) {
		?>
		<i class="fa fa-clock-o fa-5x"></i>
		<h4><?php _e( 'Support Hours', 'gravity-forms-pdf-extended' ); ?></h4>
		<p><?php printf( __( "Gravity PDF's support hours are from 9:00am-5:00pm Monday to Friday, %sSydney Australia time%s.", 'gravity-forms-pdf-extended' ), '<a href="http://www.timeanddate.com/worldclock/australia/sydney">', '</a>' ); ?></p>
		<?php
	}
}
