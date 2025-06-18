<?php

namespace GFPDF\Helper\Fields;

use Exception;
use GF_Field_List;
use GFPDF\Helper\Helper_Abstract_Fields;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Misc;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controls the display and output of a Gravity Form field
 *
 * @since 4.0
 */
class Field_List extends Helper_Abstract_Fields {

	/**
	 * Check the appropriate variables are parsed in send to the parent construct
	 *
	 * @param object               $field The GF_Field_* Object
	 * @param array                $entry The Gravity Forms Entry
	 *
	 * @param Helper_Abstract_Form $gform
	 * @param Helper_Misc          $misc
	 *
	 * @throws Exception
	 *
	 * @since 4.0
	 */
	public function __construct( $field, $entry, Helper_Abstract_Form $gform, Helper_Misc $misc ) {

		if ( ! is_object( $field ) || ! $field instanceof GF_Field_List ) {
			throw new Exception( '$field needs to be in instance of GF_Field_List' );
		}

		/* call our parent method */
		parent::__construct( $field, $entry, $gform, $misc );
	}

	/**
	 * Return the HTML form data
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function form_data() {

		if ( $this->get_output() ) {
			$this->disable_output();
			$html = $this->html();
			$this->enable_output();
		} else {
			$html = $this->html();
		}

		$data  = [];
		$label = $this->get_label();

		/* Add our List array */
		$list_array = $this->value();
		$list_array = ( 0 < count( $list_array ) ) ? $list_array : '';

		$data['list'][ $this->field->id ] = $list_array;

		/* Add our List HTML */
		$data['field'][ $this->field->id . '.' . $label ] = $html;
		$data['field'][ $this->field->id ]                = $html;
		$data['field'][ $label ]                          = $html;

		return $data;
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

		/* exit early if list field is empty */
		if ( $this->is_empty() ) {
			return parent::html( '' );
		}

		/* get out field value */
		$value   = $this->value();
		$columns = is_array( $value[0] ?? '' );

		/* Start buffer and generate a list table */
		ob_start();
		?>

		<table autosize="1" class="gfield_list">

			<!-- Loop through the column names and output in a header (if using the advanced list) -->
			<?php
			if ( $columns ) :
				$columns = array_keys( $value[0] );
				?>
				<tbody class="head">
				<tr>
					<?php foreach ( $columns as $column ) : ?>
						<th>
							<?php echo esc_html( $column ); ?>
						</th>
					<?php endforeach; ?>
				</tr>
				</tbody>
			<?php endif; ?>

			<!-- Loop through each row -->
			<tbody class="contents">
			<?php foreach ( $value as $item ) : ?>
				<tr>
					<!-- handle the basic list -->
					<?php if ( ! $columns ) : ?>
						<td><?php echo esc_html( $item ); ?></td>
						<?php
					else :
						?>
						<!-- handle the advanced list -->
						<?php foreach ( $columns as $column ) : ?>
						<td>
							<?php echo esc_html( rgar( $item, $column ) ); ?>
						</td>
					<?php endforeach; ?>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
			</tbody>

		</table>

		<?php
		/* get buffer and return HTML */

		return parent::html( ob_get_clean() );
	}

	/**
	 * Get the standard GF value of this field
	 *
	 * @return string|array
	 *
	 * @since 4.0
	 */
	public function value() {
		if ( $this->has_cache() ) {
			return $this->cache();
		}

		$value = maybe_unserialize( $this->get_value() );

		/* make sure value is an array */
		if ( ! is_array( $value ) ) {
			$value = [ $value ];
		}

		/* Remove empty rows */
		$value = $this->remove_empty_list_rows( $value );

		$this->cache( $value );

		return $this->cache();
	}

	/**
	 * Remove empty list rows
	 *
	 * @param array $list_array The current list array
	 *
	 * @return array       The filtered list array
	 *
	 * @since 4.0
	 */
	protected function remove_empty_list_rows( $list_array ) {

		/* if list field empty return early */
		if ( ! is_array( $list_array ) || count( $list_array ) === 0 ) {
			return $list_array;
		}

		/* If single list field */
		if ( ! is_array( $list_array[0] ) ) {
			$list_array = array_filter( $list_array );
			$list_array = array_map( 'esc_html', $list_array );
		} else {

			/* Loop through the multi-column list */
			foreach ( $list_array as $id => &$row ) {

				$empty = true;

				foreach ( $row as &$col ) {

					/* Check if there is data and if so break the loop */
					if ( strlen( trim( $col ) ) > 0 ) {
						$col   = esc_html( $col );
						$empty = false;
					}
				}

				unset( $col );

				/* Remove row from list */
				if ( $empty ) {
					unset( $list_array[ $id ] );
				}
			}

			unset( $row );
		}

		/* Reset the array structure */
		$list_array = array_values( $list_array );

		return $list_array;
	}
}
