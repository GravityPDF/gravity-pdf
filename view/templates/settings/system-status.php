<?php

 /*
  * Template: System Status
  * Module: Settings Page
  */
 
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
    * Don't run if the correct class isn't present
    */
    if(!class_exists('GFPDF_Settings_Model')) { die(); }
    
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
                if($gfpdfe_data->ram_compatible === false) {                
                    $ram_icon = 'fa fa-exclamation-triangle';
                    if($gfpdfe_data->ram_available < 64)
                    {
                        $ram_icon = 'fa fa-times-circle';
                    }
                }                
                ?>

                <?php if($gfpdfe_data->ram_available === -1): ?>
                    <?php echo __('Unlimited', 'pdfextended'); ?>
                <?php else: ?>
                    <?php echo $gfpdfe_data->ram_available; ?>MB
                <?php endif; ?>

                <span class="<?php echo $ram_icon; ?>"></span>

                <?php if($gfpdfe_data->ram_compatible === false): ?>
                
                <span class="gf_settings_description">
                    <?php echo sprintf(__('We strongly recommend you have at least 128MB of available WP Memory (RAM) assigned to your website. The minimum system requirement is 64MB. %sContact your web hosting provider to make this change.', 'pdfextended'), '<br />'); ?>
                </span>
                <?php endif; ?>                            
            </td>
        </tr>


        <tr>
            <th scope="row">
                <?php _e('MB String', 'pdfextended'); ?> <?php gform_tooltip('pdf_status_mbstring'); ?>
            </th>

            <td>
                <?php ($gfpdfe_data->mb_string_installed === true) ? _e('Yes', 'pdfextended') : _e('No', 'pdfextended'); ?>
                <span class="<?php echo ($gfpdfe_data->mb_string_installed === true) ? 'fa fa-check-circle' : 'fa fa-times-circle'; ?>"></span>   

                <?php if($gfpdfe_data->mb_string_installed === false): ?>
                    <span class="gf_settings_description"><?php _e('The PHP extension MB String and MB String Regex functions are required to use this plugin. Contact your web hosting provider to have it enabled.', 'pdfextended'); ?></span>
                <?php endif; ?>                             
            </td>
        </tr>

        <tr>
            <th scope="row">
                <?php _e('GD Library', 'pdfextended'); ?> <?php gform_tooltip('pdf_status_gd_library'); ?>
            </th>
            <td>
                <?php ($gfpdfe_data->gd_installed  === true) ? _e('Yes', 'pdfextended') : _e('No', 'pdfextended'); ?>
                <span class="<?php echo ($gfpdfe_data->gd_installed === true) ? 'fa fa-check-circle' : 'fa fa-times-circle'; ?>"></span>   

                <?php if($gfpdfe_data->gd_installed === false): ?>
                    <span class="gf_settings_description"><?php _e('The PHP GD Library extension is required to use this plugin. Contact your web hosting provider to have it enabled.', 'pdfextended'); ?></span>
                <?php endif; ?>                             
            </td>
        </tr>   

        <tr>
            <th scope="row">
                <?php _e('PDF Notifications', 'pdfextended'); ?> <?php gform_tooltip('pdf_status_notifications'); ?>
            </th>
            <td>
                <?php ($gfpdfe_data->can_write_output_dir  === true) ? _e('Enabled', 'pdfextended') : _e('Disabled', 'pdfextended'); ?>
                <span class="<?php echo ($gfpdfe_data->can_write_output_dir === true) ? 'fa fa-check-circle' : 'fa fa-times-circle'; ?>"></span> 

                <?php if($gfpdfe_data->can_write_output_dir === false): ?>
                    <span class="gf_settings_description"><?php echo __('The PDF save directory is not writable by your web server. PDF email notifications are currently disabled.', 'pdfextended'); ?></span>
                    <div class="clear">Path <span class="details path"><?php echo $gfpdfe_data->relative_output_location; ?></span></div>     
                <?php endif; ?>                                        
            </td>
        </tr>                 

    </table>  