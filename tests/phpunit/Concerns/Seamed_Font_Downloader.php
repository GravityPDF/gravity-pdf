<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Concerns;

use GFPDF\Model\Model_Font_Downloader;
use GFPDF\Model\Model_Font_Sources;
use GFPDF\Model\Model_Required_Fonts;
use GPDFAPI;
use GFPDF_Vendor\Psr\Log\LoggerInterface;

/**
 * A downloader with its seams set: `sources`, `budget` (seconds), `jitter` (seconds) and `required` files
 */
class Seamed_Font_Downloader extends Model_Font_Downloader {

	/**
	 * Build the downloader with the real dependencies, overriding the seams given
	 *
	 * @param array{sources?: Model_Font_Sources, budget?: int|float, jitter?: int, required?: string[]} $seams
	 */
	public function __construct( LoggerInterface $log, array $seams = [] ) {
		$sources  = $seams['sources'] ?? GPDFAPI::get_mvc_class( 'Model_Font_Sources' );
		$required = new class( $sources, GPDFAPI::get_options_class(), $log ) extends Model_Required_Fonts {
			/** @var string[]|null The required files, else the real ones */
			public $files;

			public function get_required_font_files(): array {
				return $this->files ?? parent::get_required_font_files();
			}
		};

		$required->files = $seams['required'] ?? null;

		parent::__construct( $sources, GPDFAPI::get_data_class(), GPDFAPI::get_options_class(), GPDFAPI::get_misc_class(), $log, $required );

		$this->sync_budget  = $seams['budget'] ?? $this->sync_budget;
		$this->retry_jitter = $seams['jitter'] ?? $this->retry_jitter;
	}

	/**
	 * Change the required files
	 *
	 * @param string[] $files
	 */
	public function set_required( array $files ): void {
		$this->required_fonts->files = $files;
	}
}
