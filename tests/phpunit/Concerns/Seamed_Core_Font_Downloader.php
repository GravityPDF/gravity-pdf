<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Concerns;

use GFPDF\Model\Model_Core_Font_Downloader;
use GPDFAPI;
use GFPDF_Vendor\Psr\Log\LoggerInterface;

/**
 * A downloader with its seams set: `sources`, `manifest`, `budget` (seconds), `jitter` (seconds) and `required` files
 */
class Seamed_Core_Font_Downloader extends Model_Core_Font_Downloader {

	/**
	 * The FreeSerif family's files on the core manifest
	 */
	const FREESERIF = [ 'FreeSerif.ttf', 'FreeSerifBold.ttf', 'FreeSerifItalic.ttf', 'FreeSerifBoldItalic.ttf' ];

	/**
	 * @var string[]|null
	 */
	public $required;

	public function __construct( LoggerInterface $log, array $seams = [] ) {
		parent::__construct(
			$seams['sources'] ?? GPDFAPI::get_mvc_class( 'Model_Font_Sources' ),
			GPDFAPI::get_data_class(),
			GPDFAPI::get_options_class(),
			GPDFAPI::get_misc_class(),
			$log
		);

		$this->manifest     = $seams['manifest'] ?? null;
		$this->sync_budget  = $seams['budget'] ?? $this->sync_budget;
		$this->retry_jitter = $seams['jitter'] ?? $this->retry_jitter;
		$this->required     = $seams['required'] ?? null;
	}

	public function get_required_font_files(): array {
		return $this->required ?? parent::get_required_font_files();
	}
}
