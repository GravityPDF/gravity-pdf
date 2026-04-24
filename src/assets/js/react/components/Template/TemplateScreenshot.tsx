/* Dependencies */

/**
 * Display the Template Screenshot. In list view the outer element has the
 * `theme-screenshot` class; in the detail view it is wrapped in an extra
 * `theme-screenshots` div (`wrapped` prop) so the WordPress-theme-browser
 * styling applies.
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

interface Props {
	image?: string;
	wrapped?: boolean;
}

const TemplateScreenshot = ({ image, wrapped }: Props) => {
	if (wrapped) {
		return (
			<div
				data-test="component-templateScreenshots"
				className="theme-screenshots"
			>
				<div className={image ? 'screenshot' : 'screenshot blank'}>
					{image ? <img src={image} alt="" /> : null}
				</div>
			</div>
		);
	}

	return (
		<div
			data-test="component-templateScreenshot"
			className={image ? 'theme-screenshot' : 'theme-screenshot blank'}
		>
			{image ? <img src={image} alt="" /> : null}
		</div>
	);
};

export default TemplateScreenshot;
