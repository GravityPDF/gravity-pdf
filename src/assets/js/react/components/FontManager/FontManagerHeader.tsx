/* Dependencies */
import { _n, sprintf } from '@wordpress/i18n';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

interface Props {
	count: number;
}

/**
 * The Modal already provides title + close button via `<Modal title={…} />`,
 * which auto-wires `aria-labelledby`. This header only renders the
 * count badge so it lives inline next to the title, keeping the prototype's
 * "N fonts installed" feel without duplicating the modal heading.
 * @param root0
 * @param root0.count
 */
const FontManagerHeader = ({ count }: Props) => (
	<div data-test="component-FontManagerHeader" className="gfpdf-fm-header">
		<div className="gfpdf-fm-header__count" aria-live="polite">
			{sprintf(
				/* translators: %d: number of fonts installed */
				_n(
					'%d font installed',
					'%d fonts installed',
					count,
					'gravity-pdf'
				),
				count
			)}
		</div>
	</div>
);

export default FontManagerHeader;
