<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Helper\Health\Health_Runner;
use GFPDF\Helper\Helper_Abstract_Queue;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Log\Option_Ring_Handler;

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
 * What the System Report says about fonts
 *
 * Three sections, all of them read-only: the cached repository and catalogue queries, the sources option and the
 * health report. Nothing here stats a file or makes a request — the Font Manager's "Verify font files" is the
 * on-demand path, and a support ticket should not be able to change the site it is describing.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Font_Report {

	/**
	 * @var Font_Repository
	 * @since 7.0
	 */
	protected $repository;

	/**
	 * @var Catalog_Repository
	 * @since 7.0
	 */
	protected $catalog;

	/**
	 * @var Catalog_Sync
	 * @since 7.0
	 */
	protected $sync;

	/**
	 * @var Install_Queue
	 * @since 7.0
	 */
	protected $queue;

	/**
	 * @var Registry
	 * @since 7.0
	 */
	protected $registry;

	/**
	 * @var Health_Runner
	 * @since 7.0
	 */
	protected $health;

	/**
	 * @var Helper_Data
	 * @since 7.0
	 */
	protected $data;

	public function __construct(
		Font_Repository $repository,
		Catalog_Repository $catalog,
		Catalog_Sync $sync,
		Install_Queue $queue,
		Registry $registry,
		Health_Runner $health,
		Helper_Data $data
	) {
		$this->repository = $repository;
		$this->catalog    = $catalog;
		$this->sync       = $sync;
		$this->queue      = $queue;
		$this->registry   = $registry;
		$this->health     = $health;
		$this->data       = $data;
	}

	/**
	 * What this site has, and where it came from
	 *
	 * @since 7.0
	 */
	public function fonts(): array {
		return [
			'font_folder_location' => $this->row( __( 'Font folder location', 'gravity-pdf' ), $this->data->template_font_location ),
			'font_folder_writable' => $this->row(
				__( 'Font folder writable', 'gravity-pdf' ),
				wp_is_writable( $this->data->template_font_location ) ? __( 'Writable', 'gravity-pdf' ) : __( 'Not writable', 'gravity-pdf' )
			),
			'font_schema_version'  => $this->row( __( 'Font table version', 'gravity-pdf' ), (string) get_site_option( Font_Schema::VERSION_OPTION, '' ) ),
			'installed_fonts'      => $this->row( __( 'Installed fonts', 'gravity-pdf' ), $this->fonts_by_source() ),
			'font_sources'         => $this->row( __( 'Font sources', 'gravity-pdf' ), $this->sources() ),
			'language_packs'       => $this->row( __( 'Language packs', 'gravity-pdf' ), $this->packs() ),
		];
	}

	/**
	 * Whether the background work is moving, and everything a ticket needs when it is not
	 *
	 * @since 7.0
	 */
	public function background_installs(): array {
		$stalled  = $this->catalog->stalled_entries( Install_Queue::STALLED_AFTER );
		$dispatch = Helper_Abstract_Queue::get_dispatch_error();

		return [
			'queue_running'  => $this->row(
				__( 'Install queue running', 'gravity-pdf' ),
				$this->queue->is_running() ? __( 'Yes', 'gravity-pdf' ) : __( 'No', 'gravity-pdf' )
			),
			'queue_stalled'  => $this->row(
				__( 'Installs not moving', 'gravity-pdf' ),
				$stalled === [] ? __( 'None', 'gravity-pdf' ) : $this->stalled_lines( $stalled )
			),
			'wp_cron'        => $this->row(
				'DISABLE_WP_CRON',
				defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? __( 'Yes', 'gravity-pdf' ) : __( 'No', 'gravity-pdf' )
			),
			'next_sync'      => $this->row( __( 'Next font catalogue sync', 'gravity-pdf' ), $this->next_event( Catalog_Sync::EVENT ) ),
			'next_cleanup'   => $this->row( __( 'Next hourly clean-up', 'gravity-pdf' ), $this->next_event( 'gfpdf_cleanup_tmp_dir' ) ),
			'part_files'     => $this->row( __( 'Part-downloaded files', 'gravity-pdf' ), (string) $this->part_count() ),
			'dispatch_error' => $this->row(
				__( 'Last loopback failure', 'gravity-pdf' ),
				$dispatch !== '' ? $dispatch : __( 'None', 'gravity-pdf' )
			),
			'last_errors'    => $this->row( __( 'Last errors', 'gravity-pdf' ), $this->last_errors() ),
		];
	}

	/**
	 * The daily run's answer, one row per check, whatever the checks happen to be
	 *
	 * @since 7.0
	 */
	public function health(): array {
		$report   = $this->health->get_report();
		$last_run = (int) ( $report['last_run'] ?? 0 );

		$items = [
			'last_run' => $this->row(
				__( 'Last health check', 'gravity-pdf' ),
				$last_run === 0
					? __( 'Never run', 'gravity-pdf' )
					/* translators: %s: how long ago the health check last ran */
					: sprintf( __( '%s ago', 'gravity-pdf' ), human_time_diff( $last_run ) )
			),
		];

		foreach ( $this->health->get_checks() as $check ) {
			$items[ 'check_' . $check->get_id() ] = $this->row(
				$check->get_title(),
				$this->issue_lines( Health_Runner::issues_in( $report, $check->get_id() ) )
			);
		}

		return $items;
	}

	/**
	 * @since 7.0
	 */
	protected function fonts_by_source(): string {
		$counts = [];

		foreach ( $this->repository->all() as $row ) {
			$source            = (string) ( $row['source'] ?? '' );
			$counts[ $source ] = ( $counts[ $source ] ?? 0 ) + 1;
		}

		if ( $counts === [] ) {
			return __( 'None', 'gravity-pdf' );
		}

		ksort( $counts );

		$lines = [];
		foreach ( $counts as $source => $count ) {
			$lines[] = $source . ': ' . $count;
		}

		return implode( ', ', $lines );
	}

	/**
	 * @since 7.0
	 */
	protected function sources(): string {
		$lines = [];

		foreach ( $this->sync->get_sources()->all() as $id => $source ) {
			$record  = $this->sync->get_record( (string) $id );
			$synced  = (int) $record['synced'];
			$summary = $this->catalog->summary( (string) $id );

			$line = sprintf(
				'%s: %d entries, %s',
				(string) $id,
				$summary['total'],
				$synced === 0
					? __( 'never synced', 'gravity-pdf' )
					/* translators: %s: how long ago the source last synced */
					: sprintf( __( 'synced %s ago', 'gravity-pdf' ), human_time_diff( $synced ) )
			);

			if ( Catalog_Sync::is_stale( $record ) ) {
				$line .= ' — ' . __( 'stale', 'gravity-pdf' );
			}

			if ( (string) $record['last_error'] !== '' ) {
				$line .= ' — ' . (string) $record['last_error'];
			}

			$lines[] = $line;
		}

		return $lines === [] ? __( 'None registered', 'gravity-pdf' ) : implode( "\n", $lines );
	}

	/**
	 * Every coverage entry this site has any rows for, and how far it got
	 *
	 * @since 7.0
	 */
	protected function packs(): string {
		$lines = [];

		foreach ( $this->registry->get_install_statuses( $this->queue ) as $id => $status ) {
			if ( empty( $status['installed'] ) && $status['phase'] === null ) {
				continue;
			}

			[ $source, $entry ] = Font_Sources::split( (string) $id );

			$expected = $this->catalog->file_count( $source, $entry );

			$lines[] = sprintf(
				/* translators: 1: the entry id, 2: files installed, 3: files the catalogue lists, 4: install phase */
				__( '%1$s: %2$d of %3$d files%4$s', 'gravity-pdf' ),
				(string) $id,
				(int) $status['files_done'],
				$expected,
				$status['phase'] === null ? '' : ' (' . (string) $status['phase'] . ')'
			);
		}

		return $lines === [] ? __( 'None installed', 'gravity-pdf' ) : implode( "\n", $lines );
	}

	/**
	 * @param array[] $rows
	 *
	 * @since 7.0
	 */
	protected function stalled_lines( array $rows ): string {
		$lines = [];

		foreach ( $rows as $row ) {
			$lines[] = sprintf(
				/* translators: 1: the entry id, 2: the phase it is stuck in, 3: how long it has been there */
				__( '%1$s: %2$s for %3$s', 'gravity-pdf' ),
				$row['source'] . '/' . $row['entry'],
				(string) $row['phase'],
				human_time_diff( (int) strtotime( (string) $row['phase_since'] . ' UTC' ) )
			);
		}

		return implode( "\n", $lines );
	}

	/**
	 * @param \GFPDF\Helper\Health\Health_Issue[] $issues
	 *
	 * @since 7.0
	 */
	protected function issue_lines( array $issues ): string {
		$lines = [];

		foreach ( $issues as $issue ) {
			$lines[] = trim( $issue->get_summary() . ' ' . implode( ' ', $issue->get_details() ) );
		}

		return $lines === [] ? __( 'None', 'gravity-pdf' ) : implode( "\n", $lines );
	}

	/**
	 * @since 7.0
	 */
	protected function last_errors(): string {
		$lines = [];

		foreach ( Option_Ring_Handler::get_errors() as $error ) {
			$lines[] = sprintf(
				'%s %s: %s %s',
				(string) ( $error['time'] ?? '' ),
				(string) ( $error['channel'] ?? '' ),
				(string) ( $error['message'] ?? '' ),
				(string) ( $error['context'] ?? '' )
			);
		}

		return $lines === [] ? __( 'None', 'gravity-pdf' ) : implode( "\n", $lines );
	}

	/**
	 * @since 7.0
	 */
	protected function next_event( string $hook ): string {
		$next = wp_next_scheduled( $hook );

		if ( $next === false ) {
			return __( 'Not scheduled', 'gravity-pdf' );
		}

		/* translators: %s: how far away the next run is */
		return sprintf( __( 'in %s', 'gravity-pdf' ), human_time_diff( (int) $next ) );
	}

	/**
	 * Leftovers from a download that was killed part way, which is a symptom rather than a problem of its own
	 *
	 * @since 7.0
	 */
	protected function part_count(): int {
		$dir = trailingslashit( $this->data->template_font_location ) . Font_Downloader::TMP_DIR . '/';

		return count( glob( $dir . '*.part' ) ?: [] );
	}

	/**
	 * @since 7.0
	 */
	protected function row( string $label, string $value ): array {
		return [
			'label'        => esc_html( $label ),
			'label_export' => $label,
			'value'        => $value,
		];
	}
}
