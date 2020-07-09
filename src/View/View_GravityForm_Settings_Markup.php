<?php

namespace GFPDF\View;

use GFPDF\Helper\Helper_Abstract_View;

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
 * Class View_GravityForm_Settings_Markup
 *
 * @package GFPDF\View
 */
class View_GravityForm_Settings_Markup extends Helper_Abstract_View {
	const ENABLE_PANEL_TITLE = 1;
	const DISABLE_PANEL_TITLE = 0;

	protected $view_type = 'GravityForms';

	public function do_settings_sections( $sections ) {
		$markup = '';
		foreach ( $sections as $section ) {
			$markup .= $this->fieldset( $section );
		}

		return $markup;
	}

	/**
	 * @param array $args
	 *
	 * @return string
	 */
	public function fieldset( $args ) {
		return $this->load( 'fieldset', $args, false );
	}

	/**
	 * @param string $id
	 * @param int    $output_title
	 *
	 * @return string
	 */
	public function do_settings_fields( $id, $output_title = self::DISABLE_PANEL_TITLE ) {
		global $wp_settings_fields;

		if ( ! isset( $wp_settings_fields[ $id ][ $id ] ) ) {
			return '';
		}

		ob_start();
		foreach ( (array) $wp_settings_fields[ $id ][ $id ] as $field ) {
			$args = [
				'class'         => isset( $field['args']['class'] ) ? $field['args']['class'] : '',
				'callback'      => $field['callback'],
				'callback_args' => $field['args'],
			];

			if ( $output_title === self::ENABLE_PANEL_TITLE ) {
				$args['title'] = $field['title'];
			}

			$this->load( 'settings_field', $args );
		}

		return ob_get_clean();
	}
}