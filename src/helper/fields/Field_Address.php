<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Fields;
use GFFormsModel;
use GF_Field_Address;
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
class Field_Address extends Helper_Fields
{

    /**
     * Check the appropriate variables are parsed in send to the parent construct
     * @param Object $field The GF_Field_* Object
     * @param Array $entry The Gravity Forms Entry
     * @since 4.0
     */
    public function __construct($field, $entry) {
        if(!is_object($field) || !($field instanceof GF_Field_Address)) {
            throw new Exception('$field needs to be in instance of GF_Field_Address');
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
        $data    = $this->value(); /* remove any empty fields from the array */
        $address = array();

        /* generate our HTML markup */
        $html = '<div id="field-'. $this->field->id .'" class="gfpdf-address">';

        /* check if we should display the zip before the city */
        $address_display_format = apply_filters('gform_address_display_format', 'default');

        /* Start putting our address together */
        $address[] = $data['street'];

        if(!empty($data['street2'])) {
            $address[] = $data['street2'];
        }

        /* display in the standard "city, state zip" format */
        if($address_display_format != 'zip_before_city') {
            $zip_string = $data['city'];
            $zip_string .= (!empty($zip_string) && !empty($data['state'])) ? ", {$data['state']}" : trim($data['state']);
            $zip_string .= " {$data['zip']}";

            if(!empty($zip_string)) {
                $address[] = trim($zip_string);
            }
        } else { /* display in the "zip, city state" format */
            $zip_string = trim($data['zip'] . ' ' . $data['city']);
            $zip_string .= (!empty($zip_string) && !empty($data['state'])) ? ", {$data['state']}" : trim($data['state']);

            if(!empty($zip_string)) {
                $address[] = trim($zip_string);
            }
        }

        /* add country to address, if present */
        if(!empty($data['country'])) {
            $address[] = $data['country'];
        }

        /* Apply sanitization to address */
        $address = array_map( 'esc_html', $address);

        /* display the address in the correct format */
        $html .= implode('<br />', $address);

        $html .= '</div>';

        /* return the results */
        return $html;
    }

    /**
     * Get the standard GF value of this field
     * @return Array
     * @since 4.0
     */
    public function value() {
        if($this->has_cache()) {
            return $this->cache();
        }

        $value = $this->get_value();

        /* check if the returned results are an array */
        if(! is_array($value)) {
            $value[$this->field['id'] . '.1'] = $value; /* set to the street value */
        }

        $this->cache(array(
            'street'  => trim(rgget($this->field['id'] . '.1', $value)),
            'street2' => trim(rgget($this->field['id'] . '.2', $value)),
            'city'    => trim(rgget($this->field['id'] . '.3', $value)),
            'state'   => trim(rgget($this->field['id'] . '.4', $value)),
            'zip'     => trim(rgget($this->field['id'] . '.5', $value)),
            'country' => trim(rgget($this->field['id'] . '.6', $value)),
        ));

        return $this->cache();
    }
}