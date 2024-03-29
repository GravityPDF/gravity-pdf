<?php

declare( strict_types=1 );

use Isolated\Symfony\Component\Finder\Finder;

$path = './';
if ( isset( $_SERVER['argv'][0] ) ) {
	$path = dirname( $_SERVER['argv'][0] ) . '/';
}

return [

	'prefix'    => 'GFPDF_Vendor',

	/*
	 * By default when running php-scoper add-prefix, it will prefix all relevant code found in the current working
	 * directory. You can however define which files should be scoped by defining a collection of Finders in the
	 * following configuration key.
	 *
	 * For more see: https://github.com/humbug/php-scoper#finders-and-paths
	 */
	'finders'   => [
		Finder::create()->files()->in( $path . 'vendor/mpdf/mpdf/src' )->name( [ '*.php' ] ),
		Finder::create()->files()->in( $path . 'vendor/mpdf/mpdf/' )->depth( '==0' )->name( [ 'LICENSE.txt' ] ),
		Finder::create()->files()->in( $path . 'vendor/mpdf/mpdf/data' )->name( [ '*' ] ),
		Finder::create()->files()->in( $path . 'vendor/mpdf/qrcode/' )->exclude( 'tests' )->name( [ '*.php', 'LICENSE', '*.dat' ] ),
		Finder::create()->files()->in( $path . 'vendor/mpdf/psr-log-aware-trait' )->name( [ '*.php' ] ),
		Finder::create()->files()->in( $path . 'vendor/mpdf/psr-http-message-shim' )->name( [ '*.php' ] ),
		Finder::create()->files()->in( $path . 'vendor/psr/log' )->name( [ '*.php', 'LICENSE' ] ),
		Finder::create()->files()->in( $path . 'vendor/psr/http-message' )->name( [ '*.php', 'LICENSE' ] ),
		Finder::create()->files()->in( $path . 'vendor/setasign/fpdi' )->name( [ '*.php', 'LICENSE.txt' ] ),
		Finder::create()->files()->in( $path . 'vendor/myclabs/deep-copy' )->name( [ '*.php', 'LICENSE' ] ),
	],

	/*
	 * When scoping PHP files, there will be scenarios where some of the code being scoped indirectly references the
	 * original namespace. These will include, for example, strings or string manipulations. PHP-Scoper has limited
	 * support for prefixing such strings. To circumvent that, you can define patchers to manipulate the file to your
	 * heart contents.
	 *
	 * For more see: https://github.com/humbug/php-scoper#patchers
	 */
	'patchers'  => [
		function( string $filePath, string $prefix, string $content ): string {

			/* Mpdf fixes */
			if ( basename( $filePath ) === 'Tag.php' ) {
				$content = str_replace( "'Mpdf\\\\Tag\\\\'", "'$prefix\\\\Mpdf\\\\Tag\\\\'", $content );
			}

			if ( basename( $filePath ) === 'FpdiTrait.php' ) {
				$content = str_replace( 'use \\setasign\\', "use \\$prefix\\setasign\\", $content );
			}

			if ( basename( $filePath ) === 'Svg.php' ) {
				$content = str_replace( "$prefix\\\\<svg\\\\1", '<svg\\\\1', $content );
			}

			if ( basename( $filePath ) === 'Mpdf.php' ) {
				$content = str_replace( "$prefix\\\\r\\\\n", '\\r\\n', $content );
				$content = str_replace( "$prefix\\\\</t\\\\1", '</t\\\\1', $content );
			}

			/* Remove type hinting from prefixed logger */
			$files = [
				'LoggerAwareInterface.php',
				'LoggerAwareTrait.php',
				'MpdfPsrLogAwareTrait.php',
				'PsrLogAwareTrait.php'
			];

			if ( in_array( basename( $filePath ), $files, true ) ) {
				$content = str_replace( "\\$prefix\\Psr\\Log\\LoggerInterface", '', $content );
			}

			/* Global polyfills */
			if ( basename( $filePath ) === 'functions.php' ) {
				$content = str_replace( "namespace $prefix;", '', $content );
			}

			return $content;
		},
	],
];
