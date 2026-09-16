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
 * Whether the server can download a font at all
 *
 * Separate from `Missing_Coverage_Check` because the audiences are: "install the Japanese pack" is a form editor's
 * job and "your host is blocking outbound connections" is not, and showing them the second teaches them to ignore
 * the first. The two are complementary — every entry failed, or not — so exactly one of them ever speaks.
 *
 * @package GFPDF\Fonts\Health
 *
 * @since 7.0
 */
class Font_Downloads_Check extends Health_Check {

	/**
	 * @var Uncovered_Entries
	 * @since 7.0
	 */
	protected $entries;

	public function __construct( Uncovered_Entries $entries ) {
		$this->entries = $entries;
	}

	public function get_id(): string {
		return 'font_downloads';
	}

	public function get_title(): string {
		return __( 'Font downloads', 'gravity-pdf' );
	}

	public function get_capability(): string {
		return 'manage_options';
	}

	/**
	 * @return Health_Issue[]
	 *
	 * @since 7.0
	 */
	public function run(): array {
		if ( ! $this->entries->all_failed() ) {
			return [];
		}

		$rows = $this->entries->all();

		$names = array_map(
			static function ( array $row ): string {
				return (string) $row['label'];
			},
			$rows
		);

		$disk_full = array_filter(
			$rows,
			static function ( array $row ): bool {
				return (string) $row['error'] === 'font_disk_full';
			}
		);

		/* "Your server may be blocking outbound connections" sends an admin the wrong way when the answer is `df` */
		if ( count( $disk_full ) > ( count( $rows ) / 2 ) ) {
			return [
				new Health_Issue(
					'font_disk_full',
					__( "There isn't enough free disk space to install fonts.", 'gravity-pdf' ),
					$names,
					__( 'Text in the scripts they cover renders as boxes.', 'gravity-pdf' ),
					__( 'View fonts', 'gravity-pdf' ),
					Font_Manager_Urls::manager()
				),
			];
		}

		return [
			new Health_Issue(
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
			),
		];
	}

	/**
	 * Live, like its counterpart: a server that cannot download is not news that keeps for a day
	 *
	 * @return Health_Issue[]
	 *
	 * @since 7.0
	 */
	public function notice_issues( array $report ): array {
		return $this->run();
	}
}
