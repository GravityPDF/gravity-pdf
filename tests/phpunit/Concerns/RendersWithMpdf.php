<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Concerns;

use GFPDF\Helper\Helper_PDF;
use GFPDF\Helper\Mpdf\Mpdf;
use ReflectionMethod;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * A real mPDF, built the way a render builds one
 *
 * Not `new Mpdf( … )`: everything worth asserting about fonts is decided by the config and container `Helper_PDF`
 * assembles, so a suite that constructs mPDF itself proves nothing about what a PDF actually gets.
 *
 * @since 7.0
 */
trait RendersWithMpdf {

	/**
	 * The `all-form-fields` fixture is a Helper_PDF constructor argument and nothing more, so a suite using this
	 * has to have loaded it
	 */
	protected function mpdf_for( array $settings = [] ): Mpdf {
		global $gfpdf;

		$pdf = new Helper_PDF(
			[
				'id'      => 1,
				'form_id' => $this->form( 'all-form-fields' )['id'],
			],
			array_merge( [ 'id' => '556690c67856b' ], $settings ),
			$gfpdf->gform,
			$gfpdf->data,
			$gfpdf->misc,
			$gfpdf->templates,
			$gfpdf->log
		);

		$method = new ReflectionMethod( Helper_PDF::class, 'begin_pdf' );

		/* A no-op from 8.1, and required before it */
		if ( PHP_VERSION_ID < 80100 ) {
			$method->setAccessible( true );
		}

		$method->invoke( $pdf );

		return $pdf->get_pdf_class();
	}
}
