<?php

namespace GFPDF;

use GFPDF\Helper\Helper_Abstract_Addon;
use GFPDF\Helper\Licensing\EDD_SL_Plugin_Updater;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

class Add_On_Bootstrap extends Helper_Abstract_Addon {
	public function plugin_updater() {
		$license_info = $this->get_license_info();

		new EDD_SL_Plugin_Updater(
			$this->data->store_url,
			$this->get_main_plugin_file(),
			[
				'version'   => $this->get_version(),
				'license'   => $license_info['license'],
				'item_name' => $this->get_short_name(),
				'author'    => $this->get_author(),
				'beta'      => false,
			]
		);
	}
}
