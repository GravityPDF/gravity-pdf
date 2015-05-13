<?php

/**
 * Autoloader Functionality 
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

/*
 * As we support PHP5.2+ we'll need to create our own autoloader as we cannot use namespaces
 */

class GFPDF_Autoloader {

    /**
     * The path to the plugin directory 
     * We'll use this to correctly autoload our files 
     * @var string
     */
    private $path;

    /**
     * Ensure the classes trying to be autoloaded have our prefix 
     * If not, we'll skip over them
     * @var string
     */
    private $prefix = 'GFPDF_';

    /**
     * Register our autoload methods and set up class variables 
     * @since 4.0
     */
    public function __construct($path) {
        /* setup class variables */
        $this->path = $path;

        /* setup autoloaders */
        spl_autoload_register(array($this, 'helper'));
        spl_autoload_register(array($this, 'controller'));
        spl_autoload_register(array($this, 'model'));
        spl_autoload_register(array($this, 'view'));     
        spl_autoload_register(array($this, 'loadStatic'));  

        /* include depreciated functionality */
        require_once PDF_PLUGIN_DIR . 'src/depreciated.php';  
    }   

    /**
     * Autoload helper classes  
     * @param String $className The class name to load (if able)
     * @internal filename should include 'Helper_' to make it easy to distinguish where the class is loaded from 
     * @since 4.0
     * @return void
     */
    public function helper($className) {        
        $filename = $this->path . 'src/helper/' . $className . '.php';
        $valid    = $this->is_class_valid($className);

        if ($valid && is_readable($filename)) {
            require_once($filename);
        }
    }    

    /**
     * Autoload controller class function
     * @param String $className The class name to load (if able)
     * @internal filename should include 'Controller_' to make it easy to distinguish where the class is loaded from 
     * @since 4.0
     * @return void
     */
    public function controller($className) {
        $filename = $this->path . 'src/controller/' . $className . '.php';
        $valid    = $this->is_class_valid($className);

        if ($valid && is_readable($filename)) {
            require_once($filename);
        }
    }    

    /**
     * Autoload model class function
     * @param String $className The class name to load (if able)
     * @internal filename should include 'Model_' to make it easy to distinguish where the class is loaded from 
     * @return void
     */
    public function model($className) {
        $filename = $this->path . 'src/models/' . $className . '.php';
        $valid    = $this->is_class_valid($className);

        if ($valid && is_readable($filename)) {
            require_once($filename);
        }
    }

    /**
     * Autoload view class function
     * @param String $className The class name to load (if able)
     * @internal filename should include 'View_' to make it easy to distinguish where the class is loaded from 
     * @return void
     */
    public function view($className) {
        $filename = $this->path . 'src/views/' . $className . '.php';
        $valid    = $this->is_class_valid($className);

        if ($valid && is_readable($filename)) {
            require_once($filename);
        }
    }  

    /**
     * Autoload helper classes  
     * @param String $className The class name to load (if able)
     * @internal filename should include 'Helper_' to make it easy to distinguish where the class is loaded from 
     * @since 4.0
     * @return void
     */
    public function loadStatic($className) {        
        $filename = $this->path . 'src/static/' . $className . '.php';
        $valid    = $this->is_class_valid($className);

        if ($valid && is_readable($filename)) {
            require_once($filename);
        }
    }      

    /**
     * Check if the class name is prefixed with our plugin's designated prefix string
     * @param  String  $className The class name
     * @return boolean            Whether the name is valid 
     */
    private function is_class_valid($className) {
        $length = strlen($this->prefix);

        if(substr($className, 0, $length) === $this->prefix) {
            return true;
        }

        return false;
    }        
}

/*
 * Load our autoloader class 
 */
new GFPDF_Autoloader(PDF_PLUGIN_DIR);