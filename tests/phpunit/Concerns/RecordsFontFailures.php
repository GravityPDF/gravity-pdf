<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Concerns;

use GFPDF\Model\Model_Core_Font_Downloader;
use GPDFAPI;

/**
 * Builds and stores failed core font downloads as the downloader keeps them: the retry and notice record, and each
 * file's back-off
 */
trait RecordsFontFailures {

	/**
	 * @param int      $gave_up  When its retry also failed, or 0 while the retry is pending
	 * @param int|null $retry_at When the pending retry is due, defaulting to now
	 */
	protected function failure( int $gave_up = 0, ?int $retry_at = null ): array {
		return [
			'retry_at' => $gave_up > 0 ? 0 : ( $retry_at ?? time() ),
			'gave_up'  => $gave_up,
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

	/**
	 * Back the file off as a failed download would
	 */
	protected function set_back_off( string $file ): void {
		$key = Model_Core_Font_Downloader::back_off_key( $file );
		is_multisite() ? set_site_transient( $key, time(), Model_Core_Font_Downloader::BACK_OFF ) : set_transient( $key, time(), Model_Core_Font_Downloader::BACK_OFF );
	}

	/**
	 * Expire the file's back-off
	 */
	protected function delete_back_off( string $file ): void {
		$key = Model_Core_Font_Downloader::back_off_key( $file );
		is_multisite() ? delete_site_transient( $key ) : delete_transient( $key );
	}

	/**
	 * @return mixed The file's back-off, or false
	 */
	protected function get_back_off( string $file ) {
		$key = Model_Core_Font_Downloader::back_off_key( $file );

		return is_multisite() ? get_site_transient( $key ) : get_transient( $key );
	}

	/**
	 * When the file's back-off expires, or 0
	 */
	protected function get_back_off_expiry( string $file ): int {
		$key = ( is_multisite() ? '_site_transient_timeout_' : '_transient_timeout_' ) . Model_Core_Font_Downloader::back_off_key( $file );

		return (int) ( is_multisite() ? get_site_option( $key ) : get_option( $key ) );
	}

	protected function delete_font_failures(): void {
		delete_site_option( Model_Core_Font_Downloader::FAILURES );
		delete_option( Model_Core_Font_Downloader::FAILURES );
		GPDFAPI::get_mvc_class( 'Model_Core_Font_Downloader' )->flush();
	}
}
