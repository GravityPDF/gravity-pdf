<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Log;

use GFPDF\Tests\Integration\TestCase;
use GFPDF_Vendor\Monolog\DateTimeImmutable;
use GFPDF_Vendor\Monolog\Handler\TestHandler;
use GFPDF_Vendor\Monolog\Logger as MonoLogger;

/**
 * @group logger
 */
class Test_Redact_Processor extends TestCase {

	protected function processor( $slug = 'test-plugin' ) {
		return new Redact_Processor( $slug );
	}

	/**
	 * Keyed context redaction replaces the whole value with the placeholder, case-insensitively.
	 */
	public function test_redacts_context_by_key() {
		$context = $this->processor()->context(
			[
				'license'       => 'abc123',
				'Authorization' => 'Bearer xyz',
				'status'        => 'valid',
			]
		);

		$this->assertSame( '[redacted]', $context['license'] );
		$this->assertSame( '[redacted]', $context['Authorization'] );
		$this->assertSame( 'valid', $context['status'] );
	}

	/**
	 * Recurses into nested arrays so deeply-nested secrets (eg. HTTP response headers) are caught.
	 */
	public function test_redacts_nested_arrays() {
		$context = $this->processor()->context(
			[
				'response' => [
					'headers' => [
						'authorization' => 'Bearer xyz',
						'content-type'  => 'application/json',
					],
				],
			]
		);

		$this->assertSame( '[redacted]', $context['response']['headers']['authorization'] );
		$this->assertSame( 'application/json', $context['response']['headers']['content-type'] );
	}

	/**
	 * Objects are cast to arrays and recursed so an object-valued context can't smuggle secrets past redaction.
	 */
	public function test_redacts_nested_objects() {
		$obj           = new \stdClass();
		$obj->token    = 'secret-value';
		$obj->harmless = 'keep-me';

		$context = $this->processor()->context( [ 'data' => $obj ] );

		$this->assertSame( '[redacted]', $context['data']['token'] );
		$this->assertSame( 'keep-me', $context['data']['harmless'] );
	}

	/**
	 * A circular reference must not recurse forever — the depth cap replaces the value instead of exhausting the stack.
	 */
	public function test_caps_recursion_on_circular_references() {
		$node        = new \stdClass();
		$node->child = $node;

		/* Without the depth cap this overflows the stack before returning; reaching the assertion proves it terminates. */
		$result = $this->processor()->context( [ 'root' => $node ] );

		/* Follow the self-referential chain; it must bottom out at the marker rather than loop. */
		$cursor = $result['root'];
		while ( is_array( $cursor ) ) {
			$cursor = $cursor['child'];
		}

		$this->assertSame( '[redacted]', $cursor );
	}

	/**
	 * Non-string keys and scalar values that don't match a deny-key pass through untouched.
	 */
	public function test_preserves_untargeted_values() {
		$context = $this->processor()->context(
			[
				0         => 'positional',
				'count'   => 5,
				'enabled' => true,
				'nothing' => null,
			]
		);

		$this->assertSame( [ 0 => 'positional', 'count' => 5, 'enabled' => true, 'nothing' => null ], $context );
	}

	/**
	 * The filter may add deny-keys.
	 */
	public function test_filter_adds_keys() {
		add_filter(
			'gfpdf_logging_redact_keys',
			function ( $keys ) {
				$keys[] = 'my_custom_secret';

				return $keys;
			}
		);

		$context = $this->processor()->context( [ 'my_custom_secret' => 'hunter2', 'license' => 'abc' ] );

		$this->assertSame( '[redacted]', $context['my_custom_secret'] );
		$this->assertSame( '[redacted]', $context['license'] );
	}

	/**
	 * The filter may only add keys — returning an empty set can't weaken the defaults.
	 */
	public function test_filter_cannot_remove_defaults() {
		add_filter(
			'gfpdf_logging_redact_keys',
			function () {
				return [];
			}
		);

		$context = $this->processor()->context( [ 'license' => 'abc123' ] );

		$this->assertSame( '[redacted]', $context['license'] );
	}

