<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Address;
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
class Test_Field_Address extends WP_UnitTestCase {

	/**
	 * Build a Field_Address holding the passed entry values
	 *
	 * @param array $values The address inputs stored in the entry, keyed by input ID
	 *
	 * @return Field_Address
	 */
	protected function get_field( $values ) {
		$field = new GF_Field_Address(
			[
				'id'          => 1,
				'label'       => 'Address',
				'addressType' => 'international',
				'inputs'      => [
					[ 'id' => '1.1' ],
					[ 'id' => '1.2' ],
					[ 'id' => '1.3' ],
					[ 'id' => '1.4' ],
					[ 'id' => '1.5' ],
					[ 'id' => '1.6' ],
				],
			]
		);

		$entry = array_merge(
			[
				'id'      => 0,
				'form_id' => 0,
			],
			$values
		);

		return new Field_Address( $field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
	}

	/**
	 * Gravity Forms 3.0.3 started storing the ISO code as the country value
	 */
	protected function skip_without_country_codes() {
		if ( ! method_exists( GF_Field_Address::class, 'get_country_name' ) ) {
			$this->markTestSkipped( 'Requires Gravity Forms 3.0.3+' );
		}
	}

	public function provider_country() {
		return [
			'ISO code (Gravity Forms 3.0.3+)' => [ 'AU', 'Australia', 'AU' ],
			'country name (legacy)'           => [ 'Australia', 'Australia', 'AU' ],
			'unknown country'                 => [ 'Atlantis', 'Atlantis', '' ],
		];
	}

	/**
	 * @dataProvider provider_country
	 */
	public function test_value_resolves_country_name_and_code( $stored, $expected_country, $expected_code ) {
		if ( $stored === 'AU' ) {
			$this->skip_without_country_codes();
		}

		$value = $this->get_field( [ '1.6' => $stored ] )->value();

		$this->assertSame( $expected_country, $value['country'] );
		$this->assertSame( $expected_code, $value['country_code'] );
	}

	public function test_html_renders_country_name_for_country_code() {
		$this->skip_without_country_codes();

		$field = $this->get_field(
			[
				'1.3' => 'Townsville',
				'1.6' => 'AU',
			]
		);

		$this->assertStringContainsString( 'Townsville<br />Australia', $field->html() );
	}
}
