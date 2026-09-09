<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFForms;
use GFPDF\Fonts\Catalog_Sync;
use GFPDF\Fonts\Coverage_Resolver;
use GFPDF\Fonts\Install_Queue;
use GFPDF\Helper\Health\Health_Issue;
use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Model\Model_Actions;
use GFPDF\View\View_Actions;
use GFPDF\View\View_Health;

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
 * Arms the catalogue's own scheduling, and every install trigger that is not a render
 *
 * There is no new recurring cron event: the scheduled half rides the hourly temp-directory clean-up that already
 * exists, and the on-demand half is a single event the Refresh route schedules.
 *
 * The triggers are the three moments a site's font needs change without anyone drawing a PDF — it is installed or
 * upgraded, its language changes, or a PDF is saved naming a font or a language. Each only asks; whether anything
 * is installed is the auto-install setting's answer, in `Install_Queue`.
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
	 * @var Coverage_Resolver
	 * @since 7.0
	 */
	protected $resolver;

	/**
	 * @var Model_Actions
	 * @since 7.0
	 */
	protected $actions;

	/**
	 * @var View_Health
	 * @since 7.0
	 */
	public $view;

	/**
	 * @var View_Actions
	 * @since 7.0
	 */
	protected $buttons;

	/**
	 * @var Helper_Misc
	 * @since 7.0
	 */
	protected $misc;

	public function __construct(
		Catalog_Sync $sync,
		Install_Queue $queue,
		Coverage_Resolver $resolver,
		Model_Actions $actions,
		View_Health $view,
		View_Actions $buttons,
		Helper_Misc $misc
	) {
		$this->sync     = $sync;
		$this->queue    = $queue;
		$this->resolver = $resolver;
		$this->actions  = $actions;
		$this->view     = $view;
		$this->buttons  = $buttons;
		$this->misc     = $misc;
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

		add_action( 'gfpdf_plugin_installed', [ $this, 'install_for_site_languages' ] );
		add_action( 'gfpdf_font_catalog_first_sync', [ $this, 'install_for_site_languages' ] );
		add_action( 'update_option_WPLANG', [ $this, 'install_for_site_languages' ] );

		/* A network admin changing the language never fires the per-site hook */
		add_action( 'update_site_option_WPLANG', [ $this, 'install_for_site_languages' ] );

		add_action( 'gfpdf_post_update_pdf', [ $this, 'install_for_pdf' ], 10, 1 );

		add_filter( 'gfpdf_one_time_action_routes', [ $this, 'add_stalled_route' ] );
	}

	/**
	 * The escape hatch for an install that has stopped moving
	 *
	 * Not a health check, because the daily health run rides the same cron that is the likeliest thing to be
	 * broken here — a site whose cron is dead would be told about its dead cron by its dead cron. This is a live
	 * condition on an admin page load, and its button does the work in that request.
	 *
	 * @since 7.0
	 */
	public function add_stalled_route( array $routes ): array {
		$routes[] = [
			'action'      => 'font_install_stalled',
			'action_text' => esc_html__( 'Run now', 'gravity-pdf' ),
			'capability'  => 'manage_options',
			'view_class'  => 'notice-warning',

			'condition'   => [ $this, 'is_install_stalled' ],
			'process'     => [ $this, 'run_stalled_install' ],

			/* Dated, so dismissing it means "not today" rather than "never": the site is still broken tomorrow */
			'dismiss'     => function (): void {
				$this->actions->dismiss_notice( $this->stalled_dismissal_key() );
			},

			'view'        => function ( $action, $button_text ): string {
				return $this->view->issues( [ $this->stalled_issue() ] )
					. $this->buttons->get_action_buttons( $action, $button_text );
			},
		];

		return $routes;
	}

	/**
	 * @since 7.0
	 */
	public function is_install_stalled(): bool {
		if ( ! GFForms::is_gravity_page() && ! $this->misc->is_gfpdf_page() ) {
			return false;
		}

		if ( $this->actions->is_notice_already_dismissed( $this->stalled_dismissal_key() ) ) {
			return false;
		}

		return $this->queue->is_stalled();
	}

	/**
	 * Do in this request what the batch could not
	 *
	 * The sync goes with it because the same two failures — dead cron, blocked loopback — stop it as well, and an
	 * admin who has just been told their queue is stuck should not have to find a second button for that.
	 *
	 * @since 7.0
	 */
	public function run_stalled_install(): void {
		$this->queue->run_inline();
		$this->sync->maybe_run();
	}

	/**
	 * @since 7.0
	 */
	protected function stalled_issue(): Health_Issue {
		return new Health_Issue(
			'font_install_stalled',
			esc_html__( 'A font install has stopped part way through.', 'gravity-pdf' ),
			[],
			esc_html__( "WordPress's scheduled tasks may not be running on this site.", 'gravity-pdf' )
		);
	}

	/**
	 * @since 7.0
	 */
	protected function stalled_dismissal_key(): string {
		return 'font_install_stalled_' . gmdate( 'Y-m-d' );
	}

	/**
	 * Trigger 1: the packs this site's languages call for
	 *
	 * Also called directly by the 7.0 upgrade routine, which has to sync the catalogue first — there is nothing to
	 * resolve against before that, and on a fresh install this hook has already fired by then.
	 *
	 * @since 7.0
	 */
	public function install_for_site_languages(): void {
		$this->queue->enqueue_all( $this->resolver->for_site_languages() );
	}

	/**
	 * Trigger 2: the packs a saved PDF calls for — the font the admin picked, and the language it renders in
	 *
	 * Never blocks the save: the queue claims, pushes and dispatches on `shutdown`.
	 *
	 * @param array $pdf The settings as saved
	 *
	 * @since 7.0
	 */
	public function install_for_pdf( $pdf ): void {
		$this->queue->enqueue_all( $this->resolver->for_settings( (array) $pdf ) );
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
