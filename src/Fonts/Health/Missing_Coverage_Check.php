<?php

declare( strict_types=1 );

namespace GFPDF\Fonts\Health;

use GFPDF\Fonts\Coverage_Resolver;
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
 * It has a softer half for upgraded sites. A script one of the fonts adopted from a 6.x install answers never
 * reaches `missing_scripts`, because it resolved, so the pack that would render it better is invisible to the
 * count above. Those entries are read from the legacy rows instead and offered rather than reported: the site is
 * not broken, and 7.0 must not nag a working site into a download.
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

	/**
	 * @var Coverage_Resolver
	 * @since 7.0
	 */
	protected $resolver;

	public function __construct( Uncovered_Entries $entries, Coverage_Resolver $resolver ) {
		$this->entries  = $entries;
		$this->resolver = $resolver;
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

		$issues   = array_map( [ $this, 'entry_issue' ], $this->entries->all() );
		$reported = [];

		foreach ( $issues as $issue ) {
			$reported[ $issue->get_id() ] = true;
		}

		foreach ( $this->resolver->legacy_covered_entries() as $row ) {
			$id = $row['source'] . '/' . $row['entry'];

			/* A pack a render already asked for is being waited on, not stood in for: one issue per entry is enough */
			if ( ! isset( $reported[ $id ] ) ) {
				$issues[] = $this->legacy_issue( $id, $row );
			}
		}

		return $issues;
	}

	/**
	 * The upgrade offer: a pack whose languages an adopted 6.x font is drawing today
	 *
	 * Informational, and dismissible like every other notice. The wording says what renders now before it says
	 * what is available, because the admin's first question is whether something is wrong.
	 *
	 * @since 7.0
	 */
	protected function legacy_issue( string $id, array $row ): Health_Issue {
		return new Health_Issue(
			'legacy:' . $id,
			sprintf(
				/* translators: 1: a font pack's name, e.g. Korean, 2: a comma-separated list of font names */
				__( '%1$s text renders with %2$s, kept from your previous version.', 'gravity-pdf' ),
				(string) $row['label'],
				implode( ', ', $row['legacy_fonts'] )
			),
			[
				sprintf(
					/* translators: 1: a font pack's name, 2: its download size, 3: a comma-separated list of language tags */
					__( 'The "%1$s" pack (%2$s) has newer fonts for %3$s.', 'gravity-pdf' ),
					(string) $row['label'],
					size_format( (int) $row['size'] ),
					implode( ', ', $row['legacy_tags'] )
				),
			],
			__( 'Nothing is broken: those PDFs keep rendering exactly as they did before the upgrade.', 'gravity-pdf' ),
			__( 'Install', 'gravity-pdf' ),
			Font_Manager_Urls::entry( $id )
		);
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
