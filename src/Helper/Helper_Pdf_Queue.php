<?php

namespace GFPDF\Helper;

use Exception;
use GF_Background_Process;
use GFCommon;
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
	 * Restrict object instantiation when using unserialize.
	 *
	 * @since 2.9.7
	 *
	 * @var bool|array
	 */
	protected $allowed_batch_data_classes = false;

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

		/* Something went wrong so cancel queue */
		if ( ! isset( $callback['id'], $callback['func'] ) ) {
			$this->log->critical( 'PDF queue ran with invalid queue item', [ 'callbacks' => $callbacks ] );

			return false;
		}

		$this->log->notice(
			sprintf(
				'Begin async PDF task for %s',
				$callback['id']
			)
		);

		/* Something went wrong so cancel queue */
		if ( ! is_callable( $callback['func'] ) ) {
			$this->log->critical(
				'PDF queue ran with invalid callback',
				[
					'callback'  => $callback,
					'callbacks' => $callbacks,
				]
			);

			return false;
		}

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

			/* Add back to our queue to retry once */
			if ( empty( $callback['retry'] ) ) {
				$callback['retry'] = 1;
				array_unshift( $callbacks, $callback );
			} else {
				$this->log->error(
					sprintf(
						'Async PDF task retry limit reached for %s.',
						$callback['id']
					)
				);
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
