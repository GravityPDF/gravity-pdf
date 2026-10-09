<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use GFPDF_Vendor\GravityPdf\Upload\File;
use GFPDF_Vendor\GravityPdf\Upload\FileInfo;
use GFPDF_Vendor\GravityPdf\Upload\StorageInterface;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LocalFile extends File {
	/**
	 * Bypass the is_uploaded_file() check for files that didn't arrive via a POST
	 *
	 * @since 7.0
	 */
	public function __construct( string $key, StorageInterface $storage ) {
		parent::__construct( $key, $storage );

		foreach ( $this->objects as $index => $file_info ) {
			$path = $file_info->getPathname();
			$name = $file_info->getNameWithExtension();

			$this->objects[ $index ] = new class( $path, $name ) extends FileInfo {
				public function isUploadedFile(): bool {
					return true;
				}
			};
		}
	}
}
