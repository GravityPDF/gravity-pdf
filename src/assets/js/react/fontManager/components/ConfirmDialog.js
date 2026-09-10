/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __ } from '@wordpress/i18n';
import { Button, Modal } from '@wordpress/components';

/**
 * The two-button confirm, for the changes an admin cannot take back
 *
 * @param {Object}   props
 * @param {string}   props.title
 * @param {string}   props.confirmLabel
 * @param {boolean}  props.destructive
 * @param {Function} props.onConfirm
 * @param {Function} props.onCancel
 * @param {*}        props.children     The explanation, and anything it needs to list
 *
 * @return {JSX.Element} The dialog
 *
 * @since 7.0
 */
export default function ConfirmDialog({
	title,
	confirmLabel,
	destructive = false,
	onConfirm,
	onCancel,
	children,
}) {
	return (
		<Modal
			title={title}
			onRequestClose={onCancel}
			className="gfpdf-fm-confirm"
			size="small"
		>
			<div className="gfpdf-fm-confirm-body">{children}</div>

			<div className="gfpdf-fm-confirm-actions">
				<Button variant="tertiary" onClick={onCancel}>
					{__('Cancel', 'gravity-pdf')}
				</Button>
				<Button
					variant="primary"
					isDestructive={destructive}
					onClick={onConfirm}
				>
					{confirmLabel}
				</Button>
			</div>
		</Modal>
	);
}
