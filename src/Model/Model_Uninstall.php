<?php

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Form;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Pdf_Queue;
use Psr\Log\LoggerInterface;

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
 * Class Model_Uninstall
 *
 * @package GFPDF\Model
 *
 * @since   6.0
 */
class Model_Uninstall extends Helper_Abstract_Model {

	/**
	 * @var Helper_Form
	 *
	 * @since 6.0
	 */
	protected $gform;

	/**
	 * @var LoggerInterface
	 *
	 * @since 6.0
	 */
	protected $log;

	/**
	 * @var Helper_Data
	 *
	 * @since 6.0
	 */
	protected $data;

	/**
	 * @var Helper_Misc
	 *
	 * @since 6.0
	 */
	protected $misc;

	/**
	 * @var Helper_Notices
	 *
	 * @since 6.0
	 */
	protected $notices;

	/**
	 * @var Helper_Pdf_Queue
	 *
	 * @since 6.0
	 */
	protected $queue;

	public function __construct( Helper_Abstract_Form $gform, LoggerInterface $log, Helper_Data $data, Helper_Misc $misc, Helper_Notices $notices, Helper_Pdf_Queue $queue ) {

		/* Assign our internal variables */
		$this->gform   = $gform;
		$this->log     = $log;
		$this->data    = $data;
		$this->misc    = $misc;
		$this->notices = $notices;
		$this->queue   = $queue;
	}

	/**
	 * The Gravity PDF Uninstaller
	 *
	 * @since 6.0
	 */
	public function uninstall_plugin() {
		do_action( 'gfpdf_pre_uninstall_plugin' );

		/* Clean up database */
		if ( is_multisite() ) {
			$sites = get_sites();

			foreach ( $sites as $site ) {
				$site = (array) $site; /* Back-compat: ensure the new site object introduced in 4.6 gets converted back to an array */
				switch_to_blog( $site['blog_id'] );
				$this->remove_plugin_options();
				$this->remove_plugin_form_settings();
			}
			restore_current_blog();

			$this->remove_plugin_network_options();
		} else {
			$this->remove_plugin_options();
			$this->remove_plugin_form_settings();
		}

		/* Removes background processes */
		$this->queue->clear_scheduled_events();
		$this->queue->clear_queue( true );
		$this->queue->unlock_process();

		/* Remove folder structure and deactivate */
		$this->remove_folder_structure();

		do_action( 'gfpdf_post_uninstall_plugin' );

		$this->deactivate_plugin();
	}

	/**
	 * Remove and options stored in the database
	 *
	 * @since 6.0
	 */
	public function remove_plugin_options() {
		delete_option( 'gfpdf_is_installed' );
		delete_option( 'gfpdf_current_version' );
		delete_option( 'gfpdf_settings' );

		/* Remove license API data. Deleting one by one, not with a raw DELETE, lets WordPress drop its cached copies */
		global $wpdb;

		$keys = $wpdb->get_col(
			$wpdb->prepare( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s", $wpdb->esc_like( 'gpdf_sl_' ) . '%' )
		);

		foreach ( $keys as $key ) {
			delete_option( $key );
		}
	}

	/**
	 * Remove the network-shared license package cache stored in sitemeta on a Multisite
	 *
	 * Every extension keeps its own entry (see EDD_SL_Plugin_Updater::get_network_cache_key()), but all are
	 * network-scoped rather than per-site, so one wildcard pass clears them without walking the site list.
	 *
	 * @since 6.16.0
	 */
	public function remove_plugin_network_options() {
		global $wpdb;

		/* delete_network_option() rather than delete_site_option(), which would narrow the sweep to the current
		   network and strand rows belonging to the others sharing this sitemeta table */
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT site_id, meta_key FROM $wpdb->sitemeta WHERE meta_key LIKE %s", $wpdb->esc_like( 'gpdf_sl_net_' ) . '%' )
		);

		foreach ( $rows as $row ) {
			delete_network_option( $row->site_id, $row->meta_key );
		}
	}

	/**
	 * Remove all form settings from each individual form.
	 * Because we stored out PDF settings with each form and have no index we need to individually load and forms and check them for Gravity PDF settings
	 *
	 * @since 6.0
	 */
	public function remove_plugin_form_settings() {
		foreach ( $this->gform->get_forms() as $form ) {
			/* only update forms which have a PDF configuration */
			if ( ! isset( $form['gfpdf_form_settings'] ) ) {
				continue;
			}

			unset( $form['gfpdf_form_settings'] );

			if ( $this->gform->update_form( $form ) !== true ) {
				$this->log->error(
					'Cannot Remove PDF Settings from Form.',
					[
						'form_id' => $form['id'],
					]
				);

				$this->notices->add_error( sprintf( esc_html__( 'There was a problem removing the Gravity Form "%s" PDF configuration. Try delete manually.', 'gravity-pdf' ), $form['id'] . ': ' . $form['title'] ) );
			}
		}
	}

	/**
	 * Remove our PDF directory structure
	 *
	 * @since 6.0
	 */
	public function remove_folder_structure() {

		$paths = apply_filters(
			'gfpdf_uninstall_path',
			[
				$this->data->template_font_location,
				$this->data->template_tmp_location,
				$this->data->template_location,
			]
		);

		foreach ( $paths as $dir ) {
			if ( is_dir( $dir ) ) {
				$results = $this->misc->rmdir( $dir );

				if ( ! $results || is_wp_error( $results ) ) {
					$this->log->error(
						'Cannot Remove Folder Structure.',
						[
							'WP_Error_Message' => $results->get_error_message(),
							'WP_Error_Code'    => $results->get_error_code(),
							'dir'              => $dir,
						]
					);

					$this->notices->add_error( sprintf( esc_html__( 'There was a problem removing the %s directory. Clean up manually via (S)FTP.', 'gravity-pdf' ), '<code>' . $this->misc->relative_path( $dir ) . '</code>' ) );
				}
			}
		}
	}

	/**
	 * Deactivate Gravity PDF
	 *
	 * @since 6.0
	 */
	public function deactivate_plugin() {
		deactivate_plugins( PDF_PLUGIN_BASENAME );
	}
}
