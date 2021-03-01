<?php

namespace GFPDF\Helper;

use Exception;
use GF_Background_Process;
use GFCommon;
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

if ( ! class_exists( 'WP_Async_Request' ) ) {
	require_once( GFCommon::get_base_path() . '/includes/libraries/wp-async-request.php' );
}

if ( ! class_exists( 'GF_Background_Process' ) ) {
	require_once( GFCommon::get_base_path() . '/includes/libraries/gf-background-process.php' );
}

/**
 * Class Helper_Pdf_Queue
 *
 * @package GFPDF\Helper
 */
class Helper_Pdf_Queue extends GF_Background_Process {

	/**
	 * Holds our log class
	 *
	 * @var LoggerInterface
	 *
	 * @since 5.0
	 */
	protected $log;

	/**
	 * @var string
	 *
	 * @since 5.0
	 */
	protected $action = 'gravitypdf';

	/**
	 * Helper_Pdf_Queue constructor.
	 *
	 * @param LoggerInterface $log
	 *
	 * @since 4 .4
	 */
	public function __construct( LoggerInterface $log ) {
		parent::__construct();

		$this->log = $log;
	}

	/**
	 * Add a getter for the stored async data
	 *
	 * @return array
	 *
	 * @since 5.0
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Process our PDF queue as a background process
	 *
	 * @param array $callbacks [ 'func' => callback, 'args' => array ]
	 *
	 * @return array|false Return false if our queue has completed, otherwise return the remaining callbacks
	 *
	 * @since 5.0
	 */
	public function task( $callbacks ) {
		$callback = array_shift( $callbacks );

		$this->log->notice(
			sprintf(
				'Begin async PDF task for %s',
				$callback['id']
			)
		);

		if ( is_callable( $callback['func'] ) ) {
			try {
				/* Call our use function and pass in any arguments */
				$args = ( isset( $callback['args'] ) && is_array( $callback['args'] ) ) ? $callback['args'] : [];
				call_user_func_array( $callback['func'], $args );
			} catch ( Exception $e ) {

				/* Log Error */
				$this->log->error(
					sprintf(
						'Async PDF task error for %s',
						$callback['id']
					),
					[
						'args'      => ( isset( $callback['args'] ) ) ? $callback['args'] : [],
						'exception' => $e->getMessage(),
					]
				);

				/* Add back to our queue to retry (up to a grand total of three times) */
				if ( empty( $callback['retry'] ) || $callback['retry'] < 2 ) {
					$callback['retry'] = isset( $callback['retry'] ) ? $callback['retry'] + 1 : 1;
					array_unshift( $callbacks, $callback );
				} else {
					$this->log->error(
						sprintf(
							'Async PDF task retry limit reached for %s.',
							$callback['id']
						)
					);

					if ( $callback['unrecoverable'] ?? false ) {
						$this->log->critical(
							'Cancel async queue due to retry limit reached on unrecoverable callback.',
							[
								'callbacks' => $callbacks,
							]
						);

						$callbacks = [];
					}
				}
			}
		}

		$this->log->notice(
			sprintf(
				'End async PDF task for %s',
				$callback['id']
			)
		);

		return ( count( $callbacks ) > 0 ) ? $callbacks : false;
	}
}
