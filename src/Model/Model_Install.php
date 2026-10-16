<?php

namespace GFPDF\Model;

use GFCommon;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Form;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Trait_Removed_Methods;
use GFPDF_Vendor\Psr\Log\LoggerInterface;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
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

	use Helper_Trait_Removed_Methods;

	/**
	 * Denies web access to the tmp folder on Apache 2.2 and 2.4, and turns off directory listings
	 *
	 * @since 7.0
	 */
	const TMP_HTACCESS = "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tOrder allow,deny\n\tDeny from all\n</IfModule>\nOptions -Indexes\n";

	/**
	 * The option check_folder_permissions() stores the folders it couldn't write to in, each with when it started failing
	 *
	 * @since 6.17.3
	 */
	const UNWRITABLE_FOLDERS = 'gfpdf_unwritable_folders';

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
	 *
	 * @since 4.0
	 */
	public function __construct( LoggerInterface $log, Helper_Data $data, Helper_Misc $misc, Helper_Notices $notices, Model_Uninstall $uninstall ) {

		/* Assign our internal variables */
		$this->log       = $log;
		$this->data      = $data;
		$this->misc      = $misc;
		$this->notices   = $notices;
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

		/* See https://docs.gravitypdf.com/developers/actions/gfpdf_fully_loaded for more details about this action */
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
		/* See https://docs.gravitypdf.com/developers/filters/gfpdf_working_folder_name/ for more details about this filter */
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

		/* Allow user to change directory location(s) */

		/* See https://docs.gravitypdf.com/developers/filters/gfpdf_template_location/ for more details about this filter */
		$this->data->template_location = trailingslashit( apply_filters( 'gfpdf_template_location', $template_dir, $working_folder, $upload_dir ) ); /* needs to be accessible from the web */

		/* See https://docs.gravitypdf.com/developers/filters/gfpdf_template_location_uri/ for more details about this filter */
		$this->data->template_location_url = trailingslashit( apply_filters( 'gfpdf_template_location_uri', $template_url, $working_folder, $upload_dir_url ) ); /* needs to be accessible from the web */

		/* See https://docs.gravitypdf.com/developers/filters/gfpdf_font_location/ for more details about this filter */
		$this->data->template_font_location = trailingslashit( apply_filters( 'gfpdf_font_location', $this->data->template_location . 'fonts/', $working_folder, $upload_dir ) ); /* can be in a directory not accessible via the web */

		/* See https://docs.gravitypdf.com/developers/filters/gfpdf_tmp_location/ for more details about this filter */
		$this->data->template_tmp_location = trailingslashit( apply_filters( 'gfpdf_tmp_location', $this->data->template_location . 'tmp/', $working_folder, $upload_dir_url ) ); /* encouraged to move this to a directory not accessible via the web */

		/* See https://docs.gravitypdf.com/developers/filters/gfpdf_mpdf_tmp_location/ for more details about this filter */
		$this->data->mpdf_tmp_location = untrailingslashit( apply_filters( 'gfpdf_mpdf_tmp_location', $this->data->template_tmp_location ) );
	}

	/**
	 * If running a multisite we'll setup the path to the current multisite folder
	 *
	 * @return void
	 * @since 4.0
	 *
	 */
	public function setup_multisite_template_location() {

		if ( ! is_multisite() ) {
			return;
		}

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

		/* See https://docs.gravitypdf.com/developers/filters/gfpdf_multisite_template_location/ for more details about this filter */
		$this->data->multisite_template_location = trailingslashit( apply_filters( 'gfpdf_multisite_template_location', $template_dir, $working_folder, $upload_dir, $blog_id ) );

		/* See https://docs.gravitypdf.com/developers/filters/gfpdf_multisite_template_location_uri/ for more details about this filter */
		$this->data->multisite_template_location_url = trailingslashit( apply_filters( 'gfpdf_multisite_template_location_uri', $template_url, $working_folder, $upload_dir_url, $blog_id ) );

		/* Per-blog filters */
		$this->data->multisite_template_location     = trailingslashit( apply_filters( 'gfpdf_multisite_template_location_' . $blog_id, $this->data->multisite_template_location, $working_folder, $upload_dir, $blog_id ) );
		$this->data->multisite_template_location_url = trailingslashit( apply_filters( 'gfpdf_multisite_template_location_uri_' . $blog_id, $this->data->multisite_template_location_url, $working_folder, $upload_dir_url, $blog_id ) );
	}

	/**
	 * The folders Gravity PDF needs to write to
	 *
	 * @return string[]
	 *
	 * @since 6.17.3
	 */
	protected function get_folders(): array {
		$folders = array_merge(
			[
				$this->data->template_location,
				$this->data->template_font_location,
			],
			$this->misc->get_tmp_locations()
		);

		if ( is_multisite() ) {
			$folders[] = $this->data->multisite_template_location;
		}

		/* allow other plugins to add their own folders which should be checked */
		return apply_filters( 'gfpdf_installer_create_folders', $folders );
	}

	/**
	 * Test writing to each folder, and store the ones that fail for the dismissible notice in Controller_Actions.
	 * Run on install, upgrade, the tmp cleanup cron and the system report, not on every request.
	 *
	 * @return string[] The folders that can't be written to
	 *
	 * @since 6.17.3
	 */
	public function check_folder_permissions(): array {
		$previous   = (array) get_option( self::UNWRITABLE_FOLDERS, [] );
		$unwritable = [];

		foreach ( $this->get_folders() as $dir ) {
			if ( is_dir( $dir ) && ! $this->misc->is_directory_writable( $dir ) ) {
				$this->log->error(
					'Failed Write Permissions Check.',
					[
						'dir' => $dir,
					]
				);

				/* Keep when it started failing, so a dismissal of this run of failures still holds */
				$unwritable[ $dir ] = $previous[ $dir ] ?? time();
			}
		}

		update_option( self::UNWRITABLE_FOLDERS, $unwritable, true );

		return array_keys( $unwritable );
	}

	/**
	 * Create the appropriate folder structure automatically
	 * The upload directory should have all appropriate permissions to allow this kind of manipulation
	 * but devs who tap into the gfpdf_template_location filter will need to ensure we can write to the appropriate folder
	 *
	 * @return void
	 * @since 4.0
	 * @since 6.17.3 The write permission notice moved to Controller_Actions
	 *
	 */
	public function create_folder_structures() {

		/* don't create the folder structure if an AJAX or REST API request */
		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return null;
		}

		/* create the required folder structure, or throw error */
		foreach ( $this->get_folders() as $dir ) {
			if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
				$this->log->error(
					'Failed Creating Folder Structure',
					[
						'dir' => $dir,
					]
				);

				/* translators: %s: directory path wrapped in <code> tags */
				$this->notices->add_error( sprintf( esc_html__( 'There was a problem creating the %s directory. Ensure you have write permissions to your uploads folder.', 'gravity-pdf' ), '<code>' . $this->misc->relative_path( $dir ) . '</code>' ) );
			}

			/* create blank index file in all folders to prevent web servers listing the entire directory */
			if ( ! is_file( trailingslashit( $dir ) . 'index.html' ) ) {
				file_put_contents( trailingslashit( $dir ) . 'index.html', '' );
			}
		}

		/* create deny htaccess file to prevent direct access to files */
		if ( self::write_tmp_htaccess( $this->data->template_tmp_location ) ) {
			$this->log->notice( 'Create Apache .htaccess Security file' );
		}
	}

	/**
	 * Write the tmp folder's .htaccess if it's missing
	 *
	 * @param string $tmp_location
	 * @param bool   $replace_legacy Also replace the original `deny from all`, which Apache 2.4 ignores without
	 *                               mod_access_compat. A customised file is always left alone.
	 *
	 * @return bool Whether it was written
	 *
	 * @since 7.0
	 */
	public static function write_tmp_htaccess( $tmp_location, $replace_legacy = false ) {
		$file = $tmp_location . '.htaccess';
		if ( ! is_dir( $tmp_location ) ) {
			return false;
		}

		if ( is_file( $file ) && ! ( $replace_legacy && trim( (string) file_get_contents( $file ) ) === 'deny from all' ) ) { //phpcs:ignore
			return false;
		}

		return file_put_contents( $file, self::TMP_HTACCESS ) !== false;
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
	 * Conditionally register the PDF custom rewrite rules
	 *
	 * @param array $tags
	 *
	 * @return array
	 * @since 4.0
	 *
	 */
	public function register_rewrite_tags( $tags ) {
		global $wp;

		/* phpcs:ignore WordPress.Security.NonceVerification.Recommended */
		if ( ! empty( $_GET['gpdf'] ) || strpos( $wp->matched_query, 'gpdf=1' ) === 0 ) {
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
