<?php

namespace GFPDF\Model;

use GFCommon;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Pdf_Queue;
use Psr\Log\LoggerInterface;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Model_Install
 *
 * Handles the grunt work of our installer / uninstaller
 *
 * @since 4.0
 */
class Model_Install extends Helper_Abstract_Model {

	/**
	 * Holds the abstracted Gravity Forms API specific to Gravity PDF
	 *
	 * @var \GFPDF\Helper\Helper_Form
	 *
	 * @since 4.0
	 */
	protected $gform;

	/**
	 * Holds our log class
	 *
	 * @var LoggerInterface
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
	 * @var Helper_Pdf_Queue
	 *
	 * @since 5.0
	 */
	protected $queue;

	/**
	 * Setup our class by injecting all our dependencies
	 *
	 * @param \GFPDF\Helper\Helper_Abstract_Form $gform   Our abstracted Gravity Forms helper functions
	 * @param LoggerInterface                    $log     Our logger class
	 * @param \GFPDF\Helper\Helper_Data          $data    Our plugin data store
	 * @param \GFPDF\Helper\Helper_Misc          $misc    Our miscellaneous class
	 * @param \GFPDF\Helper\Helper_Notices       $notices Our notice class used to queue admin messages and errors
	 * @param \GFPDF\Helper\Helper_Pdf_Queue     $queue
	 *
	 * @since 4.0
	 */
	public function __construct( Helper_Abstract_Form $gform, LoggerInterface $log, Helper_Data $data, Helper_Misc $misc, Helper_Notices $notices, Helper_Pdf_Queue $queue ) {

		/* Assign our internal variables */
		$this->gform   = $gform;
		$this->log     = $log;
		$this->data    = $data;
		$this->misc    = $misc;
		$this->notices = $notices;
		$this->queue   = $queue;
	}

