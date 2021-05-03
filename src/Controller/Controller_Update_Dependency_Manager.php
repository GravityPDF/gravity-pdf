<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\View\View_Update_Dependency_Manager;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Controller_Update_Dependency_Manager
 *
 * @package GFPDF\Controller
 */
class Controller_Update_Dependency_Manager extends Helper_Abstract_Controller {

	/**
	 * @var View_Update_Dependency_Manager
	 * @since 5.4
	 */
	public $view;

	/**
	 * @var string Current WordPress version
	 * @since 5.4
	 */
	protected $wp_version;

	/**
	 * @var string Current PHP version
	 * @since 5.4
	 */
	protected $php_version;

	/**
	 * @var string Current Gravity Forms version
	 * @since 5.4
	 */
	protected $gf_version;

	/**
	 * @var string Current plugin version
	 * @since 5.4
	 */
	protected $plugin_version;

	public function __construct( View_Update_Dependency_Manager $view, $php_version, $wp_version, $gf_version, $plugin_version ) {
		$this->view           = $view;
		$this->wp_version     = $wp_version;
		$this->php_version    = $php_version;
		$this->gf_version     = $gf_version;
		$this->plugin_version = $plugin_version;
	}

	/**
	 * @since 5.4
	 */
	public function init() {
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'pre_set_site_transient' ] );
		add_filter( 'after_plugin_row_' . PDF_PLUGIN_BASENAME, [ $this, 'after_plugin_row' ], 10, 2 );
	}

	/**
	 * Prevents major version update if not currently compatible, while still allowing current major version updates
	 *
	 * @param array $value
	 *
	 * @return array
	 *
	 * @since 5.4
	 */
	public function pre_set_site_transient( $value ) {

		/* don't do anything if Gravity PDF update not available */
		if ( ! isset( $value->response[ PDF_PLUGIN_BASENAME ] ) && ! isset( $value->no_update[ PDF_PLUGIN_BASENAME ] ) ) {
			return $value;
		}

		$plugin = isset( $value->response[ PDF_PLUGIN_BASENAME ] ) ? $value->response[ PDF_PLUGIN_BASENAME ] : $value->no_update[ PDF_PLUGIN_BASENAME ];

		/* Do nothing if update for v5 */
		if ( version_compare( $plugin->new_version, '6.0.0', '<' ) ) {
			return $value;
		}

		/*
		 * Move the plugin to the `no_update` list if the current Gravity Forms version isn't compatible with v6
		 *
		 * We check if the PHP version is valid because WordPress will already stop updates and show an error message if it is not.
		 * We don't check for the WP version because it'll already be in the `no_update` list if it isn't compatible.
		 */
		if (
			! isset( $value->no_update[ PDF_PLUGIN_BASENAME ] ) &&
			version_compare( $plugin->new_version, '7.0.0', '<' ) &&
			$this->is_php_compatible( '7.3.0' ) &&
			! $this->is_gf_compatible( '2.5' )
		) {
			/* Not compatible, so move to the no_update list */
			$value->no_update[ PDF_PLUGIN_BASENAME ] = $plugin;
			unset( $value->response[ PDF_PLUGIN_BASENAME ] );
		}

		/*
		 * If there's an update for the plugin, but we don't meet the minimum requirements, check if there's one available
		 * for the current major version (5.x.x) that the user could update to instead.
		 *
		 * This allows us to support two major versions at the same time
		 */
		$update = isset( $value->no_update[ PDF_PLUGIN_BASENAME ] ) ? $this->has_update_for_current_major_version( $value->no_update[ PDF_PLUGIN_BASENAME ]->new_version ) : false;

		if ( is_array( $update ) ) {
			$plugin              = $value->no_update[ PDF_PLUGIN_BASENAME ];
			$plugin->new_version = $update[0];
			$plugin->package     = $update[1];
			unset( $plugin->requires_php );
			unset( $plugin->upgrade_notice );

			$value->response[ PDF_PLUGIN_BASENAME ] = $plugin;
			unset( $value->no_update[ PDF_PLUGIN_BASENAME ] );
		}

		return $value;
	}

	/**
	 * Show error message on plugin list page if minimum requirements a new major version have not been met
	 *
	 * @param string $file
	 * @param array  $plugin
	 *
	 * @since 5.4
	 */
	public function after_plugin_row( $file, $plugin ) {
		/* Do nothing if update for v5 or lower */
		if ( version_compare( $plugin['new_version'], '6.0.0', '<' ) ) {
			return;
		}

		/* Handle v6 minimum requirement checks */
		if ( version_compare( $plugin['new_version'], '7.0.0', '<' ) ) {
			/* If current PHP isn't compatible let WordPress handle the error message */
			if ( ! $this->is_php_compatible( '7.3.0' ) ) {
				return;
			}

			$error_message = esc_html__( 'There is a new version of %1$s available, but it doesn&#8217;t work with your version of %2$s.', 'gravity-forms-pdf-extended' );

			/* Display error if current Gravity Forms isn't compatible */
			if ( ! $this->is_gf_compatible( '2.5.0' ) ) {
				$this->view->plugin_list_error(
					[
						'message' => sprintf( $error_message, $plugin['Name'], 'Gravity Forms' ),
						'plugin'  => $plugin,
					]
				);

				return;
			}

			/* Display error if current WordPress isn't compatible */
			if ( ! $this->is_wp_compatible( '5.3' ) ) {
				$this->view->plugin_list_error(
					[
						'message' => sprintf( $error_message, $plugin['Name'], 'WordPress' ),
						'plugin'  => $plugin,
					]
				);

				return;
			}
		}
	}

	/**
	 * Check if there's a new update available on the current major version, provided the user is on an older major version
	 *
	 * @param string $new_available_version Latest version of the plugin, as retrieved by WP.org
	 *
	 * @return array|false
	 *
	 * @since 5.4
	 */
	protected function has_update_for_current_major_version( $new_available_version ) {

		$current_version_array       = explode( '.', $this->plugin_version );
		$new_available_version_array = explode( '.', $new_available_version );

		/* If the major version is the same for the current version and new available version, don't check for an update */
		if ( $current_version_array[0] === $new_available_version_array[0] ) {
			return false;
		}

		$slug     = dirname( PDF_PLUGIN_BASENAME );
		$response = wp_remote_get( 'https://api.wordpress.org/plugins/info/1.0/' . $slug . '.json' );
		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['versions'] ) || ! is_array( $body['versions'] ) ) {
			return false;
		}

		/* Get the first matching tag for the current major release and url (the latest) */
		foreach ( array_reverse( $body['versions'] ) as $update_version => $download_url ) {
			$update_version_array = explode( '.', $update_version );
			if ( $update_version_array[0] === $current_version_array[0] ) {
				break;
			}
		}

		if ( version_compare( $update_version, $this->plugin_version, '>' ) ) {
			return [ $update_version, $download_url ];
		}

		return false;
	}

	/**
	 * Check if the current WP version exceeds $version
	 *
	 * @param string $version
	 *
	 * @return bool
	 *
	 * @since 5.4
	 */
	protected function is_wp_compatible( $version ) {
		return version_compare( $this->wp_version, $version, '>=' );
	}

	/**
	 * Check if the current GF version exceeds $version
	 *
	 * @param string $version
	 *
	 * @return bool
	 *
	 * @since 5.4
	 */
	protected function is_gf_compatible( $version ) {
		return version_compare( $this->gf_version, $version, '>=' );
	}

	/**
	 * Check if the current PHP version exceeds $version
	 *
	 * @param string $version
	 *
	 * @return bool
	 *
	 * @since 5.4
	 */
	protected function is_php_compatible( $version ) {
		return version_compare( $this->php_version, $version, '>=' );
	}
}
