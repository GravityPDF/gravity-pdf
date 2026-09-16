<?php

declare( strict_types=1 );

namespace GFPDF\Fonts\Health;

use GFPDF\Fonts\Catalog_Sync;
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
 * Whether the site can still see what fonts exist
 *
 * Nothing breaks when the catalogue goes stale — every installed font keeps rendering — so this is the quietest of
 * the four, and the one an admin can do least about from the Font Manager. It is `manage_options` for that reason:
 * a blocked outbound connection is not a form editor's problem.
 *
 * @package GFPDF\Fonts\Health
 *
 * @since 7.0
 */
class Catalog_Sync_Check extends Health_Check {

	/**
	 * @var Catalog_Sync
	 * @since 7.0
	 */
	protected $sync;

	public function __construct( Catalog_Sync $sync ) {
		$this->sync = $sync;
	}

	public function get_id(): string {
		return 'catalog_sync';
	}

	public function get_title(): string {
		return __( 'Font catalogue', 'gravity-pdf' );
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
		$stale = $this->sync->stale_sources();

		if ( $stale === [] ) {
			return [];
		}

		return [
			new Health_Issue(
				$this->get_id(),
				__( "Gravity PDF hasn't been able to check for new or updated fonts.", 'gravity-pdf' ),
				array_map( [ $this, 'describe' ], array_keys( $stale ), $stale ),
				__( "New fonts and font updates aren't offered.", 'gravity-pdf' ),
				__( 'Refresh', 'gravity-pdf' ),
				Font_Manager_Urls::manager()
			),
		];
	}

	/**
	 * One stale source, in a line
	 *
	 * @since 7.0
	 */
	protected function describe( string $id, array $record ): string {
		$attempt = (int) ( $record['last_attempt'] ?? 0 );
		$error   = (string) ( $record['last_error'] ?? '' );

		if ( $attempt === 0 ) {
			/* translators: %s: the font source id */
			return sprintf( __( '%s: never checked', 'gravity-pdf' ), $id );
		}

		return sprintf(
			/* translators: 1: the font source id, 2: how long ago the last attempt was, 3: the error it gave */
			__( '%1$s: last tried %2$s ago — %3$s', 'gravity-pdf' ),
			$id,
			human_time_diff( $attempt ),
			$error !== '' ? $error : __( 'no error was recorded', 'gravity-pdf' )
		);
	}
}
