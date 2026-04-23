/* Dependencies */
import { useRef, useEffect } from '@wordpress/element';
import type { ReactNode } from 'react';
/* Components */
import CloseDialog from '../Modal/CloseDialog';

/**
 * Renders our Advanced Template Selector container which is shared amongst the components
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

interface Props {
	header?: ReactNode;
	footer?: ReactNode;
	children: ReactNode;
	onClose: () => void;
}

const TemplateContainer = ({ header, footer, children, onClose }: Props) => {
	const containerRef = useRef<HTMLDivElement>(null);

	useEffect(() => {
		const handleFocus = (e: FocusEvent) => {
			if (
				containerRef.current &&
				!containerRef.current.contains(e.target as Node)
			) {
				e.stopPropagation();
				containerRef.current.focus();
			}
		};

		document.addEventListener('focus', handleFocus, true);

		if (
			containerRef.current &&
			containerRef.current.className !== 'wp-filter-search'
		) {
			containerRef.current.focus();
		}

		return () => {
			document.removeEventListener('focus', handleFocus, true);
		};
	}, []);

	return (
		<div
			data-test="component-templateContainer"
			ref={containerRef}
			tabIndex={0}
		>
			<div className="backdrop theme-backdrop" />
			<div className="container theme-wrap">
				<div className="theme-header">
					{header}
					<CloseDialog onClose={onClose} />
				</div>

				<div
					id="gfpdf-template-container"
					className="theme-about wp-clearfix theme-browser rendered"
				>
					{children}
				</div>

				{footer}
			</div>
		</div>
	);
};

export default TemplateContainer;
