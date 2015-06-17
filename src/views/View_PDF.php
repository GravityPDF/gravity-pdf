<?php

namespace GFPDF\View;
use GFPDF\Helper\Helper_View;
use RGFormsModel;
use mPDF;

/**
 * PDF View
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
 * View_PDF
 *
 * A general class for PDF display
 *
 * @since 4.0
 */
class View_PDF extends Helper_View
{

    /**
     * Set the view's name
     * @var string
     * @since 4.0
     */
    protected $ViewType = 'PDF';

    public function __construct($data = array()) {
        $this->data = $data;
    }

    /**
     * Our PDF Generator
     * @param  Array $entry    The Gravity Forms Entry to process
     * @param  Array $settings The Gravity Form PDF Settings
     * @return void
     * @since 4.0
     */
    public function generate_pdf($entry, $settings) {
        global $gfpdf;

        $paper_size = 'A4';
        $orientation = '';

        $mpdf = new mPDF('', $paper_size, 0, '', 15, 15, 16, 16, 9, 9, $orientation);

        /* set up contstants for gravity forms to use so we can override the security on the printed version */
        $template = (isset($_GET['template'])) ? $_GET['template'] : '';
        $html     = '';

        /**
         * Load our arguments that should be accessed by our view
         * @var array
         */
        $args = array(
            'form_id'   => $entry['form_id'], /* backwards compat */
            'lead_ids'  => array($entry['id']), /* backwards compat */
            'lead_id'   => $entry['id'], /* backwards compat */
            
            'form'      => RGFormsModel::get_form_meta($entry['form_id']),
            'entry'     => $entry,
            'lead'      => $entry,
            'form_data' => '',
        );

        if(file_exists( $gfpdf->data->template_site_location . $template)) {
            $html = $this->load($template, $args, false, $gfpdf->data->template_site_location);
        }

        if(isset($_GET['html'])) {
            echo $html; exit;
        }

        $mpdf->WriteHTML($html);

        $mpdf->Output('test.pdf', 'I');
        exit;
    }
}
