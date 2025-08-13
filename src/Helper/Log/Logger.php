<?php

namespace GFPDF\Helper\Log;

use Exception;
use GFLogging;
use GFPDF_Vendor\Monolog\Formatter\LineFormatter;
use GFPDF_Vendor\Monolog\Handler\NullHandler;
use GFPDF_Vendor\Monolog\Handler\StreamHandler;
use GFPDF_Vendor\Monolog\Logger as MonoLogger;
use GFPDF_Vendor\Monolog\Processor\IntrospectionProcessor;
use GFPDF_Vendor\Monolog\Processor\MemoryPeakUsageProcessor;
use Psr\Log\LoggerInterface;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enable Logging for Gravity PDF et al.
 *
 * @since 6.14.0 Moved from \GFPDF\Helper\Helper_Logger
 */
class Logger {

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
	 * @return LoggerInterface
	 *
	 * @since 4.2
	 */
	public function get_logger() {

		if ( ! $this->log instanceof MonoLogger ) {
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
			$this->log = new MonoLogger( $this->slug );
			$this->log->setTimezone( wp_timezone() );

			$this->setup_gravityforms_logging();

			/* Check if we have a handler pushed and add our Introspection and Memory Peak usage processors */
			if ( count( $this->log->getHandlers() ) > 0 && substr( php_sapi_name(), 0, 3 ) !== 'cli' ) {
				$this->log->pushProcessor( new IntrospectionProcessor() );
				$this->log->pushProcessor( new MemoryPeakUsageProcessor() );

				return;
			}
		} catch ( Exception $e ) {
			/* do nothing */
		}

		/* Disable logging if using CLI, or if Gravity Forms logging isn't enabled */
		$this->log->pushHandler( new NullHandler( MonoLogger::INFO ) ); /* throw logs away */
	}

	/**
	 * Setup Gravity Forms logging, if currently enabled by the user
	 *
	 * @return void
	 *
	 * @since 4.2
	 * @since 6.14.0 Dropped support for separate GF Logging plugin + Gravity Forms < 2.5
	 */
	protected function setup_gravityforms_logging() {
		/* Skip if dependency or setting not available/enabled */
		if ( ! class_exists( 'GFLogging' ) || ! get_option( 'gform_enable_logging' ) ) {
			return;
		}

		$gf_logger          = GFLogging::get_instance();
		$gf_logger_settings = $gf_logger->get_plugin_settings();

		/* Check logging is enabled for this plugin */
		if ( empty( $gf_logger_settings[ $this->slug ]['enable'] ) ) {
			return;
		}

		$log_level    = (int) ( $gf_logger_settings[ $this->slug ]['log_level'] ?? 0 );
		$log_filename = $gf_logger->get_log_file_name( $this->slug );

		/* Skip if log level is 0 or 6 ("off" in GF world) */
		if ( empty( $log_level ) || $log_level === 6 ) {
			return;
		}

		/* Check log file can be created */
		$log_path = dirname( $log_filename );
		if ( ! wp_mkdir_p( $log_path ) ) {
			return;
		}

		if ( ! @touch( $log_filename ) ) { // phpcs:ignore
			return;
		}

		/* Convert Gravity Forms log levels to the appropriate Monolog level */
		$monolog_level = $log_level === 4 ? MonoLogger::ERROR : MonoLogger::DEBUG;

		/* Setup our stream and change the format to more-suit Gravity Forms */
		$formatter = new LineFormatter( "%datetime% - %level_name% --> %message%\n|--> %context%\n|--> %extra%\n", 'Y-m-d H:i:s (P)' );
		$stream    = new StreamHandler( $log_filename, $monolog_level );
		$stream->setFormatter( $formatter );

		/* Add our log file stream */
		$this->log->pushHandler( $stream );

		/* Add a redact handler to mask sensitive details */
		$redact = new Redact_Processor(
			[
				/* license keys */
				'license'         => -4,
				'edd_license_key' => -4,

				/* direct update links */
				'package'         => 23,
				'download_link'   => 23,
			],
			'*',
			'%s',
			32
		);

		$this->log->pushProcessor( $redact );
	}
}
