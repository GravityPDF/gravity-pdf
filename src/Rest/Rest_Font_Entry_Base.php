<?php

declare( strict_types=1 );

namespace GFPDF\Rest;

use GFPDF\Fonts\Catalog_Repository;
use GFPDF\Fonts\Font_Sources;
use WP_Error;

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
 * What the catalogue routes agree on, on top of the capability
 *
 * The catalogue browser and the install surface are separate classes because they answer from different tables and
 * one of them polls. They are not separate resources: `GET`, `POST` and `DELETE` on `/fonts/sources/{source}/{entry}`
 * are the same entry, so the route pattern and the two 404s live here rather than in each — WordPress keys its
 * endpoint table by the literal route string, so two spellings of that pattern would quietly become two routes
 * rather than an error.
 *
 * Split from `Rest_Font_Base` because custom-font rows have no source and no entry to look one up by.
 *
 * @since 7.0
 */
abstract class Rest_Font_Entry_Base extends Rest_Font_Base {

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
