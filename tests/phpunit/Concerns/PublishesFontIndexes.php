<?php

declare(strict_types=1);

namespace GFPDF\Tests\Concerns;

use GFPDF\Fonts\Catalog_Font_Adopter;
use GFPDF\Fonts\Catalog_Sync;
use GFPDF\Fonts\Font_Downloader;
use GFPDF\Fonts\Font_Lock;

/**
 * Build and serve a signed font root plus the source index it names.
 *
 * The signing half of the release pipeline lives in the update-server repo, so this is the only place the plugin's
 * side of the contract is exercised end to end: the context prefix, the detached signature, the root's hash of the
 * source index, and the layout the sync fetches them from.
 *
 * Consumers must also use `MocksHttpRequests` and `HasCatalogRows`.
 */
trait PublishesFontIndexes {

	/**
	 * @var string base64 ed25519 public key
	 */
	public $public_key = '';

	/**
	 * @var string Raw ed25519 secret key
	 */
	private $secret_key = '';

	/**
	 * @var string
	 */
	public $root = '';

	/**
	 * @var string The source index the last publish() built, so a test can corrupt it without changing its length
	 */
	public $last_index = '';

	/**
	 * A throwaway keypair for one test, so nothing is shared between cases
	 */
	protected function generate_font_signing_key(): void {
		$pair = sodium_crypto_sign_keypair();

		$this->public_key = base64_encode( sodium_crypto_sign_publickey( $pair ) );
		$this->secret_key = sodium_crypto_sign_secretkey( $pair );
		$this->root       = trailingslashit( GPDF_FONTS_URL );
	}

	/**
	 * One valid coverage entry
	 *
	 * Not `entry()`: `HasGfpdfFixtures` has one with a different signature, and overriding it is a class-load fatal
	 * PHPUnit reports as a bare exit 255.
	 */
	protected function pack_entry( string $id, array $overrides = [] ): array {
		return array_merge(
			[
				'id'       => $id,
				'label'    => ucfirst( $id ),
				'version'  => 'fonts-v1.0.0',
				'coverage' => true,
				'license'  => 'OFL-1.1',
				'size'     => 1024,
				'files'    => 1,
				'always'   => false,
				'scripts'  => [ 'und-Zsye' ],
				'entry'    => [
					'fonts' => [ $id . 'font' => [ 'R' => 'A.ttf' ] ],
					'files' => [
						'A.ttf' => [
							'sha256'      => str_repeat( 'a', 64 ),
							'size'        => 1024,
							'remote_path' => 'fonts-v1.0.0/A.ttf',
						],
					],
				],
			],
			$overrides
		);
	}

	/**
	 * Publish a signed root plus the source index it names, and mock both
	 *
	 * @param array $entries The `packs` index entries
	 *
	 * @return string The index's SHA-256, which is what the root commits to
	 */
	protected function publish( array $entries, array $root_overrides = [], array $mock_overrides = [] ): string {
		$index            = (string) wp_json_encode( [ 'schema' => 1, 'entries' => $entries ] );
		$index_hash       = hash( 'sha256', $index );
		$this->last_index = $index;

		$root = (string) wp_json_encode(
			array_merge(
				[
					'schema'    => 1,
					'generated' => gmdate( 'Y-m-d\TH:i:s\Z' ),
					'sources'   => [
						'packs' => [
							'sha256' => $index_hash,
							'size'   => strlen( $index ),
						],
					],
				],
				$root_overrides
			)
		);

		$this->mock_http(
			array_merge(
				[
					'index.json.sig' => base64_encode( sodium_crypto_sign_detached( Catalog_Sync::SIGNATURE_CONTEXT . $root, $this->secret_key ) ),
					'index.json'     => $root,
					'sources/packs-' => $index,
				],
				$mock_overrides
			)
		);

		return $index_hash;
	}

	/**
	 * A sync wired to this test's throwaway key rather than the build's `GPDF_TRUST_KEYS`
	 *
	 * @param array|null $trust_keys Pass `[]` to assert the fail-closed path
	 */
	protected function sync( ?array $trust_keys = null, string $seed_file = '' ): Catalog_Sync {
		global $gfpdf;

		return new Catalog_Sync(
			$gfpdf->get_font_repository()->get_schema(),
			$this->catalog_repository(),
			$gfpdf->get_font_sources(),
			new Font_Downloader( \GPDFAPI::get_log_class() ),
			new Font_Lock(),
			\GPDFAPI::get_log_class(),
			new Catalog_Font_Adopter( $gfpdf->get_font_repository(), $this->catalog_repository(), \GPDFAPI::get_log_class() ),
			$trust_keys === null ? [ $this->public_key ] : $trust_keys,
			$seed_file
		);
	}
}
