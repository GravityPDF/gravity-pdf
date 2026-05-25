<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Log;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * MonoLoggerPsrLog2And3 is the PSR-Log v2/v3 proxy returned by Logger::get_monolog()
 * when the active autoloader provides the typed interface. The wp-env test environment
 * ships only the bundled v1 (prefixed and class_aliased), so autoloading the class
 * raises a fatal LSP variance error that no try/catch can intercept. These tests
 * characterize the class structurally so the v2/v3 contract can't drift unnoticed.
 *
 * @group logger
 */
class Test_MonoLoggerPsrLog2And3 extends TestCase {

	private $source;

	public function set_up() {
		$this->source = file_get_contents( __DIR__ . '/../../../../../src/Helper/Log/MonoLoggerPsrLog2And3.php' );
	}

	public function test_class_implements_psr_log_interface() {
		$this->assertStringContainsString(
			'class MonoLoggerPsrLog2And3 implements \Psr\Log\LoggerInterface',
			$this->source,
			'Proxy must implement the global \Psr\Log\LoggerInterface — Gravity PDF type-hints this interface throughout the codebase.'
		);
	}

	public function test_class_holds_an_inner_monolog_instance() {
		$this->assertMatchesRegularExpression(
			'/protected\s+\$monologger;/',
			$this->source,
			'Proxy must keep a $monologger property — __call forwards to it.'
		);
	}

	public function test_constructor_builds_monolog_with_supplied_slug() {
		$this->assertMatchesRegularExpression(
			'/public\s+function\s+__construct\(\s*\$slug\s*\)\s*\{[^}]*new\s+\\\\GFPDF_Vendor\\\\Monolog\\\\Logger\(\s*\$slug\s*\)/',
			$this->source,
			'Constructor must instantiate the inner Monolog\\Logger from the supplied slug.'
		);
	}

	public function test_call_proxies_unknown_methods_to_monologger() {
		$this->assertMatchesRegularExpression(
			'/public\s+function\s+__call\(\s*\$method_name,\s*\$args\s*\)\s*\{\s*return\s+call_user_func_array\(\s*\[\s*\$this->monologger,\s*\$method_name\s*\]/',
			$this->source,
			'__call must forward to the inner monologger so non-PSR methods (e.g. getName, pushHandler) work.'
		);
	}

	/**
	 * @dataProvider psr_log_levels
	 */
	public function test_psr_log_level_methods_proxy_with_v2_v3_signature( string $level ) {
		$pattern = '/public\s+function\s+' . preg_quote( $level, '/' )
			. '\(\s*string\|\\\\Stringable\s+\$message,\s*array\s+\$context\s*=\s*\[\]\s*\)\s*:\s*void\s*\{[^}]*\$this->monologger->'
			. preg_quote( $level, '/' )
			. '\(\s*\$message,\s*\$context\s*\)/';

		$this->assertMatchesRegularExpression(
			$pattern,
			$this->source,
			"PSR-3 level method '$level' must keep its v2/v3 signature (string|\\Stringable \$message, array \$context = []): void and forward to the inner monologger."
		);
	}

	public function test_log_method_keeps_psr_v2_v3_signature() {
		$pattern = '/public\s+function\s+log\(\s*\$level,\s*string\|\\\\Stringable\s+\$message,\s*array\s+\$context\s*=\s*\[\]\s*\)\s*:\s*void\s*\{[^}]*\$this->monologger->log\(\s*\$level,\s*\$message,\s*\$context\s*\)/';

		$this->assertMatchesRegularExpression(
			$pattern,
			$this->source,
			'log() must keep its v2/v3 signature and forward to the inner monologger.'
		);
	}

	public function psr_log_levels(): array {
		return [
			'debug'     => [ 'debug' ],
			'info'      => [ 'info' ],
			'notice'    => [ 'notice' ],
			'warning'   => [ 'warning' ],
			'error'     => [ 'error' ],
			'critical'  => [ 'critical' ],
			'alert'     => [ 'alert' ],
			'emergency' => [ 'emergency' ],
		];
	}
}
