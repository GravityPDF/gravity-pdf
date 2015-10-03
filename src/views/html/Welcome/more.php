<?php

/**
 * Common welcome and update screen content
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

<div class="gfpdf-mascot-sitting"></div>

<div class="changelog">
    <h3><?php _e( 'Get more out of Gravity PDF', 'gravity-forms-pdf-extended' ); ?></h3>

    <div class="feature-section three-col">
        <div class="col gfpdf-breakdown">

            <h4><?php _e( 'PDF Template Shop', 'gravity-forms-pdf-extended' ); ?></h4>
            <p><?php printf( __( "It's like a theme shop, but for Gravity PDF templates. %sHead over to our online store%s and view our growing selection of premium PDF templates.", 'gravity-forms-pdf-extended' ), '<a href="#">', '</a>' ); ?></p>

            <h4><?php _e( 'Stay Up To Date', 'gravity-forms-pdf-extended' ); ?></h4>
            <p><?php printf( __( '%sSign up to our newsletter%s to be amongst the first to receive the latest news and details on upcoming feature.', 'gravity-forms-pdf-extended' ), '<a href="#">', '</a>' ); ?></p>

        </div>

        <div class="col gfpdf-breakdown">

            <h4><?php _e( 'Tailored PDFs', 'gravity-forms-pdf-extended' ); ?></h4>
            <p><?php printf( __( "If the PDF Shop doesn't have what you're after %sour friendly team can build a document just for you%s. With an addon, our devs can even create templates that auto fill existing PDFs – like government and legal documents.", 'gravity-forms-pdf-extended' ), '<a href="#">', '</a>' ); ?></p>

            <h4><?php _e( 'Get Support', 'gravity-forms-pdf-extended' ); ?></h4>
            <p><?php printf( __( 'Have trouble using Gravity PDF? %sContact our friendly staff%s who are avaliable 9am to 5pm Monday to Friday, %sAustralian Eastern Standard Time%s.', 'gravity-forms-pdf-extended' ), '<a href="#">', '</a>', '<a href="http://www.timeanddate.com/worldclock/australia/sydney">', '</a>' ); ?></p>
            

        </div>

        <div class="col gfpdf-breakdown last-feature">

            <h4><?php _e( 'Roll your Own', 'gravity-forms-pdf-extended' ); ?></h4>
            <p><?php printf( __( "If PHP, HTML and CSS come easy to you, you'll find creating your own PDF templates a breeze. With %sextensive documentation and great examples%s you'll be up and running in no time.", 'gravity-forms-pdf-extended' ), '<a href="#">', '</a>' ); ?></p>

        </div>

    </div>
</div>
