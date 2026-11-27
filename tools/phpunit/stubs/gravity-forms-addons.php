<?php
/**
 * PHPUnit-only stubs replacing the deleted gravityformspolls / quiz / survey vendor plugins.
 *
 * Gravity PDF's Model_PDF and field handlers only need three things from those add-ons:
 *   - class_exists('GFPolls'|'GFQuiz'|'GFSurvey') to be true so the relevant code paths run.
 *   - GFQuiz::get_instance() to return an object with a results_calculation() method
 *     (passed as a GFResults callback in Model_PDF::get_quiz_overall_data()).
 *   - On Gravity Forms < 2.10, a likert field class so {all_fields} skips its array value without a notice.
 *   - Nothing else - all field rendering and aggregation runs through Gravity Forms core.
 *
 * Loaded by tools/phpunit/bootstrap.php in place of the real plugin entrypoints.
 */

if ( ! class_exists( 'GFPolls' ) ) {
	class GFPolls {}
}

if ( ! class_exists( 'GFSurvey' ) ) {
	class GFSurvey {}

	/* Gravity Forms 2.10+ leaves an inactive add-on's array value out of {all_fields}, earlier versions raise a notice */
	if ( version_compare( GFForms::$version, '2.10', '<' ) ) {
		class GF_Field_Likert_Stub extends GF_Field {
			public $type = 'likert';

			public function get_value_entry_detail( $value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' ) {
				if ( is_array( $value ) ) {
					return $media === 'email' ? false : '';
				}

				return parent::get_value_entry_detail( $value, $currency, $use_text, $format, $media );
			}
		}

		GF_Fields::register( new GF_Field_Likert_Stub() );
	}
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
