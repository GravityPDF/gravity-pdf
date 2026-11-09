<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Trait_Removed_Methods;
use GFPDF\Model\Model_Font_Downloader;
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
 * The Core Font installer's AJAX endpoint, removed in 6.19.0 now core fonts download on their own
 *
 * Kept as an inert stub so a third party building or calling it gets a deprecation notice instead of a fatal error.
 * It registers no hooks.
 *
 * @package GFPDF\Controller
 *
 * @since      5.0
 * @deprecated 6.19.0 Core fonts download in the background, or when a PDF needs them (Model_Font_Downloader)
 */
class Controller_Save_Core_Fonts extends Helper_Abstract_Controller implements Helper_Interface_Actions {

	use Helper_Trait_Removed_Methods;

	/**
	 * @since 6.19.0
	 */
	protected static function get_removed_methods(): array {
		return [
			'save_core_font'         => '6.19.0',
			'download_and_save_font' => '6.19.0',
			'get_downloader'         => '6.19.0',
		];
	}

	/**
	 * Kept for subclasses that read them
	 *
	 * @var LoggerInterface
	 *
	 * @since 5.0
	 */
	protected $log;

	/**
	 * @var Helper_Data
	 *
	 * @since 5.0
	 */
	protected $data;

	/**
	 * @var Helper_Misc
	 *
	 * @since 5.0
	 */
	protected $misc;

	/**
	 * @var Model_Font_Downloader|null
	 *
	 * @since 6.19.0
	 */
	protected $downloader;

	/**
	 * @param LoggerInterface            $log
	 * @param Helper_Data                $data
	 * @param Helper_Misc                $misc
	 * @param Model_Font_Downloader|null $downloader
	 *
	 * @since 5.0
	 * @since 6.19.0 Deprecated
	 */
	public function __construct( LoggerInterface $log, Helper_Data $data, Helper_Misc $misc, ?Model_Font_Downloader $downloader = null ) {
		_deprecated_class( static::class, '6.19.0' );

		$this->log        = $log;
		$this->data       = $data;
		$this->misc       = $misc;
		$this->downloader = $downloader;
	}

	/**
	 * @since 5.0
	 * @since 6.19.0 Does nothing
	 */
	public function init() {}

	/**
	 * @since 5.0
	 * @since 6.19.0 Does nothing: the `wp_ajax_gfpdf_save_core_font` endpoint is gone
	 */
	public function add_actions() {}
}
