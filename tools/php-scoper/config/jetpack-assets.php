<?php

declare( strict_types=1 );

use Isolated\Symfony\Component\Finder\Finder;

$path = './';

return [

	'prefix'  => 'GFPDF_Vendor',

	/*
	 * Only scope the classes used by our plugin:
	 *   - class-assets.php  : the static Assets::register_script() API we call directly
	 *   - class-semver.php  : used internally by class-assets.php
	 *   - class-constants.php : Jetpack_Constants helper used by class-assets.php
	 *
	 * Excluded intentionally:
	 *   - class-script-data.php : pulls in automattic/jetpack-status which we do not need
	 *   - actions.php           : registers wp_default_scripts / plugins_loaded hooks for
	 *                             Jetpack-specific i18n infrastructure we do not use
	 */
	'finders' => [
		Finder::create()->files()->in( $path . 'vendor/automattic/jetpack-assets/src' )->name( '*.php' )->notName( 'class-script-data.php' ),
		Finder::create()->files()->in( $path . 'vendor/automattic/jetpack-constants/src' )->name( '*.php' ),
	],

	'patchers' => [],
];
