<?php

namespace GFPDF\Helper\Fields;

use GFCommon;
use GFPDF\Helper\Helper_Abstract_Fields;
use GFPDF\Statics\Kses;
use GP_Ecommerce_Fields;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Include any dependencies */
require_once GFCommon::get_base_path() . '/currency.php';

/**
 * Controls the display and output of a Gravity Form field
 *
 * @since 4.0
 */
class Field_Products extends Helper_Abstract_Fields {

	/**
	 * Checks if the form has any products
	 *
	 * @return boolean
	 *
	 * @since 4.0.2
	 */
	public function is_empty() {

		$form  = $this->form;
		$entry = $this->entry;

		/* Get all products for this field */
		$use_value       = (bool) apply_filters( 'gfpdf_show_field_value', false, $this->field, '' ); /* Set to `true` to show a field's value instead of the label */
		$use_admin_label = (bool) apply_filters( 'gfpdf_use_admin_label', false, $this->field, '' ); /* Set to `true` to use the admin label */

		$products = GFCommon::get_product_fields( $form, $entry, ! $use_value, $use_admin_label );

		if ( count( $products['products'] ) > 0 ) {
			return false; /* not empty */
		}

		return true;
	}

	/**
	 * Return the HTML form data
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function form_data() {
		return $this->value();
	}

	/**
	 * Display the HTML version of this field
	 *
	 * @param string $value
	 * @param bool   $label
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function html( $value = '', $label = true ) {
		$products         = $this->value();
		$form_id          = $this->form['id'];
		$unordered_fields = $this->form['fields'];
		$fields           = [];

		foreach ( $unordered_fields as $field ) {
			$fields[ $field->id ] = $field;
		}

		/* start output buffer */
		ob_start();

		?>

		<div class="row-separator products-title-container">
			<h3 class="product-field-title gfpdf-field">
				<?php
				$label = apply_filters( 'gform_order_label', __( 'Order', 'gravityforms' ), $form_id );
				$label = apply_filters( 'gform_order_label_' . $form_id, $label, $form_id );

				echo esc_html( $label );
				?>
			</h3>
		</div>

		<div class="row-separator products-container">
			<div class="gfpdf-field gfpdf-products">
				<div class="inner-container">
					<table class="entry-products" autosize="1">
						<tbody class="head">
						<tr>
							<th class="entry-products-col1">
								<?php
								$label = apply_filters( 'gform_product', __( 'Product', 'gravityforms' ), $form_id );
								$label = apply_filters( 'gform_product_' . $form_id, $label, $form_id );

								echo esc_html( $label );
								?>
							</th>

							<th class="textcenter entry-products-col2">
								<?php
								$label = apply_filters( 'gform_product_qty', __( 'Qty', 'gravityforms' ), $form_id );
								$label = apply_filters( 'gform_product_qty_' . $form_id, $label, $form_id );

								echo esc_html( $label );
								?>
							</th>
							<th class="entry-products-col3">
								<?php
								$label = apply_filters( 'gform_product_unitprice', __( 'Unit Price', 'gravityforms' ), $form_id );
								$label = apply_filters( 'gform_product_unitprice_' . $form_id, $label, $form_id );

								echo esc_html( $label );
								?>
							</th>
							<th class="entry-products-col4">
								<?php
								$label = apply_filters( 'gform_product_price', __( 'Price', 'gravityforms' ), $form_id );
								$label = apply_filters( 'gform_product_price_' . $form_id, $label, $form_id );

								echo esc_html( $label );
								?>
							</th>
						</tr>
						</tbody>

						<tbody class="contents">
						<?php
						foreach ( $products['products'] as $field_id => $product ):
							$field_id = explode( '|', $field_id )[0];

							/* Skip over Gravity Perks Ecommerce Fields */
							if ( class_exists( 'GP_Ecommerce_Fields' ) && in_array( $fields[ $field_id ]->type, [ 'tax', 'discount', 'coupon' ], true ) ) {
								continue;
							}
							?>
							<tr>
								<td>
									<div class="product_name">
										<?php Kses::output( wp_specialchars_decode( $product['name'], ENT_QUOTES ) ); ?>
									</div>

									<?php
									$price = $product['price_unformatted'];

