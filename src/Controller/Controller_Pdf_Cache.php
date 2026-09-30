<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Model\Model_Pdf_Cache;
use GFPDF\Statics\Cache;

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
 * Keeps the PDF cache in step with the entries, settings and sites it was rendered from, and runs its sweep
 *
 * @since 7.0
 */
class Controller_Pdf_Cache extends Helper_Abstract_Controller implements Helper_Interface_Actions {

	/**
	 * @var Model_Pdf_Cache
	 *
	 * @since 7.0
	 */
	public $model;

	/**
	 * @param Model_Pdf_Cache $model
	 *
	 * @since 7.0
	 */
	public function __construct( Model_Pdf_Cache $model ) {
		$this->model = $model;
		$this->model->setController( $this );
	}

	/**
	 * @return void
	 *
	 * @since 7.0
	 */
	public function init() {
		$this->add_actions();
	}

	/**
	 * @return void
	 *
	 * @since 7.0
	 */
	public function add_actions() {
		add_action( 'gfpdf_cleanup_tmp_dir', [ $this->model, 'run_scheduled_sweep' ] );
		add_action( 'gfpdf_cache_sweep', [ $this->model, 'run_sweep_slice' ] );

		/* The cache key already stops these PDFs being served. Purging deletes the personal data they hold */
		add_action( 'gform_delete_entry', [ $this->model, 'purge_entry' ] );
		add_action( 'gform_after_update_entry', [ $this, 'purge_updated_entry' ], 10, 2 );
		add_action( 'gform_post_update_entry', [ $this, 'purge_api_updated_entry' ] );
		add_action( 'gform_post_update_entry_property', [ $this, 'purge_entry_property' ], 10, 2 );
		add_action( 'wp_privacy_personal_data_erased', [ $this->model, 'purge_all' ], 10, 0 );
		add_action( 'gfpdf_invalidate_entry', [ $this->model, 'purge_entry' ] );
		add_action( 'gfpdf_invalidate_form', [ Cache::class, 'bump_form_generation' ] );
		add_action( 'wp_uninitialize_site', [ $this->model, 'delete_site' ] );

		/* Saving a setting that changes PDFs moves every cache key. WP fires only the add hook on a first save */
		add_action( 'update_option_gfpdf_settings', [ Cache::class, 'maybe_bump_generation' ], 10, 2 );
		add_action( 'add_option_gfpdf_settings', [ Cache::class, 'maybe_bump_generation' ], 10, 2 );

		/* Turning the cache off also deletes what it holds */
		add_action( 'update_option_gfpdf_settings', [ $this->model, 'maybe_request_purge' ], 10, 2 );
		add_action( 'add_option_gfpdf_settings', [ $this->model, 'maybe_request_purge' ], 10, 2 );
	}

	/**
	 * Purge an entry's cached PDFs after it's edited in the admin area
	 *
	 * @param array      $form
	 * @param int|string $entry_id
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	public function purge_updated_entry( $form, $entry_id ) {
		$this->model->purge_entry( $entry_id );
	}

	/**
	 * Purge an entry's cached PDFs after it's updated with GFAPI::update_entry()
	 *
	 * @param array $entry
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	public function purge_api_updated_entry( $entry ) {
		if ( ! empty( $entry['id'] ) ) {
			$this->model->purge_entry( $entry['id'] );
		}
	}

	/**
	 * Purge an entry's cached PDFs after a property changes, unless it's one a PDF doesn't show, e.g. opening or starring
	 * the entry. A status change still purges, so trashing an entry does.
	 *
	 * @param int|string $entry_id
	 * @param string     $property
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	public function purge_entry_property( $entry_id, $property ) {
		if ( $property === 'status' || ! in_array( $property, Cache::IGNORED_ENTRY_META, true ) ) {
			$this->model->purge_entry( $entry_id );
		}
	}
}
