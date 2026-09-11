<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use WP_Error;

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
 * Turning "install this entry" into a queued batch
 *
 * Extracted from `Rest_Font_Installs` when a second route needed it: `POST /fonts/{id} { variants }` is one
 * install of a display entry, which is the same operation the entry route performs for all of them. Deciding what
 * to download was never a REST concern, and two controllers each holding an installer and a queue would be one
 * update path too many.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Install_Requests {

	/**
	 * @var Font_Repository
	 * @since 7.0
	 */
	protected $repository;

	/**
	 * @var Font_Installer
	 * @since 7.0
	 */
	protected $installer;

	/**
	 * @var Install_Queue
	 * @since 7.0
	 */
	protected $queue;

	public function __construct( Font_Repository $repository, Font_Installer $installer, Install_Queue $queue ) {
		$this->repository = $repository;
		$this->installer  = $installer;
		$this->queue      = $queue;
	}

	/**
	 * Hand a set of installs to the queue
	 *
	 * @param array   $row      The catalog row being installed
	 * @param array[] $installs
	 *
	 * @return bool|WP_Error Whether anything was queued, or why the entry could not be read
	 *
	 * @since 7.0
	 */
	public function queue( array $row, array $installs ) {
		$id    = static::entry_id( $row );
		$files = $this->installer->files_for_installs( $id, $installs );

		if ( is_wp_error( $files ) ) {
			$files->add_data( [ 'status' => 502 ], $files->get_error_code() );

			return $files;
		}

		$requests = [];

		foreach ( $installs as $index => $install ) {
			$requests[] = [
				'install'    => $install,
				'background' => $files[ $index ],
			];
		}

		return $this->queue->enqueue_once(
			[
				'entry'    => $id,
				'installs' => $requests,

				/* An install and an update both mean "these files, whatever is already on disk" */
				'force'    => true,
			],
			true
		);
	}

	/**
	 * Take an entry's rows and the files no surviving row records
	 *
	 * Here beside the queueing so that a caller holding this service holds the whole of an entry's lifecycle and
	 * needs the installer itself for nothing.
	 *
	 * @return array|WP_Error
	 *
	 * @since 7.0
	 */
	public function remove( string $id ) {
		return $this->installer->remove( $id );
	}

	/**
	 * Every install an entry already holds, as the payloads that would rewrite them
	 *
	 * The update path, and the whole of what an empty body means. It cannot fail — the payloads are read back off
	 * rows the installer itself wrote — which is why "Update all" can reach it without repeating any of the
	 * validation the entry route does on a body.
	 *
	 * @return array[]
	 *
	 * @since 7.0
	 */
	public function on_record( array $row ): array {
		if ( (int) $row['coverage'] === 1 ) {
			return [ [] ];
		}

		$rows = $this->repository->rows_for_entry( (string) $row['source'], (string) $row['entry'] );

		return $rows === [] ? [ [] ] : array_values( array_map( [ $this, 'for_row' ], $rows ) );
	}

	/**
	 * One existing row, as the install that would rewrite it
	 *
	 * `label` is the identity the installer matches on, so this is idempotent by construction: it finds the row it
	 * came from. The variants come off the file rows because that is where the choice was recorded.
	 *
	 * @since 7.0
	 */
	public function for_row( array $font ): array {
		$variants = [];

		foreach ( (array) $font['files'] as $role => $file ) {
			if ( in_array( (string) $role, Font_Repository::FACE_ROLES, true ) && (string) $file['variant'] !== '' ) {
				$variants[ (string) $role ] = (string) $file['variant'];
			}
		}

		$install = [ 'label' => (string) $font['label'] ];

		if ( $variants !== [] ) {
			$install['variants'] = $variants;
		}

		return $install;
	}

	/**
	 * Every variant a body names has to be a role the installer fills and a style the entry publishes
	 *
	 * @since 7.0
	 */
	public function check_variants( array $row, array $variants ): ?WP_Error {
		if ( $variants === [] ) {
			return null;
		}

		$styles = array_filter( array_map( 'trim', explode( ',', (string) ( $row['styles'] ?? '' ) ) ) );

		foreach ( $variants as $role => $variant ) {
			if ( in_array( (string) $role, Font_Repository::FACE_ROLES, true ) && in_array( (string) $variant, $styles, true ) ) {
				continue;
			}

			return new WP_Error(
				'font_variant_unknown',
				sprintf(
					/* translators: %s: the style the request asked for */
					__( 'This font does not publish a %s style.', 'gravity-pdf' ),
					(string) $variant
				),
				[ 'status' => 400 ]
			);
		}

		return null;
	}

	/**
	 * @since 7.0
	 */
	public static function entry_id( array $row ): string {
		return $row['source'] . '/' . $row['entry'];
	}
}
