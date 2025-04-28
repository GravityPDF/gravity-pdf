<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use GFPDF_Vendor\GravityPdf\Upload\Storage\FileSystem;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LocalFilesystem extends FileSystem {
	protected function moveUploadedFile( string $source, string $destination ): bool {
		return copy( $source, $destination );
	}
}
