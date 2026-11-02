<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Concerns;

use GFPDF\Helper\Fonts\FlushCache;
use GFPDF\Model\Model_Font_Sources;
use GPDFAPI;

/**
 * Copies font fixtures into the PDF fonts folder and saves custom font records for them. Call
 * remove_font_fixtures() from tear_down().
 */
trait CopiesFontFixtures {

	/**
	 * @var string[]
	 */
	protected $copied_fonts = [];

	/**
	 * @var string[]
	 */
	protected $recorded_fonts = [];

	protected function font_fixture( string $file ): string {
		return PDF_PLUGIN_DIR . 'tools/phpunit/data/fonts/' . $file;
	}

	protected function copy_fixture( string $fixture, string $name = '' ): void {
		$target = GPDFAPI::get_data_class()->template_font_location . ( $name !== '' ? $name : $fixture );
		copy( $this->font_fixture( $fixture ), $target );
		$this->copied_fonts[] = $target;
	}

	/**
	 * Save a custom font record as a template's setUp() would, bypassing the unique ID rule, then flush its metrics as
	 * saving one in Font Manager does
	 *
	 * @param array<string, string> $files Style (regular, bold, …) => file name in the fonts folder
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
			$font[ $style ] = GPDFAPI::get_data_class()->template_font_location . $file;
		}

		GPDFAPI::get_mvc_class( 'Model_Custom_Fonts' )->add_font( $font );
		FlushCache::flush( $id );
		$this->recorded_fonts[] = $id;
	}

	/**
	 * A model whose bundled source accepts the fixture as a legacy Arimo copy
	 */
	protected function get_model_with_legacy( string $fixture ): Model_Font_Sources {
		$path = $this->font_fixture( $fixture );
		$size = (int) filesize( $path );

		$model = new class( GPDFAPI::get_data_class(), GPDFAPI::get_mvc_class( 'Model_Custom_Fonts' ), GPDFAPI::get_log_class() ) extends Model_Font_Sources {
			/** @var array<string, int> */
			public $legacy = [];

			protected function get_bundled_source(): array {
				return array_merge( parent::get_bundled_source(), [ 'legacy' => $this->legacy ] );
			}
		};

		$model->legacy = [ sha1( 'blob ' . $size . "\0" . file_get_contents( $path ) ) => $size ];

		return $model;
	}

	/**
	 * Flushing the metrics of a key a test re-pointed keeps a later render clear of mPDF reading back stale metrics
	 * (GravityPDF/mpdf#825)
	 */
	protected function remove_font_fixtures(): void {
		array_map( 'wp_delete_file', $this->copied_fonts );
		GPDFAPI::delete_plugin_option( 'custom_fonts' );
		array_map( [ FlushCache::class, 'flush' ], array_unique( $this->recorded_fonts ) );

		$this->copied_fonts   = [];
		$this->recorded_fonts = [];
	}
}
