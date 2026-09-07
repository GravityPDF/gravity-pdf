<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use GFPDF_Vendor\Mpdf\Fonts\FontRegistration;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One mPDF font package layer
 *
 * Two instances are registered: the bundled fonts that ship in the plugin, and every row in the font tables. They
 * differ only by data, which is why this is one class rather than two — `FontRegistry::add()` keys by `getId()`, so
 * two instances of the same class no longer collapse into one entry.
 *
 * `add()` prepends, and mPDF reads the registry in that order, so the layer added *last* is read first. `Registry`
 * adds installed then bundled: a key both claim resolves to the bundled file, and no downloaded font can replace
 * one that ships with the plugin.
 *
 * @package GFPDF\Helper\Fonts
 *
 * @since 7.0
 */
class Package extends FontRegistration {

	/**
	 * @var string
	 * @since 7.0
	 */
	protected $id;

	/**
	 * @var string
	 * @since 7.0
	 */
	protected $directory;

	/**
	 * @var array
	 * @since 7.0
	 */
	protected $fonts;

	/**
	 * @var string[]
	 * @since 7.0
	 */
	protected $backup_subs;

	/**
	 * @var array
	 * @since 7.0
	 */
	protected $substitution;

	/**
	 * @var array<string, string>
	 * @since 7.0
	 */
	protected $aliases;

	/**
	 * @var string[]
	 * @since 7.0
	 */
	protected $bmp;

	/**
	 * @var array<string, string>
	 * @since 7.0
	 */
	protected $dictionaries;

	public function __construct(
		string $id,
		string $directory,
		array $fonts,
		array $backup_subs = [],
		array $substitution = [],
		array $aliases = [],
		array $bmp = [],
		array $dictionaries = []
	) {
		$this->id           = $id;
		$this->directory    = $directory;
		$this->fonts        = $fonts;
		$this->backup_subs  = $backup_subs;
		$this->substitution = $substitution;
		$this->aliases      = $aliases;
		$this->bmp          = $bmp;
		$this->dictionaries = $dictionaries;
	}

	/**
	 * @since 7.0
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * A string, not an array: mPDF appends each package's directory to `fontDir` in the order it reads them
	 *
	 * @since 7.0
	 */
	public function getDirectory() {
		return $this->directory;
	}

	/**
	 * @since 7.0
	 */
	public function getFonts() {
		return $this->fonts;
	}

	/**
	 * @since 7.0
	 */
	public function getBackupSubsFonts() {
		return $this->backup_subs;
	}

	/**
	 * @since 7.0
	 */
	public function getFontFamilySubstitution() {
		return $this->substitution;
	}

	/**
	 * @since 7.0
	 */
	public function getFontAliases() {
		return $this->aliases;
	}

	/**
	 * @since 7.0
	 */
	public function getBmpFonts() {
		return $this->bmp;
	}

	/**
	 * Always empty
	 *
	 * The language map is not a per-layer concern: `Registry` computes one effective map and `Helper_PDF` adds it
	 * to the `LanguageToFontRegistry` after mPDF is constructed, so it is consulted before any third party's.
	 *
	 * @since 7.0
	 */
	public function getLanguageToFont() {
		return [];
	}

	/**
	 * @since 7.0
	 */
	public function getLineBreakDictionaries() {
		return $this->dictionaries;
	}
}
