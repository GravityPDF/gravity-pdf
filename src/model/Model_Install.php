<?php

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Abstract_Form;

use Psr\Log\LoggerInterface;

use GFCommon;

/**
 * Welcome Screen Model
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
 * Model_Install
 *
 * Handles the grunt work of our installer / uninstaller
 *
 * @since 4.0
 */
class Model_Install extends Helper_Abstract_Model {

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
	 * Holds our Helper_Notices object
	 * which we can use to queue up admin messages for the user
	 *
	 * @var \GFPDF\Helper\Helper_Notices
	 *
	 * @since 4.0
	 */
	protected $notices;

	/**
	 * Setup our class by injecting all our dependancies
	 *
	 * @param \GFPDF\Helper\Helper_Abstract_Form $form    Our abstracted Gravity Forms helper functions
	 * @param \Monolog\Logger|LoggerInterface    $log     Our logger class
	 * @param \GFPDF\Helper\Helper_Data          $data    Our plugin data store
	 * @param \GFPDF\Helper\Helper_Misc          $misc    Our miscellaneous class
	 * @param \GFPDF\Helper\Helper_Notices       $notices Our notice class used to queue admin messages and errors
	 *
	 * @since 4.0
	 */
	public function __construct( Helper_Abstract_Form $form, LoggerInterface $log, Helper_Data $data, Helper_Misc $misc, Helper_Notices $notices ) {

		/* Assign our internal variables */
		$this->form    = $form;
		$this->log     = $log;
		$this->data    = $data;
		$this->misc    = $misc;
		$this->notices = $notices;
	}

	/**
	 * The Gravity PDF Installer
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function install_plugin() {

		$this->log->addNotice( 'Gravity PDF Installed' );
		update_option( 'gfpdf_is_installed', true );
		$this->data->is_installed = true;

		do_action( 'gfpdf_plugin_installed' );
	}

	/**
	 * Get our permalink regex structure
	 *
	 * @return  string
	 *
	 * @since  4.0
	 */
	public function get_permalink_regex() {
		return '^pdf\/([A-Za-z0-9]+)\/([0-9]+)\/?(download)?\/?';
	}

	/**
	 * Get the plugin working directory name
	 *
	 * @return string
	 *
	 * @since  4.0
	 */
	public function get_working_directory() {
		return apply_filters( 'gfpdf_working_folder_name', 'PDF_EXTENDED_TEMPLATES' );
	}

	/**
	 * Get a link to the plugin's settings page URL
	 *
	 * @return string
	 *
	 * @since  4.0
	 */
	public function get_settings_url() {
		return admin_url( 'admin.php?page=gf_settings&subview=PDF' );
	}

	/**
	 * Get our current installation status
	 *
	 * @return  string
	 *
	 * @since  4.0
	 */
	public function is_installed() {
		return get_option( 'gfpdf_is_installed' );
	}

	/**
	 * Used to set up our PDF template folder, tmp folder and font folder
	 *
	 * @since 4.0
	 */
	public function setup_template_location() {

		$template_dir = $this->data->upload_dir . '/' . $this->data->working_folder . '/';
		$template_url = $this->data->upload_dir_url . '/' . $this->data->working_folder . '/';

		/* Legacy Filters */
		$this->data->template_location     = apply_filters( 'gfpdfe_template_location', $template_dir, $this->data->working_folder );
		$this->data->template_location_url = apply_filters( 'gfpdfe_template_location_uri', $template_url, $this->data->working_folder );

		/* Allow user to change directory location(s) */
		$this->data->template_location     = apply_filters( 'gfpdf_template_location', $template_dir, $this->data->working_folder, $this->data->upload_dir ); /* needs to be accessible from the web */
		$this->data->template_location_url = apply_filters( 'gfpdf_template_location_uri', $template_url, $this->data->working_folder, $this->data->upload_dir_url ); /* needs to be accessible from the web */

		$this->data->template_font_location = apply_filters( 'gfpdf_font_location', $this->data->template_location . 'fonts/', $this->data->working_folder, $this->data->upload_dir ); /* can be in a directory not accessible via the web */
		$this->data->template_tmp_location  = apply_filters( 'gfpdf_tmp_location', $this->data->template_location . 'tmp/', $this->data->working_folder, $this->data->upload_dir ); /* encouraged to move this to a directory not accessible via the web */

		$this->log->addNotice( 'Template Locations', array(
			'path' => $this->data->template_location,
			'url'  => $this->data->template_location_url,
			'font' => $this->data->template_font_location,
			'tmp'  => $this->data->template_tmp_location,
		) );
	}

