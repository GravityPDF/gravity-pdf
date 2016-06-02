<?php

/**
 * Plugin: Gravity PDF
 * File: major-upgrade-checker.php
 *
 * Prevents Gravity PDF upgrade showing up if 4.0 requirements are not met
 */

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
 * Class GFPDF_Major_Upgrade_Checker
 *
 * @since 3.7.8
 */
class GFPDF_Major_Upgrade_Checker {

	/**
	 * WordPress actions to start the upgrade checker
	 *
	 * @since 3.7.8
	 */
	public function init() {
		add_action( 'site_transient_update_plugins', array( $this, 'maybe_prevent_major_upgrade') );
	}

	/**
	 * Check if the there is a major Gravity PDF update, see if the server is fully compatible
	 * and prevent Gravity PDF updates if requirements are not met
	 *
	 * This is a filter for 'site_transient_update_plugins'
	 *
	 * @since 3.7.8
	 *
	 * @param array $upgrades
	 *
	 * @return array
	 */
	public function maybe_prevent_major_upgrade( $upgrades ) {
		global $wp_version;

		if ( isset( $upgrades->response[ GF_PDF_EXTENDED_PLUGIN_BASENAME ] ) ) {
			$upgrade_version = $upgrades->response[ GF_PDF_EXTENDED_PLUGIN_BASENAME ]->new_version;

			/* Check if it is a major release  (i.e anything over version 3) and Gravity Forms is activated on the site */
			if ( 4 <= (int) $upgrade_version[0] && class_exists( 'GFCommon' ) ) {

				/* Add switch to prevent other checks if upgrade already removed */
				$prevented = false;

				/* Check the required PHP version */
				if ( ! version_compare( phpversion(), '5.4', '>=' ) ) {
					unset( $upgrades->response[ GF_PDF_EXTENDED_PLUGIN_BASENAME ] );
					$prevented = true;
				}

				/* Check the required WP version */
				if ( ! $prevented && ! version_compare( $wp_version, '4.2', '>=' ) ) {
					unset( $upgrades->response[ GF_PDF_EXTENDED_PLUGIN_BASENAME ] );
					$prevented = true;
				}

				/* Check the required Gravity Forms version */
				if ( ! $prevented && ! version_compare( GFCommon::$version, '1.9', '>=' ) ) {
					unset( $upgrades->response[ GF_PDF_EXTENDED_PLUGIN_BASENAME ] );
					$prevented = true;
				}

				/* Check if using our v3 Widgets and Shortcodes plugin */
				if ( ! $prevented && class_exists( 'GFPDFEWidgetsAndShortcode' ) ) {
					unset( $upgrades->response[ GF_PDF_EXTENDED_PLUGIN_BASENAME ] );
					$prevented = true;
				}

				/* Check if using our v3 Multi Report plugin */
				if ( ! $prevented && class_exists( 'gfpdfe_multi_reports' ) ) {
					unset( $upgrades->response[ GF_PDF_EXTENDED_PLUGIN_BASENAME ] );
					$prevented = true;
				}

				/* Check if using our v3 WooCommerce Integration */
				if ( ! $prevented && class_exists( 'GFPDFE_Woocommerce_Gravityforms_Notifications' ) ) {
					unset( $upgrades->response[ GF_PDF_EXTENDED_PLUGIN_BASENAME ] );
				}
			}
		}

		return $upgrades;
	}
}

$gfpdf_upgrade_checker = new GFPDF_Major_Upgrade_Checker();
$gfpdf_upgrade_checker->init();

