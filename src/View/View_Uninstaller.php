<?php

declare( strict_types=1 );

namespace GFPDF\View;

use GFPDF\Helper\Helper_Abstract_View;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class View_Uninstaller extends Helper_Abstract_View {

	/**
	 * Set the view's name
	 *
	 * @var string
	 *
	 * @since 6.0
	 */
	protected $view_type = 'Uninstaller';
}
