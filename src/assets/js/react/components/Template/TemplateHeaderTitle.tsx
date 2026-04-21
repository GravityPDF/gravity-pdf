/* Dependencies */

/**
 * Renders the Template Header Title
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

interface Props {
	header?: string;
}

const TemplateHeaderTitle = ({ header }: Props) => (
	<h1 data-test="component-templateHeaderTitle">{header}</h1>
);

export default TemplateHeaderTitle;
