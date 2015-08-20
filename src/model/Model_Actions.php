<?php

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Options;

/**
 * Action Model
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
 * Model_Actions
 *
 * Handles the grunt work of our one-time actions
 *
 * @since 4.0
 */
class Model_Actions extends Helper_Abstract_Model {

    /**
     * Holds our Helper_Options / Helper_Options_Fields object
     * Makes it easy to access global PDF settings and individual form PDF settings
     * @var Object
     * @since 4.0
     */
    protected $options;

    /**
     * Load our model and view and required actions
     */
    public function __construct( Helper_Options $options ) {
        
        /* Assign our internal variables */
        $this->options = $options;
    }


    /**
     * Check if the current notice has already been dismissed
     * @param  String  $type The current notice ID
     * @return boolean       True if dismissed, false otherwise
     * @since 4.0
     */
    public function is_notice_already_dismissed( $type ) {

        $dismissed_notices = $this->options->get_option( 'action_dismissal', array() );

        if( isset( $dismissed_notices[ $type ] ) ) {
            return true;
        }

        return false;
    }

    /**
     * Mark the current notice as being dismissed
     * @param  String $type The current notice ID
     * @return void
     * @since 4.0
     */
    public function dismiss_notice( $type ) {

        $dismissed_notices = $this->options->get_option( 'action_dismissal', array() );
        $dismissed_notices[ $type ] = $type;
        $this->options->update_option( 'action_dismissal', $dismissed_notices );
    }

    /**
     * Check if our review notice condition has been met
     * A review will only display if more than 100 PDFs have been generated
     * @return Boolean
     * @since 4.0
     */
	public function review_condition() {

        $total_pdf_count = (int) $this->options->get_option( 'pdf_count', 0 );

        if( 100 < $total_pdf_count ) {
            return true;
        }

        return false;
	}


}
