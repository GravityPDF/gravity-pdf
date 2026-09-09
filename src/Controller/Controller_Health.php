<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Helper\Health\Health_Runner;
use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Misc;

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
 * Arms the daily health run
 *
 * On the hourly clean-up that already exists rather than an event of its own, so a site that has one dead cron
 * does not acquire a second. `Health_Runner::maybe_run()` is what decides a day has passed.
 *
 * @package GFPDF\Controller
 *
 * @since 7.0
 */
class Controller_Health extends Helper_Abstract_Controller {

	/**
	 * @var Health_Runner
	 * @since 7.0
	 */
	protected $runner;

	/**
	 * @var Helper_Misc
	 * @since 7.0
	 */
	protected $misc;

	public function __construct( Health_Runner $runner, Helper_Misc $misc ) {
		$this->runner = $runner;
		$this->misc   = $misc;
	}

	/**
	 * @since 7.0
	 */
	public function init(): void {
		$this->add_actions();
	}

	/**
	 * @since 7.0
	 */
	public function add_actions(): void {
		add_action( 'gfpdf_cleanup_tmp_dir', [ $this, 'maybe_run' ] );
	}

	/**
	 * The hourly listener
	 *
	 * The report is a network option and most of what it describes is network-global, so one site asking is the
	 * whole network asking. Same reasoning as the catalogue sync, and the same optimisation rather than
	 * correctness: `run()` takes a lock either way.
	 *
	 * @since 7.0
	 */
	public function maybe_run(): void {
		if ( $this->misc->is_secondary_network_site( PDF_PLUGIN_BASENAME ) ) {
			return;
		}

		$this->runner->maybe_run();
	}
}
