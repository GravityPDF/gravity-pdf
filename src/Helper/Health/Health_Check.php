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
 * The two answers most checks give
 *
 * A notice that repeats the daily run, and an audience of form editors. A check overrides `notice_issues()` only
 * when it can answer live for less than the report costs, and `get_capability()` only when its issue is
 * infrastructure rather than something a form editor can fix.
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
	 * @return Health_Issue[]
	 *
	 * @since 7.0
	 */
	public function notice_issues( array $report ): array {
		return Health_Runner::issues_in( $report, $this->get_id() );
	}
}
