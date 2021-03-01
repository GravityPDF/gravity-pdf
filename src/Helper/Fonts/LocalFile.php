<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use GFPDF_Vendor\Upload\File;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LocalFile extends File {
	public function isUploadedFile(): bool {
		return true;
	}
}
