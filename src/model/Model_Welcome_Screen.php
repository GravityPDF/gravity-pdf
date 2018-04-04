<?php

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Abstract_Model;

use Psr\Log\LoggerInterface;

/**
 * Welcome Screen Model
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
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
 * Model_Welcome_Screen
 *
 * A general class for About / Intro Screen
 *
 * @since 4.0
 */
class Model_Welcome_Screen extends Helper_Abstract_Model {

	/**
	 * @var string The capability users should have to view the page
	 *
	 * @since 4.0
	 */
	public $minimum_capability = 'read';

	/**
	 * @var string The welcome page title
	 *
	 * @since 4.0
	 */
	public $welcome_title;

	/**
	 * @var string The updated page title
	 *
	 * @since 4.0
	 */
	public $updated_title;

	/**
	 * Holds our log class
	 *
	 * @var LoggerInterface
	 * @since 4.0
	 */
	protected $log;

	/**
	 * Setup our view with the needed data and classes
	 *
	 * @param LoggerInterface $log Our logger class
	 *
	 * @since 4.0
	 */
	public function __construct( LoggerInterface $log ) {

		/* Assign our internal variables */
		$this->log = $log;

		$this->welcome_title = esc_html__( 'Welcome to Gravity PDF', 'gravity-forms-pdf-extended' );
	}

	/**
	 * Register the Dashboard Welcome pages and then hide them so they aren't displayed in the navigation
	 *
	 * @since  4.0
	 *
	 * @return void
	 */
	public function admin_menus() {
		add_dashboard_page(
			$this->welcome_title,
			$this->welcome_title,
			$this->minimum_capability,
			'gfpdf-getting-started',
			[ $this, 'getting_started_screen' ]
		);
	}

	/**
	 * Hide the new dashboard pages we registered in self::admin_menus()
	 * We had to move this to the "admin_head" action instead of all in one place
	 * because it was causing authentication problems for some users.
	 *
	 * @since  4.0
	 *
	 * @return void
	 */
	public function hide_admin_menus() {
		remove_submenu_page( 'index.php', 'gfpdf-getting-started' );
	}

	/**
	 * Because we want to hide our welcome pages (using remove_submenu_page) our page titles no longer work
	 * This method will fix that
	 *
	 * @param string $title The page title
	 *
	 * @return string
	 */
	public function add_page_title( $title ) {

		switch ( rgget( 'page' ) ) {
			case 'gfpdf-getting-started':
				return $this->welcome_title;
			break;
		}

		return $title;
	}

	/**
	 * Load our welcome screen
	 *
	 * @return void
	 *
	 * @since 4.2
	 */
	public function getting_started_screen() {
		$controller = $this->getController();
		$controller->view->welcome();
	}
}