	/**
	 * If running a multisite we'll setup the path to the current multisite folder
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function setup_multisite_template_location() {

		if ( is_multisite() ) {

			$template_dir = $this->data->upload_dir . '/' . $this->data->working_folder . '/' . $blog_id . '/';
			$template_url = $this->data->upload_dir_url . '/' . $this->data->working_folder . '/' . $blog_id . '/';

			$blog_id = get_current_blog_id();

			/**
			 * Allow user to change directory location(s)
			 *
			 * @internal Folder location needs to be accessible from the web
			 */
			$this->data->multisite_template_location     = apply_filters( 'gfpdf_multisite_template_location', $template_dir, $this->data->working_folder, $blog_id, $this->data->upload_dir );
			$this->data->multisite_template_location_url = apply_filters( 'gfpdf_multisite_template_location_url', $template_url, $this->data->working_folder, $blog_id, $this->data->upload_dir_url );
			$this->data->multisite_template_location     = apply_filters( 'gfpdf_multisite_template_location_' . $blog_id, $template_dir, $this->data->working_folder, $blog_id, $this->data->upload_dir );
			$this->data->multisite_template_location_url = apply_filters( 'gfpdf_multisite_template_location_url_' . $blog_id, $template_url, $this->data->working_folder, $blog_id, $this->data->upload_dir_url );

