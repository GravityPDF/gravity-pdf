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
            <?php _e('Installation Status', 'gravitypdf'); ?>
        </span>
    </h3>

    <table id="pdf-system-status" class="form-table">

        <tr>
            <th scope="row">
                <?php _e('WP Memory Available', 'gravitypdf'); ?> <?php gform_tooltip('pdf_status_wp_memory'); ?>
            </th>

            <td>

                <?php
                $ram_icon = 'fa fa-check-circle';
                if($args['memory'] < 128 && $args['memory'] !== -1) {
                    $ram_icon = 'fa fa-exclamation-triangle';
                }
                ?>

                <?php if($args['memory'] === -1): ?>
                    <?php echo __('Unlimited', 'gravitypdf'); ?>
                <?php else: ?>
                    <?php echo $args['memory']; ?>MB
                <?php endif; ?>

                <span class="<?php echo $ram_icon; ?>"></span>

                <?php if($args['memory'] < 128 && $args['memory'] !== -1): ?>
                
                <span class="gf_settings_description">
                    <?php echo sprintf(__('We strongly recommend you have at least 128MB of available WP Memory (RAM) assigned to your website. %sFind out how to increase this limit%s.', 'gravitypdf'), '<br /><a href="#">', '</a>'); /* @todo - UPDATE LINK - see http://docs.woothemes.com/document/increasing-the-wordpress-memory-limit/ for example */ ?>
                </span>
                <?php endif; ?>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <?php _e('WordPress Version', 'gravitypdf'); ?>
            </th>

            <td>
                <?php echo $args['wp']; ?>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <?php _e('Gravity Forms Version', 'gravitypdf'); ?>
            </th>

            <td>
                <?php echo $args['gf']; ?>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <?php _e('PHP Version', 'gravitypdf'); ?>
            </th>

            <td>
                <?php echo $args['php']; ?>
            </td>
        </tr>

    </table>
