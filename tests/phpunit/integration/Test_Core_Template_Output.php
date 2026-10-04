<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Integration;

use GFFormsModel;
use GFPDF\Tests\Concerns\AssertsSnapshots;
use GPDFAPI;
use RuntimeException;
use WP_Error;

/**
 * Golden files of the HTML each core template hands to mPDF for the all-form-fields entry, and of its $form_data.
 *
 * @group snapshot
 */
class Test_Core_Template_Output extends TestCase {

	use AssertsSnapshots;

	/* The display options the core templates share, all switched on for the "all-options" variant */
	private const ALL_OPTIONS = [
		'show_form_title'      => 'Yes',
		'show_page_names'      => 'Yes',
		'show_html'            => 'Yes',
		'show_section_content' => 'Yes',
		'enable_conditional'   => 'Yes',
		'show_empty'           => 'Yes',
	];

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
		static::point_uploads_at_fixture_form();
		static::copy_test_fonts();
	}

	public static function tear_down_after_class(): void {
		static::remove_test_fonts();
		parent::tear_down_after_class();
	}

	/**
	 * @dataProvider provider_templates
	 */
	public function test_template_html_matches_snapshot( string $template, string $variant, array $options ): void {
		$this->assertMatchesSnapshot( "pdf/$template-$variant.html", $this->render( $template, $options ) );
	}

	public function provider_templates(): array {
		$data = [];
		foreach ( [ 'blank-slate', 'focus-gravity', 'rubix', 'zadani' ] as $template ) {
			$data[ "$template defaults" ]    = [ $template, 'defaults', [] ];
			$data[ "$template all-options" ] = [ $template, 'all-options', self::ALL_OPTIONS ];
		}

		return $data;
	}

	public function test_form_data_matches_snapshot(): void {
		[ 'form' => $form, 'entry' => $entry ] = $this->form_and_entry();

		$form_data             = GPDFAPI::get_form_data( $entry['id'] );
		$form_data['form_id']  = '{form_id}';
		$form_data['entry_id'] = '{entry_id}';

		$json = (string) wp_json_encode( $form_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		$this->assertMatchesSnapshot( 'pdf/form-data.json', $this->normalise( $json, (int) $form['id'] ) . "\n" );
	}

	/**
	 * Renders up to the point the HTML reaches mPDF, then stops before it is written.
	 *
	 * @param array<string, string> $options
	 */
	private function render( string $template, array $options ): string {
		[ 'form' => $form, 'entry' => $entry ] = $this->form_and_entry();

		$capture = new class() extends RuntimeException {
			/** @var string */
			public $html = '';
		};

		$stop = static function ( string $html ) use ( $capture ): string {
			$capture->html = $html;

			throw $capture;
		};
		add_filter( 'gfpdf_pdf_html_output_' . $form['id'], $stop, PHP_INT_MAX );

		$settings = array_merge( $form['gfpdf_form_settings']['555ad84787d7e'], [ 'template' => $template ], $options );
		$result   = GPDFAPI::get_mvc_class( 'Model_PDF' )->generate_and_save_pdf( $entry, $settings, true );

		$this->assertInstanceOf( WP_Error::class, $result, 'The render should have been stopped' );
		$this->assertNotSame( '', $capture->html, "The $template render failed before its HTML was built" );

		return $this->normalise( $capture->html, (int) $form['id'] );
	}

	/**
	 * The fixture gets a new form ID, and so a new upload folder and download hashes, depending on what ran before it.
	 */
	private function normalise( string $text, int $form_id ): string {
		$text = strtr(
			$text,
			[
				GFFormsModel::get_upload_url( $form_id ) => '{uploads}',
				'form-id=' . $form_id                    => 'form-id={form_id}',
			]
		);

		return (string) preg_replace( '/hash=[0-9a-f]{64}/', 'hash={hash}', $text );
	}
}
