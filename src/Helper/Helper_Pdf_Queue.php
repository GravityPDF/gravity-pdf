<?php

namespace GFPDF\Helper;

use Exception;

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
 * Class Helper_Pdf_Queue
 *
 * @package GFPDF\Helper
 */
class Helper_Pdf_Queue extends Helper_Abstract_Queue {

	/**
	 * @var string
	 *
	 * @since 5.0
	 */
	protected $action = 'gravitypdf';

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
