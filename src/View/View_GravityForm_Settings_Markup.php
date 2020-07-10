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
		ob_start();
		foreach ( (array) $this->get_section_fields( $id ) as $field ) {
			echo $this->get_field_content( $field, $output_title );
		}

		return ob_get_clean();
	}

	public function get_section_fields( $id ) {
		global $wp_settings_fields;

		if ( ! isset( $wp_settings_fields[ $id ][ $id ] ) ) {
			return [];
		}

		return $wp_settings_fields[ $id ][ $id ];
	}

	/**
	 * @param       $id
	 * @param array $overrides
	 *
	 * @return array
	 */
	public function do_settings_fields_as_individual_fieldset( $id, $overrides = [] ) {

		$section = [];
		foreach ( $this->get_section_fields( $id ) as $field ) {
			$id      = $field['args']['id'];
			$content = $this->get_field_content( $field );

			$section[] = [
				'id'            => $id,
				'width'         => isset( $overrides[ $id ]['width'] ) ? $overrides[ $id ]['width'] : 'half',
				'title'         => $field['title'],
				'description'   => isset( $overrides[ $id ]['description'] ) ? $overrides[ $id ]['description'] : '',
				'content'       => $content,
				'content_class' => isset( $overrides[ $id ]['content_class'] ) ? $overrides[ $id ]['content_class'] : '',
				'tooltip'       => ! empty( $field['args']['tooltip'] ) ? $this->get_tooltip_markup( $field['args']['tooltip'] ) : '',
				'collapsable'   => isset( $overrides[ $id ]['collapsable'] ) ? (bool) $overrides[ $id ]['collapsable'] : false,
			];
		}

		return $section;
	}

	public function get_field_content( $field, $output_title = self::DISABLE_PANEL_TITLE ) {
		$args = [
			'class'         => isset( $field['args']['class'] ) ? $field['args']['class'] : '',
			'callback'      => $field['callback'],
			'callback_args' => $field['args'],
		];

		if ( $output_title === self::ENABLE_PANEL_TITLE ) {
			$args['title'] = $field['title'];

			if ( ! empty( $field['args']['tooltip'] ) ) {
				$args['tooltip'] = $this->get_tooltip_markup( $field['args']['tooltip'] );
			}
		}

		return $this->load( 'settings_field', $args, false );
	}

	public function get_tooltip_markup( $html ) {
		$name = 'gfpdf_tooltip';

		$register = function( $tooltips ) use ( $name, $html ) {
			$tooltips[ $name ] = $html;

			return $tooltips;
		};

		add_filter( 'gform_tooltips', $register );
		$tooltip = gform_tooltip( $name, 'gfpdf-tooltip', true );
		remove_filter( 'gform_tooltips', $register );

		return $tooltip;
	}
}