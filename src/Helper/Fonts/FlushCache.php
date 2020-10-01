<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FlushCache {
	public static function flush(): void {
		$misc = GPDFAPI::get_misc_class();
		$data = GPDFAPI::get_data_class();
		$misc->cleanup_dir( $data->mpdf_tmp_location );
	}
}
