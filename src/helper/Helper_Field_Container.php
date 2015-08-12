<?php

namespace GFPDF\Helper;

use GF_Field;

/**
 * Splits up the PDF fields so that floats can be better supported in respect to
 * Gravity Forms CSS Ready Classes.
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

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
 * @since 4.0
 */
class Helper_Field_Container {
    
    /**
     * Holds the current width of our container based on the field passed in
     * The value is out of 100
     * @var Integer
     * @since 4.0
     */
    private $current_width = 0;

    /**
     * Boolean value to tell if the element is currently opened
     * @var boolean
     * @since 4.0
     */
    private $currently_open = false;

    /**
     * Matches class names to width percentages
     * @var array
     * @since 4.0
     */
    private $class_map = array(
        'gf_left_half'    => 50,
        'gf_right_half'   => 50,
        'gf_left_third'   => 33.3,
        'gf_middle_third' => 33.3,
        'gf_right_third'  => 33.3,
    );

    /**
     * The HTML tag used when opening the container
     * @var string
     * @since 4.0
     */
    private $open_tag = '<div class="row-separator">';

    /**
     * The HTML tag used when closing the container
     * @var string
     * @since 4.0
     */
    private $close_tag = '</div>';

    /**
     * The Gravity Form fields we should not wrap in a container
     * @var array
     * @since 4.0
     */
    private $skip_fields = array(
        'page',
        'section',
        'html',
    );

    /**
     * Set up the object
     * @param array $config Allow user to override the open / close tag and which fields are skipped
     * @since 4.0
     */
    public function __construct($config = array()) {
        if(isset($config['open_tag'])) {
            $this->open_tag = $config['open_tag'];
        }

        if(isset($config['close_tag'])) {
            $this->close_tag = $config['close_tag'];
        }

        if(isset($config['skip_fields'])) {
            $this->skip_fields = $config['skip_fields'];
        }
    }


    /**
     * Handles the opening and closing of our container
     * @param  GF_Field $field The Gravity Form field currently being processed
     * @return void
     * @since 4.0
     */
    public function generate(GF_Field $field) {

        /* Check if we are processing a field that should be skipped */
        if($this->process_skipped_fields($field)) {
            return; /* exit early if skipped field processed */
        }

        /* check if we need to close the container */
        if($this->currently_open) {
            $this->handle_open_container($field);
        }

        /* Open the tag if not currently opened*/
        if(!$this->currently_open) {
            $this->handle_closed_container($field);
        }
    }

    /**
     * Close the current container if still open.
     * This is usually called publically after the form loop
     * @return void
     * @since 4.0
     */
    public function close() {
        if($this->currently_open) {
            $this->close_container();
            $this->reset();
        }
    }

    /**
     * Open the container
     * @param  GF_Field $field The Gravity Form field currently being processed
     * @return void
     * @since 4.0
     */
    private function handle_closed_container($field) {
        $this->start();
        $this->open_container();
        $this->increment_width($field->cssClass);
    }

    /**
     * Determine if we should close a container based on its classes
     * @param  GF_Field $field The Gravity Form field currently being processed
     * @return void
     * @since 4.0
     */
    private function handle_open_container($field) {
        $width = $this->get_field_width($field->cssClass); /* current field width */

        /* if the current field width is more than 100 we will close the container */
        if( ($this->current_width + $width) > 100) {
            $this->close();
        } else {
            $this->increment_width($field->cssClass);
        }
    }

    /**
     * Process our skipped Gravity Form fields (close the container if needed)
     * @param  GF_Field $field The Gravity Form field currently being processed
     * @return boolean true if we processed a skipped field, false otherwise
     * @since 4.0
     */
    private function process_skipped_fields($field) {
        /* if we have a skipped field and the container is open we will close it */
        if(in_array($field->type, $this->skip_fields)) {
            if($this->currently_open) {
                $this->close_container();
                $this->reset();
            }
            return true;
        }

        return false;
    }

    /**
     * Output the open tag
     * @return void
     * @since 4.0
     */
    private function open_container() {
        echo $this->open_tag;
    }

    /**
     * Output the close tag
     * @return void
     * @since 4.0
     */
    private function close_container() {
        echo $this->close_tag;
    }

    /**
     * Mark our class as currently being open
     * @return void
     * @since 4.0
     */
    private function start() {
        $this->currently_open = true;
    }

    /**
     * Reset our class back to its original state
     * @return void
     * @since 4.0
     */
    private function reset() {
        $this->currently_open = false;
        $this->current_width = 0;
    }

    /**
     * Increment our current field width
     * @param  String $classes The field classes
     * @return void
     * @since  4.0
     */
    private function increment_width($classes) {
        $this->current_width += $this->get_field_width($classes);
    }

    /**
     * Loop through all classes and return our class map if found, or 100
     * @param  String $classes The field classes
     * @return void
     * @since  4.0
     */
    private function get_field_width($classes) {
        $classes = explode(' ', $classes);

        foreach($classes as $class) {
            if(isset($this->class_map[$class])) {
                /* return field width */
                return $this->class_map[$class];
            }
        }

        /* no match, so assuming full width */
        return 100;
    }
}