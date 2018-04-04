<?php

namespace GFPDF\Helper;

use Monolog\Logger;

/**
 * Add logger support to class
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.3
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2018, Blue Liquid Designs

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

/**
 * @since 4.3
 */
trait Helper_Trait_Logger {

	/**
	 * Holds our log class
	 *
	 * @var \Monolog\Logger
	 *
	 * @since 4.3
	 */
	protected $logger;

	/**
	 * @param Logger $log
	 *
	 * @since 4.3
	 */
	public function set_logger( Logger $log ) {
		$this->logger = $log;
	}

	/**
	 * @return Logger
	 *
	 * @since 4.3
	 */
	public function get_logger() {
		return $this->logger;
	}
}
