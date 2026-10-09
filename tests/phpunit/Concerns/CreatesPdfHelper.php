<?php

declare(strict_types=1);

namespace GFPDF\Tests\Concerns;

use GFPDF\Helper\Helper_PDF;

/**
 * Builds a Helper_PDF from the global plugin services, e.g. to read the tmp path a PDF is saved to.
 */
trait CreatesPdfHelper {

	protected function get_pdf_helper( array $entry, array $settings ): Helper_PDF {
		global $gfpdf;

		return new Helper_PDF( $entry, $settings, $gfpdf->gform, $gfpdf->data, $gfpdf->misc, $gfpdf->templates, $gfpdf->log );
	}
}
