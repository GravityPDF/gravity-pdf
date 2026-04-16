<?php

/* Only run on test site and excluded from production build */
if ( ! defined( 'TEST_SUITE' ) || ! TEST_SUITE ) {
	return;
}

add_filter( 'gfpdf_one_time_action_routes', '__return_empty_array' );