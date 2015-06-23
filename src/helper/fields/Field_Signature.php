<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Fields;
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
class Field_Signature extends Helper_Fields
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
    }

    /**
     * Display the HTML version of this field
     * @return String
     * @since 4.0
     */
    public function html() {
        $value = $this->value();

        return parent::html($value['img']);
    }

    /**
     * Get the standard GF value of this field
     * @return String/Array
     * @since 4.0
     */
    public function value() {
        if($this->has_cache()) {
            return $this->cache();
        }

        /* Get our signature details */
        $signature_name        = $this->get_value();
        $signature_upload_url  = GFFormsModel::get_upload_url_root() . 'signatures/';
        $signature_upload_path = GFFormsModel::get_upload_root() . 'signatures/';
        $signature             = $signature_upload_path . $signature_name;

        /* Get some sane signature defaults */
        $width  = 75;
        $height = 45;
        $html   = '<img src="' . $signature . '" alt="Signature" width="' . $width . '" />';

        /* If we can load in the signature let's optimise the signature size for PDF display */
        if(is_file($signature)) {
            $signature_details = getimagesize($signature);
            $optimised_width   = $signature_details[0] / 3;
            $optimised_height  = $signature_details[1] / 3;
            $html              = str_replace('width="' . $width . '"', 'width="' . $optimised_width . '"', $html);
            
            /* override the default width */
            $width             = $optimised_width;
            $height            = $optimised_height;
        }

        /*
         * Build our signature array
         */
        $value = array(
            'img'    => $html,
            'path'   => $signature,
            'url'    => $signature_upload_url . $signature_name,
            'width'  => $width,
            'height' => $height,
        );

        $this->cache($value);
        
        return $this->cache();
    }
}