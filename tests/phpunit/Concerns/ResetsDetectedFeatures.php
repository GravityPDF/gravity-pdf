<?php

declare(strict_types=1);

namespace GFPDF\Tests\Concerns;

/**
 * Clears the deprecation records a test writes, for the classes that write them.
 */
trait ResetsDetectedFeatures {

	/**
	 * Reset what Deprecation and the notices read
	 *
	 * Helper_Abstract_Options keeps `gfpdf_settings` in memory, and the WP test transaction rolls the database
	 * back without touching that copy — so a record written by one test is still readable in the next.
	 */
	protected function reset_detected_features(): void {
		$options = \GPDFAPI::get_options_class();

		$options->update_option( 'deprecated_features', [] );
		$options->update_option( 'action_dismissal', [] );
	}
}
