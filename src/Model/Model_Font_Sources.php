<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use Exception;
use GFPDF\Helper\Fonts\FlushCache;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Data;
use GFPDF_Vendor\Mpdf\Config\FontVariables;
use GFPDF_Vendor\Psr\Log\LoggerInterface;
use GPDFAPI;

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
 * The fonts Gravity PDF provides: the bundled Arimo faces and mPDF's core fonts
 *
 * A source has a `version` and `families` (mPDF font data plus an optional `name`), and optionally a bundled `dir`, a
 * `manifest`, a font list `group` and `legacy` (git blob SHA-1 => size of other copies of its files).
 *
 * @since 6.19.0
 */
class Model_Font_Sources extends Helper_Abstract_Model {

	/**
	 * Each source's version at the last check, so a new or updated source adopts its fonts once
	 *
	 * @since 6.19.0
	 */
	const OPTION = 'gfpdf_font_sources';

	/**
	 * @var Helper_Data
	 * @since 6.19.0
	 */
	protected $data;

	/**
	 * @var Model_Custom_Fonts
	 * @since 6.19.0
	 */
	protected $custom_fonts;

	/**
	 * @var LoggerInterface
	 * @since 6.19.0
	 */
	protected $log;

	/**
	 * @var array|null
	 * @since 6.19.0
	 */
	protected $sources;

	/**
	 * @since 6.19.0
	 */
	public function __construct( Helper_Data $data, Model_Custom_Fonts $custom_fonts, LoggerInterface $log ) {
		$this->data         = $data;
		$this->custom_fonts = $custom_fonts;
		$this->log          = $log;
	}

	/**
	 * @since 6.19.0
	 */
	public function get_sources(): array {
		if ( $this->sources === null ) {
			$this->sources = [
				'bundled' => $this->get_bundled_source(),
				'core'    => $this->get_core_source(),
			];
		}

		return $this->sources;
	}

