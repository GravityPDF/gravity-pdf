<?php

namespace GFPDF\View;

use GFPDF\Helper\Helper_Abstract_View;
use WP_Error;

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
	 * @param array $sections
	 * @param bool  $should_output
	 *
	 * @return string
	 */
	public function do_settings_sections( $sections, $should_output = false ) {
		$markup = '';
		foreach ( $sections as $section ) {
			$markup .= $this->fieldset( $section, $should_output );
		}

		return $markup;
	}

	/**
	 * @param array $args
	 * @param bool  $should_output
	 *
	 * @return string
	 */
	public function fieldset( $args, $should_output = false ) {
		return $this->load( 'fieldset', $args, $should_output );
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
			$this->get_field_content( $field, $output_title, true );
		}

		return ob_get_clean();
	}

	/**
	 * @param string $id
	 * @param int    $output_title
	 *
	 * @since 6.4.0
	 */
	public function output_settings_fields( string $id, int $output_title = self::DISABLE_PANEL_TITLE ): void {
		foreach ( (array) $this->get_section_fields( $id ) as $field ) {
			$this->get_field_content( $field, $output_title, true );
		}
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
			$id = $field['args']['id'];

			$section[] = [
				'id'            => $id,
				'width'         => $overrides[ $id ]['width'] ?? 'half',
				'title'         => $field['title'],
				'desc'          => $overrides[ $id ]['desc'] ?? '',
				'callback'      => function () use ( $field ) {
					$this->get_field_content( $field, self::DISABLE_PANEL_TITLE, true );
				},
				'content_class' => $overrides[ $id ]['content_class'] ?? '',
				'tooltip'       => ! empty( $field['args']['tooltip'] ) ? $this->get_tooltip_markup( $field['args']['tooltip'] ) : '',
				'collapsible'   => isset( $overrides[ $id ]['collapsible'] ) ? (bool) $overrides[ $id ]['collapsible'] : false,
			];
		}

		return $section;
	}

	/**
	 * @param     $field
	 * @param int $output_title
	 * @param bool $should_output
	 *
	 * @return bool|string|WP_Error
	 */
	public function get_field_content( $field, int $output_title = self::DISABLE_PANEL_TITLE, bool $should_output = false ) {

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

		return $this->load( 'settings_field', $args, $should_output );
	}

	/**
	 * @param $html
	 *
	 * @return string
	 */
	public function get_tooltip_markup( $html ) {
		$name = 'gfpdf_tooltip';

		$register = function ( $tooltips ) use ( $name, $html ) {
			$tooltips[ $name ] = $html;

			return $tooltips;
		};

		add_filter( 'gform_tooltips', $register );
		$tooltip = gform_tooltip( $name, 'gfpdf-tooltip', true );
		remove_filter( 'gform_tooltips', $register );

		return $tooltip;
	}
}
