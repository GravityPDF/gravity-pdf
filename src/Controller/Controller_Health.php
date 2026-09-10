<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Helper\Health\Health_Check_Interface;
use GFPDF\Helper\Health\Health_Issue;
use GFPDF\Helper\Health\Health_Runner;
use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Model\Model_Actions;
use GFPDF\View\View_Actions;
use GFPDF\View\View_Health;
use GFForms;

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
 * Arms the daily health run, and the notices that show what it found
 *
 * On the hourly clean-up that already exists rather than an event of its own, so a site that has one dead cron
 * does not acquire a second. `Health_Runner::maybe_run()` is what decides a day has passed.
 *
 * One notice route per registered check, so a check added through `gfpdf_health_checks` gets a notice without
 * anything here changing. Dismissal is recorded against *what the notice said* rather than against the notice, so
 * silencing today's problem says nothing about tomorrow's.
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
		Health_Runner $runner,
		Model_Actions $actions,
		View_Health $view,
		View_Actions $buttons,
		Helper_Misc $misc
	) {
		$this->runner  = $runner;
		$this->actions = $actions;
		$this->view    = $view;
		$this->buttons = $buttons;
		$this->misc    = $misc;
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
		add_filter( 'gfpdf_one_time_action_routes', [ $this, 'add_routes' ] );
	}

	/**
	 * One notice route per check
	 *
	 * The route table is built twice on every `admin_init`, before any condition runs, so nothing here may read:
	 * every value is either a constant or a closure. That is also why a check's capability and its button text
	 * have to be properties of the check rather than of what it happened to find.
	 *
	 * @since 7.0
	 */
	public function add_routes( array $routes ): array {
		foreach ( $this->runner->get_checks() as $check ) {
			$routes[] = $this->route( $check );
		}

		return $routes;
	}

	/**
	 * @since 7.0
	 */
	protected function route( Health_Check_Interface $check ): array {
		return [
			'action'      => 'health_' . $check->get_id(),
			'action_text' => $check->get_action_text(),
			'capability'  => $check->get_capability(),
			'view_class'  => 'notice-warning',

			'condition'   => function () use ( $check ): bool {
				return $this->should_notice( $check );
			},

			/* Whatever the check has to do, then the report — which is where an admin can see whether it worked */
			'process'     => function () use ( $check ): void {
				$check->act();

				$this->actions->system_report_redirect();
			},

			/* Against what it said, not against itself: a new problem has to raise it again */
			'dismiss'     => function () use ( $check ): void {
				$this->actions->dismiss_notice( $this->dismissal_key( $check ) );
			},

			'view'        => function ( $action, $button_text ) use ( $check ): string {
				return $this->view->issues( $this->issues( $check ) )
					. $this->buttons->get_action_buttons( $action, $button_text );
			},
		];
	}

	/**
	 * Whether this check has something to say that the admin has not already waved away
	 *
	 * The page gate comes first because a check may query to answer, and the notice framework runs conditions on
	 * the dashboard and the plugins screen too.
	 *
	 * @since 7.0
	 */
	protected function should_notice( Health_Check_Interface $check ): bool {
		if ( ! GFForms::is_gravity_page() && ! $this->misc->is_gfpdf_page() ) {
			return false;
		}

		if ( $this->issues( $check ) === [] ) {
			return false;
		}

		return ! $this->actions->is_notice_already_dismissed( $this->dismissal_key( $check ) );
	}

	/**
	 * A key that changes when the problem does
	 *
	 * The issue ids the notice is showing, plus when each was first seen: a new issue changes it, and so does one
	 * that cleared and came back. Hashed only to keep the settings blob small — nothing about it is secret.
	 *
	 * @since 7.0
	 */
	protected function dismissal_key( Health_Check_Interface $check ): string {
		$first_seen = Health_Runner::first_seen_in( $this->runner->get_report(), $check->get_id() );
		$state      = [];

		foreach ( $this->issues( $check ) as $issue ) {
			$state[] = $issue->get_id() . ':' . ( $first_seen[ $issue->get_id() ] ?? 0 );
		}

		sort( $state );

		return 'health_' . $check->get_id() . '_' . md5( implode( '|', $state ) );
	}

	/**
	 * What the notice would show
	 *
	 * Asked three times per notice — for the condition, for the dismissal key and for the view — and deliberately
	 * not memoised: the report is an autoloaded option WordPress already caches, and the checks that answer live
	 * share a collaborator that reads their rows once. A memo would outlive the request on a container singleton.
	 *
	 * @return Health_Issue[]
	 *
	 * @since 7.0
	 */
	protected function issues( Health_Check_Interface $check ): array {
		return $check->notice_issues( $this->runner->get_report() );
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
