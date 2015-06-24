<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Fields;
use GFPDF\Stat\Stat_Functions;
use GFFormsModel;
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
class Field_Survey extends Helper_Fields
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
         * Survey Field can be any of the following:
         * single line text, paragraph, dropdown, select, checkbox,
         * likert or rating
         */
        $class = Stat_functions::get_field_class($field->inputType);

        try {
            /* check load our class */
            if(class_exists($class)) {
                $this->fieldObject = new $class($field, $entry);
            } else {
                throw new Exception();
            }
        } catch(Exception $e) {
            /* Exception thrown. Load generic field loader */
            $this->fieldObject = new Field_Default($field, $entry);
        }

        /* force the fieldObject value cache */
        $this->value();
    }

    /**
     * Used to check if the current field has a value
     * @since 4.0
     * @internal Child classes can override this method when dealing with a specific use case
     */
    public function is_empty() {
        return $this->fieldObject->is_empty();
    }

    /**
     * Display the HTML version of this field
     * @return String
     * @since 4.0
     */
    public function html() {
        return parent::html($this->fieldObject->html(), false);
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

        $value = $this->fieldObject->value();

        $this->fieldObject->cache($value);
        
        return $this->fieldObject->cache();
    }
}