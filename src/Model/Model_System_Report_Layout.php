<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Statics\Deprecation;

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
 * Sorts the filtered report sections into the Checks and Configuration tables
 *
 * @since 7.0
 */
class Model_System_Report_Layout {

	/**
	 * The section and row of each check after the deprecation groups', in the order they're listed
	 *
	 * @since 7.0
	 */
	private const CHECKS = [
		[ 'php', 'memory' ],
		[ 'php', 'allow_url_fopen' ],
		[ 'directories', 'folder_permissions' ],
		[ 'directories', 'temp_folder_protected' ],
		[ 'directories', 'core_fonts' ],
		[ 'directories', 'outdated_templates' ],
		[ 'cache', 'cache_cleanup' ],
	];

	/**
	 * The section and row of each Configuration row, in the order they're listed
	 *
	 * @since 7.0
	 */
	private const CONFIGURATION = [
		[ 'directories', 'pdf_working_directory' ],
		[ 'directories', 'folders' ],
		[ 'directories', 'core_font_downloads' ],
		[ 'global', 'pdf_entry_list_action' ],
		[ 'global', 'background_processing_enabled' ],
		[ 'global', 'debug_mode_enabled' ],
		[ 'security', 'user_restrictions' ],
		[ 'security', 'logged_out_timeout' ],
		[ 'cache', 'pdf_cache' ],
		[ 'php', 'charset' ],
		[ 'directories', 'substitution_fonts' ],
	];

	/**
	 * Failing checks first, then one row for those that passed. The copied report keeps a line per check.
	 *
	 * Rows an add-on adds go to Checks when they're outdated templates or fail, and to the end of Configuration otherwise.
	 *
	 * @param array $sections Report rows, keyed by section and then row
	 *
	 * @return array{checks: array, configuration: array}
	 *
	 * @since 7.0
	 */
	public function get_tables( array $sections ): array {
		$failed        = [];
		$passed        = [];
		$configuration = [];
		$total         = 0;

		/* A deprecation group's check is its whole section */
		foreach ( Deprecation::get_groups() as $group ) {
			if ( ! empty( $sections[ $group ] ) ) {
				$this->add_check( array_values( (array) $sections[ $group ] ), $failed, $passed, $total );
			}

			unset( $sections[ $group ] );
		}

		foreach ( self::CHECKS as $check ) {
			if ( isset( $sections[ $check[0] ][ $check[1] ] ) ) {
				$this->add_check( [ $sections[ $check[0] ][ $check[1] ] ], $failed, $passed, $total );
				unset( $sections[ $check[0] ][ $check[1] ] );
			}
		}

		foreach ( self::CONFIGURATION as $row ) {
			if ( isset( $sections[ $row[0] ][ $row[1] ] ) ) {
				$configuration[] = $this->tidy( $sections[ $row[0] ][ $row[1] ] );
				unset( $sections[ $row[0] ][ $row[1] ] );
			}
		}

		foreach ( $sections as $rows ) {
			foreach ( (array) $rows as $key => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}

				if ( ( $row['is_valid'] ?? null ) === false || substr( (string) $key, -18 ) === 'outdated_templates' ) {
					$this->add_check( [ $row ], $failed, $passed, $total );
				} else {
					$configuration[] = $this->tidy( $row );
				}
			}
		}

		return [
			'checks'        => array_merge( $failed, $this->get_passed_rows( $passed, $total ) ),
			'configuration' => $configuration,
		];
	}

	/**
	 * File a check's rows as failed, or as passed when its one row passed
	 *
	 * @param array[] $rows
	 * @param array[] $failed
	 * @param array[] $passed
	 * @param int     $total How many checks there are
	 *
	 * @since 7.0
	 */
	protected function add_check( array $rows, array &$failed, array &$passed, int &$total ): void {
		++$total;

		if ( count( $rows ) === 1 && ( $rows[0]['is_valid'] ?? null ) === true ) {
			$passed[] = $rows[0];

			return;
		}

		foreach ( $rows as $row ) {
			$failed[] = $this->tidy( $row );
		}
	}

	/**
	 * One row on screen listing the checks that passed. It copies as the first of them, and the rest copy on their own.
	 *
	 * @param array[] $passed
	 * @param int     $total How many checks there are
	 *
	 * @since 7.0
	 */
	protected function get_passed_rows( array $passed, int $total ): array {
		if ( $passed === [] ) {
			return [];
		}

		$summaries = array_map(
			static function ( $row ) {
				return $row['summary'] ?? wp_strip_all_tags( $row['label'] );
			},
			$passed
		);

		$first = $passed[0];
		$rows  = [
			[
				'label'        => count( $passed ) === $total
					/* translators: %d: How many checks there are */
					? esc_html( sprintf( __( 'All %d checks passed', 'gravity-pdf' ), $total ) )
					/* translators: 1: How many checks passed, 2: How many checks there are */
					: esc_html( sprintf( __( '%1$d of %2$d checks passed', 'gravity-pdf' ), count( $passed ), $total ) ),
				'label_export' => $first['label_export'] ?? wp_strip_all_tags( $first['label'] ),
				'value'        => implode( ' · ', $summaries ),
				'value_export' => $first['value_export'] ?? $first['value'],
				'is_valid'     => true,
			],
		];

		foreach ( array_slice( $passed, 1 ) as $row ) {
			$rows[] = [ 'export_only' => true ] + $row;
		}

		return $rows;
	}

	/**
	 * Drop the line break a row's value ends in when no add-on appended to it
	 *
	 * @since 7.0
	 */
	protected function tidy( array $row ): array {
		if ( isset( $row['value'] ) && is_string( $row['value'] ) ) {
			$row['value'] = preg_replace( '/<br>$/', '', $row['value'] );
		}

		return $row;
	}
}
