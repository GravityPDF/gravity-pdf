/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
/* Components */
import Icon from './Icon';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.19.0
 */

/**
 * What's using a font, with links to the settings of its PDFs. They open in a new tab so unsaved settings under the
 * Font Manager aren't lost. Empty until usage loads.
 *
 * @param { Object } props
 * @param { string } props.id
 * @param { Object } props.fontUsage
 *
 * @return { JSX.Element | null } FontUsage component
 *
 * @since 6.19.0
 */
export const FontUsage = ({ id, fontUsage }) => {
	if (!fontUsage) {
		return null;
	}

	const usage = fontUsage[id];

	return (
		<div
			data-test="component-FontUsage"
			className="font-section font-usage"
		>
			<h3>{GFPDF.fontListInUse}</h3>

			{!usage && <p className="font-unused">{GFPDF.fontListNotInUse}</p>}

			{usage?.reasons.length > 0 && <p>{usage.reasons.join(', ')}</p>}

			{usage?.pdfs.length > 0 && (
				<>
					<p>{usage.pdfs_label}</p>
					<ul data-test="font-list-pdfs">
						{usage.pdfs.map((pdf) => (
							<li key={pdf.url}>
								<a
									href={pdf.url}
									target="_blank"
									rel="noopener noreferrer"
								>
									{pdf.name}
									<span className="screen-reader-text">
										{' ' + GFPDF.fontManagerOpensNewTab}
									</span>
									<Icon name="external" />
								</a>
								<span className="font-pdf-form">
									{pdf.form}
								</span>
							</li>
						))}
					</ul>
				</>
			)}
		</div>
	);
};

/**
 * PropTypes
 *
 * @since 6.19.0
 */
FontUsage.propTypes = {
	id: PropTypes.string.isRequired,
	fontUsage: PropTypes.object,
};

const mapStateToProps = (state) => ({
	fontUsage: state.fontManager.fontUsage,
});

export default connect(mapStateToProps)(FontUsage);
