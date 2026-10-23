<?php

namespace GFPDF\Helper\Log;

use GFPDF\Helper\Mpdf\Request;
use GPDFAPI;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

class Test_MonoLogger_Bridge extends WP_UnitTestCase {

	public function test_mpdf_accepts_logger() {
		/* mPDF's setLogger() type-hints the scoped interface */
		( new Request() )->setLogger( GPDFAPI::get_log_class() );

		$this->addToAssertionCount( 1 );
	}

	/**
	 * Each case needs its own process, as constants, aliases and interfaces can only be declared once
	 *
	 * @dataProvider provider_get_monolog
	 */
	public function test_get_monolog( $setup, $expected ) {
		$this->assertSame( $expected, $this->run_get_monolog( $setup ) );
	}

	public function provider_get_monolog() {
		$plain   = 'GFPDF_Vendor\Monolog\Logger none';
		$aliased = 'GFPDF\Helper\Log\MonoLogger_Bridge GFPDF_Vendor\Psr\Log\LoggerInterface';
		$theirs  = 'GFPDF\Helper\Log\MonoLogger_Bridge Psr\Log\LoggerInterface';

		$cases = [
			'no add-on, no global psr/log' => [ '', $plain ],
			'Bulk Generator 3.0.0'         => [ 'define( "GFPDF_PDF_BULK_GENERATOR_VERSION", "3.0.0" );', $aliased ],
			'Bulk Generator 3.1.0'         => [ 'define( "GFPDF_PDF_BULK_GENERATOR_VERSION", "3.1.0" );', $plain ],
			'PDF for GravityView 1.3.0'    => [ 'define( "GFPDF_PDF_FOR_GV_VERSION", "1.3.0" );', $aliased ],
			'PDF for GravityView 2.0.0'    => [ 'define( "GFPDF_PDF_FOR_GV_VERSION", "2.0.0" );', $plain ],
			'filter forces the alias'      => [ '$filter = true;', $aliased ],
		];

		/* psr/log v2 and v3 require PHP 8.0 */
		if ( PHP_VERSION_ID >= 80000 ) {
			$cases['another plugin loaded psr/log v2'] = [ $this->get_global_interface( '' ), $theirs ];
			$cases['another plugin loaded psr/log v3'] = [ $this->get_global_interface( ': void' ), $theirs ];
		}

		return $cases;
	}

	protected function get_global_interface( $return_type ) {
		$levels  = [ 'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug' ];
		$methods = '';
		foreach ( $levels as $level ) {
			$methods .= "public function $level( string|\\Stringable \$message, array \$context = [] )$return_type;";
		}
		$methods .= "public function log( \$level, string|\\Stringable \$message, array \$context = [] )$return_type;";

		return "eval( 'namespace Psr\\\\Log; interface LoggerInterface { $methods }' );";
	}

	/**
	 * Calls Logger::get_monolog() in a fresh PHP process; reports the class returned and what the global interface is
	 */
	protected function run_get_monolog( $setup ) {
		$autoloader = var_export( PDF_PLUGIN_DIR . 'vendor/autoload.php', true );

		$script = <<<PHP
<?php
define( 'ABSPATH', __DIR__ );
require $autoloader;
$setup
function apply_filters( \$hook, \$value ) { return \$GLOBALS['filter'] ?? \$value; }
\$method = new ReflectionMethod( GFPDF\Helper\Log\Logger::class, 'get_monolog' );
if ( PHP_VERSION_ID < 80100 ) {
	\$method->setAccessible( true );
}
\$log = \$method->invoke( new GFPDF\Helper\Log\Logger( 'test', 'Test' ) );
\$log->error( 'test' );
\$global = 'none';
if ( interface_exists( 'Psr\Log\LoggerInterface', false ) ) {
	\$global = ( new ReflectionClass( 'Psr\Log\LoggerInterface' ) )->getName();
}
echo get_class( \$log ), ' ', \$global;
PHP;

		$file = tempnam( get_temp_dir(), 'gfpdf' );
		file_put_contents( $file, $script );

		/* XDEBUG_MODE beats any ini setting, so an inherited step-debug config can't print warnings into the output */
		exec( 'XDEBUG_MODE=off ' . escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $file ) . ' 2>&1', $output );
		unlink( $file );

		return implode( "\n", $output );
	}
}
