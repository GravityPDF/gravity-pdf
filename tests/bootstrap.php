<?php

/*
    This file is part of Gravity PDF.

    Gravity PDF is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Gravity PDF is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Gravity PDF. If not, see <http://www.gnu.org/licenses/>.
*/

$_tests_dir = getenv('WP_TESTS_DIR');
if ( !$_tests_dir ) $_tests_dir = '/tmp/wordpress-tests-lib';

define( 'FS_METHOD', 'direct' );

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../../gravityforms/gravityforms.php';	

	GFForms::setup(true);	
	require dirname( __FILE__ ) . '/../../gravityformssurvey/survey.php';	
	require dirname( __FILE__ ) . '/../../gravityformsquiz/quiz.php';	
	require dirname( __FILE__ ) . '/../../gravityformspolls/polls.php';	
	require dirname( __FILE__ ) . '/../pdf.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

