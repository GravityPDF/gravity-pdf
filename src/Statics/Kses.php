<?php

namespace GFPDF\Statics;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @since 6.4.0
 */
class Kses {

	/**
	 * Echo the HTML after escaping using wp_kses()
	 *
	 * @param string $html
	 *
	 * @return void
	 *
	 * @since 6.4.0
	 */
	public static function output( string $html ): void {
		/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
		echo self::parse( $html );
	}

	/**
	 * Returns the HTML after escaping using wp_kses()
	 *
	 * @param string $html
	 *
	 * @return string
	 *
	 * @since 6.4.0
	 */
	public static function parse( string $html ): string {
		add_filter( 'safe_style_css', '\GFPDF\Statics\Kses::get_allowed_pdf_styles' );
		$html = wp_kses( $html, self::get_allowed_pdf_tags() );
		remove_filter( 'safe_style_css', '\GFPDF\Statics\Kses::get_allowed_pdf_styles' );

		return $html;
	}

	/**
	 * Get all allowed HTML tags that will be passed to wp_kses() when building HTML for the PDFs
	 *
	 * @param array|null $tags An array of existing tags to pass to wp_kses(), or null to use the allowed "post" context tags
	 *
	 * @return array
	 *
	 * @since 6.4.0
	 */
	public static function get_allowed_pdf_tags( $tags = null ): array {
		$tags = is_array( $tags ) ? $tags : wp_kses_allowed_html( 'post' );

		/* Add additional Table support */
		$tags['table']['autosize'] = true;
		$tags['table']['rotate']   = true;

		/* Add IMG rotate support */
		$tags['img']['rotate'] = true;

		/* Add <dottab /> support */
		$tags['dottab'] = [
			'dir'     => true,
			'outdent' => true,
			'id'      => true,
			'class'   => true,
			'style'   => true,
		];

		/* Add <meter /> support */
		$tags['meter'] = [
			'dir'     => true,
			'value'   => true,
			'max'     => true,
			'min'     => true,
			'low'     => true,
			'high'    => true,
			'optimum' => true,
			'type'    => true,
			'id'      => true,
			'class'   => true,
			'style'   => true,
		];

		/* Add <progress /> support */
		$tags['progress'] = [
			'dir'    => true,
			'value'  => true,
			'max'    => true,
			'width'  => true,
			'height' => true,
			'type'   => true,
			'id'     => true,
			'class'  => true,
			'style'  => true,
		];

		/* Add <pagebreak /> support */
		$tags['pagebreak'] = [
			'orientation'   => true,
			'type'          => true,
			'resetpagenum'  => true,
			'pagenumstyle'  => true,
			'suppress'      => true,
			'sheet-size'    => true,
			'page-selector' => true,
			'margin-left'   => true,
			'margin-right'  => true,
			'margin-top'    => true,
			'margin-bottom' => true,
		];

		/* Add <barcode /> support */
		$tags['barcode'] = [
			'code'   => true,
			'type'   => true,
			'text'   => true,
			'size'   => true,
			'height' => true,
			'pr'     => true,
			'id'     => true,
			'class'  => true,
			'style'  => true,
		];

		return $tags;
	}

	/**
	 * Add additional inline style properties that are valid in PDFs
	 *
	 * @param array $styles
	 *
	 * @return array
	 *
	 * @since 6.4.0
	 */
	public static function get_allowed_pdf_styles( $styles ): array {
		if ( ! is_array( $styles ) ) {
			return $styles;
		}

		return array_merge(
			$styles,
			[
				'background-image-opacity',
				'background-image-resize',
				'box-shadow',
				'hyphens',
				'page',
				'page-break-inside',
				'page-break-before',
				'page-break-after',
				'rotate',
				'z-index',
			]
		);
	}
}
