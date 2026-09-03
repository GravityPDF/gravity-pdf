<?php

declare(strict_types=1);

namespace GFPDF\Tests\Concerns;

/**
 * Installs v3 PDF templates in the working directory, which Deprecation detects.
 */
trait CreatesLegacyTemplates {

	/**
	 * Write a v3 template, which is one carrying no file headers, and return its path
	 *
	 * A body calling `gfpdfe_business_plus::initilise` is what makes it a Business Plus (Tier 2) template rather
	 * than a plain one.
	 *
	 * @param string $name The file to write into the PDF working directory
	 * @param string $body PHP appended after the v3 boilerplate
	 *
	 * @return string The new template's absolute path
	 */
	protected function create_legacy_template( string $name = 'my-legacy-template.php', string $body = '' ): string {
		$path = \GPDFAPI::get_data_class()->template_location . $name;

		file_put_contents( $path, '<?php if ( ! class_exists( "RGForms" ) ) { return; } ' . $body );

		/* the template list is memoized per request by GFCache, and what Deprecation read off it by the detector */
		\GFCache::flush();
		\GFPDF\Statics\Deprecation::flush_cache();

		return $path;
	}

	/**
	 * Create a form with one PDF set to render through the v3 Advanced Templating mode
	 *
	 * The setting is not a detection signal — the template file is — so this exists to prove it isn't one.
	 *
	 * @return int The new form's ID
	 */
	protected function create_form_with_advanced_templating(): int {
		$form_id = (int) $this->gf_factory()->form->create();

		$this->gf_factory()->pdf->set_form_id( $form_id )->create( [ 'advanced_template' => 'Yes' ] );

		return $form_id;
	}

	/**
	 * Delete templates written by self::create_legacy_template(), so later tests don't read them
	 *
	 * @param string ...$paths
	 */
	protected function delete_legacy_templates( string ...$paths ): void {
		foreach ( $paths as $path ) {
			@unlink( $path );
		}

		\GFCache::flush();
		\GFPDF\Statics\Deprecation::flush_cache();
	}
}
