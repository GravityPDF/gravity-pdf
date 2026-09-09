<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF_Vendor\Mpdf\Fonts\FontFileFinder;
use GFPDF_Vendor\Mpdf\MpdfException;
use GFPDF_Vendor\Psr\Log\LoggerInterface;

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
 * What happens when a font row's file is not on disk
 *
 * The tables are authoritative on the load path, so nothing stats a font file before mPDF asks for it — which makes
 * `AddFont()` the first and only place an FTP delete or a half-restored backup can be noticed. Flagging the row here
 * and handing back a bundled face keeps the render that found the problem going, and leaves the Font Manager, the
 * health check and the System Report a `missing` row to report without a second pass.
 *
 * The substituted render is the *worst* document of the sequence, not a repaired one: Arimo does not carry a pack's
 * glyphs, so anything that needed them falls to mPDF's per-character substitution. The next render is the real
 * recovery — the face is flagged, `Registry::installed_package()` drops the key, and the font family resolves
 * through the fallback chain that does have the glyphs.
 *
 * Only the row whose file was asked for: a directory scan is `Font_Repository::verify()`'s job, behind the Font
 * Manager's action and the daily health run, where the cost is paid once rather than once per render.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Font_File_Finder extends FontFileFinder {

	/**
	 * @var Font_Repository
	 * @since 7.0
	 */
	protected $repository;

	/**
	 * @var array<string, string> The bundled font's faces, keyed by role
	 * @since 7.0
	 */
	protected $bundled;

	/**
	 * @var LoggerInterface
	 * @since 7.0
	 */
	protected $log;

	/**
	 * @var FontFileFinder|object|null
	 * @since 7.0
	 */
	protected $inner;

	/**
	 * The directories are mPDF's to set: it calls `setDirectories()` for each package layer it reads
	 *
	 * @param array<string, string> $bundled The substitute for each role, from the bundled package
	 * @param object|null           $inner   A finder an add-on put in the container, which this one wraps rather
	 *                                       than replaces
	 *
	 * @since 7.0
	 */
	public function __construct( Font_Repository $repository, array $bundled, LoggerInterface $log, $inner = null ) {
		parent::__construct( [] );

		$this->repository = $repository;
		$this->bundled    = $bundled;
		$this->log        = $log;
		$this->inner      = $inner;
	}

	/**
	 * @param array|string $directories
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	public function setDirectories( $directories ) {
		parent::setDirectories( $directories );

		if ( $this->inner !== null ) {
			$this->inner->setDirectories( $directories );
		}
	}

	/**
	 * @param string $name The filename a font row records, relative to one of the font directories
	 *
	 * @return string
	 *
	 * @throws MpdfException When the bundled face is gone too.
	 *
	 * @since 7.0
	 */
	public function findFontFile( $name ) {
		$path = (string) $name;

		try {
			return $this->inner !== null ? $this->inner->findFontFile( $path ) : parent::findFontFile( $path );
		} catch ( MpdfException $e ) {
			$row        = $this->repository->file_for_path( $path );
			$substitute = $this->bundled[ $row['role'] ?? '' ] ?? $this->bundled['R'];

			/* Resolved before anything is flagged, so a vanished bundled face rethrows rather than recording a lie */
			$found = parent::findFontFile( $substitute );

			/* A path no row records — a bundled face, a directory an add-on added — matches nothing and writes nothing */
			$this->repository->set_missing( $path, true );

			$this->log->error(
				'Substituted the bundled font for a font file that is no longer on disk',
				[
					'font'       => $row['font_key'] ?? '',
					'path'       => $path,
					'substitute' => $substitute,
				]
			);

			return $found;
		}
	}
}
