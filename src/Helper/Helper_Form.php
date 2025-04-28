<?php

namespace GFPDF\Helper;

use GFAPI;
use GFCommon;
use GFFormsModel;
use WP_Error;

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
 * Class to set up the settings api fields
 *
 * @since 4.0
 */
class Helper_Form extends Helper_Abstract_Form {

	/**
	 * Get the form plugins current version
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function get_version() {
		return GFCommon::$version;
	}

	/**
	 * Get form plugin's path
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function get_plugin_path() {
		return GFCommon::get_base_path();
	}

	/**
	 * Get form plugin's URL
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function get_plugin_url() {
		return GFCommon::get_base_url();
	}

	/**
	 * Adds a new form and returns the newly-added form ID
	 *
	 * @param array $form The form object to add
	 *
	 * @return integer|object The ID if successful, or a WP_Error
	 * @since 4.0
	 *
	 */
	public function add_form( $form ) {
		return GFAPI::add_form( $form );
	}

	/**
	 * Deletes a form by ID
	 *
	 * @param integer $form_id The form ID to remove
	 *
	 * @return boolean|WP_Error
	 * @since 4.0
	 *
	 */
	public function delete_form( $form_id ) {
		return GFAPI::delete_form( $form_id );
	}

	/**
	 * Get form plugin's form array
	 * The GFAPI has a performance problem when using GFAPI::get_form() and makes a database call each time to get the `is_active`, `date_created`, or `is_trash` data.
	 * We're bypassing the API to prevent that problem but the array doesn't include the aforementioned fields. Once the issue is resolved we'll look at switching back.
	 *
	 * @param integer $form_id
	 *
	 * @return mixed
	 *
	 * @since 4.0
	 */
	public function get_form( $form_id ) {
		return GFFormsModel::get_form_meta( $form_id );
	}

	/**
	 * Get form plugin's current forms array
	 *
	 * @return mixed
	 *
	 * @since 4.0
	 */
	public function get_forms() {

		$form_ids = GFFormsModel::get_form_ids( true, false );
		if ( empty( $form_ids ) ) {
			return [];
		}

		$forms = [];
		foreach ( $form_ids as $form_id ) {
			$forms[] = GFFormsModel::get_form_meta( $form_id );
		}

		return $forms;
	}

	/**
	 * Get form plugin's form array
	 *
	 * @param array|object $form The form object to be updated
	 *
	 * @return mixed
	 *
	 * @since 4.0
	 */
	public function update_form( $form ) {

		/**
		 * Because of the performance issues mentioned in the `get_form` method the `is_active`, `date_created` and `is_trash` keys are missing from `$form`
		 * We'll add them back in right before the update to prevent any issues with the form status
		 */
		$form_info            = GFFormsModel::get_form( $form['id'], true );
		$form['is_active']    = $form_info->is_active;
		$form['date_created'] = $form_info->date_created;
		$form['is_trash']     = $form_info->is_trash;

		return GFAPI::update_form( $form );
	}

	/**
	 * Get the entry based on the ID
	 *
	 * @param integer $entry_id
	 *
	 * @return mixed
	 *
	 * @since 4.0
	 */
	public function get_entry( $entry_id ) {
		return GFAPI::get_entry( $entry_id );
	}

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
	public function get_entries( $form_ids, $search_criteria = [], $sorting = null, $paging = null ) {
		return GFAPI::get_entries( $form_ids, $search_criteria, $sorting, $paging );
	}

	/**
	 * Update the current entry object
	 *
	 * @param array|object $entry The entry to be updated
	 *
	 * @return mixed
	 *
	 * @since 4.0
	 */
	public function update_entry( $entry ) {
		return GFAPI::update_entry( $entry );
	}

	/**
	 * Get all custom form plugin capabilities added to WordPress, if any
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_capabilities() {
		return GFCommon::all_caps();
	}

	/**
	 * Check if the user has the capability passed
	 *
	 * @param string|array $capability
	 * @param integer|null $user_id
	 *
	 * @return boolean            True if successful, false if failed
	 *
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
			wp_set_current_user( $current_user->ID );
		}

		return $has_capability;
	}

	/**
	 * Replace all the Merge Tag data in the string
	 *
	 * @param string $text  The string to process
	 * @param array  $form  The Gravity Form array
	 * @param array  $entry The Gravity Form Entry Array
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function process_tags( $text, $form, $entry ) {

		$text = str_replace( '{all_fields}', '', $text );

		return trim( GFCommon::replace_variables( $text, $form, $entry, false, false, false ) );
	}
}
