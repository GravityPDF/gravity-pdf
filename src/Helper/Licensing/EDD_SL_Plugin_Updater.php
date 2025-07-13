<?php

namespace GFPDF\Helper\Licensing;

/**
 * @package     Gravity PDF
 * @author      Easy Digital Downloads
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.2
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stub for WP.org
 */
class EDD_SL_Plugin_Updater {
	public function __construct( $_api_url, $_plugin_file, $_api_data = null ) {}
	public function init() {}
	public function check_update( $_transient_data ) {}
	public function get_repo_api_data() {}
	public function show_update_notification( $file, $plugin ) {}
	public function plugins_api_filter( $_data, $_action = '', $_args = null ) {}
	public function http_request_args( $args, $url ) {}
	public function show_changelog() {}
	public function get_cached_version_info( $cache_key = '' ) {}
	public function set_version_info_cache( $value = '', $cache_key = '' ) {}
}
