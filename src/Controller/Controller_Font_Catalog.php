<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Fonts\Catalog_Sync;
use GFPDF\Fonts\Install_Queue;
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
 * Arms the two things that can start a catalog sync
 *
 * There is no new recurring cron event: the scheduled half rides the hourly temp-directory clean-up that already
 * exists, and the on-demand half is a single event the Refresh route schedules.
 *
 * @package GFPDF\Controller
 *
 * @since 7.0
 */
class Controller_Font_Catalog extends Helper_Abstract_Controller {

	/**
	 * @var Catalog_Sync
	 * @since 7.0
	 */
	protected $sync;

	/**
	 * @var Install_Queue
	 * @since 7.0
	 */
	protected $queue;

	/**
	 * @var Helper_Misc
	 * @since 7.0
	 */
	protected $misc;

	public function __construct( Catalog_Sync $sync, Install_Queue $queue, Helper_Misc $misc ) {
		$this->sync  = $sync;
		$this->queue = $queue;
		$this->misc  = $misc;
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
		add_action( 'gfpdf_cleanup_tmp_dir', [ $this, 'maybe_sync' ] );
		add_action( 'gfpdf_cleanup_tmp_dir', [ $this, 'maybe_retry_installs' ] );
		add_action( Catalog_Sync::EVENT, [ $this->sync, 'run' ] );
	}

	/**
	 * The hourly listener
	 *
	 * The primary-site check is an optimisation, not correctness: `run()` takes a network lock and the catalog is
	 * network-global, so a secondary site firing this would be harmless. Under per-site activation
	 * `is_secondary_network_site()` is false everywhere, which is what makes gating to the main site wrong — the
	 * plugin may be inactive there.
	 *
	 * @since 7.0
	 */
	public function maybe_sync(): void {
		if ( $this->misc->is_secondary_network_site( PDF_PLUGIN_BASENAME ) ) {
			return;
		}

		$this->sync->maybe_run();
	}

	/**
	 * The hourly listener's other half
	 *
	 * A trigger fires once, so nothing else ever re-tries a pack that failed on its own — an `emoji` install that
	 * lost an origin blip would stay failed until something happened to ask for it again. Same primary-site
	 * reasoning as `maybe_sync()`: the catalogue and the claim are network-global.
	 *
	 * @since 7.0
	 */
	public function maybe_retry_installs(): void {
		if ( $this->misc->is_secondary_network_site( PDF_PLUGIN_BASENAME ) ) {
			return;
		}

		$this->queue->maybe_retry();
	}
}
