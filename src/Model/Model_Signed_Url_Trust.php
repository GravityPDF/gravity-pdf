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
 * Tracks which PDF merge tags may be signed without checking the user can view the entry, because they came from
 * content the plugin or a form editor wrote rather than a submitter, and whether a PDF is being rendered or
 * user-submitted content processed
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
	 * The entries whose PDFs are being rendered, innermost last
	 *
	 * @var int[]
	 * @since 6.17.3
	 */
	protected $rendering_entry_ids = [];

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
	 * Run the callback while rendering an entry's PDF, or merging its field content, which mixes what form editors and
	 * submitters wrote
	 *
	 * @param int      $entry_id
	 * @param callable $callback
	 *
	 * @return mixed The callback's return value
	 *
	 * @since 6.17.3
	 */
	public function run_rendering_pdf( $entry_id, callable $callback ) {
		$this->rendering_entry_ids[] = (int) $entry_id;

		try {
			return $callback();
		} finally {
			array_pop( $this->rendering_entry_ids );
		}
	}

	/**
	 * @return bool Whether a PDF is being rendered
	 *
	 * @since 6.17.3
	 */
	public function is_rendering_pdf() {
		return ! empty( $this->rendering_entry_ids );
	}

	/**
	 * @return int The entry whose PDF is being rendered, or 0 when there isn't one
	 *
	 * @since 6.17.3
	 */
	public function get_rendering_entry_id() {
		return (int) end( $this->rendering_entry_ids );
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
