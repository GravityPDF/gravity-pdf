<?php

namespace GFPDF\Statics;

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
 * @since 6.4.0
 */
class Kses {

	/**
	 * How make_tags_inert() stops shortcodes and merge tags matching in text
	 *
	 * @since 6.18.1
	 */
	private const TAG_JOINERS = [
		'[' => "[\u{FEFF}",
		'{' => "{\u{FEFF}",
		':' => ":\u{FEFF}",
	];

	/**
	 * How make_tags_inert() stops them matching in HTML attributes
	 *
	 * @since 6.18.1
	 */
	private const URL_ENCODED_TAGS = [
		'[' => '%5B',
		']' => '%5D',
		'{' => '%7B',
		'}' => '%7D',
	];

	/**
	 * The private-use characters keep_merged_tags_inert() wraps a merge tag in: the opening marker's pair, then the closing one's
	 *
	 * @since 6.18.1
	 */
	private const MERGE_TAG_MARKERS = [
		[ "\u{E000}", "\u{E001}" ],
		[ "\u{E002}", "\u{E003}" ],
	];

	/**
	 * Counts the gform_replace_merge_tags calls keep_merged_tags_inert() marked, so nested ones keep their own markers
	 *
	 * @since 6.18.1
	 */
	private static $merge_tag_passes = 0;

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

		$html = wp_kses( self::keep_escaped_tags_inert( self::strip_scripts( (string) $html ) ), self::get_allowed_pdf_tags(), self::get_allowed_pdf_protocols() );

		remove_filter( 'safe_style_css', '\GFPDF\Statics\Kses::get_allowed_pdf_styles' );

