/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';
import { sprintf } from 'sprintf-js';
/* Components */
import FontDeleteButton from './FontDeleteButton';
import FontInstallButton from './FontInstallButton';
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
 * The read-only detail of a bundled or core font. A core font can be installed if it has files to download, and deleted
 * if it's on disk and not in use.
 *
 * @param { Object } props
 * @param { Object } props.font
 *
 * @return { JSX.Element } FontDetail component
 *
 * @since 6.19.0
 */
export const FontDetail = ({ font }) => {
	const isAvailable = font.type === 'available';
	const isBundled = font.type === 'bundled';
	const description = {
		bundled: GFPDF.fontManagerBundledDesc,
		available: GFPDF.fontManagerAvailableHint,
		core: font.auto_install
			? GFPDF.fontManagerCoreHint
			: GFPDF.fontManagerCoreHintOffline,
	}[font.type];
	const coreLabel = isAvailable
		? GFPDF.fontManagerTypeAvailable
		: GFPDF.fontManagerTypeCore;

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
					{isBundled ? (
						<>
							<Icon name="lock" />
							{GFPDF.fontManagerBundledWith}
						</>
					) : (
						coreLabel
					)}
				</span>
				<span>{getStyleCount(font)}</span>
				<span>{font.size_label}</span>
				<span>
					{GFPDF.fontManagerTemplateUse}{' '}
					<code>font-family: {font.id}</code>
				</span>
			</div>

			<p className="font-detail-desc">{description}</p>

			{font.languages?.length > 0 && (
				<div data-test="font-languages" className="font-section">
					<h3>{GFPDF.fontManagerLanguages}</h3>
					<p>{font.languages.join(', ')}</p>
				</div>
			)}

			<FontUsage id={font.id} />

			<FontPreview font={font} />

			{!isBundled && !font.can_delete && !font.can_install && (
				<p
					data-test="font-list-network-note"
					className="font-detail-desc"
				>
					{GFPDF.fontManagerNetworkOnly}
				</p>
			)}

			{(font.can_delete || font.can_install) && (
				<div className="font-detail-footer">
					{font.can_install && <FontInstallButton id={font.id} />}
					{font.can_delete && (
						<FontDeleteButton
							id={font.id}
							type={font.type}
							message={confirmation}
						/>
					)}
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
