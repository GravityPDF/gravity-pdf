<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Health;

use GFPDF\Fonts\Font_Lock;
use GFPDF_Vendor\Psr\Log\LoggerInterface;
use Throwable;

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
 * Asks every registered check once a day and writes down what they said
 *
 * The report is the single feed for the System Report and the admin notices, which is what keeps a check from
 * having to know about either — and what keeps both off the render path, since neither ever runs a check.
 *
 * No cron event of its own: it rides the hourly clean-up that already exists and does nothing until a day has
 * passed, so a site with dead cron loses the daily run rather than acquiring a second dead event.
 *
 * @package GFPDF\Helper\Health
 *
 * @since 7.0
 */
class Health_Runner {

	/**
	 * Autoloaded, and network-wide on multisite because the fonts it mostly asks about are
	 *
	 * @since 7.0
	 */
	public const OPTION = 'gfpdf_health_report';

	/**
	 * @since 7.0
	 */
	public const LOCK = 'health_run';

	/**
	 * @since 7.0
	 */
	public const LOCK_TTL = 120;

	/**
	 * @since 7.0
	 */
	public const INTERVAL = DAY_IN_SECONDS;

	/**
	 * @var Health_Check_Interface[]
	 * @since 7.0
	 */
	protected $checks;

	/**
	 * @var Font_Lock
	 * @since 7.0
	 */
	protected $lock;

	/**
	 * @var LoggerInterface
	 * @since 7.0
	 */
	protected $log;

	/**
	 * @param Health_Check_Interface[] $checks The core checks, before the filter
	 */
	public function __construct( array $checks, Font_Lock $lock, LoggerInterface $log ) {
		$this->checks = $checks;
		$this->lock   = $lock;
		$this->log    = $log;
	}

	/**
	 * Every check that will run, core and registered alike
	 *
	 * @return Health_Check_Interface[]
	 *
	 * @since 7.0
	 */
	public function get_checks(): array {
		/**
		 * Register a health check
		 *
		 * @param Health_Check_Interface[] $checks
		 *
		 * @since 7.0
		 */
		$checks = apply_filters( 'gfpdf_health_checks', $this->checks );

		return array_values(
			array_filter(
				(array) $checks,
				static function ( $check ): bool {
					return $check instanceof Health_Check_Interface;
				}
			)
		);
	}

	/**
	 * Ask every check and write the report
	 *
	 * @return bool Whether this call did the asking
	 *
	 * @since 7.0
	 */
	public function run(): bool {
		if ( ! $this->lock->acquire( static::LOCK, static::LOCK_TTL ) ) {
			return false;
		}

		try {
			$previous = $this->get_report();
			$now      = time();
			$checks   = [];

			foreach ( $this->get_checks() as $check ) {
				$id = $check->get_id();

				try {
					$checks[ $id ] = $this->record( $check->run(), (array) ( $previous['checks'][ $id ] ?? [] ), $now );
				} catch ( Throwable $e ) {
					$this->log->error(
						'A health check could not run',
						[
							'check' => $id,
							'error' => $e->getMessage(),
						]
					);

					/* Kept rather than cleared: a check that threw has said nothing, not that the problem is gone */
					if ( isset( $previous['checks'][ $id ] ) ) {
						$checks[ $id ] = (array) $previous['checks'][ $id ];
					}
				}
			}

			update_site_option(
				static::OPTION,
				[
					'last_run' => $now,
					'checks'   => $checks,
				]
			);
		} finally {
			$this->lock->release( static::LOCK );
		}

		return true;
	}

	/**
	 * Run when a day has passed
	 *
	 * @since 7.0
	 */
	public function maybe_run(): bool {
		$report = $this->get_report();

		if ( ( time() - (int) ( $report['last_run'] ?? 0 ) ) < static::INTERVAL ) {
			return false;
		}

		return $this->run();
	}

	/**
	 * @since 7.0
	 */
	public function get_report(): array {
		$report = get_site_option( static::OPTION, [] );

		return is_array( $report ) ? $report : [];
	}

	/**
	 * One check's issues out of a report
	 *
	 * @return Health_Issue[]
	 *
	 * @since 7.0
	 */
	public static function issues_in( array $report, string $check_id ): array {
		return array_map(
			[ Health_Issue::class, 'from_array' ],
			(array) ( $report['checks'][ $check_id ]['issues'] ?? [] )
		);
	}

	/**
	 * When each of a check's issues was first seen, out of a report
	 *
	 * The other half of dismissal: an issue that cleared and came back is a new problem, and its notice has to
	 * re-show even though its id has not changed.
	 *
	 * @return array<string, int>
	 *
	 * @since 7.0
	 */
	public static function first_seen_in( array $report, string $check_id ): array {
		return array_map( 'intval', (array) ( $report['checks'][ $check_id ]['first_seen'] ?? [] ) );
	}

	/**
	 * One check's entry in the report, carrying forward when each issue was first seen
	 *
	 * @param Health_Issue[] $issues
	 *
	 * @since 7.0
	 */
	protected function record( array $issues, array $previous, int $now ): array {
		$known      = array_map( 'intval', (array) ( $previous['first_seen'] ?? [] ) );
		$first_seen = [];
		$written    = [];

		foreach ( $issues as $issue ) {
			$this->log->warning(
				'Health check issue',
				[
					'issue'   => $issue->get_id(),
					'summary' => $issue->get_summary(),
				]
			);

			$written[]                      = $issue->to_array();
			$first_seen[ $issue->get_id() ] = $known[ $issue->get_id() ] ?? $now;
		}

		return [
			'issues'     => $written,
			'first_seen' => $first_seen,
		];
	}
}
