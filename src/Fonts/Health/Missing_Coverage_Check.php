<?php

declare( strict_types=1 );

namespace GFPDF\Fonts\Health;

use GFPDF\Fonts\Catalog_Repository;
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
 * Scripts a PDF was asked to draw and could not
 *
 * The only one of the four that answers live rather than from the daily report: a submission in a language the
 * site has no font for produces a wrong PDF *now*, and waiting up to a day to say so would mean the admin hears
 * about it after the customer does. It costs one cached query for that, and never writes.
 *
 * When every entry it would name has failed outright it says something different — one issue about the server
 * rather than N about fonts, because a site that cannot reach the origin has one problem, not eight.
 *
 * @package GFPDF\Fonts\Health
 *
 * @since 7.0
 */
class Missing_Coverage_Check extends Health_Check {

	/**
	 * The issues that speak to a server administrator rather than to whoever builds the forms
	 *
	 * @since 7.0
	 */
	public const INFRASTRUCTURE = [ 'font_downloads_failing', 'font_disk_full' ];

	/**
	 * @var Catalog_Repository
	 * @since 7.0
	 */
	protected $catalog;

	/**
	 * @var Health_Issue[]|null
	 * @since 7.0
	 */
	protected $issues;

	public function __construct( Catalog_Repository $catalog ) {
		$this->catalog = $catalog;
	}

	/**
	 * Who this is for depends on what it found
	 *
	 * Alone among the four, because the same check answers both kinds: "install the Japanese pack" is a form
	 * editor's job and "your server cannot reach the internet" is not, and telling a form editor the second only
	 * teaches them to ignore the first.
	 *
	 * @since 7.0
	 */
	public function get_capability(): string {
		foreach ( $this->run() as $issue ) {
			if ( in_array( $issue->get_id(), static::INFRASTRUCTURE, true ) ) {
				return 'manage_options';
			}
		}

		return parent::get_capability();
	}

	public function get_id(): string {
		return 'missing_coverage';
	}

	public function get_title(): string {
		return __( 'Language coverage', 'gravity-pdf' );
	}

	/**
	 * @return Health_Issue[]
	 *
	 * @since 7.0
	 */
	public function run(): array {
		/* Asked twice per admin page load — for the notice and for its audience — and it is a query */
		if ( $this->issues === null ) {
			$this->issues = $this->build();
		}

		return $this->issues;
	}

	/**
	 * @return Health_Issue[]
	 *
	 * @since 7.0
	 */
	protected function build(): array {
		$rows = $this->catalog->entries_missing_coverage();

		if ( $rows === [] ) {
			return [];
		}

		$failed = array_filter(
			$rows,
			static function ( array $row ): bool {
				return (string) $row['phase'] === 'failed';
			}
		);

		/* Every one of them failed, so the fonts are not the problem and eight notices about them would not help */
		if ( count( $failed ) === count( $rows ) ) {
			return [ $this->downloads_failing( $failed ) ];
		}

		return array_map( [ $this, 'entry_issue' ], $rows );
	}

	/**
	 * Live, from the cached coverage rows, so a wrong PDF is reported on the next admin page load
	 *
	 * @return Health_Issue[]
	 *
	 * @since 7.0
	 */
	public function notice_issues( array $report ): array {
		return $this->run();
	}

	/**
	 * @since 7.0
	 */
	protected function entry_issue( array $row ): Health_Issue {
		$id      = $row['source'] . '/' . $row['entry'];
		$details = [
			sprintf(
				/* translators: 1: a comma-separated list of scripts, 2: how long ago the first PDF needed them */
				__( 'Scripts seen in entries: %1$s (first %2$s ago)', 'gravity-pdf' ),
				(string) $row['missing_scripts'],
				human_time_diff( (int) strtotime( (string) $row['missing_since'] . ' UTC' ) )
			),
		];

		if ( (string) $row['error'] !== '' ) {
			/* translators: %s: the error code the last install attempt recorded */
			$details[] = sprintf( __( 'The last install attempt failed: %s', 'gravity-pdf' ), (string) $row['error'] );
		}

		return new Health_Issue(
			$id,
			sprintf(
				/* translators: %s: the font pack's name */
				__( 'PDFs need the "%s" font pack, which is not installed.', 'gravity-pdf' ),
				(string) $row['label']
			),
			$details,
			__( 'Text in those scripts renders as boxes.', 'gravity-pdf' ),
			__( 'Install', 'gravity-pdf' ),
			Font_Manager_Urls::entry( $id )
		);
	}

	/**
	 * The one issue that replaces the rest: nothing can be downloaded at all
	 *
	 * A full disk gets its own wording, and its own audience — an admin who reads "your server may be blocking
	 * outbound connections" while the real answer is `df` has been sent the wrong way.
	 *
	 * @param array[] $rows
	 *
	 * @since 7.0
	 */
	protected function downloads_failing( array $rows ): Health_Issue {
		$disk_full = array_filter(
			$rows,
			static function ( array $row ): bool {
				return (string) $row['error'] === 'font_disk_full';
			}
		);

		$names = array_map(
			static function ( array $row ): string {
				return (string) $row['label'];
			},
			$rows
		);

		if ( count( $disk_full ) > ( count( $rows ) / 2 ) ) {
			return new Health_Issue(
				'font_disk_full',
				__( "There isn't enough free disk space to install fonts.", 'gravity-pdf' ),
				$names,
				__( 'Text in the scripts they cover renders as boxes.', 'gravity-pdf' ),
				__( 'View fonts', 'gravity-pdf' ),
				Font_Manager_Urls::manager()
			);
		}

		return new Health_Issue(
			'font_downloads_failing',
			sprintf(
				/* translators: %d: how many font packs failed to download */
				_n(
					'%d font pack could not be downloaded — your server may be blocking outbound connections.',
					'%d font packs could not be downloaded — your server may be blocking outbound connections.',
					count( $rows ),
					'gravity-pdf'
				),
				count( $rows )
			),
			$names,
			__( 'Text in the scripts they cover renders as boxes.', 'gravity-pdf' ),
			__( 'View fonts', 'gravity-pdf' ),
			Font_Manager_Urls::manager()
		);
	}
}
