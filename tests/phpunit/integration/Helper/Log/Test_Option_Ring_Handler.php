<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Log;

use GFPDF\Tests\Integration\TestCase;
use GFPDF_Vendor\Monolog\Logger as MonoLogger;
use stdClass;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * The errors kept for a support ticket nobody knew they would need
 *
 * @package   GFPDF\Helper\Log
 *
 * @group     helper
 */
class Test_Option_Ring_Handler extends TestCase {

	/**
	 * @var MonoLogger
	 */
	public $log;

	/**
	 * @var Option_Ring_Handler
	 */
	public $handler;

	public function set_up(): void {
		parent::set_up();

		$this->handler = new Option_Ring_Handler( MonoLogger::ERROR );

		$this->log = new MonoLogger( 'gravity-pdf' );
		$this->log->pushHandler( $this->handler );
	}

	public function tear_down(): void {
		Option_Ring_Handler::clear();

		parent::tear_down();
	}

	/**
	 * One write per request rather than one per error, so a batch failing against a dead origin is still one write
	 */
	protected function persist(): array {
		$this->handler->persist();

		return Option_Ring_Handler::get_errors();
	}

	public function test_an_error_is_kept_with_what_it_was_about() {
		$this->log->error( 'Font install failed', [ 'entry' => 'packs/emoji' ] );

		$errors = $this->persist();

		$this->assertCount( 1, $errors );
		$this->assertSame( 'Font install failed', $errors[0]['message'] );
		$this->assertSame( 'gravity-pdf', $errors[0]['channel'] );
		$this->assertStringContainsString( 'packs/emoji', $errors[0]['context'] );
	}

	/**
	 * Monolog is the verbose tier and it is off by default; this one is not, so it only takes what matters
	 */
	public function test_anything_below_an_error_is_not_kept() {
		$this->log->warning( 'A warning' );
		$this->log->notice( 'A notice' );
		$this->log->debug( 'A debug line' );

		$this->assertSame( [], $this->persist() );
	}

	public function test_the_eleventh_error_evicts_the_first() {
		for ( $i = 1; $i <= 12; $i++ ) {
			$this->log->error( 'Error ' . $i );
		}

		$errors = $this->persist();

		$this->assertCount( Option_Ring_Handler::SIZE, $errors );
		$this->assertSame( 'Error 3', $errors[0]['message'] );
		$this->assertSame( 'Error 12', $errors[9]['message'] );
	}

	public function test_errors_from_an_earlier_request_are_kept_beside_this_ones() {
		$this->log->error( 'First request' );
		$this->persist();

		$this->log->error( 'Second request' );

		$this->assertSame( [ 'First request', 'Second request' ], array_column( $this->persist(), 'message' ) );
	}

	/**
	 * A caller can put anything in a context, including an object holding an entire mPDF instance
	 */
	public function test_an_object_in_the_context_is_named_rather_than_serialised() {
		$this->log->error( 'PDF Generation Error', [ 'pdf' => new stdClass(), 'entry_id' => 12 ] );

		$this->assertSame( '{"pdf":"(object)","entry_id":12}', $this->persist()[0]['context'] );
	}

	/**
	 * A ticket is read by a person, and an escaped path is one more thing between them and the answer
	 */
	public function test_a_path_in_the_context_stays_readable() {
		$this->log->error( 'Font install failed', [ 'entry' => 'packs/emoji' ] );

		$this->assertSame( '{"entry":"packs/emoji"}', $this->persist()[0]['context'] );
	}

	public function test_a_very_long_message_is_truncated() {
		$this->log->error( str_repeat( 'a', Option_Ring_Handler::MAX_MESSAGE + 100 ) );

		$this->assertSame( Option_Ring_Handler::MAX_MESSAGE, strlen( $this->persist()[0]['message'] ) - 3 );
	}

	/**
	 * The licence key must not reach an option a support ticket copies out of the site
	 */
	public function test_the_ring_is_redacted_like_every_other_handler() {
		$this->log->pushProcessor( new Redact_Processor( 'gravity-pdf' ) );

		$key = str_repeat( 'a1b2', 8 );

		$this->log->error( 'Update check failed', [ 'edd_license_key' => $key, 'body' => 'key=' . $key ] );

		$context = $this->persist()[0]['context'];

		$this->assertStringNotContainsString( $key, $context );
		$this->assertStringContainsString( '[redacted]', $context );
	}
}
