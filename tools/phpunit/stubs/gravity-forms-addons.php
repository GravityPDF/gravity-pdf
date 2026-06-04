<?php
/**
 * PHPUnit-only stubs replacing the deleted gravityformspolls / quiz / survey vendor plugins.
 *
 * Gravity PDF's Model_PDF and field handlers only need three things from those add-ons:
 *   - class_exists('GFPolls'|'GFQuiz'|'GFSurvey') to be true so the relevant code paths run.
 *   - GFQuiz::get_instance() to return an object with a results_calculation() method
 *     (passed as a GFResults callback in Model_PDF::get_quiz_overall_data()).
 *   - Nothing else - all field rendering and aggregation runs through Gravity Forms core.
 *
 * Loaded by tools/phpunit/bootstrap.php in place of the real plugin entrypoints.
 */

if ( ! class_exists( 'GFPolls' ) ) {
	class GFPolls {}
}

if ( ! class_exists( 'GFSurvey' ) ) {
	class GFSurvey {}
}

if ( ! class_exists( 'GFQuiz' ) ) {
	class GFQuiz {

		/** @var self|null */
		private static $instance;

		public static function get_instance() {
			if ( self::$instance === null ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function results_calculation( $data, $form, $fields, $leads ) {
			return $data;
		}
	}
}
