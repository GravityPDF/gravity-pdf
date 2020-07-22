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
		Finder::create()->files()->in( $path . 'vendor/querypath/querypath/' )->depth('== 0')->name( [ 'CREDITS', 'COPYING-MIT.txt' ] ),
		Finder::create()->files()->in( $path . 'vendor/querypath/querypath/src/' )->name( [ '*.php' ] ),
		Finder::create()->files()->in( $path . 'vendor/masterminds/html5/' )->depth('== 0')->name( [ 'LICENSE.txt', 'CREDITS' ] ),
		Finder::create()->files()->in( $path . 'vendor/masterminds/html5/src/' )->name( [ '*.php' ] ),
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
		function (string $filePath, string $prefix, string $content): string {

			if ( basename( $filePath ) === 'DOMTraverser.php' ) {
				$content = str_replace( "\\$prefix\SPLObjectStorage", '\SPLObjectStorage', $content );
			}

			return str_replace(
				"'\\\\QueryPath\\\\",
				"'\\\\$prefix\\\\QueryPath\\\\",
				$content
			);
		},
	],

	'whitelist' => [
		'Psr\*',
	],
];
