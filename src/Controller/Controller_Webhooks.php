<?php

namespace GFPDF\Controller;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Controller_Webhooks
 *
 * @package GFPDF\Controller
 */
class Controller_Webhooks {

	/**
	 * @since 6.0
	 */
	public function init(): void {
		add_filter( 'gform_webhooks_request_data', [ $this, 'webhook_request_data' ], 10, 3 );
	}

	/**
	 * Include the Gravity PDF URLs in the "All Fields" Webhook request
	 *
	 * @since 6.0
	 */
	public function webhook_request_data( array $request_data, array $feed, array $entry ): array {
		if ( $feed['meta']['requestBodyType'] !== 'all_fields' ) {
			return $request_data;
		}

		$model_pdf = \GPDFAPI::get_mvc_class( 'Model_PDF' );
		$pdfs      = $model_pdf->get_pdf_display_list( $entry );

		foreach ( $pdfs as $pdf ) {
			$request_data[ 'gpdf_' . $pdf['settings']['id'] ] = $pdf['view'];
		}

		return $request_data;
	}
}
