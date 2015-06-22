<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Fields;
use GFCommon;

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

/* Include any dependancies */
require_once( GFCommon::get_base_path() . '/currency.php' );

/**
 * Controls the display and output of a Gravity Form field
 *
 * @since 4.0
 */
class Field_Products extends Helper_Fields
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
        $products = $this->value();
        $form_id  = $this->form['id'];

        /* start output buffer */
        ob_start();

        $include_styles = apply_filters('gfpdf_include_product_table_styles', true);

        ?>
        <?php if($include_styles): ?>
            <style>
                table.entry-products th {
                    background-color: #F4F4F4;
                    border-bottom: 1px solid #DFDFDF;
                    border-right: 1px solid #DFDFDF !important;
                }
                table.entry-products td.textcenter, table.entry-products th.textcenter {
                    text-align: center;
                }
                table.entry-products col.entry-products-col2 {
                    width: 50px;
                }
                table.entry-products col.entry-products-col3 {
                    width: 155px;
                }
                table.entry-products col.entry-products-col4 {
                    width: 155px;
                }
                table.entry-products {
                    border: 1px solid #DFDFDF;
                    margin: 10px 0;
                }
                table.entry-products td {
                    border-bottom: 1px solid #DFDFDF;
                    border-right: 1px solid #DFDFDF !important;
                    padding: 7px 7px 8px;
                    vertical-align: top;
                }
                table.entry-products td.emptycell {
                    background-color: #F4F4F4;
                }
                table.entry-products td.totals {
                    font-size: 13px;
                    font-weight: bold;
                    padding-bottom: 8px;
                    padding-top: 7px;
                }
                table.entry-products td.textright, table.entry-products th.textright {
                    text-align: right;
                }
            </style>
        <?php endif; ?>

        <h3 id="product-field-title">
            <?php
                echo apply_filters("gform_order_label_{$form_id}",
                     apply_filters('gform_order_label', __('Order', 'gravityforms'),
                     $form_id),
                     $form_id);
            ?>
        </h3>

        <table class="entry-products" autosize="1" cellspacing="0" width="97%">
            <colgroup>
                  <col class="entry-products-col1" />
                  <col class="entry-products-col2" />
                  <col class="entry-products-col3" />
                  <col class="entry-products-col4" />
            </colgroup>

            <thead>
              <tr>
                <th scope="col">
                    <?php
                        echo apply_filters('gform_product_{$form_id}',
                             apply_filters('gform_product', __('Product', 'gravityforms'),
                             $form_id),
                             $form_id);
                    ?>
                </th>

                <th scope="col" class='textcenter'>
                    <?php
                        echo apply_filters('gform_product_qty_{$form_id}',
                             apply_filters('gform_product_qty', __('Qty', 'gravityforms'),
                             $form_id),
                             $form_id);
                    ?>
                </th>
                <th scope="col">
                    <?php
                        echo apply_filters('gform_product_unitprice_{$form_id}',
                             apply_filters('gform_product_unitprice', __('Unit Price', 'gravityforms'),
                             $form_id),
                             $form_id);
                    ?>
                </th>
                <th scope="col">
                    <?php
                        echo apply_filters('gform_product_price_{$form_id}',
                             apply_filters('gform_product_price', __('Price', 'gravityforms'),
                             $form_id),
                             $form_id);
                    ?>
                </th>
              </tr>
            </thead>

            <tbody>
                <?php foreach($products['products'] as $product): ?>
                        <tr>
                            <td>
                                <div class="product_name">
                                    <?php echo $product['name']; ?>
                                </div>

                                    <?php
                                    $price = $product['price_unformatted'];

                                    if(sizeof($product['options']) > 0): ?>
                                        <ul class="product_options">
                                            <?php foreach($product['options'] as $option): $price += $option['price']; ?>
                                                <li><?php echo $option['option_label']; ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                            </td>
                            <td class="textcenter"><?php echo $product['quantity']; ?></td>
                            <td><?php echo GFCommon::format_number($price, 'currency'); ?></td>
                            <td><?php echo $product['subtotal_formatted'] ?></td>
                        </tr>
                <?php endforeach; ?>

                <?php if(!empty($products['products_totals']['shipping_name'])): ?>
                    <tr>
                        <td rowspan="3" class="emptycell"></td>
                        <td colspan="2" class="textright subtotal totals"><?php _e('Subtotal', 'gravitypdf'); ?></td>
                        <td class="subtotal_amount totals"><?php echo $products['products_totals']['subtotal_formatted']; ?></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="textright shipping totals"><?php echo sprintf(__('Shipping (%s)', 'gravitypdf'), $products['products_totals']['shipping_name']); ?></td>
                        <td class="shipping_amount totals"><?php echo $products['products_totals']['shipping_formatted']; ?></td>
                    </tr>
                <?php endif; ?>

                <tr>
                    <?php if(empty($products['products_totals']['shipping_name'])): ?>
                        <td class="emptycell"></td>
                    <?php endif; ?>

                    <td colspan="2" class="textright grandtotal totals"><?php _e('Total', 'gravityforms') ?></td>
                    <td class="grandtotal_amount totals"><?php echo $products['products_totals']['total_formatted']; ?></td>
                </tr>
            </tbody>
        </table>

        <?php

        return ob_get_clean();
    }

    /**
     * Gravity Forms handles product fields in bulk as they are all linked together to get the order totals
     * This class is used to handle this in bulk
     * @return String/Array
     * @since 4.0
     */
    public function value() {

        /* check if we have a value already stored in the cache */
        if($this->has_cache()) {
            return $this->cache();
        }

        /* Set up the form / lead information */
        $form            = $this->form;
        $lead            = $this->entry;
        
        /* Set up the curreny format we should output products totals in */
        $currency_type   = GFCommon::is_currency_decimal_dot();
        $currency_format = $currency_type ? 'decimal_dot' : 'decimal_comma';
        
        /* Get all products for this field */
        $products        = GFCommon::get_product_fields($form, $lead, true);
        
        /* Set up the appropriate varaibles needed for our product processing */
        $form_array      = array(); /* holds the actual product data */
        $order_total     = 0; /* holds the total cost of the order */

        /* check that there are actual product fields to process */
        if(sizeof($products['products']) > 0 ) {

            foreach($products['products'] as $id => $product) {

                /* Get the raw pricing data */
                $product_raw_price = GFCommon::to_number($product['price']);

                /* Check if we should include options */
                $options = isset($product['options']) ? $product['options'] : array();

                /* Process our options array */
                foreach($options as &$option) {
                    /* Get the options raw price */
                    $option_raw_price          = GFCommon::to_number($option['price']);

                    /* Add the options price to the products price */
                    $product_raw_price         += $option_raw_price;

                    /* add our formatted options price to the array */
                    $option['price_formatted'] = GFCommon::format_number($option_raw_price, 'currency');

                    /* Format our option strings correctly */
                    $option['field_label']  = esc_html($option['field_label']);
                    $option['option_name']  = esc_html($option['option_name']);
                    $option['option_label'] = esc_html($option['option_label']);
                }

                /* calculate subtotal */
                $product_subtotal = floatval($product['quantity']) * $product_raw_price;

                /* increment the total */
                $order_total += $product_subtotal;

                /* Store product in $form_array array */
                $form_array['products'][$id] = array(
                        'name'               => esc_html($product['name']),
                        'price'              => GFCommon::format_number(GFCommon::clean_number($product['price'], $currency_format), 'currency'),
                        'price_unformatted'  => GFCommon::clean_number($product['price'], $currency_format),
                        'options'            => $options,
                        'quantity'           => $product['quantity'],
                        'subtotal'           => $product_subtotal,
                        'subtotal_formatted' => GFCommon::format_number($product_subtotal, 'currency'));
            }

            /* Increment total */
            $shipping_price = (isset($products['shipping']['price'])) ? floatval($products['shipping']['price']) : 0;
            $order_total    += $shipping_price;
            $order_subtotal = $order_total - $shipping_price;

            /* add totals to form data */
            $form_array['products_totals'] = array(
                    'subtotal'           => $order_subtotal,
                    'subtotal_formatted' => GFCommon::format_number($order_subtotal, 'currency'),
                    'shipping'           => $shipping_price,
                    'shipping_formatted' => GFCommon::format_number($shipping_price, 'currency'),
                    'shipping_name'      => (isset($products['shipping']['name'])) ? preg_replace('/(.+?) \((.+?)\)/', '$2', $products['shipping']['name']) : '',
                    'total'              => $order_total,
                    'total_formatted'    => GFCommon::format_number($order_total, 'currency'),

            );
        }

        /* Save the array into the cache */
        $this->cache($form_array);
        
        /* return the cache results */
        return $this->cache();
    }
}