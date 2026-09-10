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
 * The answers most checks give
 *
 * A notice that repeats the daily run, an audience of form editors, and a button that only shows them everything.
 * A check overrides `notice_issues()` when it can answer live for less than the report costs, `get_capability()`
 * when its issue is infrastructure rather than something a form editor can fix, and `act()` when its remedy is
 * something to run rather than somewhere to go.
 *
 * @package GFPDF\Helper\Health
 *
 * @since 7.0
 */
abstract class Health_Check implements Health_Check_Interface {

	/**
	 * @since 7.0
	 */
	public function get_capability(): string {
		return 'gravityforms_edit_forms';
	}

	/**
	 * @since 7.0
	 */
	public function get_action_text(): string {
		return esc_html__( 'View the system report', 'gravity-pdf' );
	}

	/**
	 * @since 7.0
	 */
	public function act(): void {
	}

	/**
	 * @return Health_Issue[]
	 *
	 * @since 7.0
	 */
	public function notice_issues( array $report ): array {
		return Health_Runner::issues_in( $report, $this->get_id() );
	}
}
