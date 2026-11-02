<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Concerns;

use GFPDF\Model\Model_Core_Font_Downloader;
use GFPDF\Model\Model_Required_Fonts;
use GPDFAPI;
use GFPDF_Vendor\Psr\Log\LoggerInterface;

/**
 * A downloader with its seams set: `sources`, `budget` (seconds), `jitter` (seconds) and `required` files
 */
class Seamed_Core_Font_Downloader extends Model_Core_Font_Downloader {

	/**
	 * The FreeSerif family's files on the core manifest
	 */
	const FREESERIF = [ 'FreeSerif.ttf', 'FreeSerifBold.ttf', 'FreeSerifItalic.ttf', 'FreeSerifBoldItalic.ttf' ];

	public function __construct( LoggerInterface $log, array $seams = [] ) {
		$sources  = $seams['sources'] ?? GPDFAPI::get_mvc_class( 'Model_Font_Sources' );
		$required = GPDFAPI::get_mvc_class( 'Model_Required_Fonts' );

		if ( isset( $seams['required'] ) ) {
			$required        = new class( $sources, GPDFAPI::get_options_class() ) extends Model_Required_Fonts {
				/** @var string[] */
				public $files = [];

				public function get_required_font_files(): array {
					return $this->files;
				}
			};
			$required->files = $seams['required'];
		}

		parent::__construct( $sources, GPDFAPI::get_data_class(), GPDFAPI::get_options_class(), GPDFAPI::get_misc_class(), $log, $required );

		$this->sync_budget  = $seams['budget'] ?? $this->sync_budget;
		$this->retry_jitter = $seams['jitter'] ?? $this->retry_jitter;
	}

	/**
	 * Change the required files a `required` seam set
	 *
	 * @param string[] $files
	 */
	public function set_required( array $files ): void {
		$this->required_fonts->files = $files;
	}
}
