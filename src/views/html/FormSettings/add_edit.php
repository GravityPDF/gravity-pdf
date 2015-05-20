<?php 

/**
 * The Add/Edit Form Settings View
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

<!-- Merge tag functionality requires a global form object -->
<script type="text/javascript">
    <?php \GFCommon::gf_global(); ?>
    <?php \GFCommon::gf_vars(); ?>   
    var form = <?php echo json_encode( $vars['form'] ); ?>;
    var gfpdf_current_pdf = <?php echo json_encode( $vars['pdf'] ); ?>;
    
    <?php \GFFormSettings::output_field_scripts(); ?>    
</script>

<?php \GFFormSettings::page_header( $vars['title'] ); ?>

	<h3>
		<span>
		  <i class="fa fa-file-o"></i>
		  <?php echo $vars['title']; ?>		 
		</span>
	</h3>


    <form method="post" id="gfpdf_pdf_form">

        <?php wp_nonce_field( 'gfpdf_save_pdf', 'gfpdf_save_pdf' ) ?>        
        
        <input type="hidden" id="gform_pdf_id" name="gform_pdf_id" value="<?php echo $vars['pdf_id']; ?>" />

        <!-- display standard fields -->
        <table id="pdf-form-settings" class="form-table">     
            <?php do_settings_fields('gfpdf_settings_form_settings', 'gfpdf_settings_form_settings'); ?>
        </table>


        <div class="hr-divider"></div>      

            <h3>
                <span>
                  <i class="fa fa-adjust"></i>
                  <?php _e('Appearance', 'pdfextended'); ?>
                </span>
            </h3>

        <!-- display appearance fields -->
        <div id="gfpdf-appearance-options">
            <table id="pdf-general-security" class="form-table">        
                <?php do_settings_fields('gfpdf_settings_form_settings_appearance', 'gfpdf_settings_form_settings_appearance'); ?>
            </table>    
        </div>

        <div class="hr-divider"></div>

        <!-- display advanced fields -->              
        <div id="gfpdf-advanced-options">
            <h3>
                <span>
                  <i class="fa fa-cogs"></i>
                  <?php _e('Advanced', 'pdfextended'); ?>
                </span>
            </h3>
        
            <table id="pdf-general-advanced" class="form-table">        
                <?php do_settings_fields('gfpdf_settings_form_settings_advanced', 'gfpdf_settings_form_settings_advanced'); ?>
            </table>    
        </div>

        <div class="gfpdf-advanced-options"><a href="#"><?php _e('Show Advanced Options...', 'pdfextended'); ?></a></div>       

        <p class="submit">            
            <input class="button-primary" type="submit" value="<?php echo $vars['button_label']; ?>" name="save"/>                        
        </p>
    </form>


<?php \GFFormSettings::page_footer(); ?>