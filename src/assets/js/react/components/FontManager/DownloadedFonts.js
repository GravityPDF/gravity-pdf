/* Dependencies */
import React, { useEffect } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { sprintf } from 'sprintf-js';
/* Redux actions */
import {
	getDownloadedFonts as getDownloadedFontsAction,
	deleteDownloadedFont as deleteDownloadedFontAction,
} from '../../actions/downloadedFonts';
/* Components */
import Alert from '../Alert/Alert';
import Spinner from '../Spinner';
/* Utilities */
import { checkFontListIncludes } from '../../utilities/FontManager/fontManagerReducer';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.19.0
 */

/**
 * Whether the font's name, key or a file name has the search keyword, matched as the custom font list does
 *
 * @param {Object} font
 * @param {string} keyword
 *
 * @return {boolean} Whether to show the font
 *
 * @since 6.19.0
 */
export const matchesKeyword = (font, keyword) => {
	const search = keyword.toLowerCase();

	return [font.label, font.id, ...font.files.map((face) => face.file)].some(
		(text) => checkFontListIncludes(text, search)
	);
};

const STYLES = {
	R: 'fontListRegular',
	B: 'fontListBold',
	I: 'fontListItalics',
	BI: 'fontListBoldItalics',
};

/**
 * The core fonts downloaded into the fonts folder, which can be deleted while unused
 *
 * @param {Object}        props
 * @param {boolean}       props.loading
 * @param {Array<Object>} props.fonts
 * @param {boolean}       props.canDelete
 * @param {string}        props.deletingId
 * @param {string}        props.keyword
 * @param {Object}        props.msg
 * @param {Function}      props.getDownloadedFonts
 * @param {Function}      props.deleteDownloadedFont
 *
 * @return {JSX.Element} DownloadedFonts component
 *
 * @since 6.19.0
 */
export const DownloadedFonts = ({
	loading,
	fonts,
	canDelete,
	deletingId,
	keyword,
	msg,
	getDownloadedFonts,
	deleteDownloadedFont,
}) => {
	useEffect(() => {
		getDownloadedFonts();
	}, [getDownloadedFonts]);

	const handleDelete = (font) => {
		const confirmation = font.auto_install
			? GFPDF.fontManagerDownloadedConfirm
			: GFPDF.fontManagerDownloadedConfirmOffline;

		// eslint-disable-next-line @wordpress/valid-sprintf
		if (window.confirm(sprintf(confirmation, font.label))) {
			deleteDownloadedFont({ id: font.id, label: font.label });
		}
	};

	const deleteLabel = (font) =>
		// eslint-disable-next-line @wordpress/valid-sprintf
		sprintf(GFPDF.fontManagerDownloadedDeleteAriaLabel, font.label);

	const list = fonts.filter((font) => matchesKeyword(font, keyword));

	return (
		<section
			data-test="component-DownloadedFonts"
			className="downloaded-fonts"
			aria-labelledby="gfpdf-downloaded-fonts-title"
		>
			<h3 id="gfpdf-downloaded-fonts-title">
				{GFPDF.fontManagerDownloadedTitle}
			</h3>
			<p>{GFPDF.fontManagerDownloadedDesc}</p>

			{!canDelete && fonts.length > 0 && (
				<p className="description">
					{GFPDF.fontManagerDownloadedReadOnly}
				</p>
			)}

			{msg.error && <Alert msg={msg.error} />}

			<div aria-live="polite">
				{msg.success && (
					<div className="notice notice-success gf-notice">
						<p>{msg.success}</p>
					</div>
				)}
			</div>

			{loading && <Spinner style="downloaded-fonts" />}

			{!loading && list.length === 0 && (
				<p data-test="downloaded-fonts-empty">
					{fonts.length === 0
						? GFPDF.fontManagerDownloadedEmpty
						: GFPDF.searchResultEmpty}
				</p>
			)}

			{!loading && list.length > 0 && (
				<table className="widefat striped">
					<thead>
						<tr>
							<th scope="col">
								{GFPDF.fontManagerDownloadedFont}
							</th>
							<th scope="col">
								{GFPDF.fontManagerDownloadedStyles}
							</th>
							<th scope="col">
								{GFPDF.fontManagerDownloadedSize}
							</th>
							<th scope="col">
								{GFPDF.fontManagerDownloadedStatus}
							</th>
							<td />
						</tr>
					</thead>
					<tbody>
						{list.map((font) => (
							<tr key={font.id} data-test="downloaded-font">
								<th scope="row">{font.label}</th>
								<td>
									{font.files
										.map(
											(face) => GFPDF[STYLES[face.style]]
										)
										.join(', ')}
								</td>
								<td>{font.size_label}</td>
								<td>
									{font.in_use.length > 0
										? font.in_use.join(', ')
										: GFPDF.fontManagerDownloadedUnused}
								</td>
								<td>
									{deletingId === font.id ? (
										<Spinner style="delete-font" />
									) : (
										<button
											type="button"
											className="button button-small"
											disabled={
												!canDelete ||
												font.in_use.length > 0 ||
												deletingId !== ''
											}
											aria-label={deleteLabel(font)}
											onClick={() => handleDelete(font)}
										>
											{GFPDF.fontManagerDownloadedDelete}
										</button>
									)}
								</td>
							</tr>
						))}
					</tbody>
				</table>
			)}
		</section>
	);
};

/**
 * PropTypes
 *
 * @since 6.19.0
 */
DownloadedFonts.propTypes = {
	loading: PropTypes.bool.isRequired,
	fonts: PropTypes.arrayOf(
		PropTypes.shape({
			id: PropTypes.string.isRequired,
			label: PropTypes.string.isRequired,
			files: PropTypes.arrayOf(PropTypes.object).isRequired,
			size_label: PropTypes.string.isRequired,
			in_use: PropTypes.arrayOf(PropTypes.string).isRequired,
			auto_install: PropTypes.bool.isRequired,
		})
	).isRequired,
	canDelete: PropTypes.bool.isRequired,
	deletingId: PropTypes.string.isRequired,
	keyword: PropTypes.string.isRequired,
	msg: PropTypes.object.isRequired,
	getDownloadedFonts: PropTypes.func.isRequired,
	deleteDownloadedFont: PropTypes.func.isRequired,
};

/**
 * Map redux state to props
 *
 * @param {Object} state
 *
 * @return {Object} mapped state
 *
 * @since 6.19.0
 */
const mapStateToProps = (state) => ({
	...state.downloadedFonts,
});

export default connect(mapStateToProps, {
	getDownloadedFonts: getDownloadedFontsAction,
	deleteDownloadedFont: deleteDownloadedFontAction,
})(DownloadedFonts);
