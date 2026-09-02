<?php

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Form;
use GFPDF\Helper\Helper_Interface_Extension_Uninstaller;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Pdf_Queue;
use GFPDF\Statics\Deprecation;
use GFPDF_Vendor\Psr\Log\LoggerInterface;

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
			$sites           = get_sites();
			$current_site_id = get_current_blog_id();

			foreach ( $sites as $site ) {
				$site = (array) $site; /* Back-compat: ensure the new site object introduced in 4.6 gets converted back to an array */
				switch_to_blog( $site['blog_id'] );

				$this->remove_plugin_transients();
				$this->remove_plugin_options();
				$this->remove_plugin_form_settings();
			}

			switch_to_blog( $current_site_id );

			$this->remove_plugin_network_options();
		} else {
			$this->remove_plugin_transients();
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

		/* Run addon uninstaller (if any) and deactivate addon */
		$plugins_to_deactivate = [];
		foreach ( $this->data->addon as $addon ) {
			$plugins_to_deactivate[] = plugin_basename( $addon->get_main_plugin_file() );

			if ( $addon instanceof Helper_Interface_Extension_Uninstaller ) {
				$addon->uninstall();
			}
		}

		/* add core plugin to deactivation list and deactivate all Gravity PDF plugins */
		$plugins_to_deactivate[] = PDF_PLUGIN_BASENAME;

		$this->deactivate_plugin( $plugins_to_deactivate );
	}

	/**
	 * Cleanup temporary data
	 *
	 * @since 6.15.0
	 */
	public function remove_plugin_transients() {
		delete_transient( 'gfpdf_settings_user_data' );

		$templates = \GPDFAPI::get_templates_class();
		$templates->flush_template_transient_cache();
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
		Deprecation::delete_stored_data();

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
	 * Remove all PDF form settings for each form
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

				/* translators: %s: form ID and title */
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

					/* translators: %s: directory path wrapped in <code> tags */
					$this->notices->add_error( sprintf( esc_html__( 'There was a problem removing the %s directory. Clean up manually via (S)FTP.', 'gravity-pdf' ), '<code>' . $this->misc->relative_path( $dir ) . '</code>' ) );
				}
			}
		}
	}

	/**
	 * Deactivate plugin
	 *
	 * @param string|array $basename
	 *
	 * @since 6.0
	 * @since 6.15.0 Added $basename argument
	 */
	public function deactivate_plugin( $basename = '' ) {
		if ( empty( $basename ) ) {
			$basename = PDF_PLUGIN_BASENAME;
		}

		if ( ! is_array( $basename ) ) {
			$basename = [ $basename ];
		}

		foreach ( $basename as $plugin ) {
			/* Normal site deactivation */
			if ( ! is_multisite() ) {
				deactivate_plugins( $plugin );
			}

			/* Multisite Network plugin deactivation */
			if ( is_plugin_active_for_network( $plugin ) ) {
				deactivate_plugins( $plugin, false, true );
			}
		}

		if ( ! is_multisite() ) {
			return;
		}

		/* Individual multisite deactivation */
		$sites           = get_sites();
		$current_site_id = get_current_blog_id();

		foreach ( $sites as $site ) {
			$site = (array) $site;
			switch_to_blog( $site['blog_id'] );

			foreach ( $basename as $plugin ) {
				deactivate_plugins( $plugin );
			}
		}

		switch_to_blog( $current_site_id );
	}
}
