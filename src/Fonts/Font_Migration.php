<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF_Vendor\Psr\Log\LoggerInterface;

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
 * Moves 6.x custom font records out of the settings blob and into the font tables
 *
 * The `custom_fonts` key is never deleted. It stays as a frozen snapshot of the 6.x state — 7.0 reads it once and
 * writes it never — because deleting it was the one irreversible step in the upgrade, and the rollback path needs it.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Font_Migration {

	/**
	 * @var Helper_Abstract_Options
	 * @since 7.0
	 */
	protected $options;

	/**
	 * @var LoggerInterface
	 * @since 7.0
	 */
	protected $log;

	public function __construct( Helper_Abstract_Options $options, LoggerInterface $log ) {
		$this->options = $options;
		$this->log     = $log;
	}

	/**
	 * Migrate this site's `custom_fonts` records into the font tables
	 *
	 * Idempotent: a record whose file set is already recorded is skipped before a key is derived, so a retried pass
	 * never mints a suffixed row.
	 *
	 * @return int How many rows were created
	 *
	 * @since 7.0
	 */
	public function from_option( Font_Repository $repository ): int {
		$fonts = $this->options->get_option( 'custom_fonts' );

		if ( ! is_array( $fonts ) || $fonts === [] ) {
			return 0;
		}

		$existing = $this->index_by_file_set( $repository->all() );
		$blog_id  = is_multisite() ? get_current_blog_id() : null;
		$created  = 0;

		foreach ( $fonts as $font ) {
			if ( ! is_array( $font ) || empty( $font['id'] ) ) {
				continue;
			}

			$files = $repository->build_file_rows( $font );

			if ( $files === [] ) {
				$this->log->warning( 'Skipping a 6.x custom font with no font files', [ 'font' => $font['id'] ] );
				continue;
			}

			/* Idempotency, and the multisite "identical file set inherits the row" rule, are the same check */
			if ( isset( $existing[ $this->file_set_hash( $files ) ] ) ) {
				continue;
			}

			$font_key = $this->resolve_key( $repository, (string) $font['id'], $blog_id );

			$row_id = $repository->insert(
				[
					'font_key'    => $font_key,
					'label'       => (string) ( $font['font_name'] ?? $font['id'] ),
					'source'      => 'custom',
					'blog_id'     => $blog_id,
					'use_otl'     => (int) ( $font['useOTL'] ?? 0 ),
					'use_kashida' => (int) ( $font['useKashida'] ?? 0 ),
					'files'       => $files,
				]
			);

			if ( $row_id === 0 ) {
				continue;
			}

			$existing[ $this->file_set_hash( $files ) ] = $font_key;
			++$created;
		}

		if ( $created > 0 ) {
			$this->log->notice( 'Migrated 6.x custom fonts into the font tables', [ 'created' => $created ] );
		}

		return $created;
	}

	/**
	 * The key this record takes, suffixed when another site already owns it with different files
	 *
	 * @since 7.0
	 */
	protected function resolve_key( Font_Repository $repository, string $requested, ?int $blog_id ): string {
		if ( $repository->is_key_available( $requested ) ) {
			return $requested;
		}

		/* Same key, different files: the first site to migrate owns it, and this site's row is renamed */
		$suffixed = $blog_id === null ? $requested : $requested . '-' . $blog_id;
		$font_key = $repository->unique_key( $suffixed );

		$this->log->notice(
			'A 6.x custom font migrated under a new key because another row already held it',
			[
				'previous' => $requested,
				'current'  => $font_key,
			]
		);

		return $font_key;
	}

	/**
	 * @since 7.0
	 */
	protected function index_by_file_set( array $rows ): array {
		$index = [];

		foreach ( $rows as $font_key => $font ) {
			$index[ $this->file_set_hash( $font['files'] ) ] = $font_key;
		}

		return $index;
	}

	/**
	 * Identity is the set of files a record points at, not the key derived from it
	 *
	 * @since 7.0
	 */
	protected function file_set_hash( array $files ): string {
		$set = [];
		foreach ( $files as $role => $file ) {
			$set[ (string) $role ] = (string) $file['path'];
		}

		ksort( $set );

		return md5( (string) wp_json_encode( $set ) );
	}
}
