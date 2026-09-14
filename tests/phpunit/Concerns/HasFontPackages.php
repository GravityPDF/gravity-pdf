<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Concerns;

use GFPDF\Fonts\Font_Package_Importer;
use ZipArchive;

/**
 * The offline package the release pipeline publishes, built here
 *
 * One archive layout for every suite that imports one, because it is the pipeline's shape and not any one test's:
 * bare filenames at the zip root, `entry.json` beside them, and a `package.path` naming `{entry}-{version}.zip`
 * (§4.3 Hosting). A second hand-rolled copy would keep passing while the real artefact moved.
 *
 * Pairs with `HasCatalogRows` for the row the import verifies against, and with `HasFontFixtures` for the bytes.
 */
trait HasFontPackages {

	use HasFontFixtures;

	/**
	 * @var string[] Archives written by this test, unlinked by `remove_packages()`
	 */
	protected $packages = [];

	/**
	 * The entry object, as both the catalogue row and the archive carry it — one document, which is the point
	 *
	 * Not `entry()`: `HasGfpdfFixtures` has one, with another signature.
	 */
	protected function package_entry(): array {
		$bytes = $this->font_bytes( 'DejaVuSansSymbols' );

		return [
			'fonts'   => [ 'notoemoji' => [ 'R' => 'Noto.ttf' ] ],
			'files'   => [
				'Noto.ttf' => [
					'sha256'      => hash( 'sha256', $bytes ),
					'size'        => strlen( $bytes ),
					'remote_path' => 'fonts-v1.0.0/Noto.ttf',
				],
			],
			'package' => [ 'path' => 'emoji-fonts-v1.0.0.zip' ],
		];
	}

	/**
	 * An archive of whatever members a test wants, the pipeline's layout by default
	 *
	 * @param array<string, string> $members `{ name: bytes }`
	 */
	protected function package_archive( ?array $members = null ): string {
		$members = $members ?? [
			Font_Package_Importer::MANIFEST => (string) wp_json_encode( $this->package_entry() ),
			'Noto.ttf'                      => $this->font_bytes( 'DejaVuSansSymbols' ),
		];

		$path = wp_tempnam( 'gfpdf-package.zip' );
		$zip  = new ZipArchive();

		$zip->open( $path, ZipArchive::CREATE | ZipArchive::OVERWRITE );

		foreach ( $members as $name => $bytes ) {
			$zip->addFromString( (string) $name, (string) $bytes );
		}

		$zip->close();

		$this->packages[] = $path;

		return $path;
	}

	protected function remove_packages(): void {
		foreach ( $this->packages as $package ) {
			@unlink( $package ); //phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		$this->packages = [];
	}
}
