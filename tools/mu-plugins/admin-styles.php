<?php

/* Only run on the E2E test site and excluded from production build */
if ( ! defined( 'E2E_TEST_SUITE' ) || ! E2E_TEST_SUITE ) {
	return;
}

function override_website_fonts_for_consistent_snapshot_testing() {
	echo '<style>.row-actions { position: static !important; }</style>' . PHP_EOL;
}

add_action( 'admin_head', 'override_website_fonts_for_consistent_snapshot_testing' );
