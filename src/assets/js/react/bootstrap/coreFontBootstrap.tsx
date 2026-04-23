/* Dependencies */
import { createRoot } from '@wordpress/element';
/* Components */
import CoreFontContainer from '../components/CoreFonts/CoreFontContainer';

/**
 * Core Font Downloader Bootstrap
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/**
 * Mount our Core Font UI on the DOM
 *
 * @since 5.0
 */
export default function coreFontBootstrap(): void {
	const container = document.getElementById(
		'gfpdf-button-wrapper-install_core_fonts'
	);

	createRoot(container!).render(<CoreFontContainer />);
}
