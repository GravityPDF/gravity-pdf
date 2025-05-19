<?php

namespace GFPDF\Helper;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	 * @param integer $form_id
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
	 * @param array|object $form The form object to be updated
	 *
	 * @return mixed
	 *
	 * @since 4.0
	 */
	abstract public function update_form( $form );

	/**
	 * Get the entry based on the ID
	 *
	 * @param integer $entry_id
	 *
	 * @return mixed
	 *
	 * @since 4.0
	 */
	abstract public function get_entry( $entry_id );

	/**
	 * Get multiple entries from multiple forms based on search criteria
	 *
	 * @param integer|array $form_ids        The ID's of the form or an array of ideas.
	 * @param array         $search_criteria An array containing the search criteria
	 * @param array|null    $sorting         An array containing the sort criteria
	 * @param array|null    $paging          Use to limit the number of entries returned
	 *
	 * @return mixed
	 *
	 * @since 4.0
	 */
	abstract public function get_entries( $form_ids, $search_criteria = [], $sorting = null, $paging = null );

	/**
	 * Update the current entry object
	 *
	 * @param object $entry The entry to be updated
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
	 * @param string       $capability
	 * @param integer|null $user_id
	 *
	 * @return boolean            True if successful, false if failed
	 *
	 * @since 4.0
	 */
	abstract public function has_capability( $capability, $user_id = null );

	/**
	 * Replace all the tag fields (that represent the field data) in the string
	 *
	 * @param string $text  The string to process
	 * @param array  $form  The Gravity Form array
	 * @param array  $entry The Gravity Form Entry Array
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	abstract public function process_tags( $text, $form, $entry );
}
