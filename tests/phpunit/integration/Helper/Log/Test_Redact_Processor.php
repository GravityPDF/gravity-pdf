<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Log;

use GFPDF_Vendor\Monolog\DateTimeImmutable;
use GFPDF_Vendor\Monolog\Handler\TestHandler;
use GFPDF_Vendor\Monolog\Logger as MonoLogger;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * @group logger
 * @internal Not testing WordPress, so use Yoast TestCase directly instead of WP_UnitTestCase
 */
class Test_Redact_Processor extends TestCase {

	public function test_basic_usage() {
		$handler   = new TestHandler();
		$processor = new Redact_Processor( [ 'test_key' => 4 ] );

		$logger = new MonoLogger( 'Test', [ $handler ], [ $processor ] );
		$logger->info( 'Testing', [ 'test_key' => 'test_value' ] );

		$this->assertTrue(
			$handler->hasRecordThatPasses( function ( $record ): bool {
				return $record['context']['test_key'] === 'test******';
			}, MonoLogger::INFO )
		);
	}

	public function test_redacts_records_contexts() {
		$sensitive_keys = [ 'test' => 3 ];
		$processor      = new Redact_Processor( $sensitive_keys );

		$record = $this->get_record( [ 'test' => 'foobar' ] );

		$this->assertSame( [ 'test' => 'foo***' ], $processor( $record )['context'] );
	}

	public function test_redacts_using_template() {
		$sensitive_keys = [ 'test' => 2 ];
		$processor      = new Redact_Processor( $sensitive_keys, '*', '%s(redacted)' );

		$record = $this->get_record( [ 'test' => 'foobar' ] );

		$this->assertSame( [ 'test' => 'fo****(redacted)' ], $processor( $record )['context'] );
	}

	public function test_redacts_discarding_masked() {
		$sensitive_keys = [ 'test' => 1 ];
		$processor      = new Redact_Processor( $sensitive_keys, '*', '...' );

		$record = $this->get_record( [ 'test' => 'foobar123' ] );

		$this->assertSame( [ 'test' => 'f...' ], $processor( $record )['context'] );
	}

	public function test_truncates_masked_characters() {
		$sensitive_keys = [ 'test' => 3 ];
		$processor      = new Redact_Processor( $sensitive_keys, '*', '%s', 5 );

		$record = $this->get_record( [ 'test' => 'foobar' ] );

		$this->assertSame( [ 'test' => 'foo**' ], $processor( $record )['context'] );
	}

	public function test_truncates_visible_characters() {
		$sensitive_keys = [ 'test' => 3 ];
		$processor      = new Redact_Processor( $sensitive_keys, '*', '%s', 2 );

		$record = $this->get_record( [ 'test' => 'foobar' ] );

		$this->assertSame( [ 'test' => 'fo' ], $processor( $record )['context'] );
	}

	public function test_overrides_default_replacement() {
		$sensitive_keys = [ 'test' => 3 ];
		$processor      = new Redact_Processor( $sensitive_keys, '_' );

		$record = $this->get_record( [ 'test' => 'foobar' ] );

		$this->assertSame( [ 'test' => 'foo___' ], $processor( $record )['context'] );
	}

	public function test_redacts_from_right_to_left() {
		$sensitive_keys = [ 'test' => -3 ];
		$processor      = new Redact_Processor( $sensitive_keys );

		$record = $this->get_record( [ 'test' => 'foobar' ] );

		$this->assertSame( [ 'test' => '***bar' ], $processor( $record )['context'] );
	}

	public function test_truncates_masked_from_right_to_left() {
		$sensitive_keys = [ 'test' => -3 ];
		$processor      = new Redact_Processor( $sensitive_keys, '*', '%s', 4 );

		$record = $this->get_record( [ 'test' => 'foobar' ] );

		$this->assertSame( [ 'test' => '*bar' ], $processor( $record )['context'] );
	}

	public function test_truncates_visible_from_right_to_left() {
		$sensitive_keys = [ 'test' => -3 ];
		$processor      = new Redact_Processor( $sensitive_keys, '*', '%s', 2 );

		$record = $this->get_record( [ 'test' => 'foobar' ] );

		$this->assertSame( [ 'test' => 'ar' ], $processor( $record )['context'] );
	}

	public function test_redacts_nested_arrays() {
		$sensitive_keys = [ 'test' => [ 'nested' => 3 ] ];
		$processor      = new Redact_Processor( $sensitive_keys );

		$record = $this->get_record( [ 'test' => [ 'nested' => 'foobar' ] ] );

		$this->assertSame( [ 'test' => [ 'nested' => 'foo***' ] ], $processor( $record )['context'] );
	}

	public function test_redacts_inside_nested_arrays() {
		$sensitive_keys = [ 'nested' => 3 ];
		$processor      = new Redact_Processor( $sensitive_keys );

		$record = $this->get_record( [ 'test' => [ 'nested' => 'foobar' ] ] );

		$this->assertSame( [ 'test' => [ 'nested' => 'foo***' ] ], $processor( $record )['context'] );
	}