	/**
	 * @since 6.19.0
	 */
	public function is_family_key( string $key ): bool {
		foreach ( $this->get_sources() as $source ) {
			if ( isset( $source['families'][ $key ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Register each source's families with mPDF, keeping any font already registered
	 *
	 * @param array $fonts mPDF font data
	 *
	 * @since 6.19.0
	 */
	public function register_font_data( $fonts ): array {
		$fonts = is_array( $fonts ) ? $fonts : [];

		foreach ( $this->get_sources() as $source ) {
			foreach ( $source['families'] as $key => $family ) {
				if ( ! isset( $fonts[ $key ] ) ) {
					unset( $family['name'] );
					$fonts[ $key ] = $family;
				}
			}
		}

		return $fonts;
	}

	/**
	 * @param array $config mPDF config
	 *
	 * @since 6.19.0
	 */
	public function register_font_directories( $config ): array {
		$config            = is_array( $config ) ? $config : [];
		$config['fontDir'] = (array) ( $config['fontDir'] ?? [] );

		foreach ( $this->get_sources() as $source ) {
			if ( ! empty( $source['dir'] ) && ! in_array( $source['dir'], $config['fontDir'], true ) ) {
				$config['fontDir'][] = $source['dir'];
			}
		}

		return $config;
	}

	/**
	 * Prepend each source's families to its font list group, skipping any a custom font overrides
	 *
	 * @param array $fonts The font list, grouped by label
	 *
	 * @since 6.19.0
	 */
	public function add_font_list_groups( array $fonts ): array {
		$custom_fonts = $this->custom_fonts->get_custom_fonts();

		foreach ( $this->get_sources() as $source ) {
			if ( empty( $source['group'] ) ) {
				continue;
			}

			$list = [];
			foreach ( $source['families'] as $key => $family ) {
				if ( ! isset( $custom_fonts[ $key ] ) ) {
					$list[ $key ] = $family['name'] ?? $key;
				}
			}

			$fonts[ $source['group'] ] = $list + ( $fonts[ $source['group'] ] ?? [] );
		}

		return $fonts;
	}

	/**
	 * Adopt the custom font records of each new or updated source
	 *
	 * @since 6.19.0
	 */
	public function maybe_adopt_changed_sources(): void {
		$versions = array_map(
			function ( $source ) {
				return (string) $source['version'];
			},
			$this->get_sources()
		);

		$stored = get_option( self::OPTION, [] );
		$stored = is_array( $stored ) ? $stored : [];
		ksort( $stored );
		ksort( $versions );
		if ( $stored === $versions ) {
			return;
		}

		foreach ( $versions as $id => $version ) {
			if ( ( $stored[ $id ] ?? null ) !== $version ) {
				$this->adopt_records( $id );
			}
		}

		update_option( self::OPTION, $versions );
	}

	/**
	 * Remove the custom font records a source now serves: keyed by one of its families, with every file a known copy.
	 * A record with any other file was edited, so it stays and keeps overriding.
	 *
	 * @return string[] The IDs of the records removed
	 *
	 * @since 6.19.0
	 */
	public function adopt_records( string $source_id ): array {
		$source = $this->get_sources()[ $source_id ] ?? null;
		if ( $source === null ) {
			return [];
		}

		$records = array_intersect_key( $this->custom_fonts->get_custom_fonts(), $source['families'] );
		if ( count( $records ) === 0 ) {
			return [];
		}

		$known   = $this->get_known_files( $source );
		$adopted = [];

		foreach ( $records as $id => $font ) {
			$id = (string) $id;
			if ( ! $this->has_only_known_files( $font, $known ) ) {
				continue;
			}

			if ( $this->remove_record( $id ) ) {
				$adopted[] = $id;
			}
		}

		if ( count( $adopted ) > 0 ) {
			$this->log->notice(
				'Custom fonts replaced by a font source',
				[
					'source' => $source_id,
					'fonts'  => $adopted,
				]
			);
		}

		return $adopted;
	}

	/**
	 * On Multisite another site's record may still use the files in the shared fonts folder, so keep them
	 *
	 * @since 6.19.0
	 */
	protected function remove_record( string $id ): bool {
		if ( ! is_multisite() ) {
			return GPDFAPI::delete_pdf_font( $id ) === true;
		}

		try {
			if ( ! $this->custom_fonts->delete_font( $id ) ) {
				return false;
			}
		} catch ( Exception $e ) {
			$this->log->error(
				'Could not remove a custom font record replaced by a font source',
				[
					'font'      => $id,
					'exception' => $e->getMessage(),
				]
			);

			return false;
		}

		FlushCache::flush( $id );

		return true;
	}

	/**
	 * @since 6.19.0
	 */
	protected function get_bundled_source(): array {
		return [
			'version'  => '1.0.0',
			'dir'      => PDF_PLUGIN_DIR . 'fonts/',
			'group'    => esc_html__( 'Unicode', 'gravity-pdf' ),
			'families' => [
				'arimo' => [
					'name'   => 'Arimo',
					'R'      => 'GravityPDF-Arimo-Regular.ttf',
					'B'      => 'GravityPDF-Arimo-Bold.ttf',
					'I'      => 'GravityPDF-Arimo-Italic.ttf',
					'BI'     => 'GravityPDF-Arimo-BoldItalic.ttf',
					'useOTL' => 0xFF,
				],
			],
			/* The Arimo copies premium templates install */
			'legacy'   => [
				'9be443c7df4832028061c8fcd2282ca61e45b27c' => 436876,
				'da89a631d64d19458de1fd8f993a03d7a8397a00' => 439272,
				'6a8837cb0a16ddfbfa32a35be1f0d1f9a36384d3' => 441484,
				'6dc45e79e19a2589ed7608c62186c7461859be34' => 435744,
			],
		];
	}

	/**
	 * @since 6.19.0
	 */
	protected function get_core_source(): array {
		return [
			'version'  => '1.0.0',
			'families' => ( new FontVariables() )->getDefaults()['fontdata'],
			'manifest' => PDF_PLUGIN_DIR . 'build/payload/core-fonts.json',
		];
	}

	/**
	 * A source's known git blob SHA-1s, from its manifest and `legacy`, keyed by file size
	 *
	 * @return array<int, string[]>
	 *
	 * @since 6.19.0
	 */
	protected function get_known_files( array $source ): array {
		$known = [];

		$manifest = [];
		if ( ! empty( $source['manifest'] ) && is_readable( $source['manifest'] ) ) {
			$manifest = json_decode( (string) file_get_contents( $source['manifest'] ), true ); /* phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents */
		}

		foreach ( is_array( $manifest ) ? $manifest : [] as $file ) {
			if ( isset( $file['size'], $file['sha'] ) ) {
				$known[ (int) $file['size'] ][] = (string) $file['sha'];
			}
		}

		$legacy = is_array( $source['legacy'] ?? null ) ? $source['legacy'] : [];
		foreach ( $legacy as $sha => $size ) {
			if ( is_string( $sha ) && preg_match( '/^[0-9a-f]{40}$/', $sha ) && is_int( $size ) && $size > 0 ) {
				$known[ $size ][] = $sha;
			}
		}

		return $known;
	}

	/**
	 * Whether every file of a custom font record is a known copy, checking size before hash
	 *
	 * @since 6.19.0
	 */
	protected function has_only_known_files( array $font, array $known ): bool {
		$files = array_filter( [ $font['regular'] ?? '', $font['italics'] ?? '', $font['bold'] ?? '', $font['bolditalics'] ?? '' ] );
		if ( count( $files ) === 0 ) {
			return false;
		}

		foreach ( $files as $file ) {
			/* mPDF finds a font by its file name in the fonts folder, so check the copy there */
			$path = $this->data->template_font_location . basename( $file );
			if ( ! is_file( $path ) ) {
				return false;
			}

			$size = (int) filesize( $path );
			if ( ! isset( $known[ $size ] ) || ! in_array( $this->get_git_blob_sha1( $path, $size ), $known[ $size ], true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * The hash GitHub lists for a file, as `git hash-object` computes it
	 *
	 * @since 6.19.0
	 */
	protected function get_git_blob_sha1( string $path, int $size ): string {
		$hash = hash_init( 'sha1' );
		hash_update( $hash, 'blob ' . $size . "\0" );
		hash_update_file( $hash, $path );

		return hash_final( $hash );
	}
}
