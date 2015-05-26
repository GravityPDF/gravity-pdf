<?php 

/**
 * Uninstaller Settings View
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
            <i class="fa fa-times"></i>
            <?php _e('Uninstall Gravity PDF', 'gravitypdf'); ?>
        </span>
    </h3>    

<div class="delete-alert alert_red">
    <h3><i class="fa fa-exclamation-triangle gf_invalid"></i> Warning</h3>

    <div class="gf_delete_notice">
        <?php printf(__( '%sThis operation deletes ALL Gravity PDF data and deactivates the plugin.%s If you continue, all settings, configuration, custom templates and fonts will be removed.', 'gravitypdf'), '<strong>', '</strong>'); ?>
    </div>

    <form method="post">
        <?php wp_nonce_field( 'gfpdf-uninstall-plugin', 'gfpdf-uninstall-plugin' ) ?>
        <input id="gfpdf-uninstall" type="submit" class="button" value="<?php _e('Uninstall Gravity PDF', 'gravitypdf'); ?>" name="uninstall">
    </form>
</div>   

<div id="uninstall-confirm" title="<?php _e('Uninstall Gravity PDF', 'gravitypdf'); ?>" style="display: none;">
 <?php printf(__("Warning! ALL Gravity PDF data, %sincluding configuration and custom templates%s will be deleted. This cannot be undone. Select 'Uninstall' to delete, 'Cancel' to stop.", 'gravitypdf'), '<strong>', '</strong>'); ?>
</div>       