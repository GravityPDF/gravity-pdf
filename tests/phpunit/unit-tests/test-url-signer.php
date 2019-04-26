<?php

namespace GFPDF\Tests;


use GFPDF\Helper\Helper_Url_Signer;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/*
	This file is part of Gravity PDF.

	Gravity PDF – Copyright (c) 2019, Blue Liquid Designs

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * @since 5.2
 * @group url-signer
 */
class Test_Url_Signer extends WP_UnitTestCase {

	/**
	 * @throws \Spatie\UrlSigner\Exceptions\InvalidSignatureKey
	 *
	 * @since 5.2
	 */
	public function test_sign_and_verify() {
		$signer = new Helper_Url_Signer();
		$url    = 'https://test.com';

		$this->assertNotEquals( $url, $signer->sign( $url, '+ 1 day' ) );
		$this->assertTrue( $signer->verify( $signer->sign( $url, '+ 1 day' ) ) );
		$this->assertFalse( $signer->verify( $url ) );
	}
}
