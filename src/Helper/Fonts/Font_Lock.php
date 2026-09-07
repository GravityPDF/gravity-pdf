<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

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
 * @package GFPDF\Helper\Fonts
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
	 * Always call from a `finally`
	 *
	 * @since 7.0
	 */
	public function release( string $name ): void {
		delete_site_option( $this->get_option_name( $name ) );
	}
}
