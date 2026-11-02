<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Integration;

use GFPDF\Helper\Fonts\FlushCache;
use GFPDF\Model\Model_Custom_Fonts;
use GFPDF\Model\Model_Font_Sources;
use GFPDF\Tests\Concerns\AssertsSnapshots;
use GFPDF_Vendor\Mpdf\Mpdf;
use GPDFAPI;
use RuntimeException;
use WP_Error;

/**
 * Golden files of the fonts a rendered PDF embeds and the characters each font file draws
 *
 * @group snapshot
 * @group fonts
 */
class Test_Font_Rendering extends TestCase {

	use AssertsSnapshots;

	const FIXTURES = PDF_PLUGIN_DIR . 'tools/phpunit/data/fonts/';

	/**
	 * @var string[]
	 */
	protected $copied = [];

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
		wp_mkdir_p( GPDFAPI::get_data_class()->template_font_location );
		static::copy_test_fonts();
	}

	public static function tear_down_after_class(): void {
		static::remove_test_fonts();
		parent::tear_down_after_class();
	}

	public function tear_down(): void {
		array_map( 'wp_delete_file', $this->copied );
		GPDFAPI::delete_plugin_option( 'custom_fonts' );
		FlushCache::flush( 'arimo' );

		parent::tear_down();
	}

	public function test_bundled_arimo_renders_every_style(): void {
		$this->assertRenderMatchesSnapshot(
			'arimo-styles',
			'<p style="font-family: arimo">Regular <b>Bold</b> <i>Italic</i> <b><i>Bold Italic</i></b></p>'
		);
	}

	public function test_characters_arimo_lacks_are_substituted(): void {
		$this->assertRenderMatchesSnapshot( 'arimo-substitution', '<p style="font-family: arimo">Agree ✔ Decline ✖</p>' );
	}

	public function test_consent_ticks_render_from_the_bundled_symbols(): void {
		$this->assertRenderMatchesSnapshot(
			'consent-ticks',
			'<p style="font-family: arimo"><span style="font-family:gfpdf-dejavu-symbols,sans-serif;">&#10004;</span> Agree <span style="font-family:gfpdf-dejavu-symbols,sans-serif;">&#10006;</span> Decline</p>'
		);
	}

	public function test_a_core_font_renders_from_the_fonts_folder(): void {
		$this->assertRenderMatchesSnapshot( 'core-font', '<p style="font-family: dejavusans">Regular <b>Bold</b></p>' );
	}

	public function test_a_custom_font_overrides_the_bundled_arimo(): void {
		$this->copy_fixture( 'Chewy.ttf' );
		$this->add_record( 'arimo', 'Chewy.ttf' );

		$this->assertRenderMatchesSnapshot( 'custom-arimo', '<p style="font-family: arimo">Custom</p>' );
	}

	public function test_an_adopted_template_arimo_record_renders_the_bundled_faces(): void {
		$this->copy_fixture( 'Chewy.ttf', 'Arimo-Regular.ttf' );
		$this->add_record( 'arimo', 'Arimo-Regular.ttf' );

		$this->assertSame( [ 'arimo' ], $this->get_model_with_legacy( 'Chewy.ttf' )->adopt_records( 'bundled' ) );
		$this->assertRenderMatchesSnapshot( 'adopted-arimo', '<p style="font-family: arimo">Adopted</p>' );
	}

	/**
	 * Render the HTML through the PDF pipeline and compare its fonts with the snapshot
	 */
	protected function assertRenderMatchesSnapshot( string $name, string $html ): void {
		$this->assertMatchesSnapshot( "fonts/$name.txt", $this->describe_fonts( $this->render( $html ) ) );
	}

	/**
	 * Render the fixture entry's PDF with the HTML swapped in, and stop once mPDF has written it
	 *
	 * @return array{mpdf: Mpdf, pdf: string}
	 */
	protected function render( string $html ): array {
		$form  = $this->form( 'all-form-fields' );
		$entry = $this->entry( 'all-form-fields' );

		$capture = new class() extends RuntimeException {
			/** @var array{mpdf: Mpdf, pdf: string} */
			public $result;
		};

		$swap = static function () use ( $html ): string {
			return $html;
		};

		$stop = static function ( $mpdf ) use ( $capture ): void {
			$capture->result = [
				'mpdf' => $mpdf,
				'pdf'  => $mpdf->Output( '', 'S' ),
			];

			throw $capture;
		};

		add_filter( 'gfpdf_pdf_html_output_' . $form['id'], $swap, PHP_INT_MAX );
		add_action( 'gfpdf_pre_pdf_generation_output', $stop, PHP_INT_MAX );

		$settings = array_merge( $form['gfpdf_form_settings']['555ad84787d7e'], [ 'template' => 'zadani' ] );
		$result   = GPDFAPI::get_mvc_class( 'Model_PDF' )->generate_and_save_pdf( $entry, $settings, true );

		remove_filter( 'gfpdf_pdf_html_output_' . $form['id'], $swap, PHP_INT_MAX );
		remove_action( 'gfpdf_pre_pdf_generation_output', $stop, PHP_INT_MAX );

		$this->assertInstanceOf( WP_Error::class, $result, 'The render should have been stopped' );
		$this->assertNotNull( $capture->result, 'The PDF failed before it was written: ' . $result->get_error_message() );

		return $capture->result;
	}

	/**
	 * The fonts the PDF embeds, then one line per font mPDF used: its key, its file and any non-ASCII characters it
	 * drew (mPDF adds all of printable ASCII to every subset)
	 *
	 * @param array{mpdf: Mpdf, pdf: string} $render
	 */
	protected function describe_fonts( array $render ): string {
		/* Strip the random six-letter subset tag */
		preg_match_all( '#/BaseFont /(?:[A-Z]{6}\+)?([^\s/]+)#', $render['pdf'], $matches );
		$embedded = array_unique( $matches[1] );
		sort( $embedded );

		$lines = [ 'BaseFont: ' . implode( ', ', $embedded ) ];
		foreach ( $render['mpdf']->fonts as $key => $font ) {
			if ( empty( $font['used'] ) ) {
				continue;
			}

			$codes = array_filter(
				array_keys( $font['subset'] ?? [] ),
				function ( $code ) {
					return $code > 127;
				}
			);
			sort( $codes );

			$lines[] = rtrim( sprintf( '%s: %s %s', $key, basename( $font['ttffile'] ), implode( '', array_map( 'mb_chr', $codes ) ) ) );
		}

		sort( $lines );

		return implode( "\n", $lines ) . "\n";
	}

	/**
	 * A model whose bundled source accepts the fixture as a legacy Arimo copy
	 */
	protected function get_model_with_legacy( string $fixture ): Model_Font_Sources {
		$path = self::FIXTURES . $fixture;
		$size = (int) filesize( $path );
		$sha  = sha1( 'blob ' . $size . "\0" . file_get_contents( $path ) );

		return new class( GPDFAPI::get_data_class(), GPDFAPI::get_mvc_class( 'Model_Custom_Fonts' ), GPDFAPI::get_log_class(), [ $sha => $size ] ) extends Model_Font_Sources {
			protected $legacy;

			public function __construct( $data, Model_Custom_Fonts $custom_fonts, $log, array $legacy ) {
				parent::__construct( $data, $custom_fonts, $log );
				$this->legacy = $legacy;
			}

			protected function get_bundled_source(): array {
				return array_merge( parent::get_bundled_source(), [ 'legacy' => $this->legacy ] );
			}
		};
	}

	protected function copy_fixture( string $fixture, string $name = '' ): void {
		$target = GPDFAPI::get_data_class()->template_font_location . ( $name !== '' ? $name : $fixture );
		copy( self::FIXTURES . $fixture, $target );
		$this->copied[] = $target;
	}

	/**
	 * Save a custom font record as a template's setUp() would, bypassing the unique ID rule, and flush its metrics as
	 * saving one in Font Manager does
	 */
	protected function add_record( string $id, string $regular ): void {
		GPDFAPI::get_mvc_class( 'Model_Custom_Fonts' )->add_font(
			[
				'id'          => $id,
				'font_name'   => $id,
				'regular'     => GPDFAPI::get_data_class()->template_font_location . $regular,
				'italics'     => '',
				'bold'        => '',
				'bolditalics' => '',
			]
		);

		FlushCache::flush( $id );
	}
}
