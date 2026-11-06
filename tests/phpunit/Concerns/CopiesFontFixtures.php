<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Concerns;

use GFPDF\Helper\Fonts\FlushCache;
use GFPDF\Model\Model_Custom_Fonts;
use GFPDF\Model\Model_Font_Sources;
use GFPDF\Statics\Git_Blob;
use GPDFAPI;
use WP_Error;

/**
 * Copies font fixtures into the PDF fonts folder, saves custom font records for them and serves them as font downloads.
 * Call remove_font_fixtures() from tear_down().
 */
trait CopiesFontFixtures {

	use AccessesPrivateState;

	/**
	 * @var string[] The FreeSerif family's files on the core manifest
	 */
	protected static $freeserif = [ 'FreeSerif.ttf', 'FreeSerifBold.ttf', 'FreeSerifItalic.ttf', 'FreeSerifBoldItalic.ttf' ];

	/**
	 * @var string[] The core fonts with a fixture
	 */
	protected static $core_fixtures = [ 'DejaVuSans.ttf', 'DejaVuSans-Bold.ttf', 'DejaVuSansCondensed.ttf', 'DejaVuSerifCondensed.ttf' ];

	/**
	 * @var string[]
	 */
	protected $copied_fonts = [];

	/**
	 * @var string[]
	 */
	protected $recorded_fonts = [];

	/**
	 * @var string[] URLs that reached pre_http_request
	 */
	protected $requested = [];

	/**
	 * @var array<string, string> Manifest file name => the fixture served as it
	 */
	protected $served_as = [];

	/**
	 * @var bool Whether a test changed the manifest
	 */
	protected $manifest_changed = false;

	protected function font_fixture( string $file ): string {
		return PDF_PLUGIN_DIR . 'tools/phpunit/data/fonts/' . $file;
	}

	/**
	 * Where a file sits in the fonts folder
	 */
	protected function font_path( string $file ): string {
		return GPDFAPI::get_data_class()->template_font_location . $file;
	}

	/**
	 * @param string $name The file name in the fonts folder, or '' for the fixture's
	 */
	protected function copy_fixture( string $fixture, string $name = '' ): void {
		$target = $this->font_path( $name !== '' ? $name : $fixture );
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
			$font[ $style ] = $this->font_path( $file );
		}

