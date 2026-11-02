<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Concerns;

use GFPDF\Model\Model_Font_Sources;
use GFPDF_Vendor\Mpdf\Language\LanguageToFontInterface;
use GPDFAPI;

/**
 * Stubs the site's languages and the font sources
 */
trait StubsFontSources {

	/**
	 * @param mixed $languages What get_available_languages() returns
	 */
	protected function set_languages( $languages ): void {
		add_filter(
			'get_available_languages',
			static function () use ( $languages ) {
				return $languages;
			}
		);
	}

	/**
	 * @param mixed $locale What get_locale() returns
	 */
	protected function set_locale( $locale ): void {
		add_filter(
			'locale',
			static function () use ( $locale ) {
				return $locale;
			}
		);
	}

	/**
	 * Font sources with their sources changed by $change, and optionally a stub language mapper
	 *
	 * @param callable|null $change Takes and returns the registered sources
	 */
	protected function stub_font_sources( ?callable $change = null, ?LanguageToFontInterface $mapper = null ): Model_Font_Sources {
		$model = new class( GPDFAPI::get_data_class(), GPDFAPI::get_mvc_class( 'Model_Custom_Fonts' ), GPDFAPI::get_log_class() ) extends Model_Font_Sources {
			/** @var callable|null */
			public $change;

			/** @var LanguageToFontInterface|null */
			public $mapper;

			public function get_sources(): array {
				$sources = parent::get_sources();

				return $this->change !== null ? ( $this->change )( $sources ) : $sources;
			}

			protected function get_language_mapper(): LanguageToFontInterface {
				return $this->mapper ?? parent::get_language_mapper();
			}
		};

		$model->change = $change;
		$model->mapper = $mapper;

		return $model;
	}

	/**
	 * A language mapper that answers every locale with the same options
	 *
	 * @param mixed $options What getLanguageOptions() returns
	 */
	protected function stub_language_mapper( $options ): LanguageToFontInterface {
		$mapper = $this->createMock( LanguageToFontInterface::class );
		$mapper->method( 'getLanguageOptions' )->willReturn( $options );

		return $mapper;
	}

	/**
	 * Font sources whose core manifest can't be read
	 */
	protected function font_sources_without_a_manifest(): Model_Font_Sources {
		return $this->stub_font_sources(
			static function ( array $sources ): array {
				$sources['core']['manifest'] = '/missing/core-fonts.json';

				return $sources;
			}
		);
	}
}
