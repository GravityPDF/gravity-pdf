<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Exceptions\GravityPdfIdException;
use GFPDF\Fonts\Font_Repository;
use GFPDF\Helper\Helper_Abstract_Model;

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
 * Class Model_Custom_Fonts
 *
 * @package GFPDF\Model
 *
 * @since   6.0
 */
class Model_Custom_Fonts extends Helper_Abstract_Model {

	/**
	 * @var Font_Repository
	 * @since 7.0
	 */
	protected $repository;

	public function __construct( Font_Repository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * A validation callback for the REST API
	 *
	 * @since 6.0
	 */
	public function check_font_name_valid( string $name ): bool {
		return (bool) preg_match( '/^[A-Za-z0-9 ]+$/', $name );
	}

	/**
	 * A validation callback for the REST API
	 *
	 * @since 6.0
	 */
	public function check_font_id_valid( string $name ): bool {
		return (bool) preg_match( Font_Repository::KEY_PATTERN, $name );
	}

	/**
	 * Get a list of the custom fonts installed, indexed by the `id`
	 *
	 * @since 6.0
	 */
	public function get_custom_fonts(): array {
		$font_list = [];

		foreach ( $this->repository->all() as $font_key => $row ) {
			if ( $row['coverage'] !== 0 ) {
				continue;
			}

			$font_list[ $font_key ] = $this->to_legacy_shape( $font_key, $row );
		}

		return $font_list;
	}

	/**
	 * Present a font row in the 6.x array shape
	 *
	 * `GPDFAPI::get_pdf_fonts()`, `Helper_Data::customFontData` and every add-on read this shape, so the faces are
	 * rebuilt as absolute paths from the fonts directory the rows are relative to.
	 *
	 * @since 7.0
	 */
	protected function to_legacy_shape( string $font_key, array $row ): array {
		$font = [
			'id'          => $font_key,
			'font_name'   => (string) $row['label'],
			'regular'     => '',
			'bold'        => '',
			'italics'     => '',
			'bolditalics' => '',
			'useOTL'      => (int) $row['use_otl'],
			'useKashida'  => (int) $row['use_kashida'],
		];

		foreach ( Font_Repository::LEGACY_FACE_ROLES as $face => $role ) {
			if ( isset( $row['files'][ $role ] ) ) {
				$font[ $face ] = $this->repository->get_font_dir() . $row['files'][ $role ]['path'];
			}
		}

		return $font;
	}

	/**
	 * @param string $id
	 *
	 * @return array matches font
	 * @throws GravityPdfIdException If no matching found can be found
	 *
	 * @since 6.0
	 */
	public function get_font_by_id( string $id ): array {
		$fonts = $this->get_custom_fonts();

		if ( ! isset( $fonts[ $id ] ) ) {
			throw new GravityPdfIdException();
		}

		return $fonts[ $id ];
	}

	/**
	 * @param array $font An individual font setting like you'd find from the self::get_custom_fonts() method
	 *
	 * @return bool
	 * @throws GravityPdfIdException Triggered if `id` already exists
	 *
	 * @since 6.0
	 */
	public function add_font( array $font ): bool {
		if ( $this->matches_custom_font_id( $font['id'] ) ) {
			throw new GravityPdfIdException();
		}

		return $this->repository->insert(
			[
				'font_key'    => (string) $font['id'],
				'label'       => (string) ( $font['font_name'] ?? $font['id'] ),
				'source'      => 'custom',
				'blog_id'     => $this->repository->current_blog_id(),
				'use_otl'     => (int) ( $font['useOTL'] ?? 0 ),
				'use_kashida' => (int) ( $font['useKashida'] ?? 0 ),
				'files'       => $this->repository->build_file_rows( $font ),
			]
		) > 0;
	}

	/**
	 * @param array $font An individual font setting like you'd find from the self::get_custom_fonts() method
	 *
	 * @return bool
	 *
	 * @since 6.0
	 */
	public function update_font( array $font ): bool {
		$row = $this->repository->get( (string) $font['id'] );

		if ( $row === null ) {
			return $this->add_font( $font );
		}

		$updated = $this->repository->update(
			$row['id'],
			[
				'label'       => (string) ( $font['font_name'] ?? $row['label'] ),
				'use_otl'     => (int) ( $font['useOTL'] ?? 0 ),
				'use_kashida' => (int) ( $font['useKashida'] ?? 0 ),
			]
		);

		if ( ! $updated ) {
			return false;
		}

		$files = $this->repository->build_file_rows( $font );

		foreach ( Font_Repository::LEGACY_FACE_ROLES as $role ) {
			if ( isset( $files[ $role ] ) ) {
				$this->repository->insert_file( $row['id'], $role, $files[ $role ] );
			} elseif ( isset( $row['files'][ $role ] ) ) {
				$this->repository->delete_file_row( $row['id'], $role );
			}
		}

		return true;
	}

	/**
	 * @param string $id The unique ID of the font to delete
	 *
	 * @return bool
	 * @throws GravityPdfIdException Triggered if `id` already exists
	 */
	public function delete_font( string $id ): bool {
		if ( ! $this->matches_custom_font_id( $id ) ) {
			throw new GravityPdfIdException();
		}

		return $this->repository->delete( $id );
	}

	/**
	 * One installed font's row, or `[]`
	 *
	 * @since 7.0
	 */
	public function get_font( string $id ): array {
		return (array) $this->repository->get( $id );
	}

	/**
	 * Show or hide one row on the site making the request
	 *
	 * @since 7.0
	 */
	public function set_site_visibility( string $id, bool $enabled ): void {
		$row = $this->repository->get( $id );

		if ( $row !== null ) {
			$this->repository->set_site_enabled( (int) $row['id'], get_current_blog_id(), $enabled );
		}
	}

	/**
	 * Checks if the ID already exists and, if so, suffixes the ID with a string until unique
	 *
	 * @return string The unique ID
	 *
	 * @since 6.0
	 */
	public function get_unique_id( string $id ): string {
		$unique_id = false;

		do {
			if ( $this->has_unique_font_id( $id ) ) {
				$unique_id = true;
			} else {
				$id .= substr( (string) time(), -5 );
			}
		} while ( ! $unique_id );

		return $id;
	}

	/**
	 * @since 6.0
	 */
	public function has_unique_font_id( string $id ): bool {
		/* Any row takes the key, whatever its coverage — the dropdown groups are derived from the rows now */
		return $this->repository->is_key_available( $id ) && ! $this->matches_pdf_base_font( $id );
	}

	/**
	 * @since 6.0
	 */
	public function matches_reserved_font_id( string $id ): bool {
		/* One reserved list, not two: the repository owns the keys mPDF resolves before it ever reads the font map */
		return $this->repository->is_key_reserved( $id ) || $this->matches_pdf_base_font( $id );
	}

	/**
	 * The PDF standard-14 base fonts and mPDF's `c`-prefixed variants, which it resolves without a font map
	 *
	 * Kept apart from `Font_Repository::RESERVED_KEYS` deliberately: these are barred from the *upload* path, where
	 * a user typing "Arial" would be surprised by which font they got, but an imported or adopted file may claim
	 * them — a site with a loose `Arial.ttf` kept rendering it as itself in 6.x and still does.
	 *
	 * @since 7.0
	 */
	public function matches_pdf_base_font( string $id ): bool {
		$pdf_base_fonts = [
			'arial',
			'helvetica',
			'helveticab',
			'chelvetica',
			'helveticai',
			'helveticabi',
			'chelveticab',
			'chelveticai',
			'chelveticabi',
			'timesnewroman',
			'times',
			'timesb',
			'timesi',
			'timesbi',
			'ctimes',
			'ctimesb',
			'ctimesi',
			'ctimesbi',
			'courier',
			'courierb',
			'courieri',
			'courierbi',
			'ccourier',
			'ccourierb',
			'ccourieri',
			'ccourierbi',
			'zapfdingbats',
			'czapfdingbats',
			'symbol',
			'csymbol',
		];

		return in_array( $id, $pdf_base_fonts, true );
	}

	/**
	 * @since 6.0
	 */
	public function matches_custom_font_id( string $id ): bool {
		$row = $this->repository->get( $id );

		return $row !== null && $row['coverage'] === 0;
	}

	/**
	 * @since 6.0
	 */
	public function get_font_short_name( string $name ): string {
		return mb_strtolower( str_replace( ' ', '', $name ), 'UTF-8' );
	}
}
