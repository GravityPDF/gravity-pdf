<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Options;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Model_Custom_Fonts extends Helper_Abstract_Model {

	protected $options;

	public function __construct( Helper_Abstract_Options $options ) {
		$this->options = $options;
	}

	public function check_font_name_valid( string $name ): bool {
		return (bool) preg_match( '/^[A-Za-z0-9 ]+$/', $name );
	}

	public function check_font_id_valid( string $name ): bool {
		return (bool) preg_match( '/^[a-z0-9]+$/', $name );
	}

	/**
	 * Get a list of the custom fonts installed
	 *
	 * @since 4.0
	 */
	public function get_custom_fonts(): array {
		$fonts = $this->options->get_option( 'custom_fonts' );

		if ( ! is_array( $fonts ) ) {
			return [];
		}

		$font_list = [];
		foreach ( $fonts as $font ) {
			$font['shortname']               = $font['shortname'] ?? $this->get_font_short_name( $font['font_name'] );
			$font_list[ $font['shortname'] ] = $font;
		}

		return $font_list;
	}

	public function add_font( array $font ): bool {
		$fonts = $this->get_custom_fonts();

		if ( isset( $fonts[ $font['shortname'] ] ) ) {
			throw new \Exception();
		}

		$fonts[ $font['shortname'] ] = $font;

		return $this->options->update_option( 'custom_fonts', $fonts );
	}

	public function update_font( array $font ): bool {
		$fonts                       = $this->get_custom_fonts();
		$fonts[ $font['shortname'] ] = $font;

		return $this->options->update_option( 'custom_fonts', $fonts );
	}

	public function delete_font( string $id ): bool {
		$fonts = $this->get_custom_fonts();

		if ( ! isset( $fonts[ $id ] ) ) {
			throw new \Exception();
			/* @TODO */
		}

		unset( $fonts[ $id ] );

		return $this->options->update_option( 'custom_fonts', $fonts );
	}

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

	public function has_unique_font_id( string $id ): bool {
		if (
			! $this->has_reserved_font_id( $id ) &&
			! $this->has_core_font_id( $id ) &&
			! $this->has_custom_font_id( $id )
		) {
			return true;
		}

		return false;
	}

	public function has_reserved_font_id( string $id ): bool {
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

	public function get_font_short_name( $name ) {
		return mb_strtolower( str_replace( ' ', '', $name ), 'UTF-8' );
	}

	public function has_core_font_id( string $id ): bool {
		$default_fonts = $this->options->get_installed_fonts();

		unset( $default_fonts[ esc_html__( 'User-Defined Fonts', 'gravity-forms-pdf-extended' ) ] );

		/* check for exact match */
		foreach ( $default_fonts as $group ) {
			if ( isset( $group[ $id ] ) ) {
				return true;
			}
		}

		return false;
	}

	public function has_custom_font_id( string $id ): bool {
		return in_array( $id, array_column( $this->get_custom_fonts(), 'shortname' ), true );
	}

	public function get_font_by_id( string $id ): array {
		$fonts = $this->get_custom_fonts();

		if ( ! isset( $fonts[ $id ] ) ) {
			throw new \Exception();
			/* @TODO */
		}

		return $fonts[ $id ];
	}
}
