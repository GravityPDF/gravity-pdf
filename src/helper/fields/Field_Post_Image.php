<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Fields;

use GFFormsModel;
use GF_Field_Post_Image;

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
class Field_Post_Image extends Helper_Fields
{

    /**
     * Check the appropriate variables are parsed in send to the parent construct
     * @param Object $field The GF_Field_* Object
     * @param Array $entry The Gravity Forms Entry
     * @since 4.0
     */
    public function __construct($field, $entry) {
        if(!is_object($field) || !$field instanceof GF_Field_Post_Image) {
            throw new Exception('$field needs to be in instance of GF_Field_Post_Image');
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
        $value = $this->value();

        $path  = str_replace(home_url() . '/', ABSPATH, $value['url']);

        /* Start building image link */
        $html = '<a href="'. esc_url($value['url']) . '" target="_blank">';
        $html .= '<img width="150" src="' . esc_url($path) . '" />';

        /* Include title / caption / description if needed */
        if(!empty($value['title'])) {
            $html .= '<div class="gfpdf-post-image-title">' . esc_html($value['title']) . '</div>';
        }

        if(!empty($value['caption'])) {
            $html .= '<div class="gfpdf-post-image-caption">' . esc_html($value['caption']) . '</div>';
        }

        if(!empty($value['description'])) {
            $html .= '<div class="gfpdf-post-image-description">' . esc_html($value['description']) . '</div>';
        }

        $html .= '</a>';

        return parent::html($html);
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

        $value = $this->get_value();
        $img   = array();

        if(strlen($value) > 0) {
            $value = explode('|:|', $this->get_value());
            
            $img['url']         = (isset($value[0])) ? $value[0] : '';
            $img['title']       = (isset($value[1])) ? $value[1] : '';
            $img['caption']     = (isset($value[2])) ? $value[2] : '';
            $img['description'] = (isset($value[3])) ? $value[3] : '';
        }
        
        $this->cache($img);
        
        return $this->cache();
    }
}