	/**
	 * A raw non-JSON API body stored under a benign key (eg. 'response'/'body') isn't caught by keyed redaction, so
	 * its string value is run through the same message() pattern scrub — masking the signed URLs and echoed keys the
	 * raw-body fallback path would otherwise leak.
	 */
	public function test_pattern_scrubs_context_string_leaves() {
		$context = $this->processor()->context(
			[
				'response' => 'package https://store.com/download/file.zip?signature=deadbeef',
				'body'     => 'license 098f6bcd4621d373cade4e832627b4f6 rejected',
			]
		);

		$this->assertSame( 'package https://store.com/download/file.zip?', $context['response'] );
		$this->assertSame( 'license [redacted] rejected', $context['body'] );
	}

	/**
	 * set-cookie is a deny-key: a Set-Cookie header can carry a session secret.
	 */
	public function test_redacts_set_cookie_key() {
		$context = $this->processor()->context( [ 'set-cookie' => 'session=abc; HttpOnly' ] );

		$this->assertSame( '[redacted]', $context['set-cookie'] );
	}

	/**
	 * @dataProvider provider_message_patterns
	 */
	public function test_redacts_message_patterns( $message, $expected ) {
		$this->assertSame( $expected, $this->processor()->message( $message ) );
	}

	public function provider_message_patterns() {
		return [
			'hex license'  => [ 'License 098f6bcd4621d373cade4e832627b4f6 rejected', 'License [redacted] rejected' ],
			'bearer token' => [ 'Auth failed: Bearer abc.def.ghi', 'Auth failed: [redacted]' ],
			'google oauth' => [ 'token ya29.a0AfB_xyz-123 expired', 'token [redacted] expired' ],
			'stripe key'   => [ 'using sk_4eC39HqLyjWDarjtT1zdp7dc', 'using [redacted]' ],
			'plain text'   => [ 'nothing sensitive here', 'nothing sensitive here' ],
		];
	}

	/**
	 * Signed-link secrets live in the query string, so keep the path and drop everything after the ?.
	 */
	public function test_blanks_url_query_strings() {
		$this->assertSame(
			'Download failed https://example.com/file.zip?',
			$this->processor()->message( 'Download failed https://example.com/file.zip?token=secret&exp=123' )
		);
	}

	/**
	 * A newline in message data must not be able to forge a second log line.
	 */
	public function test_collapses_line_breaks() {
		$this->assertSame(
			'line one line two line three',
			$this->processor()->message( "line one\r\nline two\nline three" )
		);
	}

	/**
	 * Non-printable control characters are stripped before redaction.
	 */
	public function test_strips_control_characters() {
		$this->assertSame( 'clean', $this->processor()->message( "cl\x00ea\x07n" ) );
	}

	/**
	 * __invoke redacts both the message body and the context in a single pass.
	 */
	public function test_invoke_redacts_message_and_context() {
		$record = [
			'message'  => 'License 098f6bcd4621d373cade4e832627b4f6 rejected',
			'context'  => [ 'license' => 'abc123', 'status' => 'invalid' ],
			'level'    => MonoLogger::toMonologLevel( MonoLogger::WARNING ),
			'channel'  => 'test',
			'datetime' => new DateTimeImmutable( true ),
			'extra'    => [],
		];

		$processed = ( $this->processor() )( $record );

		$this->assertSame( 'License [redacted] rejected', $processed['message'] );
		$this->assertSame( '[redacted]', $processed['context']['license'] );
		$this->assertSame( 'invalid', $processed['context']['status'] );
	}

	/**
	 * Works as a pushed Monolog processor end-to-end.
	 */
	public function test_works_as_monolog_processor() {
		$handler = new TestHandler();
		$logger  = new MonoLogger( 'Test', [ $handler ], [ $this->processor() ] );

		$logger->info( 'Key 098f6bcd4621d373cade4e832627b4f6', [ 'token' => 'shhh' ] );

		$this->assertTrue(
			$handler->hasRecordThatPasses(
				function ( $record ): bool {
					return $record['message'] === 'Key [redacted]' && $record['context']['token'] === '[redacted]';
				},
				MonoLogger::INFO
			)
		);
	}
}