	/**
	 * The Gravity PDF Installer
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function install_plugin() {
		$this->log->notice( 'Gravity PDF Installed' );
		update_option( 'gfpdf_is_installed', true );
		$this->data->is_installed = true;

		/* See https://gravitypdf.com/documentation/v5/gfpdf_plugin_installed/ for more details about this action */
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
		return 'pdf/([A-Za-z0-9]+)/([0-9]+)/?(download)?/?';
	}

	/**
	 * Get the plugin working directory name
	 *
	 * @return string
	 *
	 * @since  4.0
	 */
	public function get_working_directory() {
		/* See https://gravitypdf.com/documentation/v5/gfpdf_working_folder_name/ for more details about this filter */
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
	 * @return  boolean
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

		$template_dir   = $this->data->upload_dir . '/' . $this->data->working_folder . '/';
		$template_url   = $this->data->upload_dir_url . '/' . $this->data->working_folder . '/';
		$working_folder = $this->data->working_folder;
		$upload_dir     = $this->data->upload_dir;
		$upload_dir_url = $this->data->upload_dir_url;

		/* Legacy Filters */
		$this->data->template_location     = apply_filters( 'gfpdfe_template_location', $template_dir, $working_folder, $upload_dir );
		$this->data->template_location_url = apply_filters( 'gfpdfe_template_location_uri', $template_url, $working_folder, $upload_dir_url );

		/* Allow user to change directory location(s) */

		/* See https://gravitypdf.com/documentation/v5/gfpdf_template_location/ for more details about this filter */
		$this->data->template_location = apply_filters( 'gfpdf_template_location', $this->data->template_location, $working_folder, $upload_dir ); /* needs to be accessible from the web */

		/* See https://gravitypdf.com/documentation/v5/gfpdf_template_location_uri/ for more details about this filter */
		$this->data->template_location_url = apply_filters( 'gfpdf_template_location_uri', $this->data->template_location_url, $working_folder, $upload_dir_url ); /* needs to be accessible from the web */

		/* See https://gravitypdf.com/documentation/v5/gfpdf_font_location/ for more details about this filter */
		$this->data->template_font_location = apply_filters( 'gfpdf_font_location', $this->data->template_location . 'fonts/', $working_folder, $upload_dir ); /* can be in a directory not accessible via the web */

		/* See https://gravitypdf.com/documentation/v5/gfpdf_tmp_location/ for more details about this filter */
		$this->data->template_tmp_location = apply_filters( 'gfpdf_tmp_location', $this->data->template_location . 'tmp/', $working_folder, $upload_dir_url ); /* encouraged to move this to a directory not accessible via the web */

		/* See https://gravitypdf.com/documentation/v5/gfpdf_mpdf_tmp_location/ for more details about this filter */
		$mpdf_tmp_path                 = $this->data->template_tmp_location . 'mpdf';
		$this->data->mpdf_tmp_location = untrailingslashit( apply_filters( 'gfpdf_mpdf_tmp_location', $mpdf_tmp_path ) );
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

			$blog_id = get_current_blog_id();

			$template_dir   = $this->data->template_location . $blog_id . '/';
			$template_url   = $this->data->template_location_url . $blog_id . '/';
			$working_folder = $this->data->working_folder;
			$upload_dir     = $this->data->upload_dir;
			$upload_dir_url = $this->data->upload_dir_url;

			/**
			 * Allow user to change directory location(s)
			 *
			 * @internal Folder location needs to be accessible from the web
			 */

			/* Global filter */

			/* See https://gravitypdf.com/documentation/v5/gfpdf_multisite_template_location/ for more details about this filter */
			$this->data->multisite_template_location = apply_filters( 'gfpdf_multisite_template_location', $template_dir, $working_folder, $upload_dir, $blog_id );

			/* See https://gravitypdf.com/documentation/v5/gfpdf_multisite_template_location_uri/ for more details about this filter */
			$this->data->multisite_template_location_url = apply_filters( 'gfpdf_multisite_template_location_uri', $template_url, $working_folder, $upload_dir_url, $blog_id );

			/* Per-blog filters */
			$this->data->multisite_template_location     = apply_filters( 'gfpdf_multisite_template_location_' . $blog_id, $this->data->multisite_template_location, $working_folder, $upload_dir, $blog_id );
			$this->data->multisite_template_location_url = apply_filters( 'gfpdf_multisite_template_location_uri_' . $blog_id, $this->data->multisite_template_location_url, $working_folder, $upload_dir_url, $blog_id );
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
			 ( rgget( 'page' ) === 'gfpdf-getting-started' || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) )
		) {
			return null;
		}

		/* add folders that need to be checked */
		$folders = [
			$this->data->template_location,
			$this->data->template_font_location,
			$this->data->template_tmp_location,
			$this->data->mpdf_tmp_location,
			$this->data->mpdf_tmp_location . '/ttfontdata',
		];

		if ( is_multisite() ) {
			$folders[] = $this->data->multisite_template_location;
		}

		/* allow other plugins to add their own folders which should be checked */
		$folders = apply_filters( 'gfpdf_installer_create_folders', $folders );

		/* create the required folder structure, or throw error */
		foreach ( $folders as $dir ) {
			if ( ! is_dir( $dir ) ) {
				if ( ! wp_mkdir_p( $dir ) ) {
					$this->log->error(
						'Failed Creating Folder Structure',
						[
							'dir' => $dir,
						]
					);

					$this->notices->add_error( sprintf( esc_html__( 'There was a problem creating the %s directory. Ensure you have write permissions to your uploads folder.', 'gravity-forms-pdf-extended' ), '<code>' . $this->misc->relative_path( $dir ) . '</code>' ) );
				}
			} else {
				/* test the directory is currently writable by the web server, otherwise throw an error */
				if ( ! wp_is_writable( $dir ) ) {
					$this->log->error(
						'Failed Write Permissions Check.',
						[
							'dir' => $dir,
						]
					);

					$this->notices->add_error( sprintf( esc_html__( 'Gravity PDF does not have write permission to the %s directory. Contact your web hosting provider to fix the issue.', 'gravity-forms-pdf-extended' ), '<code>' . $this->misc->relative_path( $dir ) . '</code>' ) );
				}
			}
		}

		/* create blank index file in all folders to prevent web servers listing the entire directory */
		if ( is_dir( $this->data->template_location ) && ! is_file( $this->data->template_location . 'index.html' ) ) {
			GFCommon::recursive_add_index_file( $this->data->template_location );
		}

		/* create deny htaccess file to prevent direct access to files */
		if ( is_dir( $this->data->template_tmp_location ) ) {
			if ( ! is_file( $this->data->template_tmp_location . 'index.html' ) ) {
				GFCommon::recursive_add_index_file( $this->data->template_tmp_location );
			}

			if ( ! is_file( $this->data->template_tmp_location . '.htaccess' ) ) {
				$this->log->notice( 'Create Apache .htaccess Security file' );
				file_put_contents( $this->data->template_tmp_location . '.htaccess', 'deny from all' );
			}
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
		global $wp_rewrite;

		/* Create two regex rules to account for users with "index.php" in the URL */
		$query = [
			'^' . $this->data->permalink,
			'^' . $wp_rewrite->index . '/' . $this->data->permalink,
		];

		$rewrite_to = 'index.php?gpdf=1&pid=$matches[1]&lid=$matches[2]&action=$matches[3]';

		/* Add our main endpoint */
		add_rewrite_rule( $query[0], $rewrite_to, 'top' );
		add_rewrite_rule( $query[1], $rewrite_to, 'top' );

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
		global $wp;

		/* Conditionally register rewrite tags to prevent conflict with other plugins */
		if ( ! empty( $_GET['gpdf'] ) || ! empty( $_GET['gf_pdf'] ) || strpos( $wp->matched_query, 'gpdf=1' ) === 0 ) {
			$tags[] = 'gpdf';
			$tags[] = 'pid';
			$tags[] = 'lid';
			$tags[] = 'action';
		}

		return $tags;
	}

	/**
	 * Check if we need to force the rewrite rules to be flushed
	 *
	 * @param array $regex The rules to check
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function maybe_flush_rewrite_rules( $regex ) {

		$rules = get_option( 'rewrite_rules' );

		foreach ( $regex as $rule ) {
			if ( ! isset( $rules[ $rule ] ) ) {
				$this->log->notice( 'Flushing WordPress Rewrite Rules.' );
				flush_rewrite_rules( false );
				break;
			}
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
		do_action( 'gfpdf_pre_uninstall_plugin' );

		/* Clean up database */
		if ( is_multisite() ) {
			$sites = get_sites();

			foreach ( $sites as $site ) {
				$site = (array) $site; /* Back-compat: ensure the new site object introduced in 4.6 gets converted back to an array */
				switch_to_blog( $site['blog_id'] );
				$this->remove_plugin_options();
				$this->remove_plugin_form_settings();
			}
			restore_current_blog();

		} else {
			$this->remove_plugin_options();
			$this->remove_plugin_form_settings();
		}

		/* Removes background processes */
		$this->queue->clear_scheduled_events();
		$this->queue->clear_queue( true );
		$this->queue->unlock_process();

		/* Remove folder structure and deactivate */
		$this->remove_folder_structure();

		do_action( 'gfpdf_post_uninstall_plugin' );

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

		$forms = $this->gform->get_forms();

		foreach ( $forms as $form ) {
			/* only update forms which have a PDF configuration */
			if ( isset( $form['gfpdf_form_settings'] ) ) {
				unset( $form['gfpdf_form_settings'] );
				if ( $this->gform->update_form( $form ) !== true ) {
					$this->log->error(
						'Cannot Remove PDF Settings from Form.',
						[
							'form_id' => $form['id'],
						]
					);

					$this->notices->add_error( sprintf( esc_html__( 'There was a problem removing the Gravity Form "%s" PDF configuration. Try delete manually.', 'gravity-forms-pdf-extended' ), $form['id'] . ': ' . $form['title'] ) );
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

		$paths = apply_filters(
			'gfpdf_uninstall_path',
			[
				$this->data->template_font_location,
				$this->data->template_tmp_location,
				$this->data->template_location,
			]
		);

		foreach ( $paths as $dir ) {
			if ( is_dir( $dir ) ) {
				$results = $this->misc->rmdir( $dir );

				if ( is_wp_error( $results ) || ! $results ) {
					$this->log->error(
						'Cannot Remove Folder Structure.',
						[
							'WP_Error_Message' => $results->get_error_message(),
							'WP_Error_Code'    => $results->get_error_code(),
							'dir'              => $dir,
						]
					);

					$this->notices->add_error( sprintf( esc_html__( 'There was a problem removing the %s directory. Clean up manually via (S)FTP.', 'gravity-forms-pdf-extended' ), '<code>' . $this->misc->relative_path( $dir ) . '</code>' ) );
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
