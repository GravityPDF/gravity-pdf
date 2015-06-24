<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Fields;
use GFFormsModel;
use GF_Field_List;
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
class Field_List extends Helper_Fields
{

    /**
     * Check the appropriate variables are parsed in send to the parent construct
     * @param Object $field The GF_Field_* Object
     * @param Array $entry The Gravity Forms Entry
     * @since 4.0
     */
    public function __construct($field, $entry) {
        if(!is_object($field) || !$field instanceof GF_Field_List) {
            throw new Exception('$field needs to be in instance of GF_Field_List');
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

        /* exit early if list field is empty */
        if($this->is_empty()) {
            return parent::html('');
        }

        /* get out field value */
        $value   = $this->value();
        $columns = is_array($value[0]);

        $css = array();

        $css['table'] = array(
            'border-collapse: collapse',
            'border: 1px solid #DFDFDF',

            'margin: 2px 0 6px',
            'padding: 0',
            
            'width: 100%',
        );

        $css['th'] = array(
            'text-align:left',
            'color:#333',
            'background-color: #F1F1F1',

            'font-size: 12px',
            'font-weight: bold',
            
            'padding: 6px 10px',
            'background-image: none',
            'border: 1px solid #DFDFDF',
        );

        $css['td'] = array(
            'font-size:12px',
            'padding: 6px 10px',
            'border: 1px solid #DFDFDF'
        );

        /* add filter so users can modify the default list style */
        $css = apply_filters('gfpdf_field_list_css', $css);

        /* Start buffer and generate a list table */
        ob_start();
        ?>
        
        <table autosize="1" class="gfield_list" style="<?php echo implode(';', $css['table']); ?>">

            <!-- Loop through the column names and output in a header (if using the advanced list) -->
            <?php if($columns): $columns = array_keys($value[0]); ?>
                <thead>
                    <tr>
                        <?php foreach($columns as $column): ?>
                            <th style="<?php echo implode(';', $css['th']); ?>">
                                <?php echo esc_html($column); ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
            <?php endif; ?>

            <!-- Loop through each row -->
            <tbody>
                    <?php foreach($value as $item): ?>
                        <tr>
                            <!-- handle the basic list -->
                            <?php if(!$columns): ?>
                                <td style="<?php echo implode(';', $css['td']); ?>"><?php echo esc_html($item); ?></td>
                            <?php else: ?><!-- handle the advanced list -->
                                <?php foreach($columns as $column): ?>
                                    <td style="<?php echo implode(';', $css['td']); ?>">
                                        <?php echo esc_html(rgar($item, $column)); ?>
                                    </td>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
            </tbody>

        </table>
        
        <?php
        /* get buffer and return HTML */
        return parent::html(ob_get_clean());
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

        $value = maybe_unserialize($this->get_value());

        /* make sure value is an array */
        if(!is_array($value)) {
            $value = array($value);
        }

        $this->cache($value);
        
        return $this->cache();
    }
}