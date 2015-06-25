<?php

namespace GFPDF\View;

use GFPDF\Helper\Helper_View;
use GFPDF_Major_Compatibility_Checks;

use GFCommon;

/**
 * Settings View
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if (! defined('ABSPATH')) {
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
 * View_Settings
 *
 * A general class for About / Intro Screen
 *
 * @since 4.0
 */
class View_Settings extends Helper_View
{

    /**
     * Set the view's name
     * @var string
     * @since 4.0
     */
    protected $ViewType = 'Settings';

    public function __construct($data = array()) {
        $this->data = $data;
    }

    /**
     * Load the Welcome Tab tabs
     * @since 4.0
     * @return void
     */
    public function tabs() {
        global $gfpdf;

        /*
         * Set up any variables we need for the view and display
         */
        $vars = array(
            'selected' => isset( $_GET['tab'] ) ? $_GET['tab'] : 'general',
            'tabs'     => $this->get_avaliable_tabs(),
            'data'     => $gfpdf->data,
        );

        $vars = array_merge($vars, $this->data);

        /* load the tabs view */
        $this->load('tabs', $vars);
    }

    /**
     * Set up our settings navigation
     * @return array The navigation array
     * @since 4.0
     */
    public function get_avaliable_tabs() {
            /**
             * Store the setting navigation
             * The array key is the settings order
             * @var array
             */
            $navigation = array(
                5 => array(
                    'name'     => __('General', 'gravitypdf'),
                    'id'       => 'general',
                ),

                100 => array(
                    'name'     => __('Tools', 'gravitypdf'),
                    'id'       => 'tools',
                ),

                120 => array(
                    'name' => __('Help', 'gravitypdf'),
                    'id' => 'help',
                ),
            );

            /**
             * Allow additional navigation to be added to the settings page
             * @since 3.8
             */
            return apply_filters('gravitypdf_settings_navigation', $navigation);
    }

    /**
     * Pull the system status details and show
     * @return void
     * @since 4.0
     */
    public function system_status() {
        global $wp_version;

        $status = new GFPDF_Major_Compatibility_Checks();

        $mb_string = false;
        if($this->get_mb_string() && $this->check_mb_string_regex()) {
            $mb_string = true;
        }

        $vars = array(
            'memory'      => $status->get_ram(ini_get('memory_limit')),
            'output'      => true, /* TODO - write installer / uninstaller first */
            'output_path' => 'path/to/file', /* TODO */
            'wp'          => $wp_version,
            'php'         => phpversion(),
            'gf'          => GFCommon::$version,
        );

        $vars = array_merge($vars, $this->data);

        /* load the system status view */
        $this->load('system_status', $vars);
    }

    /**
     * Pull the general details and display
     * @return void
     * @since 4.0
     */
    public function general() {
        global $gfpdf;

        $vars = array(
            'edit_cap' =>  GFCommon::current_user_can_any( 'gravityforms_edit_settings' ),
        );

        $vars = array_merge($vars, $this->data);

        /* load the system status view */
        $this->load('general', $vars);
    }

    /**
     * Pull the tools details and show
     * @return void
     * @since 4.0
     */
    public function tools() {
        global $gfpdf;

        /* prevent unauthorized access */
        if ( ! GFCommon::current_user_can_any( 'gravityforms_edit_settings' ) ) {
            wp_die( __('You do not have permission to access this page', 'gravitypdf') );
        }

        $vars = array(
            'template_directory' => str_replace(ABSPATH, '/', $gfpdf->data->template_location),
        );

        $vars = array_merge($vars, $this->data);

        /* load the system status view */
        $this->load('tools', $vars);
    }

    /**
     * Add Gravity Forms Tooltips
     * @param Array The existing tooltips
     * @since 4.0
     */
    public function add_tooltips($tooltips)
    {
        global $gfpdf;

        $tooltips['pdf_status_wp_memory']     = '<h6>' . __( 'WP Memory Available', 'gravitypdf' ) . '</h6>' . sprintf(__( "Producing PDF documents is hard work and Gravity PDF requires more resources than most plugins. We strongly recommend you have at least 128MB, but you may need more.", 'gravitypdf' ));
        $tooltips['pdf_status_notifications'] = '<h6>' . __( 'PDF Notifications', 'gravitypdf' ) . '</h6>' . sprintf(__( 'Sending PDFs automatically via Gravity Form notifications requires write access to our designated output directory: %s.', 'gravitypdf' ), '<code>' . $gfpdf->data->relative_output_location . '</code>');

        return apply_filters('gravitypdf_registered_tooltips', $tooltips);
    }

