<?php

namespace GFPDF\Model;

use GFFormsModel;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tracks which [gravitypdf] shortcodes and PDF merge tags may be signed without checking the user can view the entry,
 * because they came from content the plugin or a form editor wrote rather than a submitter
 *
 * @since 6.17.3
 */
class Model_Signed_Url_Trust {

	/**
	 * Single-use signing grants, keyed by what was granted, with the uses remaining
	 *
	 * @var array<string,int>
	 * @since 6.17.3
	 */
	protected $grants = [];

	/**
	 * Above zero while processing user-submitted content, which can't be granted trust
	 *
	 * @var int
	 * @since 6.17.3
	 */
	protected $untrusted_depth = 0;

	/**
	 * @param string $key
	 *
	 * @return void
	 *
	 * @since 6.17.3
	 */
	public function grant( $key ) {
		$this->grants[ $key ] = ( $this->grants[ $key ] ?? 0 ) + 1;
	}

	/**
	 * Use up one grant
	 *
	 * @param string $key
	 *
	 * @return bool Whether a grant existed
	 *
	 * @since 6.17.3
	 */
	public function consume( $key ) {
		if ( empty( $this->grants[ $key ] ) ) {
			return false;
		}

		if ( --$this->grants[ $key ] === 0 ) {
			unset( $this->grants[ $key ] );
		}

		return true;
	}

	/**
	 * Run the callback with one grant, which is removed afterwards if the callback didn't use it
	 *
	 * @param string   $key
	 * @param callable $callback
	 *
	 * @return mixed The callback's return value
	 *
	 * @since 6.17.3
	 */
	public function run_granted( $key, callable $callback ) {
		$before = $this->grants[ $key ] ?? 0;

		$this->grant( $key );

		try {
			return $callback();
		} finally {
			if ( $before > 0 ) {
				$this->grants[ $key ] = $before;
			} else {
				unset( $this->grants[ $key ] );
			}
		}
	}

	/**
	 * Run the callback without granting trust to anything it processes
	 *
	 * @param callable $callback
	 *
	 * @return mixed The callback's return value
	 *
	 * @since 6.17.3
	 */
	public function run_untrusted( callable $callback ) {
		++$this->untrusted_depth;

		try {
			return $callback();
		} finally {
			--$this->untrusted_depth;
		}
	}

	/**
	 * @return bool Whether user-submitted content is being processed
	 *
	 * @since 6.17.3
	 */
	public function is_untrusted() {
		return $this->untrusted_depth > 0;
	}

	/**
	 * Whether an administrative field holds its default value, which only a form editor can set. A value that differs from
	 * the default is never trusted.
	 *
	 * @param \GF_Field|mixed $field
	 * @param array|mixed     $entry
	 *
	 * @return bool
	 *
	 * @since 6.17.3
	 */
	public function is_trusted_field_value( $field, $entry ) {
		if ( ! $field instanceof \GF_Field || ! $field->is_administrative() || ! is_array( $entry ) ) {
			return false;
		}

		$inputs    = $field->get_entry_inputs();
		$input_ids = is_array( $inputs ) ? wp_list_pluck( $inputs, 'id' ) : [ $field->id ];

		foreach ( $input_ids as $input_id ) {
			if ( (string) ( $entry[ (string) $input_id ] ?? '' ) !== (string) GFFormsModel::get_default_value( $field, $input_id ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * The entry's administrative field values that match their defaults and hold a shortcode or merge tag
	 *
	 * @param array $form
	 * @param array $entry
	 *
	 * @return string[]
	 *
	 * @since 6.17.3
	 */
	public function get_trusted_field_values( $form, $entry ) {
		$values = [];
		foreach ( $form['fields'] ?? [] as $field ) {
			if ( ! $field instanceof \GF_Field || ! $field->is_administrative() ) {
				continue;
			}

			/* Check the tags first, as the trust check recomputes the field's default */
			$tagged = array_filter(
				(array) GFFormsModel::get_lead_field_value( $entry, $field ),
				function ( $value ) {
					return is_string( $value ) && strpbrk( $value, '[]{}' ) !== false;
				}
			);

			if ( $tagged && $this->is_trusted_field_value( $field, $entry ) ) {
				$values = array_merge( $values, array_values( $tagged ) );
			}
		}

		return $values;
	}
}
