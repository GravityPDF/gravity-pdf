<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Fields;
use GFPDF\Stat\Stat_Functions;
use GFFormsModel;
use GFCommon;
use Exception;

/**
 * Gravity Forms Field
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if (! defined('ABSPATH')) {
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
 * Controls the display and output of a Gravity Form field
 *
 * @since 4.0
 */
class Field_Post_Category extends Helper_Fields
{

    /**
     * Check the appropriate variables are parsed in send to the parent construct
     * @param Object $field The GF_Field_* Object
     * @param Array $entry The Gravity Forms Entry
     * @since 4.0
     */
    public function __construct($field, $entry) {

        /* call our parent method */
        parent::__construct($field, $entry);

        /*
         * Category can be multiple field types
         * eg. Select, MultiSelect, Radio, Dropdown
         */
        $class = Stat_functions::get_field_class($field['inputType']);
       
        try {
            /* check load our class */
            $this->fieldObject = new $class($field, $entry);
        } catch(Exception $e) {
            /* Exception thrown. Load generic field loader */
            $this->fieldObject = new Field_Default($field, $entry);
        }
    }

    /**
     * Display the HTML version of this field
     * @return String
     * @since 4.0
     */
    public function html() {

        return '<div id="field-'. $this->field->id .'" class="gfpdf-post-category gfpdf-field '. $this->field->cssClass . '">'
                    . $this->fieldObject->html()
                . '</div>';
    }

    /**
     * Get the standard GF value of this field
     * @return String/Array
     * @since 4.0
     */
    public function value() {
        if($this->fieldObject->has_cache()) {
            return $this->fieldObject->cache();
        }

        /* get the value from the correct field object */
        $items = $this->fieldObject->value();

        /**
         * Standardise the $items format
         * The Radio / Select box will return a single-dimensional array,
         * while checkbox and multiselect will not.
         */
        if(!isset($items[0])) { /* convert single-dimensional array to multi-dimensional */
            $items = array($items);
        }

        /* Loop through standardised array and convert the label / value to their appropriate category */
        foreach($items as &$val) {

            /* Process the category label */
            if(isset($val['label'])) {
                $label        = GFCommon::prepare_post_category_value($val['label'], $this->field);
                $val['label'] = (is_array($label) && isset($label[0])) ? $label[0] : $label;
            }

            /* process the category value */
            if(isset($val['value'])) {
                $id           = GFCommon::prepare_post_category_value($val['value'], $this->field, 'conditional_logic');
                $val['value'] = (is_array($id) && isset($id[0])) ? $id[0] : $id;
            }
        }

        /**
         * Return in the appropriate format.
         * Select / Radio Buttons will not have a multidimensional array
         */
        if(!isset($items[1])) {
            $items = $items[0];
        }

        /* force the fieldObject cache to be set so it doesn't run their 'value' method directly */
        $this->fieldObject->cache($items);
        
        return $this->fieldObject->cache();
    }
}