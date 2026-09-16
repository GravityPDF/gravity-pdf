<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use WP_Error;

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
 * The plugin's single locking primitive for font work
 *
 * Backed by `add_site_option()`, which is an atomic INSERT: exactly one caller gets true. Network-scoped, because
 * everything it guards (the fonts directory, the font tables) is network-global.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Font_Lock {

	/**
	 * @since 7.0
	 */
	protected function get_option_name( string $name ): string {
		return 'gfpdf_lock_' . $name;
	}

	/**
	 * Take the named lock, or take it over when the holder went away
	 *
	 * @param string $name The lock name, e.g. `fonts_migrating`
	 * @param int    $ttl  Seconds after which a held lock is considered abandoned
	 *
	 * @since 7.0
	 */
	public function acquire( string $name, int $ttl ): bool {
		$option = $this->get_option_name( $name );

		if ( add_site_option( $option, time() ) ) {
			return true;
		}

		$held_since = (int) get_site_option( $option, 0 );
		if ( $held_since > 0 && ( time() - $held_since ) < $ttl ) {
			return false;
		}

		/* Stale. Delete and re-add so the takeover is itself atomic and only one waiter wins */
		delete_site_option( $option );

		return (bool) add_site_option( $option, time() );
	}

	/**
	 * Take the lock guarding one catalogue entry's files and rows, or say who is holding it
	 *
	 * Installing a file, removing the entry and importing an offline package contend for one name deliberately — a
	 * file landing while the rows are being deleted is the window this closes. The name, the refusal and the error
	 * code all live here rather than at each caller: they only exclude each other while all three agree, and a
	 * drift would not error, it would just stop locking. The TTL stays the caller's, because it is a statement
	 * about that caller's work and nothing else — one file's fetch is not one pack's extraction.
	 *
	 * @return string|WP_Error The name to release in a `finally`
	 *
	 * @since 7.0
	 */
	public function acquire_entry( string $source, string $entry, int $ttl ) {
		$lock = Font_Sources::entry_lock( $source, $entry );

		if ( ! $this->acquire( $lock, $ttl ) ) {
			return new WP_Error(
				'font_install_in_progress',
				esc_html__( 'This font is already being installed. Try again once it has finished.', 'gravity-pdf' ),
				[ 'status' => 409 ]
			);
		}

		return $lock;
	}

	/**
	 * Always call from a `finally`
	 *
	 * @since 7.0
	 */
	public function release( string $name ): void {
		delete_site_option( $this->get_option_name( $name ) );
	}
}
