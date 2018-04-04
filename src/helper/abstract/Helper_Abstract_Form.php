<?php

namespace GFPDF\Helper;

/**
 * Abstract Helper Form Class
 * We want to abstract as much of Gravity Forms functionality out of the main plugin
 * so it's more easier to integrate a new form plugin if needed in the future
 * Gravity Form-specific areas of code will have limited decoupling as it would need to be rewritten specifically for the replacement plugin (eg. /src/helper/fields/)
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
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
 * Abstract class to abstract some of the Gravity Forms functionality
 *
 * @since 4.0
 */
abstract class Helper_Abstract_Form {

	/**
	 * Get the form plugins current version
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	abstract public function get_version();

	/**
	 * Get form plugin's path
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	abstract public function get_plugin_path();

	/**
	 * Get form plugin's URL
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	abstract public function get_plugin_url();

	/**
	 * Get form plugin's form array
	 *
	 * @param  integer $form_id
	 *
	 * @return mixed
	 *
	 * @since 4.0
	 */
	abstract public function get_form( $form_id );

	/**
	 * Get form plugin's current forms array
	 *
	 * @return mixed
	 *
	 * @since 4.0
	 */
	abstract public function get_forms();

	/**
	 * Get form plugin's form array
	 *
	 * @param  array|object $form The form object to be updated
	 *
	 * @return mixed
	 *
	 * @since 4.0
	 */
	abstract public function update_form( $form );

	/**
	 * Get the entry based on the ID
	 *
	 * @param  integer $entry_id
	 *
	 * @return mixed
	 *
	 * @since 4.0
	 */
	abstract public function get_entry( $entry_id );

	/**
	 * Get multiple entries from multiple forms based on search criteria
	 *
	 * @param  integer|array $form_ids        The ID's of the form or an array of ideas.
	 * @param  array         $search_criteria An array containing the search criteria
	 * @param  array         $sorting         An array containing the sort criteria
	 * @param  array         $paging          Use to limit the number of entries returned
	 *
	 * @return mixed
	 *
	 * @since 4.0
	 */
	abstract public function get_entries( $form_ids, $search_criteria = [], $sorting = null, $paging = null );

	/**
	 * Update the current entry object
	 *
	 * @param  object $entry The entry to be updated
	 *
	 * @return mixed
	 *
	 * @since 4.0
	 */
	abstract public function update_entry( $entry );

	/**
	 * Get all custom form plugin capabilities added to WordPress, if any
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	abstract public function get_capabilities();

	/**
	 * Check if the user has the capability passed
	 *
	 * @param  string  $capability
	 * @param  integer $user_id
	 *
	 * @return boolean            True if successful, false if failed
	 *
	 * @since 4.0
	 */
	abstract public function has_capability( $capability, $user_id = null );

	/**
	 * Replace all the tag fields (that represent the field data) in the string
	 *
	 * @param  string $string The string to process
	 * @param  array  $form   The Gravity Form array
	 * @param  array  $entry  The Gravity Form Entry Array
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	abstract public function process_tags( $string, $form, $entry );
}
