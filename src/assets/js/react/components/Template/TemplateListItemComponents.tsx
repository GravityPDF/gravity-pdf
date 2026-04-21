/* Dependencies */

/**
 * Contains stateless React components for our Template List Items
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

interface TemplateDetailsProps {
	label?: string;
}

export const TemplateDetails = ({ label }: TemplateDetailsProps) => (
	<span data-test="component-templateDetails" className="more-details">
		{label}
	</span>
);

interface GroupProps {
	group?: string;
}

export const Group = ({ group }: GroupProps) => (
	<p data-test="component-group" className="theme-author">
		{group}
	</p>
);
