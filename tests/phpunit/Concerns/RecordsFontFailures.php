<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Concerns;

use GFPDF\Model\Model_Font_Downloader;

/**
 * Stores a failed font download's back-off as the downloader keeps it
 */
trait RecordsFontFailures {

	/**
	 * Back the file off as a failed download would
	 */
	protected function set_back_off( string $file ): void {
		set_site_transient( Model_Font_Downloader::back_off_key( $file ), time(), Model_Font_Downloader::BACK_OFF );
	}

	/**
	 * Expire the file's back-off
	 */
	protected function delete_back_off( string $file ): void {
		delete_site_transient( Model_Font_Downloader::back_off_key( $file ) );
	}

	/**
	 * @return mixed The file's back-off, or false
	 */
	protected function get_back_off( string $file ) {
		return get_site_transient( Model_Font_Downloader::back_off_key( $file ) );
	}

	/**
	 * When the file's back-off expires, or 0
	 */
	protected function get_back_off_expiry( string $file ): int {
		return (int) get_site_option( '_site_transient_timeout_' . Model_Font_Downloader::back_off_key( $file ) );
	}
}