    /**
     * Add Knowledebase meta box
     * @param Object $object The metabox object
     * @return void
     * @since 4.0
     */
    public function add_meta_pdf_knowledgebase($object) {
        ?>
            <i class="fa fa-file-text-o fa-5x"></i>
            <h4><a href="https://developer.gravitypdf.com/documentation/"><?php _e('Knowledge Base', 'gravitypdf'); ?></a></h4>
            <p><?php _e('Gravity PDF has extensive online documentation to help you get started.', 'gravitypdf'); ?></p>
        <?php
    }

    /**
     * Add support forum meta box
     * @param Object $object The metabox object
     * @return void
     * @since 4.0
     */
    public function add_meta_pdf_support_forum($object) {
        ?>
            <i class="fa fa-comments-o fa-5x"></i>
            <h4><a href="https://support.gravitypdf.com/"><?php _e('Support Forum', 'gravitypdf'); ?></a></h4>
            <p><?php _e('Our community support forum is a great resource if you have a problem.', 'gravitypdf'); ?></p>
        <?php
    }

    /**
     * Add direct contact meta box
     * @param Object $object The metabox object
     * @return void
     * @since 4.0
     */
    public function add_meta_pdf_direct($object) {
        ?>
            <i class="fa fa-envelope-o fa-5x"></i>
            <h4><a href="https://developer.gravitypdf.com/contact/"><?php _e('Contact Us', 'gravitypdf'); ?></a></h4>
            <p><?php _e('You can also get in touch with Gravity PDF staff directly via email or phone.', 'gravitypdf'); ?></p>
        <?php
    }

    /**
     * Add Key Documentation meta box
     * @param Object $object The metabox object
     * @return void
     * @since 4.0
     */
    public function add_meta_pdf_popular_articles($object) {

        $articles = array(

            array(
                'title' => __('Getting Started Guide', 'gravitypdf'),
                'url' => 'https://developer.gravitypdf.com/documentation/getting-started-with-gravity-pdf-configuration/',
            ),

            array(
                'title' => __('Creating a Custom PDF Template', 'gravitypdf'),
                'url' => 'https://developer.gravitypdf.com/documentation/custom-templates-introduction/',
            ),
                                               
        );

        ?>
            <ul>
                <?php foreach($articles as $a): ?>
                    <li><a href="<?php echo $a['url']; ?>" class="rsswidget"><?php echo $a['title']; ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php
    }

    /**
     * Add Recent forum articles meta box
     * @param Object $object The metabox object
     * @return void
     * @since 4.0
     */
    public function add_meta_pdf_recent_forum_articles($object, $topics) {

        if(!$topics || !is_array($topics)) {
            _e('Latest forum topics could not be loaded.', 'gravitypdf');
            return;
        }

        ?>
            <?php foreach($topics as $topic): ?>
                <li><a href="https://support.gravitypdf.com/t/<?php echo $topic['slug']; ?>/<?php echo $topic['id']; ?>" class="rsswidget"><?php echo $topic['fancy_title']; ?></a></li>
            <?php endforeach; ?>
        <?php
    }

    /**
     * Add Support hour meta box
     * @param Object $object The metabox object
     * @return void
     * @since 4.0
     */
    public function add_meta_pdf_support_hours($object) {
        ?>
            <i class="fa fa-clock-o fa-5x"></i>
            <h4><?php _e('Support Hours', 'gravitypdf'); ?></h4>
            <p><?php printf(__("Gravity PDF's support hours are from 9:00am-5:00pm Monday to Friday, %sSydney Australia time%s.", 'gravitypdf'), '<a href="http://www.timeanddate.com/worldclock/australia/sydney">', '</a>'); ?></p>
        <?php
    }
}
