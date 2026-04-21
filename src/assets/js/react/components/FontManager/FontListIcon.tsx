/* Dependencies */

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

interface Props {
	font: string;
}

const FontListIcon = ({ font }: Props) => (
	<div data-test="component-FontListIcon">
		<span className={'dashicons dashicons-' + (font ? 'yes' : 'no-alt')} />
	</div>
);

export default FontListIcon;
