<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Concerns;

use GFPDF\Model\Model_Core_Font_Downloader;
use GPDFAPI;

/**
 * Builds and stores failed core font download records as the downloader keeps them
 */
trait RecordsFontFailures {

	/**
	 * @param int      $gave_up When its retry also failed, or 0 while the retry is pending
	 * @param int|null $time    When it last failed, defaulting to now
	 */
	protected function failure( int $gave_up = 0, ?int $time = null ): array {
		return [
			'time'    => $time ?? time(),
			'gave_up' => $gave_up,
		];
	}

	/**
	 * Store the record where the downloader keeps it, and have the shared downloader read it afresh
	 *
	 * @param mixed $failures
	 */
	protected function set_font_failures( $failures ): void {
		if ( is_multisite() ) {
			update_site_option( Model_Core_Font_Downloader::FAILURES, $failures );
		} else {
			update_option( Model_Core_Font_Downloader::FAILURES, $failures, false );
		}

		GPDFAPI::get_mvc_class( 'Model_Core_Font_Downloader' )->flush();
	}

	/**
	 * @return mixed The stored record, or false
	 */
	protected function get_font_failures() {
		return is_multisite() ? get_site_option( Model_Core_Font_Downloader::FAILURES ) : get_option( Model_Core_Font_Downloader::FAILURES );
	}

	protected function delete_font_failures(): void {
		delete_site_option( Model_Core_Font_Downloader::FAILURES );
		delete_option( Model_Core_Font_Downloader::FAILURES );
		GPDFAPI::get_mvc_class( 'Model_Core_Font_Downloader' )->flush();
	}
}
