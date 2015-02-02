<?php 

/**
 * Filename: depreciated.php 
 * This file contains any depreciated functionality to help preserve backwards compatibility 
 * with either Gravity Forms, or our template files.
 */

/*
    This file is part of Gravity PDF.

    Gravity PDF is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Gravity PDF is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Gravity PDF. If not, see <http://www.gnu.org/licenses/>.
*/

/*
 * Since v3.6
 * Moved to the $gfpdfe_data class.
 * Can now be accessed using the following:
 *
 * $gfpdfe_data->template_location
 * $gfpdfe_data->template_site_location
 * $gfpdfe_data->template_save_location
 * $gfpdfe_data->template_font_location
 * 
 */
define('PDF_SAVE_LOCATION', $gfpdfe_data->template_save_location); 
define('PDF_FONT_LOCATION', $gfpdfe_data->template_font_location); 
define('PDF_TEMPLATE_LOCATION', $gfpdfe_data->template_site_location); 
define('PDF_TEMPLATE_URL_LOCATION', $gfpdfe_data->template_site_location_url); 