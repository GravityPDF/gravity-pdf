<?php

namespace GFPDF\Helper;

use Psr\Log\LoggerInterface;

use GFCommon;
use GF_Background_Process;

use Exception;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2017, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2017, Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

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

		$this->log->addNotice( sprintf(
			'Begin async PDF task for %s',
			$callback['id']
		) );

		if ( is_callable( $callback['func'] ) ) {
			try {
				/* Call our use function and pass in any arguments */
				$args = ( isset( $callback['args'] ) && is_array( $callback['args'] ) ) ? $callback['args'] : [];
				call_user_func_array( $callback['func'], $args );
			} catch ( Exception $e ) {

				/* Log Error */
				$this->log->addError( sprintf(
					'Async PDF task error for %s',
					$callback['id']
				), [ 'args' => ( isset( $callback['args'] ) ) ? $callback['args'] : [] ] );

				/* Add back to our queue to retry (up to a grand total of three times) */
				if ( empty( $callback['retry'] ) || $callback['retry'] < 2 ) {
					$callback['retry'] = isset( $callback['retry'] ) ? $callback['retry'] + 1 : 1;
					array_unshift( $callbacks, $callback );
				}
			}
		}

		$this->log->addNotice( sprintf(
			'End async PDF task for %s',
			$callback['id']
		) );

		return ( count( $callbacks ) > 0 ) ? $callbacks : false;
	}
}