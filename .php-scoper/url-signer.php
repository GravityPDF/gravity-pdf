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
		Finder::create()->files()->in( $path . 'vendor/spatie/url-signer/' )->exclude( 'tests' )->name( [ '*.php', 'LICENSE.md' ] ),
		Finder::create()->files()->in( $path . 'vendor/league/uri/' )->name( [ '*.php', 'LICENSE' ] ),
		Finder::create()->files()->in( $path . 'vendor/league/uri-components/' )->name( [ '*.php', 'LICENSE' ] ),
		Finder::create()->files()->in( $path . 'vendor/league/uri-interfaces/' )->name( [ '*.php', 'LICENSE' ] ),
	],

	/*
	 * When scoping PHP files, there will be scenarios where some of the code being scoped indirectly references the
	 * original namespace. These will include, for example, strings or string manipulations. PHP-Scoper has limited
	 * support for prefixing such strings. To circumvent that, you can define patchers to manipulate the file to your
	 * heart contents.
	 *
	 * For more see: https://github.com/humbug/php-scoper#patchers
	 */
	'patchers'  => [],

	'whitelist' => [
		'Psr\*',
	],
];
