/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
/* Redux actions */
import { selectFont as selectFontAction } from '../../actions/fontManager';
/* Components */
import Icon from './Icon';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.19.0
 */

/**
 * Says a font is the one selected in the underlying field, or offers to make it so
 *
 * @param { Object }   props
 * @param { string }   props.id
 * @param { string }   props.selectedFont
 * @param { Function } props.selectFont
 *
 * @return { JSX.Element } FontStatus component
 *
 * @since 6.19.0
 */
export const FontStatus = ({ id, selectedFont, selectFont }) =>
	id === selectedFont ? (
		<span data-test="font-active" className="font-pill">
			<Icon name="check" />
			{GFPDF.active}
		</span>
	) : (
		<button
			type="button"
			data-test="font-set-active"
			className="button font-set-active"
			onClick={() => selectFont(id)}
		>
			<Icon name="check" />
			{GFPDF.fontManagerSetActive}
		</button>
	);

/**
 * PropTypes
 *
 * @since 6.19.0
 */
FontStatus.propTypes = {
	id: PropTypes.string.isRequired,
	selectedFont: PropTypes.string.isRequired,
	selectFont: PropTypes.func.isRequired,
};

const mapStateToProps = (state) => ({
	selectedFont: state.fontManager.selectedFont,
});

export default connect(mapStateToProps, { selectFont: selectFontAction })(
	FontStatus
);
