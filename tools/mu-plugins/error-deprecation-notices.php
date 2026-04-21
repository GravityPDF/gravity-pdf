<?php

/* Only run on test site and excluded from production build */
if ( ! defined( 'TEST_SUITE' ) || ! TEST_SUITE ) {
	return;
}

error_reporting( E_ALL ^ E_DEPRECATED ); //phpcs:ignore