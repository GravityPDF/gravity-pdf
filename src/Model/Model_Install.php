<?php

namespace GFPDF\Model;

use GFCommon;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Form;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Pdf_Queue;
use Psr\Log\LoggerInterface;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
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
	 * @var Helper_Data
	 *
	 * @since 4.0
	 */
	protected $data;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 *
	 * @var Helper_Misc
	 *
	 * @since 4.0
	 */
	protected $misc;

	/**
	 * Holds our Helper_Notices object
	 * which we can use to queue up admin messages for the user
	 *
	 * @var Helper_Notices
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
	 * @var Model_Uninstall
	 *
	 * @since 6.0
	 */
	protected $uninstall;

	/**
	 * Setup our class by injecting all our dependencies
	 *
	 * @param LoggerInterface      $log     Our logger class
	 * @param Helper_Data          $data    Our plugin data store
	 * @param Helper_Misc          $misc    Our miscellaneous class
	 * @param Helper_Notices       $notices Our notice class used to queue admin messages and errors
	 * @param Helper_Pdf_Queue     $queue
	 *
	 * @since 4.0
	 */
	public function __construct( LoggerInterface $log, Helper_Data $data, Helper_Misc $misc, Helper_Notices $notices, Helper_Pdf_Queue $queue, Model_Uninstall $uninstall ) {

		/* Assign our internal variables */
		$this->log       = $log;
		$this->data      = $data;
		$this->misc      = $misc;
		$this->notices   = $notices;
		$this->queue     = $queue;
		$this->uninstall = $uninstall;
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

		/* See https://docs.gravitypdf.com/v6/developers/actions/gfpdf_fully_loaded for more details about this action */
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
		/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_working_folder_name/ for more details about this filter */
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

		/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_template_location/ for more details about this filter */
		$this->data->template_location = apply_filters( 'gfpdf_template_location', $this->data->template_location, $working_folder, $upload_dir ); /* needs to be accessible from the web */

		/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_template_location_uri/ for more details about this filter */
		$this->data->template_location_url = apply_filters( 'gfpdf_template_location_uri', $this->data->template_location_url, $working_folder, $upload_dir_url ); /* needs to be accessible from the web */

		/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_font_location/ for more details about this filter */
		$this->data->template_font_location = apply_filters( 'gfpdf_font_location', $this->data->template_location . 'fonts/', $working_folder, $upload_dir ); /* can be in a directory not accessible via the web */

		/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_tmp_location/ for more details about this filter */
		$this->data->template_tmp_location = apply_filters( 'gfpdf_tmp_location', $this->data->template_location . 'tmp/', $working_folder, $upload_dir_url ); /* encouraged to move this to a directory not accessible via the web */

		/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_mpdf_tmp_location/ for more details about this filter */
		$mpdf_tmp_path                 = $this->data->template_tmp_location . 'mpdf';
		$this->data->mpdf_tmp_location = untrailingslashit( apply_filters( 'gfpdf_mpdf_tmp_location', $mpdf_tmp_path ) );
	}

	/**
	 * If running a multisite we'll setup the path to the current multisite folder
	 *
	 * @return void
	 * @since 4.0
	 *
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

			/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_multisite_template_location/ for more details about this filter */
			$this->data->multisite_template_location = apply_filters( 'gfpdf_multisite_template_location', $template_dir, $working_folder, $upload_dir, $blog_id );

			/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_multisite_template_location_uri/ for more details about this filter */
			$this->data->multisite_template_location_url = apply_filters( 'gfpdf_multisite_template_location_uri', $template_url, $working_folder, $upload_dir_url, $blog_id );

			/* Per-blog filters */
			$this->data->multisite_template_location     = apply_filters( 'gfpdf_multisite_template_location_' . $blog_id, $this->data->multisite_template_location, $working_folder, $upload_dir, $blog_id );
			$this->data->multisite_template_location_url = apply_filters( 'gfpdf_multisite_template_location_uri_' . $blog_id, $this->data->multisite_template_location_url, $working_folder, $upload_dir_url, $blog_id );
		}
	}

	/**
	 * Create the appropriate folder structure automatically
	 * The upload directory should have all appropriate permissions to allow this kind of manipulation
	 * but devs who tap into the gfpdfe_template_location filter will need to ensure we can write to the appropriate folder
	 *
	 * @return void
	 * @since 4.0
	 *
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
	 * @return void
	 * @since 4.0
	 *
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
	 * @param array $tags
	 *
	 * @return array
	 * @since 4.0
	 *
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
	 * @return void
	 * @since 4.0
	 *
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
	 * @deprecated 6.0
	 *
	 * @since 4.0
	 */
	public function uninstall_plugin() {
		$this->uninstall->uninstall_plugin();
	}

	/**
	 * Remove and options stored in the database
	 *
	 * @deprecated 6.0
	 *
	 * @since 4.0
	 */
	public function remove_plugin_options() {
		$this->uninstall->remove_plugin_options();
	}

	/**
	 * Remove all form settings from each individual form.
	 * Because we stored out PDF settings with each form and have no index we need to individually load and forms and check them for Gravity PDF settings
	 *
	 * @deprecated 6.0
	 *
	 * @since 4.0
	 */
	public function remove_plugin_form_settings() {
		$this->uninstall->remove_plugin_form_settings();
	}

	/**
	 * Remove our PDF directory structure
	 *
	 * @deprecated 6.0
	 *
	 * @since 4.0
	 */
	public function remove_folder_structure() {
		$this->uninstall->remove_folder_structure();
	}

	/**
	 * Deactivate Gravity PDF
	 *
	 * @deprecated 6.0
	 *
	 * @since 4.0
	 */
	public function deactivate_plugin() {
		$this->uninstall->deactivate_plugin();
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