			$this->log->addNotice( 'Multisite Template Locations', array(
				'path' => $this->data->multisite_template_location,
				'url'  => $this->data->multisite_template_location_url,
			) );
		}
	}

	/**
	 * Create the appropriate folder structure automatically
	 * The upload directory should have all appropriate permissions to allow this kind of maniupulation
	 * but devs who tap into the gfpdfe_template_location filter will need to ensure we can write to the appropraite folder
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function create_folder_structures() {

		/* don't create the folder structure on our welcome page or through AJAX as an errors on the first page they see will confuse users */
		if ( is_admin() &&
		     ( rgget( 'page' ) == 'gfpdf-getting-started' ) || ( rgget( 'page' ) == 'gfpdf-update' ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || get_transient( '_gravitypdf_activation_redirect' )
		) {
			return null;
		}

		/* add folders that need to be checked */
		$folders = array(
			$this->data->template_location,
			$this->data->template_font_location,
			$this->data->template_tmp_location,
		);

		if ( is_multisite() ) {
			$folders[] = $this->data->multisite_template_location;
		}

		/* allow other plugins to add their own folders which should be checked */
		$folders = apply_filters( 'gfpdf_installer_create_folders', $folders );

		/* create the required folder structure, or throw error */
		foreach ( $folders as $dir ) {
			if ( ! is_dir( $dir ) ) {
				if ( ! wp_mkdir_p( $dir ) ) {
					$this->log->addError( 'Failed Creating Folder Structure', array( 'dir' => $dir ) );
					$this->notices->add_error( sprintf( __( 'There was a problem creating the %s directory. Ensure you have write permissions to your uploads folder.', 'gravity-forms-pdf-extended' ), '<code>' . $this->misc->relative_path( $dir ) . '</code>' ) );
				}
			} else {
				/* test the directory is currently writable by the web server, otherwise throw and error */
				if ( ! $this->misc->is_directory_writable( $dir ) ) {
					$this->log->addError( 'Failed Write Permissions Check.', array( 'dir' => $dir ) );
					$this->notices->add_error( sprintf( __( 'Gravity PDF does not have write permission to the %s directory. Contact your web hosting provider to fix the issue.', 'gravity-forms-pdf-extended' ), '<code>' . $this->misc->relative_path( $dir ) . '</code>' ) );
				}
			}
		}

		/* create blank index file in all folders to prevent web servers listing the entire directory */
		if ( is_dir( $this->data->template_location ) && ! is_file( $this->data->template_location . 'index.html' ) ) {
			GFCommon::recursive_add_index_file( $this->data->template_location );
		}

		/* create deny htaccess file to prevent direct access to files */
		if ( is_dir( $this->data->template_tmp_location ) && ! is_file( $this->data->template_tmp_location . '.htaccess' ) ) {
			$this->log->addNotice( 'Create Apache .htaccess Security' );
			file_put_contents( $this->data->template_tmp_location . '.htaccess', 'deny from all' );
		}
	}

	/**
	 * Register our PDF custom rewrite rules
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function register_rewrite_rules() {

		/* store query */
		$query      = $this->data->permalink;
		$rewrite_to = 'index.php?gpdf=1&pid=$matches[1]&lid=$matches[2]&action=$matches[3]';

		/* Add our main endpoint */
		add_rewrite_rule(
			$query,
			$rewrite_to,
			'top' );

		$this->log->addNotice( 'Add Rewrite Rules', array(
			'query'   => $query,
			'rewrite' => $rewrite_to,
		) );

		/* check to see if we need to flush the rewrite rules */
		$this->maybe_flush_rewrite_rules( $query );
	}

	/**
	 * Register our PDF custom rewrite rules
	 *
	 * @since 4.0
	 *
	 * @param array $tags
	 *
	 * @return array
	 */
	public function register_rewrite_tags( $tags ) {
		$tags[] = 'gpdf';
		$tags[] = 'pid';
		$tags[] = 'lid';
		$tags[] = 'action';

		return $tags;
	}

	/**
	 * Check if we need to force the rewrite rules to be flushed
	 *
	 * @param string $rule The rule to check
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function maybe_flush_rewrite_rules( $rule ) {

		$rules = get_option( 'rewrite_rules' );

		if ( ! isset( $rules[ $rule ] ) ) {
			$this->log->addNotice( 'Flushing WordPress Rewrite Rules.' );
			flush_rewrite_rules( false );
		}
	}


	/**
	 * The Gravity PDF Uninstaller
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function uninstall_plugin() {
		$this->log->addNotice( 'Uninstall Gravity PDF.' );

		/* Clean up database */
		if ( is_multisite() ) {
			$sites = wp_get_sites();

			foreach ( $sites as $site ) {
				switch_to_blog( $site['blog_id'] );
				$this->remove_plugin_options();
				$this->remove_plugin_form_settings();
			}
			restore_current_blog();

		} else {
			$this->remove_plugin_options();
			$this->remove_plugin_form_settings();
		}

		/* Remove folder structure and deactivate */
		$this->remove_folder_structure();
		$this->deactivate_plugin();
	}

	/**
	 * Remove and options stored in the database
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function remove_plugin_options() {
		delete_option( 'gfpdf_is_installed' );
		delete_option( 'gfpdf_current_version' );
		delete_option( 'gfpdf_settings' );
	}

	/**
	 * Remove all form settings from each individual form.
	 * Because we stored out PDF settings with each form and have no index we need to individually load and forms and check them for Gravity PDF settings
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function remove_plugin_form_settings() {

		$forms = $this->form->get_forms();

		foreach ( $forms as $form ) {
			/* only update forms which have a PDF configuration */
			if ( isset( $form['gfpdf_form_settings'] ) ) {
				unset( $form['gfpdf_form_settings'] );
				if ( $this->form->update_form( $form ) !== true ) {
					$this->log->addError( 'Cannot Remove PDF Settings from Form.', array( 'form' => $form ) );
					$this->notices->add_error( sprintf( __( 'There was a problem removing the Gravity Form "%s" PDF configuration. Try delete manually.', 'gravity-forms-pdf-extended' ), $form['id'] . ': ' . $form['title'] ) );
				}
			}
		}
	}

	/**
	 * Remove our PDF directory structure
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function remove_folder_structure() {

		$paths = apply_filters( 'gfpdf_uninstall_path', array(
			$this->data->template_location,
		) );

		foreach ( $paths as $dir ) {
			if ( is_dir( $dir ) ) {
				$results = $this->misc->rmdir( $dir );

				if ( is_wp_error( $results ) || ! $results ) {
					$this->log->addError( 'Cannot Remove Folder Structure.', array(
						'WP_Error' => $results,
						'dir'      => $dir,
					) );

					$this->notices->add_error( sprintf( __( 'There was a problem removing the %s directory. Clean up manually via (S)FTP.', 'gravity-forms-pdf-extended' ), '<code>' . $this->misc->relative_path( $dir ) . '</code>' ) );
				}
			}
		}
	}

	/**
	 * Deactivate Gravity PDF
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function deactivate_plugin() {
		deactivate_plugins( PDF_PLUGIN_BASENAME );
	}

	/**
	 * Safe redirect after deactivation
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function redirect_to_plugins_page() {
		/* check if user can view the plugins page */
		if ( current_user_can( 'activate_plugins' ) ) {
			wp_safe_redirect( admin_url( 'plugins.php' ) );
		} else { /* otherwise redirect to dashboard */
			wp_safe_redirect( admin_url( 'index.php' ) );
		}
		exit;
	}
}
