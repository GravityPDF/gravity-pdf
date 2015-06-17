<?php

namespace GFPDF\Helper;

use Exception;
use GFFormsModel;

/**
 * Abstract Helper Fields
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

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
 * Helper fields can be extended to allow each Gravity Form field type to be displayed correctly
 * We found the default GF display functionality isn't quite up to par for the Gravity PDF requirements
 * @since 4.0
 */
abstract class Helper_Fields {
	
    /**
	 * Contains the field array
	 * @var Array
	 * @since 4.0
	 */
	public $field = array();

    /**
     * Contains the entry information
     * @var Array
     * @since 4.0
     */
    public $entry = array();

    /**
     * Set up the object
     * Check the $entry is an array, or throw exception
     * The $field is validated in the child classes
     * @param Object $field The GF_Field_* Object
     * @param Array $entry The Gravity Forms Entry
     * @since 4.0
     */
    public function __construct($field, $entry) {
        
        /* Throw error if $entry is not an array */
        if(!is_array($entry)) {
            throw new Exception('$entry needs to be an array');
        }

        $this->field = $field;
        $this->entry = $entry;
    }

    /**
     * Used to process the Gravity Forms value extracted from the entry array
     * Each value is then passed to the display_value method set up by the child objects
     * @since 4.0
     */
    final public function get_value() {
            /* Throw error if not dependacies not met */
            if(!class_exists('GFFormsModel')) {
                throw new Exception('Gravity Forms is not correctly loaded.');
            }

            /* Throw error if not an array */
            if(!is_array($this->entry)) {
                return new WP_Error('invalid_entry');
            }

            /* get the GF Value */
            return GFFormsModel::get_lead_field_value($this->entry, $this->field);
    }

    /**
     * Used to process the Gravity Forms value extracted from the entry
     * This is called from the 'value' method
     * @since 4.0
     */
    abstract public function value();

    /**
     * Get the default HTML output for this field
     * @since 4.0
     */
    abstract public function html();
}