		return $html;
	}

	/**
	 * WordPress 7.2's wp_kses() decodes character references, so an escaped `[` or `{` would come out as a live tag.
	 * A zero-width no-break space after it keeps it inert, and mPDF strips it before drawing. A URL percent-encodes it instead.
	 *
	 * @param string $html
	 *
	 * @return string
	 *
	 * @since 6.18.1
	 */
	private static function keep_escaped_tags_inert( $html ) {
		if ( strpos( $html, '&' ) === false ) {
			return $html;
		}

		$escaped = [
			'/&#(?:0*91(?![0-9])|x0*5b(?![0-9a-f]));?|&(?:lsqb|lbrack);/i',
			'/&#(?:0*123(?![0-9])|x0*7b(?![0-9a-f]));?|&(?:lcub|lbrace);/i',
		];

		$html = preg_replace_callback(
			'/(\s(?:href|src)\s*=\s*)("[^"]*"|\'[^\']*\')/i',
			function ( $url ) use ( $escaped ) {
				return $url[1] . ( preg_replace( $escaped, [ '%5B', '%7B' ], $url[2] ) ?? $url[2] );
			},
			$html
		) ?? $html;

		return preg_replace( $escaped, [ "[\u{FEFF}", "{\u{FEFF}" ], $html ) ?? $html;
	}

	/**
	 * Run a merge tag replacement, making inert any tag that the field values and custom merge tags it merges in add
	 *
	 * @param callable $replace Calls GFCommon::replace_variables() and returns the text
	 *
	 * @return mixed What $replace returns
	 *
	 * @since 6.18.1
	 */
	public static function keep_merged_tags_inert( callable $replace ) {
		/* Gravity Forms passes the value with `{` as `&#x7b;` */
		$encode = static function ( $value ) {
			return is_string( $value ) ? \GFCommon::encode_merge_tag( self::make_tags_inert( \GFCommon::decode_merge_tag( $value ) ) ) : $value;
		};

		/* Custom merge tags merge in on gform_replace_merge_tags: mark the tags left before it, encode what replaced them after */
		$passes = [];
		$mark   = static function ( $text ) use ( &$passes ) {
			$pass            = ++self::$merge_tag_passes;
			$passes[ $pass ] = [];

			return self::mark_merge_tags( $text, $pass, $passes[ $pass ] );
		};

		$unmark = static function ( $text ) use ( &$passes, $encode ) {
			if ( ! $passes ) {
				return $text;
			}

			$pass = array_key_last( $passes );

			return self::unmark_merge_tags( $text, $pass, array_pop( $passes ), $encode );
		};

		add_filter( 'gform_merge_tag_filter', $encode, PHP_INT_MAX );
		add_filter( 'gform_replace_merge_tags', $mark, PHP_INT_MIN );
		add_filter( 'gform_replace_merge_tags', $unmark, PHP_INT_MAX );

		try {
			return $replace();
		} finally {
			remove_filter( 'gform_merge_tag_filter', $encode, PHP_INT_MAX );
			remove_filter( 'gform_replace_merge_tags', $mark, PHP_INT_MIN );
			remove_filter( 'gform_replace_merge_tags', $unmark, PHP_INT_MAX );
		}
	}

	/**
	 * @param mixed    $text Another callback may have changed it to a non-string
	 * @param int      $pass Unique to the gform_replace_merge_tags call, so a nested one keeps its own markers
	 * @param string[] $tags Set to the tags marked, by position
	 *
	 * @return mixed
	 *
	 * @since 6.18.1
	 */
	private static function mark_merge_tags( $text, $pass, &$tags ) {
		if ( ! is_string( $text ) || strpos( $text, '{' ) === false ) {
			return $text;
		}

		/* Not an inert tag, or CSS's `{ `. Byte-wise, as invalid UTF-8 fails a `u` regex */
		return preg_replace_callback(
			"/\\{(?!\\s|\u{FEFF})[^{}]*\\}/",
			static function ( $tag ) use ( $pass, &$tags ) {
				$index  = count( $tags );
				$tags[] = $tag[0];

				return self::merge_tag_marker( $pass, $index ) . $tag[0] . self::merge_tag_marker( $pass, $index, true );
			},
			$text
		) ?? $text;
	}

	/**
	 * Restore the marked tags no callback replaced, and make inert what replaced the rest
	 *
	 * @param mixed    $text
	 * @param int      $pass
	 * @param string[] $tags
	 * @param callable $encode
	 *
	 * @return mixed
	 *
	 * @since 6.18.1
	 */
	private static function unmark_merge_tags( $text, $pass, $tags, $encode ) {
		if ( ! is_string( $text ) || empty( $tags ) ) {
			return $text;
		}

		$text = preg_replace_callback(
			'/' . self::merge_tag_marker( $pass, '(\d+)' ) . '(.*?)' . self::merge_tag_marker( $pass, '\1', true ) . '/s',
			static function ( $marked ) use ( $tags, $encode ) {
				return $marked[2] === ( $tags[ $marked[1] ] ?? null ) ? $marked[2] : $encode( $marked[2] );
			},
			$text
		) ?? $text;

		/* Drop any marker a callback split from its pair */
		[ $open, $close ] = self::MERGE_TAG_MARKERS;
		if ( strpos( $text, $open[0] ) === false && strpos( $text, $close[0] ) === false ) {
			return $text;
		}

		return preg_replace( "/(?:{$open[0]}|{$close[0]}){$pass}\\.\\d+(?:{$open[1]}|{$close[1]})/", '', $text ) ?? $text;
	}

	/**
	 * @param int        $pass
	 * @param int|string $index The tag's position, or a regex for it
	 * @param bool       $end   Whether it's the closing marker
	 *
	 * @return string
	 *
	 * @since 6.18.1
	 */
	private static function merge_tag_marker( $pass, $index, $end = false ) {
		[ $start, $stop ] = self::MERGE_TAG_MARKERS[ $end ? 1 : 0 ];

		return $start . $pass . '.' . $index . $stop;
	}

	/**
	 * Stop the shortcodes and merge tags in a value matching, as Helper_Abstract_Fields::encode_tags() does for a field's
	 *
	 * @param string $value The text to be converted
	 *
	 * @return string
	 *
	 * @since 6.18.1
	 */
	public static function make_tags_inert( $value ) {
		$value = (string) $value;
		if ( strpbrk( $value, '[]{}' ) === false ) {
			return $value;
		}

		/* A zero-width no-break space stops tags matching, and mPDF strips it. Added once only, so trusted defaults still match */
		$value = strtr( strtr( $value, array_flip( self::TAG_JOINERS ) ), self::TAG_JOINERS );

		/* Kses rebuilds attribute values, so URL-encode the tags there */
		if ( strpos( $value, '<' ) !== false && class_exists( '\WP_HTML_Tag_Processor' ) ) {
			$processor = new \WP_HTML_Tag_Processor( $value );
			while ( $processor->next_tag() ) {
				foreach ( $processor->get_attribute_names_with_prefix( '' ) as $name ) {
					$attribute = $processor->get_attribute( $name );
					if ( is_string( $attribute ) && strpos( $attribute, "\u{FEFF}" ) !== false ) {
						$processor->set_attribute( $name, self::encode_attribute_tags( $name, str_replace( "\u{FEFF}", '', $attribute ) ) );
					}
				}
			}

			$value = $processor->get_updated_html();
		}

		return $value;
	}

	/**
	 * URL-encode an attribute's tags, and stop a merge tag in the text ending on one of its colons
	 *
	 * @param string $name  The lowercase attribute name
	 * @param string $value The attribute value
	 *
	 * @return string
	 *
	 * @since 6.18.1
	 */
	public static function encode_attribute_tags( $name, $value ) {
		$value = strtr( $value, self::URL_ENCODED_TAGS );
		$colon = [ ':' => self::TAG_JOINERS[':'] ];

		/* A URL keeps its scheme */
		if ( in_array( $name, wp_kses_uri_attributes(), true ) ) {
			return preg_replace( '/^[a-z][a-z0-9+.-]*:(*SKIP)(*F)|:/i', '%3A', $value ) ?? '';
		}

		/* A style property keeps its colon when it's the declaration's only one */
		if ( $name === 'style' ) {
			return implode(
				';',
				array_map(
					function ( $declaration ) use ( $colon ) {
						return substr_count( $declaration, ':' ) > 1 ? strtr( $declaration, $colon ) : $declaration;
					},
					explode( ';', $value )
				)
			);
		}

		return strtr( $value, $colon );
	}

	/**
	 * Remove each <script> element along with its code. wp_kses() only strips the tags, and mPDF would print the code as text.
	 *
	 * @param string $html
	 *
	 * @return string
	 *
	 * @since 6.18.0
	 */
	public static function strip_scripts( $html ) {
		$html = (string) $html;

		/* preg_replace() returns null when a large script hits pcre.backtrack_limit; keep the content */
		return preg_replace( '#<script\b[^>]*>.*?</script\s*>#is', '', $html ) ?? $html;
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
