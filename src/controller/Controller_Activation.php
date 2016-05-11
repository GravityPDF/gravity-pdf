<?php

/* Use the global namespace due to being called directly from the initial PHP file */

/**
 * Install Update Controller
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
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

    Gravity PDF Copyright (C) 2015 Blue Liquid Designs

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
	 * Run plugin activation functionality
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public static function activation() {
		/* Add Upgraded From Option */
		set_transient( '_gravitypdf_activation_redirect', true, 30 );
	}

	/**
	 * Run plugin deactivation functionality
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public static function deactivation() {

		$data = GPDFAPI::get_data_class();

		/**
		 * Remove our rewrite rules
		 * As deactivation hook fires much earlier than flush_rewrite_rules() can be called we'll manually remove our rules from the database
		 */
		$rules = get_option( 'rewrite_rules' );

		if ( false !== $rules && isset( $rules[ $data->permalink ] ) ) {
			unset( $rules[ $data->permalink ] );
			update_option( 'rewrite_rules', $rules );
		}

		/**
		 * Remove our scheduled tasks
		 */
		wp_clear_scheduled_hook( 'gfpdf_cleanup_tmp_dir' );
	}
}
