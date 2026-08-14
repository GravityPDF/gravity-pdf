<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Integration;

/**
 * Test the php-scoper patchers in tools/php-scoper/config/
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

/**
 * php-scoper cannot rewrite class names that live inside string literals, so tools/php-scoper/config/
 * patches those by hand. The patchers match on generated source text, which shifts whenever the
 * scoper's printer changes, and a miss fails silently — mPDF simply skips the tags it can no longer
 * resolve and emits a PDF with the formatting dropped. Nothing else in the suite notices.
 *
 * @since 7.0
 * @group vendor-prefixing
 */
class Test_Vendor_Prefixing extends TestCase {

	/**
	 * mPDF resolves every HTML tag handler by building a class-name string
	 *
	 * @param string $tag
	 *
	 * @since        7.0
	 *
	 * @dataProvider provider_mpdf_tags
	 */
	public function test_mpdf_tag_handlers_are_prefixed( string $tag ) {
		$class = \GFPDF_Vendor\Mpdf\Tag::getTagClassName( $tag );

		$this->assertStringStartsWith( 'GFPDF_Vendor\\', $class );
		$this->assertTrue( class_exists( $class ), "$tag resolved to missing class $class" );
	}

	/**
	 * A spread of tags covering both the lookup map and the ucfirst() fallback
	 *
	 * @return array
	 *
	 * @since 7.0
	 */
	public function provider_mpdf_tags(): array {
		return [
			[ 'BARCODE' ],
			[ 'BLOCKQUOTE' ],
			[ 'TBODY' ],
			[ 'WATERMARKTEXT' ],
			[ 'H1' ],
			[ 'TABLE' ],
			[ 'TD' ],
			[ 'P' ],
		];
	}

	/**
	 * QueryPath registers its error handlers using string callables
	 *
	 * @param string $file
	 *
	 * @since        7.0
	 *
	 * @dataProvider provider_querypath_files
	 */
	public function test_querypath_string_callables_resolve( string $file ) {
		$literals = $this->string_literals( $file );
		$matches  = preg_grep( '/QueryPath/', $literals );

		$this->assertNotEmpty( $matches, "No QueryPath string callables found in $file" );

		foreach ( $matches as $class ) {
			$this->assertTrue( class_exists( $class ), "$file references missing class $class" );
		}
	}

	/**
	 * @return array
	 *
	 * @since 7.0
	 */
	public function provider_querypath_files(): array {
		return [
			[ 'gravitypdf/querypath/src/DOM.php' ],
			[ 'gravitypdf/querypath/src/DOMQuery.php' ],
		];
	}

	/**
	 * The scoper mistakes escape sequences like \r\n for namespaced symbols and prefixes them
	 *
	 * @since 7.0
	 */
	public function test_escape_sequences_are_not_prefixed() {
		$literals = $this->string_literals( 'mpdf/mpdf/src/Mpdf.php' );

		$this->assertContains( '\r\n', $literals, 'mPDF lost the \r\n sequence it strips out of barcode content' );
		$this->assertNotContains( 'GFPDF_Vendor\r\n', $literals );
	}

	/**
	 * Returns the evaluated value of every single/double quoted string in a scoped file
	 *
	 * @param string $relative_path Path below vendor_prefixed/
	 *
	 * @return string[]
	 *
	 * @since 7.0
	 */
	protected function string_literals( string $relative_path ): array {
		$path = PDF_PLUGIN_DIR . 'vendor_prefixed/' . $relative_path;

		$this->assertFileExists( $path );

		$literals = [];
		foreach ( token_get_all( file_get_contents( $path ) ) as $token ) {
			if ( ! is_array( $token ) || T_CONSTANT_ENCAPSED_STRING !== $token[0] ) {
				continue;
			}

			$body = substr( $token[1], 1, -1 );

			/* Only \\ and \' are escapes inside single quotes, so \r there stays two literal characters */
			$literals[] = "'" === $token[1][0]
				? str_replace( [ '\\\\', "\\'" ], [ '\\', "'" ], $body )
				: stripcslashes( $body );
		}

		return $literals;
	}
}
