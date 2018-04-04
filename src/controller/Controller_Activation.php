<?php

/* Use the global namespace due to being called directly from the initial PHP file */

/**
 * Install Update Controller
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/*
 * This file is called before compatibility checks are run
 * We cannot add namespace support here which means no access
 * to the rest of the plugin
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2018, Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * Controller_Update
 * Basic class to set up activation and deactivation functionality
 *
 * @since 4.0
 */
class Controller_Activation {
	/**
	 * Run plugin deactivation functionality
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public static function deactivation() {

		/* Check if Gravity PDF successfully loaded before trying to run deactivation code */
		if ( ! class_exists( 'GPDFAPI' ) ) {
			return null;
		}

		/*
		 * Remove our rewrite rules
		 * As deactivation hook fires much earlier than flush_rewrite_rules() can be called we'll manually remove our rules from the database
		 */
		$data  = GPDFAPI::get_data_class();
		$rules = get_option( 'rewrite_rules' );

		if ( false !== $rules ) {
			global $wp_rewrite;

			/* Create two regex rules to account for users with "index.php" in the URL */
			$query = array(
				'^' . $data->permalink,
				'^' . $wp_rewrite->root . $data->permalink,
			);

			$match = false;
			foreach ( $query as $rule ) {
				if ( isset( $rules[ $rule ] ) ) {
					unset( $rules[ $rule ] );
					$match = true;
				}
			}

			if( $match ) {
				update_option( 'rewrite_rules', $rules );
			}
		}

		/* Remove our scheduled tasks */
		wp_clear_scheduled_hook( 'gfpdf_cleanup_tmp_dir' );
	}
}
