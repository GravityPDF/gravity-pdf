<?php

declare( strict_types=1 );

namespace GFPDF\Fonts\Health;

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
 * When every entry it would name has failed outright it says nothing and `Font_Downloads_Check` speaks instead:
 * a site that cannot reach the origin has one problem, not eight, and it is not a problem the person who builds
 * the forms can fix.
 *
 * @package GFPDF\Fonts\Health
 *
 * @since 7.0
 */
class Missing_Coverage_Check extends Health_Check {

	/**
	 * @var Uncovered_Entries
	 * @since 7.0
	 */
	protected $entries;

	public function __construct( Uncovered_Entries $entries ) {
		$this->entries = $entries;
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
		if ( $this->entries->all_failed() ) {
			return [];
		}

		return array_map( [ $this, 'entry_issue' ], $this->entries->all() );
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
}
