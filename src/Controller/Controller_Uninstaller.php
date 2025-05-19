<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Helper\Helper_Form;
use GFPDF\Helper\Helper_Pdf_Queue;
use GFPDF\Model\Model_Uninstall;
use GFPDF\View\View_Uninstaller;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Helper_Uninstaller
 *
 * @package GFPDF\Helper
 */
class Controller_Uninstaller extends Helper_Abstract_Controller {

	/**
	 * @var self
	 */
	protected static $instance;

	/**
	 * @var Helper_Form
	 */
	protected $gform;

	/**
	 * @var Model_Uninstall $model
	 */
	public $model;

	public static function get_instance(): Controller_Uninstaller {
		if ( self::$instance === null ) {
			$log   = \GPDFAPI::get_log_class();
			$gform = \GPDFAPI::get_form_class();

			self::$instance = new self(
				new Model_Uninstall( $gform, $log, \GPDFAPI::get_data_class(), \GPDFAPI::get_misc_class(), \GPDFAPI::get_notice_class(), new Helper_Pdf_Queue( $log ) ),
				new View_Uninstaller(),
				$gform
			);
		}

		return self::$instance;
	}

	public function __construct( Helper_Abstract_Model $model, Helper_Abstract_View $view, Helper_Form $gform ) {
		$this->model = $model;
		$this->view  = $view;
		$this->gform = $gform;
	}

	/**
	 * Only register our uninstaller with Gravity Forms when on the uninstall admin page
	 *
	 * @since 6.0
	 */
	public function init() {
		if ( is_admin() && rgget( 'page' ) === 'gf_settings' && rgget( 'subview' ) === 'uninstall' ) {
			\GFAddOn::register( __CLASS__ );
		}
	}

	/**
	 * Display in the uninstall UI
	 *
	 * @since 6.0
	 */
	public function get_short_title(): string {
		return 'Gravity PDF';
	}

	/**
	 * Display in the uninstall UI
	 *
	 * @since 6.0
	 */
	public function get_menu_icon(): string {
		return 'gform-icon--gravity-pdf';
	}

	/**
	 * Verify the current user can uninstall capabilities for Gravity PDF
	 *
	 * @since 6.0
	 */
	public function current_user_can_uninstall(): bool {
		if ( is_multisite() ) {
			return is_super_admin();
		}

		return $this->gform->has_capability( 'gravityforms_uninstall' );
	}

	/**
	 * Output the uninstall UI
	 *
	 * @since 6.0
	 */
	public function render_uninstall() {
		if ( ! $this->current_user_can_uninstall() ) {
			return;
		}

		$args = [
			'icon'  => \GFCommon::get_icon_markup( [ 'icon' => $this->get_menu_icon() ], 'dashicon-admin-generic' ),
			'title' => $this->get_short_title(),
		];

		$this->view->uninstall_button( $args );
	}

	/**
	 * Run the uninstaller after verifying capabilities
	 *
	 * @since 6.0
	 */
	public function uninstall_addon() {
		if ( ! $this->current_user_can_uninstall() ) {
			return;
		}

		$this->model->uninstall_plugin();
	}

	/**
	 * Polyfill method so GF doesn't complain when handling the uninstall UI
	 *
	 * @since 6.0
	 */
	public function method_is_overridden( $name ): bool {
		return false;
	}
}