									if ( count( $product['options'] ) > 0 ) :
										?>
										<ul class="product_options">
											<?php
											foreach ( $product['options'] as $option ) :
												$price += $option['price'];
												?>
												<li><?php Kses::output( wp_specialchars_decode( $option['option_label'], ENT_QUOTES ) ); ?></li>
											<?php endforeach; ?>
										</ul>
									<?php endif; ?>
								</td>
								<td class="textcenter"><?php echo esc_html( $product['quantity'] ); ?></td>
								<td class="textright"><?php echo esc_html( GFCommon::format_number( $price, 'currency', rgar( $this->entry, 'currency' ) ) ); ?></td>
								<td class="textright"><?php echo esc_html( $product['subtotal_formatted'] ); ?></td>
							</tr>
						<?php endforeach; ?>

						<?php
						if ( class_exists( 'GP_Ecommerce_Fields' ) ):
							$gpecommerce     = GP_Ecommerce_Fields::get_instance( null );
							$use_value       = (bool) apply_filters( 'gfpdf_show_field_value', false, $this->field, '' ); /* Set to `true` to show a field's value instead of the label */
							$use_admin_label = (bool) apply_filters( 'gfpdf_use_admin_label', false, $this->field, '' ); /* Set to `true` to use the admin label */
							$order           = GFCommon::get_product_fields( $this->form, $this->entry, ! $use_value, $use_admin_label );
							$order_summary   = $gpecommerce->get_order_summary( $order, $this->form, $this->entry );
							?>
							<?php foreach ( $order_summary as $index => $group ): ?>
								<?php
								foreach ( $group as $item ):
									$class = rgar( $item, 'class' ) ? '.' . rgar( $item, 'class' ) : '';
									?>
								<tr style="<?php $gpecommerce->style( '.order-summary/tfoot/tr' . $class ); ?>">
									<?php if ( $index === 0 ): ?>
										<td class="emptycell"
											colspan="2"
											rowspan="<?php echo esc_attr( $gpecommerce->get_order_summary_item_count( $order_summary ) ); ?>"></td>
									<?php endif; ?>
									<td class="totals" style="<?php esc_attr( $gpecommerce->style( ".order-summary/tfoot/{$class}/td.column-3" ) ); ?>">
										<?php Kses::output( wp_specialchars_decode( $item['name'], ENT_QUOTES ) ); ?>
									</td>

									<td class="totals" style="<?php esc_attr( $gpecommerce->style( ".order-summary/tfoot/{$class}/td.column-4" ) ); ?>">
										<?php echo esc_html( GFCommon::to_money( $item['price'], $this->entry['currency'] ) ); ?>
									</td>
								</tr>
								<?php endforeach; ?>
						<?php endforeach; ?>
						<?php else : ?>
							<?php if ( ! empty( $products['products_totals']['shipping_name'] ) ) : ?>
								<tr>
									<td rowspan="3" class="emptycell"></td>
									<td colspan="2"
										class="subtotal totals"><?php esc_html_e( 'Subtotal', 'gravity-pdf' ); ?></td>
									<td class="subtotal_amount totals"><?php echo esc_html( $products['products_totals']['subtotal_formatted'] ); ?></td>
								</tr>
								<tr>
									<td colspan="2"
										class="shipping totals"><?php Kses::output( sprintf( __( 'Shipping (%s)', 'gravity-pdf' ), wp_specialchars_decode( $products['products_totals']['shipping_name'], ENT_QUOTES ) ) ); ?></td>
									<td class="shipping_amount totals"><?php echo esc_html( $products['products_totals']['shipping_formatted'] ); ?></td>
								</tr>
							<?php endif; ?>

							<tr>
								<?php if ( empty( $products['products_totals']['shipping_name'] ) ) : ?>
									<td class="emptycell"></td>
								<?php endif; ?>

								<td colspan="2"
									class="grandtotal totals"><?php esc_html_e( 'Total', 'gravityforms' ); ?></td>
								<td class="grandtotal_amount totals"><?php echo esc_html( $products['products_totals']['total_formatted'] ); ?></td>
							</tr>

						<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<?php

		$html = apply_filters( 'gfpdf_field_product_value', ob_get_clean(), $products, $this->field, $this->form, $this->entry, $this );

		if ( $this->get_output() ) {
			Kses::output( $html );
		}

