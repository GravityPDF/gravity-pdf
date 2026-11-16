<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Concerns;

use GFPDF\Model\Model_Font_Downloader;
use GPDFAPI;

/**
 * Builds and stores failed font downloads as the downloader keeps them: the retry and notice record, and each
 * file's back-off
 */
trait RecordsFontFailures {

	/**
	 * A failure record as the downloader stores it
	 *
	 * @param int      $gave_up  When its retry also failed, or 0 while the retry is pending
	 * @param int|null $retry_at When the pending retry is due, defaulting to now
	 *
	 * @return array{retry_at: int, gave_up: int, dismissed: bool, conflict: string}
	 */
	protected function failure( int $gave_up = 0, ?int $retry_at = null ): array {
		return [
			'retry_at'  => $gave_up > 0 ? 0 : ( $retry_at ?? time() ),
			'gave_up'   => $gave_up,
			'dismissed' => false,
			'conflict'  => '',
		];
	}

	/**
	 * A failure that gave up with another font's file at the font's name, as the downloader stamps it
	 *
	 * @return array{retry_at: int, gave_up: int, dismissed: bool, conflict: string}
	 */
	protected function conflict_failure( string $path ): array {
		return array_merge( $this->failure( time() ), [ 'conflict' => filesize( $path ) . ':' . filemtime( $path ) ] );
	}

	/**
	 * Store the record where the downloader keeps it, on the main network, and have the shared downloader read it afresh
	 *
	 * @param mixed $failures
	 */
	protected function set_font_failures( $failures ): void {
		update_network_option( get_main_network_id(), Model_Font_Downloader::FAILURES, $failures );
		GPDFAPI::get_mvc_class( 'Model_Font_Downloader' )->flush();
	}

	/**
	 * Read the failure record where the downloader keeps it
	 *
	 * @return mixed The stored record, or false
	 */
	protected function get_font_failures() {
		return get_network_option( get_main_network_id(), Model_Font_Downloader::FAILURES );
	}

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

	/**
	 * The stored 6.19 cleanup progress
	 *
	 * @return mixed
	 */
	protected function get_font_cleanup() {
		return get_network_option( get_main_network_id(), Model_Font_Downloader::CLEANUP );
	}

	/**
	 * @param mixed $state
	 */
	protected function set_font_cleanup( $state ): void {
		update_network_option( get_main_network_id(), Model_Font_Downloader::CLEANUP, $state );
	}

	/**
	 * Cleanup progress for the current site with nothing found yet, started after any fixture was written
	 *
	 * @param array $state Replaces the fields given
	 */
	protected function font_cleanup_state( array $state = [] ): array {
		return array_merge(
			[
				'started' => time(),
				'sites'   => [ get_current_blog_id() ],
				'keys'    => [],
			],
			$state
		);
	}

	/**
	 * Delete the failure record and the wanted files, and have the shared downloader read it afresh
	 */
	protected function delete_font_failures(): void {
		delete_network_option( get_main_network_id(), Model_Font_Downloader::FAILURES );
		delete_network_option( get_main_network_id(), Model_Font_Downloader::WANTED );
		GPDFAPI::get_mvc_class( 'Model_Font_Downloader' )->flush();
	}
}
