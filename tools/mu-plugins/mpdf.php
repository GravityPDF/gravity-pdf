<?php

/* Only run on test site and excluded from production build */
if ( ! defined( 'TEST_SUITE' ) || ! TEST_SUITE ) {
	return;
}

/* Setup basic fonts */
$config = static function ( $config ) {
	return array_merge(
		$config,
		[
			'mode'          => 'c',
			'biDirectional' => false,
		]
	);
};

add_filter( 'gfpdf_mpdf_class_config', $config );
