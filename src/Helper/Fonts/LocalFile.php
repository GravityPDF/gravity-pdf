<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use GFPDF_Vendor\GravityPdf\Upload\Exception;
use GFPDF_Vendor\GravityPdf\Upload\File;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LocalFile extends File {
	/**
	 * @return bool
	 * @throws \Exception
	 *
	 * @since 6.4
	 */
	public function isValid(): bool {
		foreach ( $this->objects as $fileInfo ) {
			$this->applyCallback( 'beforeValidationCallback', $fileInfo );
			foreach ( $this->validations as $validation ) {
				try {
					$validation->validate( $fileInfo );
				} catch ( Exception $e ) {
					$this->errors[] = \sprintf( '%s: %s', $fileInfo->getNameWithExtension(), $e->getMessage() );
				}
			}
			$this->applyCallback( 'afterValidationCallback', $fileInfo );
		}
		return empty( $this->errors );
	}
}