	public function test_redacts_nested_objects() {
		$nested         = new \stdClass();
		$nested->value  = 'foobar';
		$nested->nested = [ 'value' => 'bazqux' ];

		$sensitive_keys = [ 'test' => [ 'nested' => [ 'value' => 3, 'nested' => [ 'value' => -3 ] ] ] ];
		$processor      = new Redact_Processor( $sensitive_keys );

		$record = $this->get_record( [ 'test' => [ 'nested' => $nested ] ] );

		$this->assertSame( [ 'test' => [ 'nested' => $nested ] ], $processor( $record )['context'] );
		$this->assertSame( 'foo***', $nested->value );
		$this->assertSame( '***qux', $nested->nested['value'] );
	}

	public function test_redacts_inside_nested_objects() {
		$nested         = new \stdClass();
		$nested->value  = 'foobar';
		$nested->nested = [ 'value' => 'bazqux' ];

		$sensitive_keys = [ 'nested' => [ 'value' => -3 ] ];
		$processor      = new Redact_Processor( $sensitive_keys );

		$record = $this->get_record( [ 'test' => [ 'nested' => $nested ] ] );

		$this->assertSame( [ 'test' => [ 'nested' => $nested ] ], $processor( $record )['context'] );
		$this->assertSame( '***bar', $nested->value );
		$this->assertSame( '***qux', $nested->nested['value'] );
	}

	public function test_preserves_empty_values() {
		$sensitive_keys = [ 'test' => 3, 'optionalKey' => 10 ];
		$processor      = new Redact_Processor( $sensitive_keys );

		$record = $this->get_record( [ 'test' => 'foobar', 'optionalKey' => '' ] );

		$this->assertSame( [ 'test' => 'foo***', 'optionalKey' => '' ], $processor( $record )['context'] );
	}

	public function test_ignored_when_finds_an_un_traversable_value() {
		$sensitive_keys = [ 'test' => 3 ];
		$processor      = new Redact_Processor( $sensitive_keys );

		$context = [ 'test' => fopen( __FILE__, 'rb' ) ];
		$record  = $this->get_record( $context );

		$this->assertSame( $context, $processor( $record )['context'] );
	}

	public function test_should_not_throw_when_non_scalar_value_but_keys_are_not_nested() {
		$sensitive_keys = [ 'test' => -4 ];
		$obj            = new \stdClass();
		$processor      = new Redact_Processor( $sensitive_keys );

		$record = $this->get_record( [ 'test' => $obj ] );

		$this->assertSame( [ 'test' => $obj ], $processor( $record )['context'] );
	}

	public function test_ignore_when_null_value() {
		$sensitive_keys = [ 'test' => 3 ];
		$processor      = new Redact_Processor( $sensitive_keys );

		$record = $this->get_record( [ 'test' => 'foobar', 'optionalKey' => null ] );

		$this->assertSame( [ 'test' => 'foo***', 'optionalKey' => null ], $processor( $record )['context'] );
	}

	public function test_redacts_nested_values_when_key_is_integer() {
		$sensitive_keys = [ 'test' => 3 ];
		$processor      = new Redact_Processor( $sensitive_keys );

		$record = $this->get_record( [ 0 => [ 'good' => 'value' ], 1 => [ 'test' => 'foobar' ] ] );

		$this->assertSame( [ 0 => [ 'good' => 'value' ], 1 => [ 'test' => 'foo***' ] ], $processor( $record )['context'] );
	}

	public function test_redact_with_regex() {
		$sensitive_keys = [ 'key' => '/[A-Fa-f0-9]{28}/' ];
		$processor      = new Redact_Processor( $sensitive_keys );

		$record = $this->get_record( [ 'key' => '098f6bcd4621d373cade4e832627b4f6' ] );
		$this->assertSame( [ 'key' => '****************************b4f6' ], $processor( $record )['context'] );

		$record = $this->get_record( [ 'key' => '098f6bcd4621d373cade4e832627b4f67asb' ] );
		$this->assertSame( [ 'key' => '****************************b4f67asb' ], $processor( $record )['context'] );

		$record = $this->get_record( [ 'key' => 'rate_limit' ] );
		$this->assertSame( [ 'key' => 'rate_limit' ], $processor( $record )['context'] );
	}

	public function test_redact_with_regex_inside_nested_object() {
		$nested         = new \stdClass();
		$nested->value  = 'foobar';
		$nested->nested = [ 'key' => '098f6bcd4621d373cade4e832627b4f6', 'status' => 'rate_limit' ];

		$sensitive_keys = [ 'key' => '/[A-Fa-f0-9]{28}/', 'status' => '/[A-Fa-f0-9]{28}/' ];
		$processor      = new Redact_Processor( $sensitive_keys );

		$record = $this->get_record( [ 'test' => [ 'nested' => $nested ] ] );

		$this->assertSame( [ 'test' => [ 'nested' => $nested ] ], $processor( $record )['context'] );
		$this->assertSame( '****************************b4f6', $nested->nested['key'] );
		$this->assertSame( 'rate_limit', $nested->nested['status'] );
	}

	/**
	 * @param        $context
	 * @param        $level
	 * @param        $message
	 * @param string $channel
	 * @param        $datetime
	 * @param array  $extra
	 *
	 * @return array
	 */
	protected function get_record( $context = [], $level = MonoLogger::WARNING, $message = 'test', string $channel = 'test', $datetime = null, array $extra = [] ) {
		return [
			'message'  => (string) $message,
			'context'  => $context,
			'level'    => MonoLogger::toMonologLevel( $level ),
			'channel'  => $channel,
			'datetime' => $datetime ?? new DateTimeImmutable( true ),
			'extra'    => $extra,
		];
	}
}