		return $html;
	}

	/**
	 * Gravity Forms handles product fields in bulk as they are all linked together to get the order totals
	 * This class is used to handle this in bulk
	 *
	 * @return string|array
	 *
	 * @since 4.0
	 */
	public function value() {

		/* check if we have a value already stored in the cache */
		if ( $this->has_cache() ) {
			return $this->cache();
		}

		/* Set up the form / lead information */
		$form  = $this->form;
		$entry = $this->entry;

		/* Get all products for this field */
		$use_value       = (bool) apply_filters( 'gfpdf_show_field_value', false, $this->field, '' ); /* Set to `true` to show a field's value instead of the label */
		$use_admin_label = (bool) apply_filters( 'gfpdf_use_admin_label', false, $this->field, '' ); /* Set to `true` to use the admin label */
		$products        = GFCommon::get_product_fields( $form, $entry, ! $use_value, $use_admin_label );

		/* Set up the appropriate variables needed for our product processing */
		$form_array  = []; /* holds the actual product data */
		$order_total = 0; /* holds the total cost of the order */

		/* check that there are actual product fields to process */
		if ( count( $products['products'] ) === 0 ) {
			return $form_array;
		}

		foreach ( $products['products'] as $id => $product ) {

			if ( class_exists( 'GP_Ecommerce_Fields' ) && empty( $product['name'] ) ) {
				continue;
			}

			/* Get the raw pricing data */
			$product_raw_price  = GFCommon::to_number( $product['price'] );
			$product_unit_price = $product_raw_price;

			/* Check if we should include options */
			$options = isset( $product['options'] ) ? $product['options'] : [];

			/* Process our options array */
			foreach ( $options as &$option ) {
				/* Get the options raw price */
				$option_raw_price = GFCommon::to_number( $option['price'] );

				/* Add the options price to the products price */
				$product_unit_price += $option_raw_price;

				/* add our formatted options price to the array */
				$option['price_formatted'] = GFCommon::to_money( $option_raw_price, $entry['currency'] );

				/* Format our option strings correctly */
				$option['field_label']  = isset( $option['field_label'] ) ? wp_kses_post( $option['field_label'] ) : '';
				$option['option_name']  = isset( $option['option_name'] ) ? wp_kses_post( $option['option_name'] ) : '';
				$option['option_label'] = isset( $option['option_label'] ) ? wp_kses_post( $option['option_label'] ) : '';
			}

			unset( $option );

			/* calculate subtotal */
			$product_subtotal = ( (float) $product['quantity'] ) * $product_unit_price;

			/* increment the total */
			$order_total += $product_subtotal;

			/* Store product in $form_array array */
			$form_array['products'][ $id ] = [
				'id'                   => $id,
				'name'                 => wp_kses_post( $product['name'] ),
				'price'                => GFCommon::to_money( $product_raw_price, $entry['currency'] ),
				'price_unformatted'    => $product_raw_price,
				'unit_price'           => $product_unit_price,
				'unit_price_formatted' => GFCommon::to_money( $product_unit_price, $entry['currency'] ),
				'options'              => $options,
				'quantity'             => $product['quantity'],
				'subtotal'             => $product_subtotal,
				'subtotal_formatted'   => GFCommon::to_money( $product_subtotal, $entry['currency'] ),
			];
		}

		/* Increment total */
		$shipping_price = isset( $products['shipping']['price'] ) ? (float) $products['shipping']['price'] : 0;
		$order_total   += $shipping_price;
		$order_subtotal = $order_total - $shipping_price;

		/* add totals to form data */
		$form_array['products_totals'] = [
			'subtotal'           => $order_subtotal,
			'subtotal_formatted' => GFCommon::to_money( $order_subtotal, $entry['currency'] ),
			'shipping'           => $shipping_price,
			'shipping_formatted' => GFCommon::to_money( $shipping_price, $entry['currency'] ),
			'shipping_name'      => ( isset( $products['shipping']['name'] ) ) ? preg_replace( '/(.+?) \((.+?)\)/', '$2', $products['shipping']['name'] ) : '',
			'total'              => $order_total,
			'total_formatted'    => GFCommon::to_money( $order_total, $entry['currency'] ),
		];

		$form_array['products_totals'] = array_map( 'esc_html', $form_array['products_totals'] );

		$form_array = apply_filters( 'gfpdf_form_data_products', $form_array, $form, $entry, $this );

		/* Save the array into the cache */
		$this->cache( $form_array );

		/* return the cache results */

		return $this->cache();
	}
}
