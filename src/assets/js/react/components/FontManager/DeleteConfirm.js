/* Dependencies */
import React, { useEffect, useRef } from 'react';
import PropTypes from 'prop-types';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.19.0
 */

/**
 * Asks to confirm a font's deletion in a dialog over the Font Manager, focused on the safe choice
 *
 * @param { Object }   props
 * @param { string }   props.message
 * @param { Function } props.onConfirm
 * @param { Function } props.onCancel
 *
 * @return { JSX.Element } DeleteConfirm component
 *
 * @since 6.19.0
 */
const DeleteConfirm = ({ message, onConfirm, onCancel }) => {
	const cancel = useRef(null);

	useEffect(() => {
		cancel.current?.focus();
	}, []);

	/* Escape closes the dialog only, not the Font Manager behind it */
	const handleKeyDown = (e) => {
		if (e.key === 'Escape') {
			e.stopPropagation();
			onCancel();
		}
	};

	return (
		// eslint-disable-next-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions
		<div
			data-test="component-DeleteConfirm"
			className="font-delete-confirm"
			onClick={onCancel}
			onKeyDown={handleKeyDown}
		>
			{/* eslint-disable-next-line jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */}
			<div
				role="alertdialog"
				aria-modal="true"
				aria-labelledby="gfpdf-font-delete-title"
				aria-describedby="gfpdf-font-delete-message"
				className="font-delete-confirm-dialog"
				onClick={(e) => e.stopPropagation()}
			>
				<h3 id="gfpdf-font-delete-title">
					{GFPDF.fontManagerDeleteTitle}
				</h3>

				<p id="gfpdf-font-delete-message">{message}</p>

				<div className="font-delete-confirm-actions">
					<button
						ref={cancel}
						type="button"
						className="button font-delete-confirm-cancel"
						onClick={onCancel}
					>
						{GFPDF.cancel}
					</button>

					<button
						type="button"
						className="button font-delete-confirm-delete"
						onClick={onConfirm}
					>
						{GFPDF.delete}
					</button>
				</div>
			</div>
		</div>
	);
};

DeleteConfirm.propTypes = {
	message: PropTypes.string.isRequired,
	onConfirm: PropTypes.func.isRequired,
	onCancel: PropTypes.func.isRequired,
};

export default DeleteConfirm;
