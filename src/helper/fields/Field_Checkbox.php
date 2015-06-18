<?php

namespace GFPDF\Helper\Fields;
use GFPDF\Helper\Helper_Fields;
use GFFormsModel;
use GF_Field_Checkbox;
use GFCommon;
use Exception;

/**
 * Gravity Forms Field - Single Text Field
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
 * Field_Text
 * Controls the display and output of a Gravity Form field
 *
 * @since 4.0
 */
class Field_Checkbox extends Helper_Fields
{

    /**
     * Check the appropriate variables are parsed in send to the parent construct
     * @param Object $field The GF_Field_* Object
     * @param Array $entry The Gravity Forms Entry
     * @since 4.0
     */
    public function __construct($field, $entry) {
        if(!is_object($field) || !$field instanceof GF_Field_Checkbox) {
            throw new Exception('$field needs to be in instance of GF_Field_Checkbox');
        }

        /* call our parent method */
        parent::__construct($field, $entry);
    }

    /**
     * Display the HTML version of this field
     * @return String
     * @since 4.0
     */
    public function html() {

        $html = '<div id="field-'. $this->field->id .'" class="gfpdf-checkbox">';

        $items = $this->value();

        /* Generate our drop down list */
        if(sizeof($items) > 0) {
            $html .= '<ul class="bulleted">';
            $i    = 1;
            foreach($items as $value => $label) {
                $sanitized_value  = wp_specialchars(preg_replace('/\s+/', '', $value));
                $sanitized_option = wp_specialchars($label);

                $html .= '<li id="field-' . $this->field->id . '-option-' . $sanitized_value . '">' . $sanitized_option . '</li>';
                $i++;
            }

            $html .= '</ul>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Get the standard GF value of this field
     * @return String/Array
     * @since 4.0
     */
    public function value() {
        $value = $this->get_value();

        /* if not an array, make it so */
        if(!is_array($value)) {
            $value = array($value);
        }

        /* remove any unselected fields */
        $value = array_filter($value);

        /* loop through results and get checkbox 'labels' */
        $items = array();
        $i = 0;

        foreach($value as $key => $item) {
            $label = GFCommon::selection_display($item, $this->field, '', true);
            $value = GFCommon::selection_display($item, $this->field);
            $items[$value] = $label;
        }

        return $items;
    }
}