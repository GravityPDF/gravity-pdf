<?php

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Abstract_Model;

use Psr\Log\LoggerInterface;

/**
 * Welcome Screen Model
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF Copyright (C) 2015 Blue Liquid Designs

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
	public $minimum_capability = 'manage_options';

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

		$this->welcome_title = __( 'Welcome to Gravity PDF', 'gravity-forms-pdf-extended' );
		$this->updated_title = __( "What's new in Gravity PDF?", 'gravity-forms-pdf-extended' );
	}

	/**
	 * Register the Dashboard Welcome pages and then hide them so they aren't displayed in the navigation
	 *
	 * @since  4.0
	 *
	 * @return void
	 */
	public function admin_menus() {
		$controller = $this->getController();

		add_dashboard_page(
			$this->welcome_title,
			$this->welcome_title,
			$this->minimum_capability,
			'gfpdf-getting-started',
			array( $controller, 'getting_started_screen' )
		);

		add_dashboard_page(
			$this->updated_title,
			$this->updated_title,
			$this->minimum_capability,
			'gfpdf-update',
			array( $controller, 'update_screen' )
		);

		/* hide the new page from the menu bar */
		remove_submenu_page( 'index.php', 'gfpdf-getting-started' );
		remove_submenu_page( 'index.php', 'gfpdf-update' );
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
				$this->log->addNotice( 'Display Welcome Screen' );

				return $this->welcome_title;
				break;

			case 'gfpdf-update':
				$this->log->addNotice( 'Display Update Screen' );

				return $this->updated_title;
				break;
		}

		return $title;
	}
}
