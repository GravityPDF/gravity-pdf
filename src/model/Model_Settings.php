<?php

namespace GFPDF\Model;
use GFPDF\Helper\Helper_Model;

/**
 * Settings Model
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
 * Model_Welcome_Screen
 *
 * A general class for About / Intro Screen
 *
 * @since 4.0
 */
class Model_Settings extends Helper_Model {
  
    /**
     * Add meta boxes used in the settings "help" tab
     * @since  4.0
     * @return  void
     */
    public function add_meta_boxes() {
        /* set the meta box id */
        $id = 'pdf_knowledgebase';        
        add_meta_box( 
            $id,
            __( 'Documentation' ),
            array($this, 'add_meta_' . $id),
            'pdf-help-and-support',
            'row-1'
        );

        /* set the meta box id */
        $id = 'pdf_support_forum';
        add_meta_box( 
            $id,
            __( 'Support Forum' ),
            array($this, 'add_meta_' . $id),
            'pdf-help-and-support',
            'row-1'           
        );

        /* set the meta box id */
        $id = 'pdf_direct';
        add_meta_box( 
            $id,
            __( 'Contact Us' ),
            array($this, 'add_meta_' . $id),
            'pdf-help-and-support',
            'row-1'           
        );   

        /* set the meta box id */
        $id = 'pdf_popular_articles';
        add_meta_box( 
            $id,
            __( 'Popular Articles' ),
            array($this, 'add_meta_' . $id),
            'pdf-help-and-support',
            'row-2'
        );  

        /* set the meta box id */
        $id = 'pdf_recent_forum_articles';
        add_meta_box( 
            $id,
            __( 'Recent Forum Activity' ),
            array($this, 'add_meta_' . $id),
            'pdf-help-and-support',
            'row-2'      
        );         

        /* set the meta box id */
        $id = 'pdf_support_hours';
        add_meta_box( 
            $id,
            __( 'Support Hours' ),
            array($this, 'add_meta_' . $id),
            'pdf-help-and-support',
            'row-2'         
        );                

    }

    /**
     * [add_meta_pdf_knowledgebase description]
     * @param [type] $object [description]
     */
    public function add_meta_pdf_knowledgebase($object) {       
        ?>
            <i class="fa fa-file-text-o fa-5x"></i>
            <h4><a href="https://developer.gravitypdf.com/documentation/"><?php _e('Knowledge Base', 'pdfextended'); ?></a></h4>
            <p><?php _e('Gravity PDF has extensive online documentation to help you get started.', 'pdfextended'); ?></p>
        <?php
    }

    /**
     * [add_meta_pdf_support_forum description]
     * @param [type] $object [description]
     */
    public function add_meta_pdf_support_forum($object) {       
        ?>
            <i class="fa fa-comments-o fa-5x"></i>
            <h4><a href="https://support.gravitypdf.com/"><?php _e('Support Forum', 'pdfextended'); ?></a></h4>
            <p><?php _e('Our community support forum is a great resource if you have a problem.', 'pdfextended'); ?></p>
        <?php
    }

    /**
     * [add_meta_pdf_direct description]
     * @param [type] $object [description]
     */
    public function add_meta_pdf_direct($object) {       
        ?>
            <i class="fa fa-envelope-o fa-5x"></i>
            <h4><a href="https://developer.gravitypdf.com/contact/"><?php _e('Contact Us', 'pdfextended'); ?></a></h4>
            <p><?php _e('You can also get in touch with Gravity PDF staff directly via email or phone.', 'pdfextended'); ?></p>
        <?php
    }    

    /**
     * [add_meta_pdf_popular_articles description]
     * TODO
     * @param [type] $object [description]
     */
    public function add_meta_pdf_popular_articles($object) {       
        ?>
            <ul>
                <li>
                  <a href="https://wordpress.org/news/2015/04/powell/" class="rsswidget">WordPress 4.2 “Powell”</a>                  
                </li>
                <li>
                  <a href="https://wordpress.org/news/2015/04/powell/" class="rsswidget">WordPress 4.2 “Powell”</a> 
                </li>

                <li>
                  <a href="https://wordpress.org/news/2015/04/powell/" class="rsswidget">WordPress 4.2 “Powell”</a>                 
                </li>

                <li>
                  <a href="https://wordpress.org/news/2015/04/powell/" class="rsswidget">WordPress 4.2 “Powell”</a>                  
                </li>                                                  

                <li>
                  <a href="https://wordpress.org/news/2015/04/powell/" class="rsswidget">WordPress 4.2 “Powell”</a>                  
                </li>   

                <li>
                  <a href="https://wordpress.org/news/2015/04/powell/" class="rsswidget">WordPress 4.2 “Powell”</a>                 
                </li>                                   
            </ul> 
        <?php
    }

    /**
     * [add_meta_pdf_recent_forum_articles description]
     * TODO
     * @param [type] $object [description]
     */
    public function add_meta_pdf_recent_forum_articles($object) {       
        ?>
            <ul>
                <li>
                  <a href="https://wordpress.org/news/2015/04/powell/" class="rsswidget">WordPress 4.2 “Powell”</a> <span class="rss-date">April 23, 2015</span>                 
                </li>
                <li>
                  <a href="https://wordpress.org/news/2015/04/powell/" class="rsswidget">WordPress 4.2 “Powell”</a> <span class="rss-date">April 23, 2015</span>                 
                </li>

                <li>
                  <a href="https://wordpress.org/news/2015/04/powell/" class="rsswidget">WordPress 4.2 “Powell”</a> <span class="rss-date">April 23, 2015</span>                 
                </li>

                <li>
                  <a href="https://wordpress.org/news/2015/04/powell/" class="rsswidget">WordPress 4.2 “Powell”</a> <span class="rss-date">April 23, 2015</span>                 
                </li>    

                <li>
                  <a href="https://wordpress.org/news/2015/04/powell/" class="rsswidget">WordPress 4.2 “Powell”</a> <span class="rss-date">April 23, 2015</span>                 
                </li>   

                <li>
                  <a href="https://wordpress.org/news/2015/04/powell/" class="rsswidget">WordPress 4.2 “Powell”</a> <span class="rss-date">April 23, 2015</span>                 
                </li>                                                                                 
            </ul> 
        <?php
    }

    /**
     * [add_meta_pdf_support_hours description]
     * TODO
     * @param [type] $object [description]
     */
    public function add_meta_pdf_support_hours($object) {       
        ?>
            <i class="fa fa-clock-o fa-5x"></i>
            <h4><?php _e('Support Hours', 'pdfextended'); ?></h4>
            <p><?php printf(__("Gravity PDF's support hours are from 9:00am-5:00pm Monday to Friday, %sSydney Australia time%s.", 'pdfextended'), '<a href="http://www.timeanddate.com/worldclock/australia/sydney">', '</a>'); ?></p>
        <?php
    }       
}