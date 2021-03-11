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
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
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

	public function init() {
		if ( is_admin() && rgget( 'page' ) === 'gf_settings' && rgget( 'subview' ) === 'uninstall' ) {
			\GFAddOn::register( Controller_Uninstaller::class );
		}
	}

	public function get_short_title(): string {
		return 'Gravity PDF';
	}

	public function get_menu_icon() {
		return 'dashicons-media-document';
	}

	public function current_user_can_uninstall(): bool {
		if ( is_multisite() ) {
			return is_super_admin();
		}

		return $this->gform->has_capability( 'gravityforms_uninstall' );
	}

	/**
	 * Override this function to customize the markup for the uninstall section on the plugin settings page.
	 *
	 * @since Unknown
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

	public function uninstall_addon() {
		if ( ! $this->current_user_can_uninstall() ) {
			return;
		}

		$this->model->uninstall_plugin();
	}

	public function method_is_overridden( $name ) {
		return false;
	}
}
