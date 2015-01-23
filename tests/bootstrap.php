<?php

$_tests_dir = getenv('WP_TESTS_DIR');
if ( !$_tests_dir ) $_tests_dir = '/tmp/wordpress-tests-lib';

define( 'FS_METHOD', 'direct' );

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../../gravityforms/gravityforms.php';	
	require dirname( __FILE__ ) . '/../../gravityformssurvey/survey.php';	
	require dirname( __FILE__ ) . '/../../gravityformsquiz/quiz.php';	
	require dirname( __FILE__ ) . '/../../gravityformspolls/polls.php';	
	require dirname( __FILE__ ) . '/../pdf.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

