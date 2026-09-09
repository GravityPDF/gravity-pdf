<?php

declare( strict_types=1 );

namespace GFPDF\View;

use GFPDF\Helper\Health\Health_Issue;
use GFPDF\Helper\Helper_Abstract_View;

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
 * The health notices
 *
 * @package GFPDF\View
 *
 * @since 7.0
 */
class View_Health extends Helper_Abstract_View {

	/**
	 * @var string
	 * @since 7.0
	 */
	protected $view_type = 'Health';

	/**
	 * One check's issues, as one notice
	 *
	 * The summaries and consequences, and nothing else — the details a check records are for the System Report,
	 * where there is room for the filenames and form names behind them. A notice is read at a glance.
	 *
	 * The framework's dismiss/action buttons are `View_Actions`' and are appended by the caller, which is what
	 * makes a health notice dismiss exactly the way every other one does.
	 *
	 * @param Health_Issue[] $issues
	 *
	 * @since 7.0
	 */
	public function issues( array $issues ): string {
		$rows = array_map(
			static function ( Health_Issue $issue ): array {
				return [
					'summary'     => $issue->get_summary(),
					'consequence' => $issue->get_consequence(),
					'label'       => $issue->get_action_label(),
					'url'         => $issue->get_action_url(),
				];
			},
			$issues
		);

		return (string) $this->load( 'issues', [ 'issues' => $rows ], false );
	}
}
