/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { SearchControl } from '@wordpress/components';

/**
 * The sidebar and browser search field
 *
 * @param {Object}   props
 * @param {string}   props.value
 * @param {Function} props.onChange
 * @param {string}   props.label
 * @param {string}   props.placeholder
 *
 * @return {JSX.Element} The field
 *
 * @since 7.0
 */
export default function SearchBox({ value, onChange, label, placeholder }) {
	return (
		<SearchControl
			__nextHasNoMarginBottom
			label={label}
			placeholder={placeholder}
			value={value}
			onChange={onChange}
		/>
	);
}
