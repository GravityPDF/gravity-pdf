<?php

namespace GFPDF\Helper;

use GFFormsModel;
use GF_Field;
use WP_Error;
use Exception;

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
	 * @var Array / Object
	 * @since 4.0
	 */
	public $field;

    /**
     * Contains the form information
     * @var Array
     * @since 4.0
     */
    public $form;

    /**
     * Contains the entry information
     * @var Array
     * @since 4.0
     */
    public $entry;

    /**
     * Used to cache the $this->value() results
     * @var Array
     * @since 4.0
     */
    private $cached_results;

    /**
     * As come fields can have multiple field types we'll use $fieldObject to store the object
     * @var Object
     * @since 4.0
     */
    public $fieldObject;

    /**
     * Set up the object
     * Check the $entry is an array, or throw exception
     * The $field is validated in the child classes
     * @param Object $field The GF_Field_* Object
     * @param Array $entry The Gravity Forms Entry
     * @since 4.0
     */
    public function __construct($field, $entry) {
        /* Throw error if not dependacies not met */
        if(!class_exists('GFFormsModel')) {
            throw new Exception('Gravity Forms is not correctly loaded.');
        }

        if(!is_object($field) || ! ($field instanceof GF_Field)) {
            throw new Exception('$field needs to be in instance of GF_Field');
        }

        /* Throw error if $entry is not an array */
        if(!is_array($entry)) {
            throw new Exception('$entry needs to be an array');
        }

        $this->field = $field;
        $this->entry = $entry;
        $this->form  = GFFormsModel::get_form_meta( $entry['id'] );
    }

    /**
     * Control the getting and setting of the cache
     * @param  Boolean / String / Array $value is passed in it will set a new cache
     * @return Boolean / String / Array The current cached_results
     * @since 4.0
     */
    final public function cache($value = null) {
        if(!is_null($value)) {
            $this->cached_results = $value;
        }

        return $this->cached_results;
    }

    /**
     * Check if we currently have a cach
     * @return Boolean True is we have a cache and false if we do not
     * @since 4.0
     */
    final public function has_cache() {
        if(! is_null($this->cached_results)) {
            return true;
        }
        return false;
    }

    /**
     * Reset the cache
     * @since 4.0
     */
    final public function remove_cache() {
        $this->cached_results = null;
    }

    /**
     * Used to process the Gravity Forms value extracted from the entry array
     * Each value is then passed to the display_value method set up by the child objects
     * @since 4.0
     */
    final public function get_value() {
        /* get the GF Value */
        return apply_filters( 'gfpdf_field_content', GFFormsModel::get_lead_field_value($this->entry, $this->field), $this->field, $this->entry);
    }

    /**
     * Used to check if the current field has a value
     * @since 4.0
     * @internal Child classes can override this method when dealing with a specific use case
     */
    public function is_empty() {
        $value = $this->value();
        
        if(is_array($value) && sizeof(array_filter($value) > 0)) { /* check for an array */
            return true;
        } else if(is_string($value) && strlen(trim($value)) > 0) { /* check for a string */
            return true;
        }

        return false;
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