<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GF_Zapier {

	private static $_instance = null;

	public static function get_instance() {
		if ( self::$_instance === null ) {
			self::$_instance = new GF_Zapier();
		}

		return self::$_instance;
	}

	public function get_body_key( $body, $label ) {
		$count = 1;
		$key   = $label;

		while ( array_key_exists( $key, $body ) ) {
			$key = $label . ' - ' . $count;
			$count++;
		}

		return $key;
	}
}
