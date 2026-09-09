<?php

declare( strict_types=1 );

namespace GFPDF\Fonts\Health;

use GFPDF\Fonts\Font_Repository;
use GFPDF\Fonts\Registry;
use GFPDF\Helper\Health\Health_Check;
use GFPDF\Helper\Health\Health_Issue;

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
 * Fonts the site has a row for and no file
 *
 * A migration that moved the uploads directory, a backup restored without it, a tidy-up script. The render path
 * already survives it — the file finder substitutes a bundled face and flags the row — but silently, and the PDFs
 * that went out in the meantime were wrong.
 *
 * @package GFPDF\Fonts\Health
 *
 * @since 7.0
 */
class Missing_Font_Files_Check extends Health_Check {

	/**
	 * @var Font_Repository
	 * @since 7.0
	 */
	protected $repository;

	/**
	 * @var Registry
	 * @since 7.0
	 */
	protected $registry;

	/**
	 * @var Configured_Fonts
	 * @since 7.0
	 */
	protected $configured;

	public function __construct( Font_Repository $repository, Registry $registry, Configured_Fonts $configured ) {
		$this->repository = $repository;
		$this->registry   = $registry;
		$this->configured = $configured;
	}

	public function get_id(): string {
		return 'missing_font_files';
	}

	public function get_title(): string {
		return __( 'Font files', 'gravity-pdf' );
	}

	/**
	 * @return Health_Issue[]
	 *
	 * @since 7.0
	 */
	public function run(): array {
		/* The one disk scan, and it also clears the flag on anything that came back */
		$this->repository->verify();

		$issues = [];

		foreach ( $this->repository->all() as $font_key => $row ) {
			$missing = $this->missing_files( (array) $row['files'] );

			if ( $missing === [] ) {
				continue;
			}

			$issues[] = $this->issue( (string) $font_key, (array) $row, $missing );
		}

		return $issues;
	}

	/**
	 * @return string[]
	 *
	 * @since 7.0
	 */
	protected function missing_files( array $files ): array {
		$missing = [];

		foreach ( $files as $file ) {
			if ( (int) ( $file['missing'] ?? 0 ) === 1 ) {
				$missing[] = basename( (string) $file['path'] );
			}
		}

		return $missing;
	}

	/**
	 * @param string[] $missing
	 *
	 * @since 7.0
	 */
	protected function issue( string $font_key, array $row, array $missing ): Health_Issue {
		$entry = (string) ( $row['entry'] ?? '' );
		$label = (string) ( $row['label'] ?? $font_key );

		return new Health_Issue(
			$font_key,
			sprintf(
				/* translators: %s: the font's name */
				__( 'Files for the font "%s" are missing from the server.', 'gravity-pdf' ),
				$label
			),
			array_merge(
				[ implode( ', ', $missing ) ],
				$this->configured->by_key()[ $font_key ] ?? []
			),
			sprintf(
				/* translators: %s: the font PDFs fall back to */
				__( 'PDFs that use it fall back to %s.', 'gravity-pdf' ),
				$this->registry->get_default_font()
			),
			/* A downloaded font can be fetched again; one somebody uploaded can only be replaced by them */
			$entry !== '' ? __( 'Reinstall', 'gravity-pdf' ) : __( 'Replace', 'gravity-pdf' ),
			$entry !== '' ? Font_Manager_Urls::entry( $entry ) : Font_Manager_Urls::font( $font_key )
		);
	}
}
