<?php

namespace GFPDF\Helper;

use Exception;
use GFFormsModel;
use GFLogging;
use GFPDF_Vendor\Monolog\Formatter\LineFormatter;
use GFPDF_Vendor\Monolog\Handler\NullHandler;
use GFPDF_Vendor\Monolog\Handler\StreamHandler;
use GFPDF_Vendor\Monolog\Logger;
use GFPDF_Vendor\Monolog\Processor\IntrospectionProcessor;
use GFPDF_Vendor\Monolog\Processor\MemoryPeakUsageProcessor;
use Psr\Log\LoggerInterface;

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
 * An abstract class to assist with logging
 */
class Helper_Logger {

	/**
	 * @var string
	 *
	 * @since 4.2
	 */
	protected $slug;

	/**
	 * @var string
	 *
	 * @since 4.2
	 */
	protected $name;

	/**
	 * Holds our log class
	 *
	 * @var LoggerInterface
	 *
	 * @since 4.2
	 */
	protected $log;

	/**
	 * Helper_Logger constructor.
	 *
	 * @param string $slug
	 * @param string $name
	 *
	 * @since 4.2
	 */
	public function __construct( $slug, $name ) {
		$this->slug = $slug;
		$this->name = $name;
	}

	/**
	 * Returns the logger instance, and initiates it if needed
	 *
	 * @return Logger
	 *
	 * @since 4.2
	 */
	public function get_logger() {

		if ( ! $this->log instanceof Logger ) {
			$this->setup_logger();
			add_filter( 'gform_logging_supported', [ $this, 'register_logger_with_gf' ] );
		}

		return $this->log;
	}

	/**
	 * Register our plugin with Gravity Form's Logger
	 *
	 * @param array $loggers
	 *
	 * @return array
	 *
	 * @since 4.2
	 */
	public function register_logger_with_gf( $loggers ) {
		$loggers[ $this->slug ] = $this->name;

		return $loggers;
	}

	/**
	 * Initialise our logging class (we're using Monolog instead of Gravity Form's KLogger)
	 * and set up appropriate handlers based on the logger settings
	 *
	 * @return void
	 *
	 * @since 4.2
	 */
	protected function setup_logger() {

		/* Setup our Gravity Forms local file logger, if enabled */
		try {
			$this->log = new Logger( $this->slug );
			$this->log->setTimezone( wp_timezone() );

			$this->setup_gravityforms_logging();

			/* Check if we have a handler pushed and add our Introspection and Memory Peak usage processors */
			if ( count( $this->log->getHandlers() ) > 0 && substr( php_sapi_name(), 0, 3 ) !== 'cli' ) {
				$this->log->pushProcessor( new IntrospectionProcessor );
				$this->log->pushProcessor( new MemoryPeakUsageProcessor );

				$this->log->notice( 'Log initialized' );

				return;
			}
		} catch ( Exception $e ) {
			/* do nothing */
		}

		/* Disable logging if using CLI, or if Gravity Forms logging isn't enabled */
		$this->log->pushHandler( new NullHandler( Logger::INFO ) ); /* throw logs away */
	}

	/**
	 * Setup Gravity Forms logging, if currently enabled by the user
	 *
	 * @return void
	 *
	 * @since 4.2
	 */
	protected function setup_gravityforms_logging() {

		/* Check if Gravity Forms logging is enabled and push stream logging */
		if ( class_exists( 'GFLogging' ) ) {

			/*
			 * Get the current plugin logger settings and check if it's enabled
			 * The new version of the logger uses the add-on storage method, while the old one stores it in gf_logging_settings
			 * so we'll test which settings we should use and get the appropriate log level
			 */
			if ( ! get_option( 'gform_enable_logging' ) && ( ! defined( 'GF_LOGGING_VERSION' ) || version_compare( GF_LOGGING_VERSION, '1.1', '<' ) ) ) {
				$settings = get_option( 'gf_logging_settings' );

				$log_level    = (int) rgar( $settings, $this->slug );
				$log_filename = GFFormsModel::get_upload_root() . 'logs/' . $this->slug . '.txt';
			} else {
				$gf_logger          = GFLogging::get_instance();
				$gf_logger_settings = $gf_logger->get_plugin_settings();

				if ( isset( $gf_logger_settings[ $this->slug ]['enable'] ) && $gf_logger_settings[ $this->slug ]['enable'] ) {
					$log_level    = ( isset( $gf_logger_settings[ $this->slug ]['log_level'] ) ) ? (int) $gf_logger_settings[ $this->slug ]['log_level'] : 0;
					$log_filename = get_option( 'gform_enable_logging' ) ? $gf_logger->get_log_file_name( $this->slug ) : $gf_logger::get_log_file_name( $this->slug );
				}
			}

			/* Enable logging if not equivalent to 0 or non-existent and not level 6 ("off" in GF world) */
			if ( ! empty( $log_level ) && $log_level !== 6 ) {

				/* Convert Gravity Forms log levels to the appropriate Monolog level */
				$monolog_level = ( $log_level === 4 ) ? Logger::ERROR : Logger::DEBUG;

				/* Setup our stream and change the format to more-suit Gravity Forms */
				$formatter = new LineFormatter( "%datetime% - %level_name% --> %message%\n|--> %context%\n|--> %extra%\n", 'Y-m-d H:i:s (P)' );
				$stream    = new StreamHandler( $log_filename, $monolog_level );
				$stream->setFormatter( $formatter );

				/* Add our log file stream */
				$this->log->pushHandler( $stream );
			}
		}
	}
}
