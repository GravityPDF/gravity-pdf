/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

/**
 * The button beside a font dropdown that opens the manager
 *
 * A plain `<button>` with WordPress's own classes rather than a `@wordpress/components` one: it sits inside a
 * Gravity Forms settings row next to core controls, and matching them is the point.
 *
 * @param {Object}   props
 * @param {string}   props.label
 * @param {Function} props.onClick
 *
 * @return {JSX.Element} The button
 *
 * @since 7.0
 */
export default function ManageFontsButton({ label, onClick }) {
	return (
		<button
			type="button"
			className="button gfpdf-button"
			data-test="component-ManageFontsButton"
			onClick={onClick}
		>
			{label}
		</button>
	);
}
