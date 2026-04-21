/* Dependencies */
/* Components */
import CloseDialog from '../Modal/CloseDialog';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

interface Props {
	id?: string;
}

const FontManagerHeader = ({ id }: Props) => (
	<div data-test="component-FontManagerHeader" className="theme-header">
		<h1>{GFPDF.fontManagerTitle}</h1>

		<CloseDialog id={id} />
	</div>
);

export default FontManagerHeader;
