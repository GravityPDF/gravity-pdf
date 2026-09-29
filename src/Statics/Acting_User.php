<?php

namespace GFPDF\Statics;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Run code as another WordPress user
 *
 * @since 7.0
 */
class Acting_User {

	/**
	 * Run a callback as the given user, then switch back to the current user, even if the callback throws
	 *
	 * @param int      $user_id  The user to act as (0 for a logged-out visitor)
	 * @param callable $callback The code to run as that user
	 *
	 * @return mixed What `$callback` returns
	 *
	 * @since 7.0
	 */
	public static function run( $user_id, callable $callback ) {
		$previous_user_id = get_current_user_id();
		wp_set_current_user( (int) $user_id );

		try {
			return $callback();
		} finally {
			wp_set_current_user( $previous_user_id );
		}
	}
}
