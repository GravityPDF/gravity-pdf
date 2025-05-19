<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Exceptions\GravityPdfIdException;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Options;

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
 * Class Model_Custom_Fonts
 *
 * @package GFPDF\Model
 *
 * @since   6.0
 */
class Model_Custom_Fonts extends Helper_Abstract_Model {

	/**
	 * @var Helper_Abstract_Options
	 * @since 6.0
	 */
	protected $options;

	public function __construct( Helper_Abstract_Options $options ) {
		$this->options = $options;
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
		return (bool) preg_match( '/^[a-z0-9\-]+$/', $name );
	}

	/**
	 * Get a list of the custom fonts installed, indexed by the `id`
	 *
	 * @since 6.0
	 */
	public function get_custom_fonts(): array {
		$fonts = $this->options->get_option( 'custom_fonts' );

		if ( ! is_array( $fonts ) ) {
			return [];
		}

		$font_list = [];
		foreach ( $fonts as $font ) {
			/* Set defaults for all the non-required fields */
			$font['italics']     = $font['italics'] ?? '';
			$font['bold']        = $font['bold'] ?? '';
			$font['bolditalics'] = $font['bolditalics'] ?? '';
			$font['useOTL']      = $font['useOTL'] ?? 0x00;
			$font['useKashida']  = $font['useKashida'] ?? 0;

			$font_list[ $font['id'] ] = $font;
		}

		return $font_list;
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
		$fonts = $this->get_custom_fonts();

		if ( isset( $fonts[ $font['id'] ] ) ) {
			throw new GravityPdfIdException();
		}

		$fonts[ $font['id'] ] = $font;

		return $this->options->update_option( 'custom_fonts', $fonts );
	}

	/**
	 * @param array $font An individual font setting like you'd find from the self::get_custom_fonts() method
	 *
	 * @return bool
	 *
	 * @since 6.0
	 */
	public function update_font( array $font ): bool {
		$fonts                = $this->get_custom_fonts();
		$fonts[ $font['id'] ] = $font;

		return $this->options->update_option( 'custom_fonts', $fonts );
	}

	/**
	 * @param string $id The unique ID of the font to delete
	 *
	 * @return bool
	 * @throws GravityPdfIdException Triggered if `id` already exists
	 */
	public function delete_font( string $id ): bool {
		$fonts = $this->get_custom_fonts();

		if ( ! isset( $fonts[ $id ] ) ) {
			throw new GravityPdfIdException();
		}

		unset( $fonts[ $id ] );

		return $this->options->update_option( 'custom_fonts', $fonts );
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
		if (
			! $this->matches_reserved_font_id( $id ) &&
			! $this->matches_core_font_id( $id ) &&
			! $this->matches_custom_font_id( $id )
		) {
			return true;
		}

		return false;
	}

	/**
	 * @since 6.0
	 */
	public function matches_reserved_font_id( string $id ): bool {
		$core_fonts = [
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

		return in_array( $id, $core_fonts, true );
	}

	/**
	 * @since 6.0
	 */
	public function matches_core_font_id( string $id ): bool {
		$default_fonts = $this->options->get_installed_fonts();

		unset( $default_fonts[ esc_html__( 'User-Defined Fonts', 'gravity-pdf' ) ] );

		/* check for exact match */
		foreach ( $default_fonts as $group ) {
			if ( isset( $group[ $id ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @since 6.0
	 */
	public function matches_custom_font_id( string $id ): bool {
		return in_array( $id, array_column( $this->get_custom_fonts(), 'id' ), true );
	}

	/**
	 * @since 6.0
	 */
	public function get_font_short_name( string $name ): string {
		return mb_strtolower( str_replace( ' ', '', $name ), 'UTF-8' );
	}
}
