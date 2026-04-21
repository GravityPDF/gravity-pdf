/* Dependencies */
import { Spinner as WPSpinner } from '@wordpress/components';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

interface Props {
	style?: string;
}

const Spinner = ({ style }: Props) => (
	<WPSpinner className={'gfpdf-spinner ' + (style ?? '')} />
);

export default Spinner;
