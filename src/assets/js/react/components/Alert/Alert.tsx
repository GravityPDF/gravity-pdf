/* Dependencies */

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

interface Props {
	msg: string;
}

export const Alert = ({ msg }: Props) => (
	<div data-test="component-Alert" id="gf-admin-notices-wrapper">
		<div
			className="notice notice-error gf-notice"
			dangerouslySetInnerHTML={{ __html: msg }}
		/>
	</div>
);

export default Alert;
