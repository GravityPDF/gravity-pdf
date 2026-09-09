<?php

declare( strict_types=1 );

namespace GFPDF\Fonts\Health;

use GFPDF\Fonts\Catalog_Repository;
use GFPDF\Fonts\Registry;
use GFPDF\Helper\Health\Health_Check;
use GFPDF\Helper\Health\Health_Issue;

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
 * Fonts a PDF is configured to use and the site does not have
 *
 * A saved setting outlives the font it names: a deleted custom font, a pack that never finished installing, a
 * settings export moved to another site. The render already falls back rather than failing, so the only symptom is
 * a PDF that quietly looks wrong.
 *
 * @package GFPDF\Fonts\Health
 *
 * @since 7.0
 */
class Unregistered_Font_Check extends Health_Check {

	/**
	 * @var Registry
	 * @since 7.0
	 */
	protected $registry;

	/**
	 * @var Catalog_Repository
	 * @since 7.0
	 */
	protected $catalog;

	/**
	 * @var Configured_Fonts
	 * @since 7.0
	 */
	protected $configured;

	public function __construct( Registry $registry, Catalog_Repository $catalog, Configured_Fonts $configured ) {
		$this->registry   = $registry;
		$this->catalog    = $catalog;
		$this->configured = $configured;
	}

	public function get_id(): string {
		return 'unregistered_font';
	}

	public function get_title(): string {
		return __( 'Fonts in use', 'gravity-pdf' );
	}

	/**
	 * @return Health_Issue[]
	 *
	 * @since 7.0
	 */
	public function run(): array {
		$issues = [];

		foreach ( $this->configured->by_key() as $font_key => $used_by ) {
			if ( $this->registry->is_registered( (string) $font_key ) ) {
				continue;
			}

			$issues[] = $this->issue( (string) $font_key, $used_by );
		}

		return $issues;
	}

	/**
	 * @param string[] $used_by
	 *
	 * @since 7.0
	 */
	protected function issue( string $font_key, array $used_by ): Health_Issue {
		/* A key the catalogue knows can be installed; anything else is gone and has to be chosen again */
		$entry = $this->catalog->entry_for_font_key( $font_key );

		return new Health_Issue(
			$font_key,
			sprintf(
				/* translators: %s: the font key a PDF is configured to use */
				__( 'The font "%s" is selected but not installed.', 'gravity-pdf' ),
				$font_key
			),
			$used_by,
			sprintf(
				/* translators: %s: the font those PDFs render in instead */
				__( 'Those PDFs render in %s.', 'gravity-pdf' ),
				$this->registry->get_default_font()
			),
			$entry !== null ? __( 'Install', 'gravity-pdf' ) : __( 'Choose another font', 'gravity-pdf' ),
			$entry !== null ? Font_Manager_Urls::entry( $entry ) : Font_Manager_Urls::manager()
		);
	}
}
