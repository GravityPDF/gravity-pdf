<?php

namespace GFPDF\Helper;

use GFPDF\Helper\Helper_Abstract_Form; /* not needed, but helps define usage */

use GFAPI;
use GFCommon;

/**
 * Gravity Forms Abstraction Method Class
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
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

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class to set up the settings api fields
 *
 * @since 4.0
 */
class Helper_Form extends Helper_Abstract_Form {

	/**
	 * Get the form plugins current version
	 * @return String
	 * @since 4.0
	 */
	public function get_version() {
		return GFCommon::$version;
	}

	/**
	 * Get form plugin's path
	 * @return String
	 * @since 4.0
	 */
	public function get_plugin_path() {
		return GFCommon::get_base_path();
	}

	/**
	 * Get form plugin's URL
	 * @return String
	 * @since 4.0
	 */
	public function get_plugin_url() {
		return GFCommon::get_base_url();
	}

	/**
	 * Get form plugin's form array
	 * @param  Integer $form_id
	 * @return Mixed
	 * @since 4.0
	 */
	public function get_form( $form_id ) {
		return GFAPI::get_form( $form_id );
	}

	/**
	 * Get form plugin's current forms array
	 * @return Mixed
	 * @since 4.0
	 */
	public function get_forms() {
		return GFAPI::get_forms();
	}

	/**
	 * Get form plugin's form array
	 * @param  Array / Object $form The form object to be updated
	 * @return Mixed
	 * @since 4.0
	 */
	public function update_form( $form ) {
		return GFAPI::update_form( $form );
	}

	/**
	 * Get the entry based on the ID
	 * @param  Integer $entry_id
	 * @return Mixed
	 * @since 4.0
	 */
	public function get_entry( $entry_id ) {
		return GFAPI::get_entry( $entry_id );
	}

	/**
	 * Get multiple entries from multiple forms based on search criteria
	 * @param  Integer | Array $form_ids    The ID's of the form or an array of ideas.
	 * @param  Array           $search_criteria      An array containing the search criteria
	 * @param  Array           $sorting               An array containing the sort criteria
	 * @param  Array           $paging                Use to limit the number of entries returned
	 * @return Mixed
	 * @since 4.0
	 */
	public function get_entries( $form_ids, $search_criteria = array(), $sorting = null, $paging = null ) {
		return GFAPI::get_entries( $form_ids, $search_criteria, $sorting, $paging );
	}

	/**
	 * Update the current entry object
	 * @param  Object $entry The entry to be updated
	 * @return Mixed
	 * @since 4.0
	 */
	public function update_entry( $entry ) {
		return GFAPI::update_entry( $entry );
	}

	/**
	 * Get all custom form plugin capabilities added to WordPress, if any
	 * @return Array
	 * @since 4.0
	 */
	public function get_capabilities() {
		return GFCommon::all_caps();
	}

	/**
	 * Check if the user has the capability passed
	 * @param  String | Array $capability
	 * @param  Integer        $user_id
	 * @return Boolean            True if successful, false if failed
	 * @since 4.0
	 */
	public function has_capability( $capability, $user_id = null ) {

		/* Override current user */
		if ( $user_id !== null ) {
			$current_user = wp_get_current_user();
			wp_set_current_user( $user_id );
		}

		$has_capability = GFCommon::current_user_can_any( $capability );

		/* Restore current user */
		if ( $user_id !== null ) {
			wp_set_current_user( $current_user );
		}

		return $has_capability;
	}
}
