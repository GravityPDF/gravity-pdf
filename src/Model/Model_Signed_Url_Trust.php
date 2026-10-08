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
	 * The shortcodes and merge tags in an administrative field's default. Gravity Forms resolves some on save and leaves
	 * others for later, so each is trusted on its own.
	 *
	 * @param \GF_Field|mixed $field
	 *
	 * @return string[]
	 *
	 * @since 6.17.3
	 */
	public function get_default_tags( $field ) {
		if ( ! $field instanceof \GF_Field || ! $field->is_administrative() ) {
			return [];
		}

		$inputs   = is_array( $field->inputs ) ? $field->inputs : [];
		$defaults = array_merge( [ $field->defaultValue ], array_column( $inputs, 'defaultValue' ) );
		$regex    = '/' . get_shortcode_regex() . '|\{[^{}]+\}/';

		$tags = [];
		foreach ( $defaults as $default ) {
			if ( is_string( $default ) && strpbrk( $default, '[{' ) !== false && preg_match_all( $regex, $default, $matches ) ) {
				array_push( $tags, ...$matches[0] );
			}
		}

		return array_values( array_unique( $tags ) );
	}

	/**
	 * The default tags the entry's administrative fields still hold
	 *
	 * @param array $form
	 * @param array $entry
	 *
	 * @return string[]
	 *
	 * @since 6.17.3
	 */
	public function get_trusted_field_tags( $form, $entry ) {
		$trusted = [];
		foreach ( $form['fields'] ?? [] as $field ) {
			$tags = $this->get_default_tags( $field );

			foreach ( $tags ? (array) GFFormsModel::get_lead_field_value( $entry, $field ) : [] as $value ) {
				if ( ! is_string( $value ) ) {
					continue;
				}

				foreach ( $tags as $tag ) {
					if ( strpos( $value, $tag ) !== false ) {
						$trusted[] = $tag;
					}
				}
			}
		}

		return array_values( array_unique( $trusted ) );
	}
}
