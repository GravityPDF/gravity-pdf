<?php

/*
 * This file is called before compatibility checks are run
 * We cannot add namespace support here which means no access
 * to the rest of the plugin
 */

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	 * @return void
	 * @since 4.0
	 *
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
			$query = [
				'^' . $data->permalink,
				'^' . $wp_rewrite->root . $data->permalink,
			];

			$match = false;
			foreach ( $query as $rule ) {
				if ( isset( $rules[ $rule ] ) ) {
					unset( $rules[ $rule ] );
					$match = true;
				}
			}

			if ( $match ) {
				update_option( 'rewrite_rules', $rules );
			}
		}

		/* Remove our scheduled tasks */
		wp_clear_scheduled_hook( 'gfpdf_cleanup_tmp_dir' );
	}
}
