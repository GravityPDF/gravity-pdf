<?php

/**
 * Override certain pluggable functions so we can unit test them correctly
 */
function auth_redirect() {
    throw new Exception('Redirecting');
}

$_tests_dir = getenv('WP_TESTS_DIR');
if ( !$_tests_dir ) $_tests_dir = '/tmp/wordpress-tests-lib';

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../../gravityforms/gravityforms.php';
	require dirname( __FILE__ ) . '/../../gravityformspolls/polls.php';
	require dirname( __FILE__ ) . '/../../gravityformsquiz/quiz.php';
	require dirname( __FILE__ ) . '/../../gravityformssurvey/survey.php';

    /* initialise Gravity Forms tables are created */
    GFForms::setup(true);
    
	require dirname( __FILE__ ) . '/../../gravity-pdf/gravity-pdf.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

register_shutdown_function(function(){
    /* remove Gravity Form tables */
    RGFormsModel::drop_tables();
});
