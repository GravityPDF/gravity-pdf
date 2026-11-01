<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Tests\Integration\TestCase;
use GFPDF_Vendor\Mpdf\Config\FontVariables;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group model
 * @group fonts
 */
class Test_Model_Font_Sources extends TestCase {

	const FIXTURES = PDF_PLUGIN_DIR . 'tools/phpunit/data/fonts/';

	/**
	 * @var Model_Font_Sources
	 */
	protected $model;

	/**
	 * @var Model_Custom_Fonts
	 */
	protected $custom_fonts;

	/**
	 * @var string
	 */
	protected $font_dir;

	/**
	 * @var string[]
	 */
	protected $copied = [];

	public function set_up(): void {
		parent::set_up();

		$this->model        = GPDFAPI::get_mvc_class( 'Model_Font_Sources' );
		$this->custom_fonts = GPDFAPI::get_mvc_class( 'Model_Custom_Fonts' );
		$this->font_dir     = GPDFAPI::get_data_class()->template_font_location;

		wp_mkdir_p( $this->font_dir );
	}

	public function tear_down(): void {
		array_map( 'wp_delete_file', $this->copied );
		GPDFAPI::delete_plugin_option( 'custom_fonts' );
		delete_option( Model_Font_Sources::OPTION );

		parent::tear_down();
	}

	public function test_the_bundled_and_core_sources_are_registered() {
		$sources = $this->model->get_sources();

		$this->assertSame( [ 'bundled', 'core' ], array_keys( $sources ) );
		$this->assertSame( $this->core_fontdata(), $sources['core']['families'] );

		$arimo = $sources['bundled']['families']['arimo'];
		foreach ( [ 'R', 'B', 'I', 'BI' ] as $style ) {
			$this->assertFileExists( $sources['bundled']['dir'] . $arimo[ $style ] );
		}

		$this->assertTrue( $this->model->is_family_key( 'arimo' ) );
		$this->assertTrue( $this->model->is_family_key( 'dejavusanscondensed' ) );
		$this->assertFalse( $this->model->is_family_key( 'chewy' ) );
	}

	public function test_the_bundled_legacy_hashes_are_sha1_to_size() {
		foreach ( $this->model->get_sources()['bundled']['legacy'] as $sha => $size ) {
			$this->assertMatchesRegularExpression( '/^[0-9a-f]{40}$/', $sha );
			$this->assertIsInt( $size );
		}
	}

	public function test_mpdf_font_data_registers_arimo_before_custom_fonts() {
		$this->assertSame( 5, has_filter( 'mpdf_font_data', [ $this->model, 'register_font_data' ] ) );

		$fonts = apply_filters( 'mpdf_font_data', $this->core_fontdata() );

		$this->assertSame(
			[
				'R'      => 'GravityPDF-Arimo-Regular.ttf',
				'B'      => 'GravityPDF-Arimo-Bold.ttf',
				'I'      => 'GravityPDF-Arimo-Italic.ttf',
				'BI'     => 'GravityPDF-Arimo-BoldItalic.ttf',
				'useOTL' => 0xFF,
			],
			$fonts['arimo']
		);

		$this->add_record( 'arimo', [ 'regular' => 'Chewy.ttf' ] );
		$fonts = apply_filters( 'mpdf_font_data', $this->core_fontdata() );

		$this->assertSame( 'Chewy.ttf', $fonts['arimo']['R'] );
		$this->assertArrayNotHasKey( 'B', $fonts['arimo'] );
	}

	public function test_mpdf_font_data_leaves_fonts_already_registered() {
		$fonts = $this->model->register_font_data( [ 'arimo' => [ 'R' => 'Other.ttf' ] ] );

		$this->assertSame( [ 'R' => 'Other.ttf' ], $fonts['arimo'] );
	}

	public function test_the_bundled_dir_is_appended_to_font_dir() {
		$this->assertNotFalse( has_filter( 'gfpdf_mpdf_class_config', [ $this->model, 'register_font_directories' ] ) );

		$bundled = PDF_PLUGIN_DIR . 'fonts/';
		$config  = $this->model->register_font_directories( [ 'fontDir' => [ $this->font_dir ] ] );

		$this->assertSame( [ $this->font_dir, $bundled ], $config['fontDir'] );
		$this->assertSame( [ $this->font_dir, $bundled ], $this->model->register_font_directories( $config )['fontDir'] );
		$this->assertSame( [ '/fonts/', $bundled ], $this->model->register_font_directories( [ 'fontDir' => '/fonts/' ] )['fontDir'] );
	}

