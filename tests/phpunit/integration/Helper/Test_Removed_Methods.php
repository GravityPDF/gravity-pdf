<?php

declare( strict_types=1 );

namespace GFPDF\Helper;

use GFPDF\Statics\Queue_Callbacks;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Test the classes that lost methods in 7.0 report the call instead of ending the request
 *
 * @since 7.0
 * @group removed-methods
 */
class Test_Removed_Methods extends TestCase {

	/**
	 * Every class 7.0 removed a method from
	 *
	 * The views are absent on purpose. Helper_Abstract_View::__call() is final and already routes an unknown method to
	 * a same-named template, so a removed view method reports through _doing_it_wrong() from there instead — covered by
	 * Test_MVC_Abstracts::test_abstract_view().
	 *
	 * @since 7.0
	 */
	public function classes(): array {
		return [
			[ \GFPDF_Major_Compatibility_Checks::class ],
			[ \GFPDF\Router::class ],
			[ \GFPDF\Controller\Controller_Install::class ],
			[ \GFPDF\Controller\Controller_PDF::class ],
			[ \GFPDF\Controller\Controller_Pdf_Queue::class ],
			[ Helper_Abstract_Options::class ],
			[ Helper_Misc::class ],
			[ Helper_Notices::class ],
			[ Helper_Options_Fields::class ],
			[ \GFPDF\Model\Model_Install::class ],
			[ \GFPDF\Model\Model_PDF::class ],
			[ \GFPDF\Model\Model_Settings::class ],
			[ \GFPDF\Model\Model_Shortcodes::class ],
			[ \GFPDF\Statics\Deprecation::class ],
			[ \GFPDF\Statics\Deprecation_V3::class ],
			[ Queue_Callbacks::class ],
		];
	}

	/**
	 * Parents are walked so the trait can be consolidated onto a base class without this failing
	 *
	 * @dataProvider classes
	 *
	 * @since 7.0
	 */
	public function test_class_reports_its_removed_methods( string $class ) {
		$traits = [];

		foreach ( array_merge( [ $class ], class_parents( $class ) ) as $ancestor ) {
			$traits = array_merge( $traits, class_uses( $ancestor ) );
		}

		$message = $class . ' no longer reports calls to the methods 7.0 removed';

		$this->assertContains( Helper_Trait_Removed_Methods::class, $traits, $message );
	}

	/**
	 * A removed instance method reports and returns null, rather than ending the request
	 *
	 * @since 7.0
	 */
	public function test_removed_instance_method_is_reported() {
		$this->setExpectedDeprecated( Helper_Misc::class . '::backwards_compat_output' );

		$this->assertNull( $this->gfpdf()->misc->backwards_compat_output( 'view' ) );
	}

	/**
	 * A removed static method reports and returns null, rather than ending the request
	 *
	 * @since 7.0
	 */
	public function test_removed_static_method_is_reported() {
		$this->setExpectedDeprecated( Queue_Callbacks::class . '::cleanup_pdfs' );

		$this->assertNull( Queue_Callbacks::cleanup_pdfs( 1, 2 ) );
	}

	/**
	 * A class that loses methods in more than one release names the right one for each
	 *
	 * Nothing declares a map yet, since 7.0 is the first round, so the contract is proved on a fixture instead.
	 *
	 * @since 7.0
	 */
	public function test_a_declared_method_reports_the_release_it_went_in() {
		$this->setExpectedDeprecated( Multi_Release_Removals::class . '::removed_later' );
		$this->setExpectedDeprecated( Multi_Release_Removals::class . '::removed_in_seven' );

		$versions = [];

		add_action(
			'deprecated_function_run',
			function ( $function_name, $replacement, $version ) use ( &$versions ) {
				$versions[ $function_name ] = $version;
			},
			10,
			3
		);

		$class = new Multi_Release_Removals();

		$class->removed_later();
		$class->removed_in_seven();

		/* The declared method names its own release; anything left out falls back to the first round */
		$this->assertSame( '8.0', $versions[ Multi_Release_Removals::class . '::removed_later' ] );
		$this->assertSame( '7.0', $versions[ Multi_Release_Removals::class . '::removed_in_seven' ] );
	}
}

/**
 * A class past its second round of removals, which is when the version map earns its keep
 *
 * @since 7.0
 */
class Multi_Release_Removals {

	use Helper_Trait_Removed_Methods;

	protected static function get_removed_methods(): array {
		return [ 'removed_later' => '8.0' ];
	}
}
