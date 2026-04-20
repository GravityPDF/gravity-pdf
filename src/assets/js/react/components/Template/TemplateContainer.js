/* Dependencies */
import React, { useRef, useEffect } from 'react';
import PropTypes from 'prop-types';
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

/**
 * React Component
 *
 * @param {Object} root0
 * @param {*}      root0.header
 * @param {*}      root0.footer
 * @param {*}      root0.children
 * @param {*}      root0.closeRoute
 * @since 4.1
 */
const TemplateContainer = ({ header, footer, children, closeRoute }) => {
	const containerRef = useRef(null);

	useEffect(() => {
		const handleFocus = (e) => {
			if (!containerRef.current.contains(e.target)) {
				e.stopPropagation();
				containerRef.current.focus();
			}
		};

		document.addEventListener('focus', handleFocus, true);

		if (containerRef.current.className !== 'wp-filter-search') {
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
			tabIndex="0"
		>
			<div className="backdrop theme-backdrop" />
			<div className="container theme-wrap">
				<div className="theme-header">
					{header}
					<CloseDialog closeRoute={closeRoute} />
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

TemplateContainer.propTypes = {
	header: PropTypes.oneOfType([PropTypes.string, PropTypes.element]),
	footer: PropTypes.oneOfType([PropTypes.string, PropTypes.element]),
	children: PropTypes.node.isRequired,
	closeRoute: PropTypes.string,
};

export default TemplateContainer;
