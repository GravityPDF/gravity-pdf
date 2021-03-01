<?php

namespace GFPDF\View;

use GFPDF\Helper\Helper_Abstract_View;
use WP_Error;

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
 * Class View_GravityForm_Settings_Markup
 *
 * @package GFPDF\View
 */
class View_GravityForm_Settings_Markup extends Helper_Abstract_View {
	const ENABLE_PANEL_TITLE  = 1;
	const DISABLE_PANEL_TITLE = 0;

	/**
	 * @var string
	 */
	protected $view_type = 'GravityForms';

	/**
	 * @param $sections
	 *
	 * @return string
	 */
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

	/**
	 * @param $id
	 *
	 * @return array|mixed
	 */
	public function get_section_fields( $id ) {
		global $wp_settings_fields;

		if ( ! isset( $wp_settings_fields[ $id ][ $id ] ) ) {
			return [];
		}

		return $wp_settings_fields[ $id ][ $id ];
	}

	/**
	 * @param       $section_id
	 * @param array $overrides
	 *
	 * @return array
	 */
	public function do_settings_fields_as_individual_fieldset( $section_id, $overrides = [] ) {

		$section = [];
		foreach ( $this->get_section_fields( $section_id ) as $field ) {
			$id      = $field['args']['id'];
			$content = $this->get_field_content( $field );

			$section[] = [
				'id'            => $id,
				'width'         => isset( $overrides[ $id ]['width'] ) ? $overrides[ $id ]['width'] : 'half',
				'title'         => $field['title'],
				'desc'          => isset( $overrides[ $id ]['desc'] ) ? $overrides[ $id ]['desc'] : '',
				'content'       => $content,
				'content_class' => isset( $overrides[ $id ]['content_class'] ) ? $overrides[ $id ]['content_class'] : '',
				'tooltip'       => ! empty( $field['args']['tooltip'] ) ? $this->get_tooltip_markup( $field['args']['tooltip'] ) : '',
				'collapsible'   => isset( $overrides[ $id ]['collapsible'] ) ? (bool) $overrides[ $id ]['collapsible'] : false,
			];
		}

		return $section;
	}

	/**
	 * @param     $field
	 * @param int $output_title
	 *
	 * @return bool|string|WP_Error
	 */
	public function get_field_content( $field, $output_title = self::DISABLE_PANEL_TITLE ) {

		$class = 'gform-settings-field gfpdf-settings-field-wrapper';

		$args = [
			'id'            => 'gfpdf-settings-field-wrapper-' . $field['args']['id'],
			'class'         => isset( $field['args']['class'] ) ? $field['args']['class'] . ' ' . $class : $class,
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

	/**
	 * @param $html
	 *
	 * @return string
	 */
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