		GPDFAPI::get_mvc_class( 'Model_Custom_Fonts' )->add_font( $font );
		FlushCache::flush( $id );
		GPDFAPI::get_mvc_class( 'Model_Font_Sources' )->flush();
		$this->recorded_fonts[] = $id;
	}

	/**
	 * A model whose bundled source accepts the fixture as a legacy Arimo copy
	 */
	protected function get_model_with_legacy( string $fixture, ?Model_Custom_Fonts $custom_fonts = null ): Model_Font_Sources {
		$model = new class( GPDFAPI::get_data_class(), $custom_fonts ?? GPDFAPI::get_mvc_class( 'Model_Custom_Fonts' ), GPDFAPI::get_log_class() ) extends Model_Font_Sources {
			/** @var array<string, int> */
			public $legacy = [];

			protected function get_bundled_source(): array {
				return array_merge( parent::get_bundled_source(), [ 'legacy' => $this->legacy ] );
			}
		};

		$model->legacy = $this->fixture_blob( $fixture );

		return $model;
	}

	/**
	 * @return array<string, int> The fixture's git blob SHA-1 => size
	 */
	protected function fixture_blob( string $fixture ): array {
		/* The fixtures never change, so each is hashed once per run */
		static $blobs = [];

		if ( ! isset( $blobs[ $fixture ] ) ) {
			$path = $this->font_fixture( $fixture );
			$size = (int) filesize( $path );

			$blobs[ $fixture ] = [ Git_Blob::sha1( $path, $size ) => $size ];
		}

		return $blobs[ $fixture ];
	}

	/**
	 * A manifest entry whose size and hash are the fixture's
	 *
	 * @return array{url: string, size: int, sha: string}
	 */
	protected function manifest_entry( string $fixture, string $url ): array {
		$blob = $this->fixture_blob( $fixture );

		return [
			'url'  => $url,
			'size' => current( $blob ),
			'sha'  => (string) key( $blob ),
		];
	}

	/**
	 * Answer every font download with the response. A 200 streams the URL's fixture font (changed by $change) into it.
	 *
	 * @param array|WP_Error|null $response Defaults to a 200
	 * @param callable|null       $change   Given the downloaded file's handle to alter it
	 */
	protected function serve_font_downloads( $response = null, ?callable $change = null ): void {
		$response = $response ?? $this->http_response( 200 );

		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) use ( $response, $change ) {
				$this->requested[] = $url;

				if ( is_wp_error( $response ) || empty( $args['filename'] ) ) {
					return $response;
				}

				$file    = basename( $url );
				$fixture = $this->served_as[ $file ] ?? $file;
				if ( is_file( $this->font_fixture( $fixture ) ) ) {
					copy( $this->font_fixture( $fixture ), $args['filename'] );
					$this->copied_fonts[] = $this->font_path( $file );

					/* A file saved beside another font's has its hashed name */
					$this->copied_fonts[] = $this->font_path( GPDFAPI::get_mvc_class( 'Model_Font_Sources' )->get_hashed_name( $file ) );
				}

				if ( $change !== null ) {
					$handle = fopen( $args['filename'], 'r+' );
					$change( $handle );
					fclose( $handle );
				}

				return $response;
			},
			10,
			3
		);
	}

	/**
	 * Put another font's file at a manifest name, one of a different size unless $same_size, then a copy of the fixture
	 * with a byte changed to be the same size as the fixture but not its hash
	 *
	 * @param string $name     The manifest file name
	 * @param string $fixture  The fixture the manifest lists for it
	 * @param bool   $same_size
	 */
	protected function put_other_font_at( string $name, string $fixture, bool $same_size = false ): void {
		if ( ! $same_size ) {
			$this->copy_fixture( 'Chewy.ttf', $name );

			return;
		}

		$bytes = (string) file_get_contents( $this->font_fixture( $fixture ) );
		$last  = strlen( $bytes ) - 1;

		$bytes[ $last ] = $bytes[ $last ] === 'x' ? 'y' : 'x';
		file_put_contents( $this->font_path( $name ), $bytes );
		$this->copied_fonts[] = $this->font_path( $name );
	}

	/**
	 * Put a renderable copy of the fixture at a manifest name that is one byte longer, so a size check finds it isn't
	 * the manifest's
	 */
	protected function put_longer_font_at( string $name, string $fixture ): void {
		file_put_contents( $this->font_path( $name ), (string) file_get_contents( $this->font_fixture( $fixture ) ) . "\0" );
		$this->copied_fonts[] = $this->font_path( $name );
	}

	/**
	 * Serve a fixture as a manifest file, with a manifest entry matching the fixture so the plugin's downloader verifies it
	 */
	protected function serve_fixture_as( string $fixture, string $file ): void {
		$this->list_fixture_as( $fixture, $file );
		$this->served_as[ $file ] = $fixture;
	}

	/**
	 * List the fixture as the manifest file's build, so a copy of it at that name is the font's own and not another
	 * font's, which the plugin would save the real one beside
	 */
	protected function list_fixture_as( string $fixture, string $file ): void {
		$manifest = GPDFAPI::get_mvc_class( 'Model_Font_Sources' )->get_manifest();

		$manifest[ $file ] = $this->manifest_entry( $fixture, $manifest[ $file ]['url'] ?? 'https://example.com/' . $file );

		$this->set_shared_manifest( $manifest );
		$this->manifest_changed = true;
	}

	/**
	 * Stand fixtures in for the files the removed Core Font installer downloaded
	 *
	 * @param array<string, string>|null $files File name => fixture, else each core font fixture as itself
	 */
	protected function set_installer_manifest( ?array $files = null ): void {
		$manifest = [];
		foreach ( $files ?? array_combine( static::$core_fixtures, static::$core_fixtures ) as $file => $fixture ) {
			$manifest[ $file ] = $this->manifest_entry( $fixture, 'https://example.com/' . $file );
		}

		$this->set_private( GPDFAPI::get_mvc_class( 'Model_Font_Sources' ), 'legacy_manifests', [ 'core' => $manifest ] );
	}

	/**
	 * @param array|null $manifest Null to load the real manifest again
	 */
	private function set_shared_manifest( ?array $manifest ): void {
		$this->set_private( GPDFAPI::get_mvc_class( 'Model_Font_Sources' ), 'manifest', $manifest );
	}

	/**
	 * Fail every font download as if offline
	 */
	protected function fail_font_downloads(): void {
		$this->serve_font_downloads( new WP_Error( 'http_request_failed', 'Offline' ) );
	}

	/**
	 * A pre_http_request response with the status code
	 *
	 * @return array{headers: array, body: string, response: array{code: int}, cookies: array, filename: null}
	 */
	protected function http_response( int $code ): array {
		return [
			'headers'  => [],
			'body'     => '',
			'response' => [ 'code' => $code ],
			'cookies'  => [],
			'filename' => null,
		];
	}

	/**
	 * Delete the copied fonts and every custom font record, and flush the metrics of keys a test re-pointed so a later
	 * render doesn't read stale ones back (GravityPDF/mpdf#825)
	 */
	protected function remove_font_fixtures(): void {
		array_map( 'wp_delete_file', $this->copied_fonts );
		GPDFAPI::delete_plugin_option( 'custom_fonts' );
		array_map( [ FlushCache::class, 'flush' ], array_unique( $this->recorded_fonts ) );
		GPDFAPI::get_mvc_class( 'Model_Font_Sources' )->flush();
		GPDFAPI::get_mvc_class( 'Model_Required_Fonts' )->flush();

		/* A download or its fallback caches metrics under every font key the render asked for, substitutes included */
		if ( $this->requested !== [] ) {
			FlushCache::flush();
		}

		if ( $this->manifest_changed ) {
			$this->set_shared_manifest( null );
		}

		$this->set_private( GPDFAPI::get_mvc_class( 'Model_Font_Sources' ), 'legacy_manifests', [] );

		$this->copied_fonts   = [];
		$this->recorded_fonts = [];
		$this->requested      = [];
		$this->served_as        = [];
		$this->manifest_changed = false;
	}

	/**
	 * Stand Chewy in for each FreeSerif file, so the family is on disk
	 */
	protected function put_freeserif_on_disk(): void {
		foreach ( static::$freeserif as $file ) {
			$this->copy_fixture( 'Chewy.ttf', $file );
			$this->list_fixture_as( 'Chewy.ttf', $file );
		}
	}
}
