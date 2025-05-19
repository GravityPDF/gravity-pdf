<?php

namespace GFPDF\Helper;

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
 * Interface Helper_Interface_Url_Signer
 *
 * @package GFPDF\Helper
 */
interface Helper_Interface_Url_Signer {

	/**
	 * Sign a URL with a secret token
	 *
	 * @param string $url
	 * @param string $expiration
	 *
	 * @return string
	 *
	 * @since 5.2
	 */
	public function sign( $url, $expiration );

	/**
	 * Verify if the signed URL is valid
	 *
	 * @param string $url
	 *
	 * @return bool
	 *
	 * @since 5.2
	 */
	public function verify( $url );
}
