<?php

namespace GFPDF\Helper\Log;

use Exception;
use GFLogging;
use GFPDF_Vendor\Monolog\Formatter\LineFormatter;
use GFPDF_Vendor\Monolog\Handler\NullHandler;
use GFPDF_Vendor\Monolog\Handler\StreamHandler;
use GFPDF_Vendor\Monolog\Logger as MonoLoggerPsrLog1;
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
 *  Moved from \GFPDF\Helper\Helper_Logger
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
	 * Defines the maximum file size for a log file.
	 *
	 * @since    6.14.0
	 * @internal copied from \GFLogging to do log rotations
	 */
	protected $max_file_size = 5242880;

	/**
	 * Defines the maximum number of log files to store for a plugin.
	 *
	 * @since    6.14.0
	 * @internal copied from \GFLogging to do log rotations
	 */
	protected $max_file_count = 10;

	/**
	 * Defines the date format for logged messages.
	 *
	 * @since    6.14.0
	 * @internal copied from \GFLogging to do log rotations
	 */
	protected $date_format_log_file = 'YmdGis';

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

		if ( ! isset( $this->log ) ) {
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
			$this->log = $this->get_monolog();
			$this->log->setTimezone( wp_timezone() );

			$this->setup_gravityforms_logging();

			/* Check if we have a handler pushed and add our Introspection and Memory Peak usage processors */
			if ( count( $this->log->getHandlers() ) > 0 && substr( php_sapi_name(), 0, 3 ) !== 'cli' ) {
				$this->log->pushProcessor( new IntrospectionProcessor( MonoLoggerPsrLog1::DEBUG, [ 'MonoLogger' ] ) );
				$this->log->pushProcessor( new MemoryPeakUsageProcessor() );

				return;
			}
		} catch ( Exception $e ) {
			/* do nothing */
		}

		/* Disable logging if using CLI, or if Gravity Forms logging isn't enabled */
		$this->log->pushHandler( new NullHandler( MonoLoggerPsrLog1::INFO ) ); /* throw logs away */
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

		/* Add support for rotating logs */
		$this->rotate_logs( $log_filename );

		/* Convert Gravity Forms log levels to the appropriate Monolog level */
		$monolog_level = $log_level === 4 ? MonoLoggerPsrLog1::ERROR : MonoLoggerPsrLog1::DEBUG;

		/* Setup our stream and change the format to more-suit Gravity Forms */
		$formatter = new LineFormatter( "%datetime% - %level_name% --> %message%\n|--> %context%\n|--> %extra%\n", 'Y-m-d H:i:s.u (P)' );
		$stream    = new StreamHandler( $log_filename, $monolog_level );
		$stream->setFormatter( $formatter );

		/* Add our log file stream */
		$this->log->pushHandler( $stream );
	}

	/**
	 * Clean up log files.
	 *
	 * @param string $file_path Path to log.
	 *
	 * @since    6.14.0
	 * @internal copied from \GFLogging::maybe_reset_logs()
	 */
	protected function rotate_logs( $file_path ) {
		$gmt_offset = get_option( 'gmt_offset', 0 ) * 3600;
		$path       = pathinfo( $file_path );
		$folder     = $path['dirname'] . '/';
		$file_base  = $path['filename'];
		$file_ext   = $path['extension'];

		/* Check size of current file. If greater than max file size, rename using time. */
		if ( is_file( $file_path ) && filesize( $file_path ) > $this->max_file_size ) {
			$adjusted_date = gmdate( $this->date_format_log_file, time() + $gmt_offset );
			$new_file_name = $file_base . '_' . $adjusted_date . '.' . $file_ext;
			@rename( $file_path, $folder . $new_file_name ); // phpcs:ignore
		}

		/* Get files which match the base name. */
		$similar_files = \GFCommon::glob( $file_base . '*.*', $folder );
		$file_count    = count( $similar_files );

		/* Check quantity of files and delete older ones if too many. */
		if ( false !== $similar_files && $file_count > $this->max_file_count ) {

			/* Sort by date so oldest are first. */
			usort(
				$similar_files,
				function ( $a, $b ) {
					return filemtime( $a ) - filemtime( $b );
				}
			);

			$delete_count = $file_count - $this->max_file_count;

			for ( $i = 0; $i < $delete_count; $i++ ) {
				if ( is_file( $similar_files[ $i ] ) ) {
					@unlink( $similar_files[ $i ] ); // phpcs:ignore
				}
			}
		}
	}

	/**
	 * Return a class that is compatible with the PSR/Log version loaded
	 *
	 * This compatibility layer is necessary because:
	 *
	 * - \Psr\Log\LoggerInterface is type-hinted throughout Gravity PDF + add-ons and not easily altered
	 * - Gravity PDF includes v1 of the Psr\Log library, which is incompatible with v2 and v3
	 * - While Gravity PDF supports PHP 7.3+ the Psr\Log library cannot be upgraded from v1
	 * - Other WordPress plugins are shipping Psr\Log v2 and v3
	 * - A PHP declaration error occurs when Gravity PDF runs alongside Psr\Log v2 or v3
	 *
	 * This is a temporary measure. Over time our plugins will be updated to support both \Psr\Log\LoggerInterface and '\GFPDF_Vendor\Psr\Log\LoggerInterface' and this won't be required.
	 *
	 * @return LoggerInterface
	 * @since 6.14.0
	 *
	 */
	protected function get_monolog() {
		static $psr_log_version;

		if ( empty( $psr_log_version ) ) {
			/* Check what version of \Psr\Log is actually loaded and return a compatible version of Monolog */
			if ( trait_exists( '\Psr\Log\LoggerTrait' ) ) {
				$reflected       = new \ReflectionClass( '\Psr\Log\LoggerTrait' );
				$psr_log_content = file_get_contents( $reflected->getFileName() ); //phpcs:ignore

				/* Test for v3 */
				if ( strpos( $psr_log_content, 'function log($level, string|\Stringable $message, array $context = []): void;' ) !== false ) {
					$psr_log_version = 3;
				}

				/* Test for v2 */
				if ( strpos( $psr_log_content, 'function log($level, string|\Stringable $message, array $context = []);' ) !== false ) {
					$psr_log_version = 2;
				}

				/* Default to v1 when no match found */
				if ( empty( $psr_log_version ) ) {
					$psr_log_version = 1;
				}
			} else {
				/* No PSR Log library loaded. Alias namespaced v1 */
				$psr_log_version = 1;
				class_alias( '\GFPDF_Vendor\Psr\Log\AbstractLogger', '\Psr\Log\AbstractLogger' );
				class_alias( '\GFPDF_Vendor\Psr\Log\InvalidArgumentException', '\Psr\Log\InvalidArgumentException' );
				class_alias( '\GFPDF_Vendor\Psr\Log\LoggerAwareInterface', '\Psr\Log\LoggerAwareInterface' );
				class_alias( '\GFPDF_Vendor\Psr\Log\LoggerAwareTrait', '\Psr\Log\LoggerAwareTrait' );
				class_alias( '\GFPDF_Vendor\Psr\Log\LoggerInterface', '\Psr\Log\LoggerInterface' );
				class_alias( '\GFPDF_Vendor\Psr\Log\LoggerTrait', '\Psr\Log\LoggerTrait' );
				class_alias( '\GFPDF_Vendor\Psr\Log\LogLevel', '\Psr\Log\LogLevel' );
				class_alias( '\GFPDF_Vendor\Psr\Log\NullLogger', '\Psr\Log\NullLogger' );
			}
		}

		switch ( $psr_log_version ) {
			case 1:
				return new MonoLoggerPsrLog1( $this->slug );

			case 2:
			case 3:
				return new MonoLoggerPsrLog2And3( $this->slug );
		}
	}
}
