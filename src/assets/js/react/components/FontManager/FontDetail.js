/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';
import { sprintf } from 'sprintf-js';
/* Components */
import FontDeleteButton from './FontDeleteButton';
import FontPreview from './FontPreview';
import FontStatus from './FontStatus';
import FontUsage from './FontUsage';
import Icon from './Icon';
/* Utilities */
import { getStyleCount } from '../../utilities/FontManager/fontManagerReducer';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.19.0
 */

/**
 * The read-only detail of a bundled or core font. Only a core font not in use can be deleted.
 *
 * @param { Object } props
 * @param { Object } props.font
 *
 * @return { JSX.Element } FontDetail component
 *
 * @since 6.19.0
 */
export const FontDetail = ({ font }) => {
	const isCore = font.type === 'core';

	// %s is found inside these strings
	// eslint-disable-next-line @wordpress/valid-sprintf
	const confirmation = sprintf(
		font.auto_install
			? GFPDF.fontManagerCoreConfirm
			: GFPDF.fontManagerCoreConfirmOffline,
		font.font_name
	);

	return (
		<div data-test="component-FontDetail" className="font-detail">
			<div className="font-detail-header">
				<h2>{font.font_name}</h2>
				<FontStatus id={font.id} />
			</div>

			<div className="font-eyebrow">
				<span className="font-eyebrow-source">
					{isCore ? (
						GFPDF.fontManagerTypeCore
					) : (
						<>
							<Icon name="lock" />
							{GFPDF.fontManagerBundledWith}
						</>
					)}
				</span>
				<span>{getStyleCount(font)}</span>
				<span>{font.size_label}</span>
				<span>
					{GFPDF.fontManagerTemplateUse}{' '}
					<code>font-family: {font.id}</code>
				</span>
			</div>

			<p className="font-detail-desc">
				{!isCore && GFPDF.fontManagerBundledDesc}
				{isCore &&
					(font.auto_install
						? GFPDF.fontManagerCoreHint
						: GFPDF.fontManagerCoreHintOffline)}
			</p>

			<FontUsage id={font.id} />

			<FontPreview font={font} />

			{isCore && !font.can_delete && (
				<p
					data-test="font-list-network-note"
					className="font-detail-desc"
				>
					{GFPDF.fontManagerNetworkOnly}
				</p>
			)}

			{isCore && font.can_delete && (
				<div className="font-detail-footer">
					<FontDeleteButton
						id={font.id}
						type={font.type}
						message={confirmation}
					/>
				</div>
			)}
		</div>
	);
};

/**
 * PropTypes
 *
 * @since 6.19.0
 */
FontDetail.propTypes = {
	font: PropTypes.object.isRequired,
};

export default FontDetail;
