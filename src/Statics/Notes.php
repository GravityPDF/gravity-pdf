<?php

namespace GFPDF\Statics;

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
 * Gravity PDF's notes on Gravity Forms entries
 *
 * @since 6.17.1
 */
class Notes {

	/**
	 * The note type Gravity PDF's entry notes are saved with
	 *
	 * @since 6.17.1
	 */
	public const NOTE_TYPE = 'gravity-pdf';

	/**
	 * Add a note to an entry from Gravity PDF
	 *
	 * @param int    $entry_id The Gravity Forms entry ID
	 * @param string $note     The note, which is displayed after passing through wp_kses_post()
	 * @param string $sub_type Optional Gravity Forms sub-type, such as "success" or "error"
	 * @param string $author   Shown as the note's author, which Gravity Forms escapes on output
	 *
	 * @return void
	 *
	 * @since 6.17.1
	 */
	public static function add_entry_note( $entry_id, $note, $sub_type = '', $author = 'Gravity PDF' ) {
		/* The note is displayed in a flex container, so wrap it to keep inline HTML flowing with the text around it */
		GFFormsModel::add_note( $entry_id, 0, $author, '<div>' . $note . '</div>', self::NOTE_TYPE, $sub_type );
	}

	/**
	 * Show the Gravity PDF logo, instead of the Gravity Forms one, beside notes Gravity PDF added to an entry
	 *
	 * @param string $avatar The avatar HTML
	 * @param object $note   The Gravity Forms note
	 *
	 * @return string
	 *
	 * @since 6.17.1
	 */
	public static function note_avatar( $avatar, $note ) {
		if ( $note->note_type !== self::NOTE_TYPE || ! empty( $note->user_id ) ) {
			return $avatar;
		}

		return sprintf(
			'<img alt="%s" src="%s" class="avatar avatar-48" height="48" width="48" />',
			esc_attr__( 'Gravity PDF', 'gravity-pdf' ),
			esc_url( PDF_PLUGIN_URL . 'src/assets/images/gravitypdf-logo.svg' )
		);
	}
}
