/* Dependencies */
import React, { useEffect } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
/* Redux actions */
import { getFontPdfs as getFontPdfsAction } from '../../actions/fontManager';
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
 * @param { Object }        props
 * @param { string }        props.id
 * @param { Object }        props.fontUsage
 * @param { Array<Object> } props.pdfs
 * @param { Function }      props.getFontPdfs
 *
 * @return { JSX.Element | null } FontUsage component
 *
 * @since 6.19.0
 */
export const FontUsage = ({ id, fontUsage, pdfs, getFontPdfs }) => {
	const usage = fontUsage?.[id];
	const hasPdfs = !!usage?.pdfs_label;
	const needsPdfs = hasPdfs && !pdfs;

	useEffect(() => {
		if (needsPdfs) {
			getFontPdfs(id);
		}
	}, [id, needsPdfs, getFontPdfs]);

	if (!fontUsage) {
		return null;
	}

	return (
		<div
			data-test="component-FontUsage"
			className="font-section font-usage"
		>
			<h3>{GFPDF.fontListInUse}</h3>

			{!usage && <p className="font-unused">{GFPDF.fontListNotInUse}</p>}

			{usage?.reasons.length > 0 && <p>{usage.reasons.join(', ')}</p>}

			{hasPdfs && (
				<>
					<p>{usage.pdfs_label}</p>
					<ul data-test="font-list-pdfs">
						{pdfs?.map((pdf) => (
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
	pdfs: PropTypes.array,
	getFontPdfs: PropTypes.func.isRequired,
};

const mapStateToProps = (state, { id }) => ({
	fontUsage: state.fontManager.fontUsage,
	pdfs: state.fontManager.fontPdfs[id],
});

export default connect(mapStateToProps, { getFontPdfs: getFontPdfsAction })(
	FontUsage
);
