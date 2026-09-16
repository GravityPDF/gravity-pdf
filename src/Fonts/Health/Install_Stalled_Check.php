<?php

declare( strict_types=1 );

namespace GFPDF\Fonts\Health;

use GFPDF\Fonts\Catalog_Sync;
use GFPDF\Fonts\Install_Queue;
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
 * A font install that has stopped part way
 *
 * The one check whose remedy is a button rather than a link, and the one that never uses the daily report: the two
 * ways an install stops — a dead cron and a blocked loopback — are also the two ways the daily run stops, so a site
 * would be told about its dead cron by its dead cron. It answers live, and its button does the work in that
 * request.
 *
 * @package GFPDF\Fonts\Health
 *
 * @since 7.0
 */
class Install_Stalled_Check extends Health_Check {

	/**
	 * @var Install_Queue
	 * @since 7.0
	 */
	protected $queue;

	/**
	 * @var Catalog_Sync
	 * @since 7.0
	 */
	protected $sync;

	public function __construct( Install_Queue $queue, Catalog_Sync $sync ) {
		$this->queue = $queue;
		$this->sync  = $sync;
	}

	public function get_id(): string {
		return 'install_stalled';
	}

	public function get_title(): string {
		return __( 'Background installs', 'gravity-pdf' );
	}

	public function get_capability(): string {
		return 'manage_options';
	}

	public function get_action_text(): string {
		return esc_html__( 'Run now', 'gravity-pdf' );
	}

	/**
	 * Do in this request what nothing else is going to
	 *
	 * The sync goes with it because the same two failures stop that too, and an admin who has just been told their
	 * queue is stuck should not have to find a second button for it.
	 *
	 * @since 7.0
	 */
	public function act(): void {
		$this->queue->run_inline();
		$this->sync->maybe_run();
	}

	/**
	 * Nothing, on purpose: this is not news that keeps for a day
	 *
	 * @return Health_Issue[]
	 *
	 * @since 7.0
	 */
	public function run(): array {
		return [];
	}

	/**
	 * @return Health_Issue[]
	 *
	 * @since 7.0
	 */
	public function notice_issues( array $report ): array {
		if ( ! $this->queue->is_stalled() ) {
			return [];
		}

		return [
			new Health_Issue(
				/* Dated, so dismissing it means "not today" rather than "never": the site is still broken tomorrow */
				$this->get_id() . '_' . gmdate( 'Y-m-d' ),
				esc_html__( 'A font install has stopped part way through.', 'gravity-pdf' ),
				[],
				esc_html__( "WordPress's scheduled tasks may not be running on this site.", 'gravity-pdf' )
			),
		];
	}
}
