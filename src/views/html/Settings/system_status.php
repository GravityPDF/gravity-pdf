<?php 

/**
 * System Status Settings View
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

?>

<div class="hr-divider"></div>

    <h3>
        <span>
            <i class="fa fa-dashboard"></i>
            <?php _e('Installation Status', 'pdfextended'); ?>
        </span>
    </h3>    

    <table id="pdf-system-status" class="form-table">  

        <tr>
            <th scope="row">
                <?php _e('WP Memory Available', 'pdfextended'); ?> <?php gform_tooltip('pdf_status_wp_memory'); ?>
            </th>

            <td>

                <?php
                $ram_icon = 'fa fa-check-circle';
                if($vars['memory'] < 128 && $vars['memory'] !== -1) {                
                    $ram_icon = 'fa fa-exclamation-triangle';
                }                
                ?>

                <?php if($vars['memory'] === -1): ?>
                    <?php echo __('Unlimited', 'pdfextended'); ?>
                <?php else: ?>
                    <?php echo $vars['memory']; ?>MB
                <?php endif; ?>

                <span class="<?php echo $ram_icon; ?>"></span>

                <?php if($vars['memory'] < 128 && $vars['memory'] !== -1): ?>
                
                <span class="gf_settings_description">
                    <?php echo sprintf(__('We strongly recommend you have at least 128MB of available WP Memory (RAM) assigned to your website. The minimum system requirement is 64MB. %sFind out how to change this limit%s.', 'pdfextended'), '<br /><a href="#">', '</a>'); /* TODO - UPDATE LINK - see http://docs.woothemes.com/document/increasing-the-wordpress-memory-limit/ for example */ ?>
                </span>
                <?php endif; ?>                            
            </td>
        </tr>

        <tr>
            <th scope="row">
                <?php _e('PDF Notifications', 'pdfextended'); ?> <?php gform_tooltip('pdf_status_notifications'); ?>
            </th>
            <td>
                <?php ($vars['output']  === true) ? _e('Enabled', 'pdfextended') : _e('Disabled', 'pdfextended'); ?>
                <span class="<?php echo ($vars['output'] === true) ? 'fa fa-check-circle' : 'fa fa-times-circle'; ?>"></span> 

                <?php if($vars['output'] === false): ?>
                    <span class="gf_settings_description"><?php echo __('The PDF save directory is not writable by your web server. PDF email notifications are currently disabled.', 'pdfextended'); ?></span>
                    <div class="clear">Path <span class="details path"><?php echo $vars['output_path']; ?></span></div>     
                <?php endif; ?>                                        
            </td>
        </tr>          


        <tr>
            <th scope="row">
                <?php _e('WordPress Version', 'pdfextended'); ?>
            </th>

            <td>
                <?php echo $vars['wp']; ?>                
            </td>
        </tr>  

        <tr>
            <th scope="row">
                <?php _e('Gravity Forms Version', 'pdfextended'); ?>
            </th>

            <td>
                <?php echo $vars['gf']; ?>                
            </td>
        </tr>   

        <tr>
            <th scope="row">
                <?php _e('PHP Version', 'pdfextended'); ?>
            </th>

            <td>
                <?php echo $vars['php']; ?>                
            </td>
        </tr>                                      

    </table>  