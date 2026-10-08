<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Post_Content;
use GF_UnitTest_Factory;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Post_Content extends WP_UnitTestCase {

	public function test_rich_text_merge_tags_only_processed_when_opted_in() {
		$form_id  = ( new GF_UnitTest_Factory() )->form->create( [], [ 'title' => 'Post Content Tags', 'fields' => [ new GF_Field_Post_Content( [ 'id' => 1, 'useRichTextEditor' => true ] ) ] ] );
		$gf_field = \GFAPI::get_form( $form_id )['fields'][0];
		$entry    = [ 'id' => 0, 'form_id' => $form_id, '1' => 'Form {form_id}' ];

		$value = function () use ( $gf_field, $entry ) {
			return ( new Field_Post_Content( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) )->value();
		};

		$this->assertStringContainsString( 'Form {form_id}', $value() );

		add_filter( 'gfpdf_field_process_merge_tags', '__return_true' );
		$this->assertStringContainsString( "Form $form_id", $value() );
		remove_filter( 'gfpdf_field_process_merge_tags', '__return_true' );
	}
}
