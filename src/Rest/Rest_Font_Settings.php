<?php

declare( strict_types=1 );

namespace GFPDF\Rest;

use GFPDF\Fonts\Language_To_Font;
use GFPDF\Fonts\Registry;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Abstract_Options;
use WP_REST_Response;
use WP_REST_Server;

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
 * Read and write the four language settings the Font Manager owns
 *
 * They live in `gfpdf_settings` with everything else (§4.8) but never appear on the settings page: a language map
 * is a table of eighty rows against a font list, which is a screen rather than a field.
 *
 * @since 7.0
 */
class Rest_Font_Settings extends Rest_Font_Base {

	/**
	 * @since 7.0
	 */
	public const API_BASE = '/fonts/settings';

	/**
	 * @var Registry
	 * @since 7.0
	 */
	protected $registry;

	/**
	 * @var Helper_Abstract_Options
	 * @since 7.0
	 */
	protected $options;

	public function __construct( Registry $registry, Helper_Abstract_Options $options, Helper_Abstract_Form $gform ) {
		$this->registry = $registry;
		$this->options  = $options;
		$this->gform    = $gform;
	}

	/**
	 * @since 7.0
	 */
	public function register_routes() {
		register_rest_route(
			static::NAMESPACE,
			static::API_BASE,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'update_settings' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => [
						'default_pdf_language'    => [
							'type'              => 'string',
							'sanitize_callback' => [ $this, 'sanitize_language' ],
						],
						'document_script'         => [
							'type'              => 'string',
							'sanitize_callback' => [ $this, 'sanitize_script' ],
						],
						'auto_install_fonts'      => [
							'type' => 'boolean',
						],
						'font_language_overrides' => [
							'type' => 'object',
						],
					],
				],
			]
		);
	}

	/**
	 * The language settings, and the map they act on
	 *
	 * The overrides themselves are not sent: they are the rows where `font !== default_font`, and a second copy
	 * could only ever contradict the first.
	 *
	 * @return WP_REST_Response
	 *
	 * @since 7.0
	 */
	public function get_items( $request ) {
		$language = $this->registry->get_default_language();
		$labels   = Language_To_Font::labels();

		/* A locale the table has no name for still has to be the select's selected option rather than silently the first */
		if ( ! isset( $labels[ $language ] ) ) {
			$labels[ $language ] = $language;
		}

		return rest_ensure_response(
			[
				'default_pdf_language' => $language,
				'document_script'      => (string) $this->options->get_option( 'document_script', '' ),
				'auto_install_fonts'   => $this->registry->auto_install_enabled(),
				'auto_install_locked'  => defined( 'GPDF_AUTO_INSTALL_FONTS' ),
				'language_map'         => $this->registry->language_map(),
				'labels'               => (object) $labels,
				'scripts'              => Registry::scripts(),
			]
		);
	}

	/**
	 * Save whichever of the four keys the request carried, and answer with the settings as they now read
	 *
	 * Absent means unchanged, not empty: the panel posts all four together but the route is the only writer of
	 * these keys and a caller sending one must not blank the rest.
	 *
	 * @return WP_REST_Response
	 *
	 * @since 7.0
	 */
	public function update_settings( $request ) {
		foreach ( [ 'default_pdf_language', 'document_script' ] as $key ) {
			if ( $request->has_param( $key ) ) {
				$this->options->update_option( $key, (string) $request->get_param( $key ) );
			}
		}

		if ( $request->has_param( 'auto_install_fonts' ) ) {
			/* Stored `'Yes'`/`'No'`, because `update_option()` deletes a stored `false` and `get_option()` then resurrects the default */
			$this->options->update_option( 'auto_install_fonts', $request->get_param( 'auto_install_fonts' ) ? 'Yes' : 'No' );
		}

		if ( $request->has_param( 'font_language_overrides' ) ) {
			$this->options->update_option(
				'font_language_overrides',
				$this->registry->prune_language_overrides( (array) $request->get_param( 'font_language_overrides' ) )
			);
		}

		return $this->get_items( $request );
	}

	/**
	 * A language tag, or `''` for "follow the site locale"
	 *
	 * Deliberately not checked against `labels()`: the list is the names this plugin can display, not the tags
	 * mPDF will parse, and a site writing in a language nobody has named yet still gets the OTL forms for it.
	 *
	 * @param mixed $value
	 *
	 * @since 7.0
	 */
	public function sanitize_language( $value ): string {
		$value = strtolower( trim( (string) $value ) );

		return preg_match( Registry::TAG_PATTERN, $value ) === 1 ? $value : '';
	}

	/**
	 * A script name — `LATIN`, `HAN` — or `''` to derive the script from the document language
	 *
	 * Stored without mPDF's `SCRIPT_` prefix, which is the spelling the select offers. A name no script answers to
	 * is refused here rather than stored: a shape check alone would let `BANANA` sit in the option looking like a
	 * setting while `get_document_script()` ignored it forever.
	 *
	 * @param mixed $value
	 *
	 * @since 7.0
	 */
	public function sanitize_script( $value ): string {
		$name = strtoupper( trim( (string) $value ) );

		return Registry::script_constant( $name ) === null ? '' : (string) preg_replace( '/^SCRIPT_/', '', $name );
	}
}
