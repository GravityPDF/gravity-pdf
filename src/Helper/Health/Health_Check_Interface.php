<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Health;

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
 * One question the health runner asks about a site
 *
 * Register through `gfpdf_health_checks`. Nothing in the runner or the System Report knows what any check is
 * about, so a new one — core or add-on — appears in both without either changing. Extend `Health_Check` rather
 * than implementing this directly: it answers the four questions most checks have no opinion on.
 *
 * @package GFPDF\Helper\Health
 *
 * @since 7.0
 */
interface Health_Check_Interface {

	/**
	 * A stable slug: it keys the report, the notice route and the admin's dismissal
	 *
	 * @since 7.0
	 */
	public function get_id(): string;

	/**
	 * Translated, for the System Report
	 *
	 * @since 7.0
	 */
	public function get_title(): string;

	/**
	 * Who this check's notice is for
	 *
	 * Infrastructure problems return `manage_options` so form editors are never shown an alarm they cannot act on.
	 *
	 * @since 7.0
	 */
	public function get_capability(): string;

	/**
	 * Ask the question. Daily, from cron, and never on a render
	 *
	 * @return Health_Issue[]
	 *
	 * @since 7.0
	 */
	public function run(): array;

	/**
	 * The notice button's label
	 *
	 * @since 7.0
	 */
	public function get_action_text(): string;

	/**
	 * What the button does, before the admin is sent to the System Report
	 *
	 * Most checks have nothing to do here: their remedy is a link on the issue itself, and the button is only a
	 * way to see everything. A check whose remedy *is* an action — running a queue nothing else will run —
	 * overrides this.
	 *
	 * @since 7.0
	 */
	public function act(): void;

	/**
	 * What the notice should say right now, given the last run's report
	 *
	 * Evaluated on every admin page load, so an override of the default — the check's own issues from the report —
	 * has to stay to about one autoloaded option read.
	 *
	 * @return Health_Issue[]
	 *
	 * @since 7.0
	 */
	public function notice_issues( array $report ): array;
}
