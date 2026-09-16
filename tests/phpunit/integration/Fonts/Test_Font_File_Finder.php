<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Helper\Mpdf\Mpdf;
use GFPDF\Tests\Concerns\HasFontRows;
use GFPDF\Tests\Concerns\RendersWithMpdf;
use GFPDF\Tests\Integration\TestCase;
use GFPDF_Vendor\Mpdf\Fonts\FontFileFinder;
use GFPDF_Vendor\Mpdf\Mpdf as VendorMpdf;
use GFPDF_Vendor\Mpdf\MpdfException;
use GPDFAPI;
use ReflectionProperty;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * The render-time answer to a font row whose file is no longer on disk
 *
 * Nothing stats a font file until mPDF asks for it, so these cases stand in for every way a site can lose one
 * behind the plugin's back: an FTP delete, a restore that skipped `uploads/`, a clone without the media.
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Font_File_Finder extends TestCase {

	use HasFontRows;
	use RendersWithMpdf;

	public static function set_up_before_class(): void {
		parent::set_up_before_class();

		/* A Helper_PDF constructor argument, for the cases that render */
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}

	public static function tear_down_after_class(): void {
		static::cleanup_class_fixtures();
		parent::tear_down_after_class();
	}

	public function tear_down(): void {
		$this->remove_font_rows();

		parent::tear_down();
	}

	/**
	 * The plugin's finder with the directories mPDF would have given it: bundled first, then uploads
	 */
	protected function finder( bool $with_bundled = true ): Font_File_Finder {
		$finder = GPDFAPI::get_font_registry()->font_file_finder();

		$finder->setDirectories(
			array_merge(
				$with_bundled ? [ untrailingslashit( PDF_PLUGIN_DIR . 'fonts' ) ] : [],
				[ untrailingslashit( $this->font_dir() ) ]
			)
		);

		return $finder;
	}

	protected function bundled( string $filename ): string {
		return untrailingslashit( PDF_PLUGIN_DIR . 'fonts' ) . '/' . $filename;
	}

	protected function mpdf_finder( Mpdf $mpdf ) {
		$property = new ReflectionProperty( VendorMpdf::class, 'fontFileFinder' );

		/* A no-op from 8.1, and required before it */
		if ( PHP_VERSION_ID < 80100 ) {
			$property->setAccessible( true );
		}

		return $property->getValue( $mpdf );
	}

	public function test_a_file_that_is_where_the_row_says_resolves_untouched() {
		$this->install_font_row( 'chewy' );

		$this->assertSame( $this->font_dir() . 'test-chewy.ttf', $this->finder()->findFontFile( 'test-chewy.ttf' ) );
		$this->assertSame( 0, $this->file_row( 'chewy' )['missing'] );
	}

	public function test_a_missing_face_is_substituted_by_the_bundled_face_of_the_same_style_and_flagged_alone() {
		$this->install_entry_row( 'chewy', 'packs/chewy', [], [ 'R', 'B' ] );
		unlink( $this->font_dir() . 'test-chewy-b.ttf' );

		$this->assertSame( $this->bundled( 'Arimo-Bold.ttf' ), $this->finder()->findFontFile( 'test-chewy-b.ttf' ) );
		$this->assertSame( 1, $this->file_row( 'chewy', 'B' )['missing'] );
		$this->assertSame( 0, $this->file_row( 'chewy', 'R' )['missing'] );
	}

	public function test_a_file_no_row_records_is_substituted_without_writing_to_the_tables() {
		$repository = $this->font_repository();
		$before     = $repository->get_last_changed();

		$this->assertSame( $this->bundled( 'Arimo-Regular.ttf' ), $this->finder()->findFontFile( 'never-recorded.ttf' ) );
		$this->assertSame( $before, $repository->get_last_changed() );
	}

	public function test_a_file_that_is_already_flagged_is_not_written_again() {
		$this->install_font_row( 'chewy' );
		unlink( $this->font_dir() . 'test-chewy.ttf' );

		$this->finder()->findFontFile( 'test-chewy.ttf' );

		$repository = $this->font_repository();
		$before     = $repository->get_last_changed();

		$this->finder()->findFontFile( 'test-chewy.ttf' );

		$this->assertSame( $before, $repository->get_last_changed() );
	}

	public function test_a_missing_bundled_face_is_still_an_error() {
		$this->expectException( MpdfException::class );

		$this->finder( false )->findFontFile( 'Arimo-Regular.ttf' );
	}

	public function test_mpdf_resolves_the_plugin_finder_rather_than_building_its_own() {
		$this->assertInstanceOf( Font_File_Finder::class, $this->mpdf_finder( $this->mpdf_for() ) );
	}

	/**
	 * The finder mPDF ends up with when an add-on replaces the whole container with one of its own
	 */
	protected function finder_behind( FontFileFinder $replaced ): Font_File_Finder {
		$container = function () use ( $replaced ) {
			return [ 'fontFileFinder' => $replaced ];
		};

		add_filter( 'gfpdf_mpdf_class_container', $container );
		$finder = $this->mpdf_finder( $this->mpdf_for() );
		remove_filter( 'gfpdf_mpdf_class_container', $container );

		return $finder;
	}

	public function test_an_addon_replacing_the_container_cannot_drop_the_recovery() {
		$finder = $this->finder_behind( new FontFileFinder( [] ) );

		$this->assertInstanceOf( Font_File_Finder::class, $finder );
	}

	public function test_a_finder_an_addon_supplied_is_what_resolves_the_paths() {
		$replaced = new Addon_Font_File_Finder();

		$this->finder_behind( $replaced );

		/* mPDF resolves its default font during construction, so this is the whole render path answering */
		$this->assertContains( 'Arimo-Regular.ttf', $replaced->asked );
	}

	public function test_mpdf_reaches_a_finder_an_addon_supplied_with_the_layer_directories() {
		$replaced = new Addon_Font_File_Finder();

		$this->finder_behind( $replaced );

		$this->assertSame( untrailingslashit( PDF_PLUGIN_DIR . 'fonts' ), $replaced->given[0] );
	}

	public function test_a_render_whose_font_file_has_vanished_produces_a_pdf_and_drops_the_face_from_the_next_one() {
		$this->install_font_row( 'chewy' );
		unlink( $this->font_dir() . 'test-chewy.ttf' );

		$mpdf = $this->mpdf_for();
		$mpdf->WriteHTML( '<p style="font-family: chewy;">still rendered</p>' );

		$this->assertArrayHasKey( 'chewy', $mpdf->fonts );
		$this->assertStringStartsWith( '%PDF-', $mpdf->Output( '', 'S' ) );
		$this->assertSame( 1, $this->file_row( 'chewy' )['missing'] );

		$this->assertArrayNotHasKey( 'chewy', $this->mpdf_for()->fontdata );
	}
}

/**
 * A finder of the shape an add-on would put in the container, recording what it was handed and asked for
 */
class Addon_Font_File_Finder extends FontFileFinder {

	/**
	 * @var array The directories mPDF gave it
	 */
	public $given = [];

	/**
	 * @var array The filenames it was asked to resolve
	 */
	public $asked = [];

	public function __construct() {
		parent::__construct( [] );
	}

	public function setDirectories( $directories ) {
		$this->given = (array) $directories;

		parent::setDirectories( $directories );
	}

	public function findFontFile( $name ) {
		$this->asked[] = $name;

		return parent::findFontFile( $name );
	}
}
