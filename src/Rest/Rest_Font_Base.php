<?php

declare( strict_types=1 );

namespace GFPDF\Rest;

use GFPDF\Fonts\Catalog_Repository;
use GFPDF\Fonts\Font_Sources;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Data;
use WP_Error;
use WP_REST_Controller;

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
 * What every font route agrees on
 *
 * The catalogue browser and the install surface are separate classes because they answer from different tables and
 * one of them polls. They are not separate resources: `GET` and `POST` on `/fonts/sources/{source}/{entry}` are the
 * same entry, so the route pattern, the capability and the two 404s live here rather than in each — WordPress keys
 * its endpoint table by the literal route string, so two spellings of that pattern would quietly become two
 * routes rather than an error.
 *
 * @since 7.0
 */
abstract class Rest_Font_Base extends WP_REST_Controller {

	/**
	 * @since 7.0
	 */
	public const NAMESPACE = Helper_Data::REST_API_BASENAME . 'v1';

	/**
	 * @since 7.0
	 */
	public const API_BASE = '/fonts/sources';

	/**
	 * The one spelling of the entry route, shared by every method registered against it
	 *
	 * @since 7.0
	 */
	public const ENTRY_ROUTE = self::API_BASE . '/(?P<source>[a-z0-9-]+)/(?P<entry>[a-z0-9-]+)';

	/**
	 * @var Font_Sources
	 * @since 7.0
	 */
	protected $sources;

	/**
	 * @var Helper_Abstract_Form
	 * @since 7.0
	 */
	protected $gform;

	/**
	 * @since 7.0
	 */
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * @return true|WP_Error
	 *
	 * @since 7.0
	 */
	public function get_items_permissions_check( $request ) {
		if ( $this->gform->has_capability( 'gravityforms_edit_forms' ) ) {
			return true;
		}

		return new WP_Error(
			'rest_cannot_view',
			__( 'Sorry, you do not have access to this endpoint.', 'gravity-pdf' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}

	/**
	 * @return true|WP_Error
	 *
	 * @since 7.0
	 */
	public function update_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * @since 7.0
	 */
	protected function check_source( string $id ): ?WP_Error {
		if ( $this->sources->get( $id ) !== null ) {
			return null;
		}

		return new WP_Error(
			'font_source_unknown',
			__( 'That font source is not registered.', 'gravity-pdf' ),
			[ 'status' => 404 ]
		);
	}

	/**
	 * The catalog row a request names, or the 404 that says why not
	 *
	 * Both segments are checked here so `GET` and `POST` on one entry cannot answer a missing source or a missing
	 * entry differently.
	 *
	 * @return array|WP_Error
	 *
	 * @since 7.0
	 */
	protected function entry_row( Catalog_Repository $catalog, string $source, string $entry ) {
		$error = $this->check_source( $source );

		if ( $error !== null ) {
			return $error;
		}

		$row = $catalog->entry( $source, $entry );

		if ( $row === null ) {
			return new WP_Error(
				'font_entry_unknown',
				__( 'That font is not in the catalogue.', 'gravity-pdf' ),
				[ 'status' => 404 ]
			);
		}

		return $row;
	}
}