	public function test_mpdf_renders_the_bundled_arimo() {
		$mpdf = new \GFPDF\Helper\Mpdf\Mpdf(
			apply_filters(
				'gfpdf_mpdf_class_config',
				[
					'fontDir'      => [ get_temp_dir() . 'gfpdf-no-fonts/' ],
					'fontdata'     => apply_filters( 'mpdf_font_data', $this->core_fontdata() ),
					'tempDir'      => get_temp_dir(),
					'default_font' => 'arimo',
				],
				[],
				[],
				[],
				null
			)
		);

		$mpdf->WriteHTML( '<p style="font-family: arimo"><b>Bold</b> <i>Italic</i> <b><i>Both</i></b> Regular</p>' );
		$pdf = $mpdf->Output( '', 'S' );

		foreach ( [ 'Arimo-Regular', 'Arimo-Bold', 'Arimo-Italic', 'Arimo-BoldItalic' ] as $face ) {
			$this->assertStringContainsString( '+' . $face, $pdf );
		}
	}

	public function test_arimo_is_listed_first_in_the_unicode_group() {
		$this->assertSame( 5, has_filter( 'gfpdf_font_list', [ $this->model, 'add_font_list_groups' ] ) );

		$fonts = GPDFAPI::get_pdf_fonts();

		$this->assertSame( 'arimo', array_key_first( $fonts['Unicode'] ) );
		$this->assertSame( 'Arimo', $fonts['Unicode']['arimo'] );
		$this->assertSame( 'Dejavu Sans Condensed', $fonts['Unicode']['dejavusanscondensed'] );
	}

	public function test_a_custom_font_with_a_source_key_is_listed_once() {
		$this->add_record( 'arimo', [ 'regular' => 'Chewy.ttf' ] );

		$fonts = GPDFAPI::get_pdf_fonts();

		$this->assertArrayNotHasKey( 'arimo', $fonts['Unicode'] );
		$this->assertSame( 'arimo', $fonts['User-Defined Fonts']['arimo'] );
	}

	public function test_the_arimo_default_is_only_for_unsaved_settings() {
		$field = GPDFAPI::get_options_class()->get_registered_fields()['general_defaults']['default_font'];
		$this->assertSame( 'arimo', $field['std'] );

		GPDFAPI::get_options_class()->update_option( 'default_font', 'dejavusanscondensed' );
		$this->assertSame( 'dejavusanscondensed', GPDFAPI::get_options_class()->get_form_value( $field ) );
	}

	public function test_adopt_records_removes_a_record_matching_the_manifest() {
		$this->copy_fixture( 'DejaVuSansCondensed.ttf', 'DejaVuSansCondensed85274.ttf' );
		$this->copy_fixture( 'DejaVuSans-Bold.ttf' );
		$this->add_record(
			'dejavusanscondensed',
			[
				'regular' => 'DejaVuSansCondensed85274.ttf',
				'bold'    => 'DejaVuSans-Bold.ttf',
			]
		);

		$this->assertSame( [ 'dejavusanscondensed' ], $this->model->adopt_records( 'core' ) );
		$this->assertSame( [], $this->custom_fonts->get_custom_fonts() );

		/* Multisite keeps the files in the shared fonts folder */
		$this->assertSame( is_multisite(), is_file( $this->font_dir . 'DejaVuSansCondensed85274.ttf' ) );
		$this->assertSame( is_multisite(), is_file( $this->font_dir . 'DejaVuSans-Bold.ttf' ) );
	}

	public function test_adopt_records_removes_a_record_matching_legacy_hashes() {
		$model = $this->get_model_with_legacy( 'Chewy.ttf' );

		$this->copy_fixture( 'Chewy.ttf', 'Arimo-Regular85274.ttf' );
		$this->add_record( 'arimo', [ 'regular' => 'Arimo-Regular85274.ttf' ] );

		$this->assertSame( [ 'arimo' ], $model->adopt_records( 'bundled' ) );
		$this->assertSame( [], $this->custom_fonts->get_custom_fonts() );
		$this->assertSame( is_multisite(), is_file( $this->font_dir . 'Arimo-Regular85274.ttf' ) );
	}

	public function test_adopt_records_keeps_an_edited_record() {
		$model = $this->get_model_with_legacy( 'Chewy.ttf' );

		$this->copy_fixture( 'Chewy.ttf' );
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->add_record(
			'arimo',
			[
				'regular' => 'Chewy.ttf',
				'bold'    => 'DejaVuSerifCondensed.ttf',
			]
		);

		$this->assertSame( [], $model->adopt_records( 'bundled' ) );
		$this->assertArrayHasKey( 'arimo', $this->custom_fonts->get_custom_fonts() );
		$this->assertFileExists( $this->font_dir . 'Chewy.ttf' );
	}

