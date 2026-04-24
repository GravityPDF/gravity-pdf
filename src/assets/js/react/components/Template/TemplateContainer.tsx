/* Dependencies */
import type { ReactNode } from 'react';
import { Modal } from '@wordpress/components';

/**
 * Renders our Advanced Template Selector container which is shared amongst the components
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

interface Props {
	title?: string;
	header?: ReactNode;
	footer?: ReactNode;
	children: ReactNode;
	onClose: () => void;
}

const TemplateContainer = ({
	title,
	header,
	footer,
	children,
	onClose,
}: Props) => {
	return (
		<Modal
			title={title}
			onRequestClose={onClose}
			className="gfpdf-template-manager-modal"
			size="fill"
		>
			<div
				data-test="component-templateContainer"
				className="gfpdf-template-manager-body"
			>
				{header}
				<div
					id="gfpdf-template-container"
					className="theme-about wp-clearfix theme-browser rendered"
				>
					{children}
				</div>
				{footer}
			</div>
		</Modal>
	);
};

export default TemplateContainer;
