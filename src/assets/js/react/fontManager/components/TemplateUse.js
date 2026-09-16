/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __ } from '@wordpress/i18n';

/**
 * The one line that tells an admin how to reach this font from a template
 *
 * It replaces 6.x's "Show template usage" disclosure and its copyable snippet: the key is the whole answer,
 * and hiding it behind a toggle only made people hunt for it.
 *
 * @param {Object} props
 * @param {string} props.fontKey The mPDF key
 *
 * @return {JSX.Element} The help line
 *
 * @since 7.0
 */
export default function TemplateUse({ fontKey }) {
	return (
		<p className="gfpdf-fm-template-use">
			{__('Template use:', 'gravity-pdf')}{' '}
			<code>font-family: {fontKey}</code>
		</p>
	);
}