	public function test_adopt_records_keeps_a_record_with_a_missing_file() {
		$this->add_record( 'dejavusanscondensed', [ 'regular' => 'DejaVuSansCondensed-missing.ttf' ] );

		$this->assertSame( [], $this->model->adopt_records( 'core' ) );
		$this->assertArrayHasKey( 'dejavusanscondensed', $this->custom_fonts->get_custom_fonts() );
	}

	public function test_adopt_records_ignores_records_with_other_keys() {
		$this->copy_fixture( 'DejaVuSansCondensed.ttf' );
		$this->add_record( 'mydejavu', [ 'regular' => 'DejaVuSansCondensed.ttf' ] );

		$this->assertSame( [], $this->model->adopt_records( 'core' ) );
		$this->assertSame( [], $this->model->adopt_records( 'unknown' ) );
		$this->assertArrayHasKey( 'mydejavu', $this->custom_fonts->get_custom_fonts() );
	}

	public function test_admin_init_adopts_new_and_updated_sources_once() {
		$model = new class( GPDFAPI::get_data_class(), $this->custom_fonts, GPDFAPI::get_log_class() ) extends Model_Font_Sources {
			public $adopted = [];
			public $version = '1.0.0';

			public function get_sources(): array {
				$sources                       = parent::get_sources();
				$sources['bundled']['version'] = $this->version;

				return $sources;
			}

			public function adopt_records( string $source_id ): array {
				$this->adopted[] = $source_id;

				return [];
			}
		};

		$model->maybe_adopt_changed_sources();
		$this->assertSame( [ 'bundled', 'core' ], $model->adopted );
		$this->assertSame(
			[
				'bundled' => '1.0.0',
				'core'    => '1.0.0',
			],
			get_option( Model_Font_Sources::OPTION )
		);

		$model->maybe_adopt_changed_sources();
		$this->assertSame( [ 'bundled', 'core' ], $model->adopted );

		$model->version = '1.1.0';
		$model->maybe_adopt_changed_sources();
		$model->maybe_adopt_changed_sources();
		$this->assertSame( [ 'bundled', 'core', 'bundled' ], $model->adopted );
	}

	public function test_admin_init_adopts_a_template_arimo_record() {
		$this->assertNotFalse( has_action( 'admin_init', [ $this->model, 'maybe_adopt_changed_sources' ] ) );

		$model = $this->get_model_with_legacy( 'Chewy.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'Arimo-Regular.ttf' );
		$this->add_record( 'arimo', [ 'regular' => 'Arimo-Regular.ttf' ] );

		$model->maybe_adopt_changed_sources();

		$this->assertSame( [], $this->custom_fonts->get_custom_fonts() );
	}

	/**
	 * A model whose bundled source accepts the fixture as a legacy Arimo copy
	 */
	protected function get_model_with_legacy( string $fixture ): Model_Font_Sources {
		$path = self::FIXTURES . $fixture;
		$size = (int) filesize( $path );
		$sha  = sha1( 'blob ' . $size . "\0" . file_get_contents( $path ) );

		return new class( GPDFAPI::get_data_class(), $this->custom_fonts, GPDFAPI::get_log_class(), [ $sha => $size ] ) extends Model_Font_Sources {
			protected $legacy;

			public function __construct( $data, $custom_fonts, $log, array $legacy ) {
				parent::__construct( $data, $custom_fonts, $log );
				$this->legacy = $legacy;
			}

			protected function get_bundled_source(): array {
				return array_merge( parent::get_bundled_source(), [ 'legacy' => $this->legacy ] );
			}
		};
	}

	protected function core_fontdata(): array {
		return ( new FontVariables() )->getDefaults()['fontdata'];
	}

	protected function copy_fixture( string $fixture, string $name = '' ): void {
		$target = $this->font_dir . ( $name !== '' ? $name : $fixture );
		copy( self::FIXTURES . $fixture, $target );
		$this->copied[] = $target;
	}

	/**
	 * Save a custom font record as a template's setUp() would, bypassing the unique ID rule
	 */
	protected function add_record( string $id, array $files ): void {
		$font = [
			'id'          => $id,
			'font_name'   => $id,
			'regular'     => '',
			'italics'     => '',
			'bold'        => '',
			'bolditalics' => '',
		];

		foreach ( $files as $style => $file ) {
			$font[ $style ] = $this->font_dir . $file;
		}

		$this->custom_fonts->add_font( $font );
	}
}
