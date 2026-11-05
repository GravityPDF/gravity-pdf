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
 * Test the classes that lost methods report the call instead of ending the request
 *
 * @since 7.0
 * @group removed-methods
 */
class Test_Removed_Methods extends TestCase {

	/**
	 * The release each removed method reported, keyed by its name
	 *
	 * @var array<string, string>
	 */
	private $deprecated_versions = [];

	/**
	 * Every class that has had a method removed
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
			[ \GFPDF\Controller\Controller_Settings::class ],
			[ \GFPDF\Controller\Controller_Pdf_Queue::class ],
			[ \GFPDF\Controller\Controller_Save_Core_Fonts::class ],
			[ Helper_Abstract_Options::class ],
			[ Helper_Misc::class ],
			[ Helper_Notices::class ],
			[ Helper_Options_Fields::class ],
			[ \GFPDF\Model\Model_Actions::class ],
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

		$message = $class . ' no longer reports calls to its removed methods';

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
	 * Proved on a fixture, as the real maps don't leave a method out to fall back to 7.0.
	 *
	 * @since 7.0
	 */
	public function test_a_declared_method_reports_the_release_it_went_in() {
		$this->setExpectedDeprecated( Multi_Release_Removals::class . '::removed_later' );
		$this->setExpectedDeprecated( Multi_Release_Removals::class . '::removed_in_seven' );

		$this->record_deprecated_versions();

		$class = new Multi_Release_Removals();

		$class->removed_later();
		$class->removed_in_seven();

		/* The declared method names its own release; anything left out falls back to the first round */
		$this->assertSame( '8.0', $this->deprecated_versions[ Multi_Release_Removals::class . '::removed_later' ] );
		$this->assertSame( '7.0', $this->deprecated_versions[ Multi_Release_Removals::class . '::removed_in_seven' ] );
	}

	/**
	 * The Core Font installer and the Tools tab went in 6.19.0
	 *
	 * @since 6.19.0
	 */
	public function removed_in_6_19(): array {
		return [
			[ \GFPDF\Router::class, 'load_core_font_handler' ],
			[ \GFPDF\Model\Model_Actions::class, 'core_font_condition' ],
			[ \GFPDF\Model\Model_Actions::class, 'core_font_redirect' ],
			[ \GFPDF\Controller\Controller_Settings::class, 'disable_tools_on_view_cap' ],
		];
	}

	/**
	 * @dataProvider removed_in_6_19
	 *
	 * @since 6.19.0
	 */
	public function test_a_method_removed_in_6_19_reports_it( string $class, string $method ) {
		$name = $class . '::' . $method;
		$this->setExpectedDeprecated( $name );

		$this->record_deprecated_versions();

		$instance = $class === \GFPDF\Router::class ? $this->gfpdf() : \GPDFAPI::get_mvc_class( substr( strrchr( $class, '\\' ), 1 ) );

		$this->assertNull( $instance->$method() );
		$this->assertSame( [ $name => '6.19.0' ], $this->deprecated_versions );
	}

	/**
	 * The installer's controller stays as an inert stub, so building or calling it reports instead of ending the request
	 *
	 * @since 6.19.0
	 */
	public function test_the_core_font_controller_is_an_inert_stub() {
		$class_name = \GFPDF\Controller\Controller_Save_Core_Fonts::class;

		$this->setExpectedDeprecated( $class_name );
		$this->setExpectedDeprecated( $class_name . '::save_core_font' );

		$this->record_deprecated_versions();

		$gfpdf      = $this->gfpdf();
		$controller = new $class_name( $gfpdf->log, $gfpdf->data, $gfpdf->misc );
		$controller->init();

		$this->assertFalse( has_action( 'wp_ajax_gfpdf_save_core_font' ) );
		$this->assertNull( $controller->save_core_font() );
		$this->assertSame( [ $class_name . '::save_core_font' => '6.19.0' ], $this->deprecated_versions );
	}

	/**
	 * Collect the release each removed method reports into $deprecated_versions
	 *
	 * @since 6.19.0
	 */
	private function record_deprecated_versions(): void {
		add_action(
			'deprecated_function_run',
			function ( $function_name, $replacement, $version ) {
				$this->deprecated_versions[ $function_name ] = $version;
			},
			10,
			3
		);
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
