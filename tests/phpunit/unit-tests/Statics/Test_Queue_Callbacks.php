<?php

declare( strict_types=1 );

namespace GFPDF\Statics;

use RuntimeException;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group statics
 */
class Test_Queue_Callbacks extends WP_UnitTestCase {

	public function test_create_pdf_removes_its_bypass_filter_when_generation_throws() {
		$bypass_filter = null;

		add_action(
			'gfpdf_pre_generate_and_save_pdf',
			static function () use ( &$bypass_filter ) {
				$bypass_filter = has_filter( 'gfpdf_override_pdf_bypass', '__return_false' );

				throw new RuntimeException( 'Generation failed' );
			}
		);

		try {
			Queue_Callbacks::create_pdf( $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0]['id'], '555ad84787d7e' );
			$this->fail( 'The exception was swallowed' );
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'Generation failed', $e->getMessage() );
		}

		$this->assertSame( 20, $bypass_filter );
		$this->assertFalse( has_filter( 'gfpdf_override_pdf_bypass', '__return_false' ) );
	}
}
