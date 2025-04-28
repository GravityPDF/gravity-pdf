<?php

namespace GFPDF\Statics;

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
	public static function output( $html ): void {
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
	public static function parse( $html ) {
		add_filter( 'safe_style_css', '\GFPDF\Statics\Kses::get_allowed_pdf_styles' );

		$html = wp_kses( (string) $html, self::get_allowed_pdf_tags(), self::get_allowed_pdf_protocols() );

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
	public static function get_allowed_pdf_tags( $tags = null ) {
		$tags = is_array( $tags ) ? $tags : wp_kses_allowed_html( 'post' );

		/* Form fields */
		$tags['form'] = [
			'id'     => true,
			'class'  => true,
			'style'  => true,
			'dir'    => true,
			'action' => true,
			'method' => true,
		];

		$tags['input'] = [
			'id'         => true,
			'class'      => true,
			'style'      => true,
			'dir'        => true,
			'type'       => true,
			'title'      => true,
			'name'       => true,
			'disabled'   => true,
			'size'       => true,
			'value'      => true,
			'maxlength'  => true,
			'readonly'   => true,
			'required'   => true,
			'checked'    => true,
			'spellcheck' => true,
			'alt'        => true,
			'src'        => true,
			'noprint'    => true,
		];

		$tags['textarea'] = [
			'id'         => true,
			'class'      => true,
			'style'      => true,
			'dir'        => true,
			'title'      => true,
			'name'       => true,
			'disabled'   => true,
			'cols'       => true,
			'rows'       => true,
			'readonly'   => true,
			'spellcheck' => true,
		];

		$tags['select'] = [
			'id'         => true,
			'class'      => true,
			'style'      => true,
			'dir'        => true,
			'size'       => true,
			'multiple'   => true,
			'required'   => true,
			'spellcheck' => true,
			'editable'   => true,
		];

		$tags['option'] = [
			'id'       => true,
			'class'    => true,
			'style'    => true,
			'dir'      => true,
			'selected' => true,
			'value'    => true,
		];

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
			'id'      => true,
			'class'   => true,
			'style'   => true,
			'width'   => true,
			'height'  => true,
		];

		/* Add <progress /> support */
		$tags['progress'] = [
			'dir'    => true,
			'value'  => true,
			'max'    => true,
			'width'  => true,
			'height' => true,
			'id'     => true,
			'class'  => true,
			'style'  => true,
		];

		/* Add <pagebreak /> support */
		$tags['pagebreak'] = [
			'orientation'      => true,
			'type'             => true,
			'resetpagenum'     => true,
			'pagenumstyle'     => true,
			'suppress'         => true,
			'sheet-size'       => true,
			'page-selector'    => true,
			'margin-left'      => true,
			'margin-right'     => true,
			'margin-top'       => true,
			'margin-bottom'    => true,
			'odd-header-name'  => true,
			'odd-footer-name'  => true,
			'odd-header-value' => true,
			'odd-footer-value' => true,
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

		/* Annotation support */
		$tags['annotation'] = [
			'content' => true,
			'pos-x'   => true,
			'pos-y'   => true,
			'icon'    => true,
			'author'  => true,
			'subject' => true,
			'opacity' => true,
			'color'   => true,
			'popup'   => true,
		];

		/* Bookmark support */
		$tags['bookmark'] = [
			'content' => true,
			'level'   => true,
		];

		/* Columns support */
		$tags['column'] = [
			'column-count' => true,
			'valign'       => true,
			'column-gap'   => true,
		];

		$tags['columnbreak'] = [];

		/* Header */
		$tags['htmlpageheader'] = [
			'name' => true,
		];

		$tags['sethtmlpageheader'] = [
			'name'           => true,
			'page'           => true,
			'value'          => true,
			'show-this-page' => true,
		];

		/* Footer */
		$tags['htmlpagefooter'] = [
			'name' => true,
		];

		$tags['sethtmlpagefooter'] = [
			'name'  => true,
			'page'  => true,
			'value' => true,
		];

		/* Index */
		$tags['indexentry'] = [
			'content' => true,
			'xref'    => true,
		];

		$tags['indexinsert'] = [
			'links'         => true,
			'usedivletters' => true,
		];

		/* Table of Contents */
		$tags['tocpagebreak'] = [
			'paging'               => true,
			'links'                => true,
			'toc-odd-header-name'  => true,
			'toc-odd-footer-name'  => true,
			'toc-odd-header-value' => true,
			'toc-odd-footer-value' => true,
			'toc-prehtml'          => true,
			'toc-posthtml'         => true,
			'toc-bookmarktext'     => true,
			'name'                 => true,
			'toc-page-selector'    => true,
			'toc-sheet-size'       => true,
			'toc-resetpagenum'     => true,
			'toc-resetpagestyle'   => true,
			'toc-suppress'         => true,
		];

		$tags['tocentry'] = [
			'content' => true,
			'level'   => true,
			'name'    => true,
		];

		return apply_filters( 'gfpdf_wp_kses_allowed_html', $tags );
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
	public static function get_allowed_pdf_styles( $styles ) {
		if ( ! is_array( $styles ) ) {
			return $styles;
		}

		$styles = array_merge(
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

		return apply_filters( 'gfpdf_wp_kses_allowed_pdf_styles', $styles );
	}

	/**
	 * A custom list of allowed protocols in the PDF
	 *
	 * @param array|null $protocols
	 *
	 * @return array
	 *
	 * @since 6.4.2
	 */
	public static function get_allowed_pdf_protocols( $protocols = null ) {
		if ( ! is_array( $protocols ) ) {
			$protocols = wp_allowed_protocols();
		}

		$protocols[] = 'data';

		/* allow Windows drive letters */
		$protocols = array_merge( $protocols, range( 'a', 'z' ) );

		return apply_filters( 'gfpdf_wp_kses_allowed_pdf_protocols', $protocols );
	}
}
