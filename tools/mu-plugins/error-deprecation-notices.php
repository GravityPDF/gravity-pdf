<?php

/* Only run on test site and excluded from production build. GPDF_REPORT_DEPRECATIONS keeps deprecations visible */
if ( ! defined( 'TEST_SUITE' ) || ! TEST_SUITE || ( defined( 'GPDF_REPORT_DEPRECATIONS' ) && GPDF_REPORT_DEPRECATIONS ) ) {
	return;
}

error_reporting( E_ALL ^ E_DEPRECATED ); //phpcs:ignore