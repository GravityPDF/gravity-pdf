<?php

declare( strict_types=1 );

namespace GFPDF\Fonts\Health;

use GFPDF\Fonts\Catalog_Repository;

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
 * The entries a render asked for and could not draw, read once
 *
 * Two checks ask about these rows and give opposite answers: which fonts to install, and whether the server can
 * install anything at all. They read the table once between them, which matters because both answer live on every
 * admin page rather than from the daily report.
 *
 * @package GFPDF\Fonts\Health
 *
 * @since 7.0
 */
class Uncovered_Entries {

	/**
	 * @var Catalog_Repository
	 * @since 7.0
	 */
	protected $catalog;

	/**
	 * @var array[]|null
	 * @since 7.0
	 */
	protected $rows;

	public function __construct( Catalog_Repository $catalog ) {
		$this->catalog = $catalog;
	}

	/**
	 * @return array[]
	 *
	 * @since 7.0
	 */
	public function all(): array {
		if ( $this->rows === null ) {
			$this->rows = $this->catalog->entries_missing_coverage();
		}

		return $this->rows;
	}

	/**
	 * @return array[]
	 *
	 * @since 7.0
	 */
	protected function failed(): array {
		return array_values(
			array_filter(
				$this->all(),
				static function ( array $row ): bool {
					return (string) $row['phase'] === 'failed';
				}
			)
		);
	}

	/**
	 * Whether every entry that was asked for has failed outright
	 *
	 * The line between the two checks: below it the site is missing fonts, above it the server cannot download
	 * anything, and those are different problems for different people.
	 *
	 * @since 7.0
	 */
	public function all_failed(): bool {
		$rows = $this->all();

		return $rows !== [] && $this->failed() === $rows;
	}
}
