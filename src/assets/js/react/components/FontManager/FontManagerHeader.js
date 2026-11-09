/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';
import { sprintf } from 'sprintf-js';
/* Components */
import CloseDialog from '../Modal/CloseDialog';
/* Helpers */
import withRouterHooks from '../../utilities/withRouterHooks';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

const CloseDialogWithRouter = withRouterHooks(CloseDialog);

/**
 * Display the header of font manager  UI
 *
 * @param { Object } props
 * @param { string } props.id
 *
 * @since 6.0
 */
const FontManagerHeader = ({ id, count }) => (
	<div data-test="component-FontManagerHeader" className="theme-header">
		<h1>{GFPDF.fontManagerTitle}</h1>

		{count > 0 && (
			<span className="font-count">
				{/* %d is found inside these strings */}
				{/* eslint-disable-next-line @wordpress/valid-sprintf */}
				{sprintf(
					count === 1
						? GFPDF.fontManagerFontInstalled
						: GFPDF.fontManagerFontsInstalled,
					count
				)}
			</span>
		)}

		<CloseDialogWithRouter id={id} />
	</div>
);

/**
 * PropTypes
 *
 * @since 6.0
 */
FontManagerHeader.propTypes = {
	id: PropTypes.string,
	count: PropTypes.number,
};

export default FontManagerHeader;
