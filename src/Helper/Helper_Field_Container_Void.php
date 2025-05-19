<?php

namespace GFPDF\Helper;

use GF_Field;

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
 * @since 4.0
 */
class Helper_Field_Container_Void extends Helper_Field_Container {

	/*
	 * Empty method easily disables Helper_Field_Container functionality
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function generate( GF_Field $field ) {
		/* Do nothing */
	}

	/**
	 * Empty method easily disables Helper_Field_Container functionality
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function close() {
		/* Do nothing */
	}

	/**
	 * Empty method easily disables Helper_Field_Container functionality
	 *
	 * @param GF_Field $field
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function does_fit_in_row( GF_Field $field ) {
		/* Do nothing */
	}

	/**
	 * Empty method easily disables Helper_Field_Container functionality
	 *
	 * @param GF_Field $field
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function maybe_display_faux_column( GF_Field $field ) {
		/* Do nothing */
	}
}
