/* Dependencies */
import React, { useState } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
/* Redux actions */
import { deleteFont as deleteFontAction } from '../../actions/fontManager';
/* Components */
import DeleteConfirm from './DeleteConfirm';
import Icon from './Icon';
import Spinner from '../Spinner';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.19.0
 */

/**
 * Deletes a font once confirmed, or says why it can't while it's in use. Off until usage loads.
 *
 * @param { Object }   props
 * @param { string }   props.id
 * @param { string }   props.type
 * @param { string }   props.message
 * @param { Object }   props.fontUsage
 * @param { boolean }  props.loading
 * @param { Function } props.deleteFont
 *
 * @return { JSX.Element } FontDeleteButton component
 *
 * @since 6.19.0
 */
export const FontDeleteButton = ({
	id,
	type,
	message,
	fontUsage,
	loading,
	deleteFont,
}) => {
	const [confirming, setConfirming] = useState(false);
	const usage = fontUsage?.[id];
	const locked = !fontUsage || !!usage;

	if (loading) {
		return <Spinner style="delete-font" />;
	}

	return (
		<>
			<button
				type="button"
				className="button font-delete"
				aria-disabled={locked}
				title={
					usage
						? [...usage.reasons, usage.pdfs_label]
								.filter(Boolean)
								.join(', ')
						: undefined
				}
				onClick={() => !locked && setConfirming(true)}
			>
				<Icon name="trash" />
				{GFPDF.fontManagerDeleteFontAriaLabel}
			</button>

			{confirming && (
				<DeleteConfirm
					message={message}
					onConfirm={() => {
						setConfirming(false);
						deleteFont(id, type);
					}}
					onCancel={() => setConfirming(false)}
				/>
			)}
		</>
	);
};

/**
 * PropTypes
 *
 * @since 6.19.0
 */
FontDeleteButton.propTypes = {
	id: PropTypes.string.isRequired,
	type: PropTypes.string.isRequired,
	message: PropTypes.string.isRequired,
	fontUsage: PropTypes.object,
	loading: PropTypes.bool.isRequired,
	deleteFont: PropTypes.func.isRequired,
};

const mapStateToProps = (state) => ({
	fontUsage: state.fontManager.fontUsage,
	loading: state.fontManager.deleteFontLoading,
});

export default connect(mapStateToProps, { deleteFont: deleteFontAction })(
	FontDeleteButton
);
