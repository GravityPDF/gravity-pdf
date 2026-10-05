<?php

declare( strict_types=1 );

namespace GFPDF\Helper;

use GFPDF\Tests\Integration\TestCase;
use WP_Error;

/**
 * @group helper-misc
 */
class Test_Helper_Misc_Files extends TestCase {

	public Helper_Misc $misc;

	private string $tmp;

	public function set_up(): void {
		global $gfpdf;
		parent::set_up();
		$this->misc = new Helper_Misc( $gfpdf->log, $gfpdf->gform, $gfpdf->data );
		$this->tmp  = $gfpdf->data->template_tmp_location . 'misc-files-' . wp_generate_password( 8, false ) . '/'; /* rmdir() only deletes our own folders */
	}

	public function tear_down(): void {
		if ( is_dir( $this->tmp ) ) {
			$this->misc->rmdir( $this->tmp );
		}
		parent::tear_down();
	}

	public function test_copyr_copies_a_folder_tree_into_a_new_destination() {
		wp_mkdir_p( $this->tmp . 'source/nested/deeper' );
		wp_mkdir_p( $this->tmp . 'source/empty' );
		file_put_contents( $this->tmp . 'source/top.txt', 'top' );
		file_put_contents( $this->tmp . 'source/nested/deeper/inner.txt', 'inner' );

		/* The destination is concatenated with each relative path, so it needs its trailing slash */
		$this->assertTrue( $this->misc->copyr( $this->tmp . 'source', $this->tmp . 'destination/' ) );

		$this->assertStringEqualsFile( $this->tmp . 'destination/top.txt', 'top' );
		$this->assertStringEqualsFile( $this->tmp . 'destination/nested/deeper/inner.txt', 'inner' );
		$this->assertDirectoryExists( $this->tmp . 'destination/empty' );
	}

	public function test_copyr_overwrites_files_and_keeps_extra_ones_in_an_existing_destination() {
		wp_mkdir_p( $this->tmp . 'source' );
		wp_mkdir_p( $this->tmp . 'destination' );
		file_put_contents( $this->tmp . 'source/file.txt', 'new' );
		file_put_contents( $this->tmp . 'destination/file.txt', 'old' );
		file_put_contents( $this->tmp . 'destination/extra.txt', 'extra' );

		$this->assertTrue( $this->misc->copyr( $this->tmp . 'source', $this->tmp . 'destination/' ) );

		$this->assertStringEqualsFile( $this->tmp . 'destination/file.txt', 'new' );
		$this->assertStringEqualsFile( $this->tmp . 'destination/extra.txt', 'extra' );
	}

	public function test_copyr_returns_an_error_when_the_destination_cannot_be_created() {
		wp_mkdir_p( $this->tmp . 'source' );
		file_put_contents( $this->tmp . 'blocker', '' );

		$result = $this->misc->copyr( $this->tmp . 'source', $this->tmp . 'blocker/destination/' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'recursion_copy_problem', $result->get_error_code() );
	}

	public function test_copyr_returns_an_error_when_the_source_is_missing() {
		$result = $this->misc->copyr( $this->tmp . 'missing', $this->tmp . 'destination/' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'recursion_copy_problem', $result->get_error_code() );
	}

	/**
	 * @dataProvider provider_convert_path_to_url
	 *
	 * @param string|false $expected
	 */
	public function test_convert_path_to_url( string $path, $expected ) {
		$this->assertSame( $expected, $this->misc->convert_path_to_url( $path ) );
	}

	public function provider_convert_path_to_url(): array {
		$uploads = wp_upload_dir();

		return [
			'empty'               => [ '', '' ],
			'whitespace'          => [ '  ', '  ' ],
			'uploads'             => [ $uploads['basedir'] . '/2026/file.pdf', $uploads['baseurl'] . '/2026/file.pdf' ],
			'wp-content'          => [ WP_CONTENT_DIR . '/plugins/file.php', WP_CONTENT_URL . '/plugins/file.php' ],
			'outside the install' => [ '/somewhere/else/file.pdf', false ],
		];
	}
}
