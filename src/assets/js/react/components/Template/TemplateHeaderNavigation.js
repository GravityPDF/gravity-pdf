/* Dependencies */
import React, { useRef, useEffect } from 'react';
import PropTypes from 'prop-types';

/**
 * Renders the template navigation header that get displayed on the
 * /template/:id pages.
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
 * @param {*}      root0.templates
 * @param {*}      root0.templateIndex
 * @param {*}      root0.template
 * @param {*}      root0.navigate
 * @param {*}      root0.showPreviousTemplateText
 * @param {*}      root0.showNextTemplateText
 * @since 4.1
 */
const TemplateHeaderNavigation = ({
	templates,
	templateIndex,
	template,
	navigate,
	showPreviousTemplateText,
	showNextTemplateText,
}) => {
	const lastIdx = templates.length - 1;
	const isFirst = templates[0]?.id === template?.id;
	const isLast = templates[lastIdx]?.id === template?.id;

	/* Ref mirrors for stale-closure-safe keydown handler */
	const isFirstRef = useRef(isFirst);
	isFirstRef.current = isFirst;
	const isLastRef = useRef(isLast);
	isLastRef.current = isLast;
	const navigateRef = useRef(navigate);
	navigateRef.current = navigate;
	const templatesRef = useRef(templates);
	templatesRef.current = templates;
	const templateIndexRef = useRef(templateIndex);
	templateIndexRef.current = templateIndex;

	useEffect(() => {
		const handleKeyPress = (e) => {
			if (!isFirstRef.current && e.keyCode === 37) {
				e.preventDefault();
				e.stopPropagation();
				const prevId =
					templatesRef.current[templateIndexRef.current - 1]?.id;
				if (prevId) {
					navigateRef.current('/template/' + prevId);
				}
			}
			if (!isLastRef.current && e.keyCode === 39) {
				e.preventDefault();
				e.stopPropagation();
				const nextId =
					templatesRef.current[templateIndexRef.current + 1]?.id;
				if (nextId) {
					navigateRef.current('/template/' + nextId);
				}
			}
		};

		window.addEventListener('keydown', handleKeyPress, false);

		return () => {
			window.removeEventListener('keydown', handleKeyPress, false);
		};
	}, []);

	const handlePreviousTemplate = (e) => {
		e.preventDefault();
		e.stopPropagation();

		const prevId = templates[templateIndex - 1]?.id;
		if (prevId) {
			navigate('/template/' + prevId);
		}
	};

	const handleNextTemplate = (e) => {
		e.preventDefault();
		e.stopPropagation();

		const nextId = templates[templateIndex + 1]?.id;
		if (nextId) {
			navigate('/template/' + nextId);
		}
	};

	const prevClass = isFirst
		? 'dashicons dashicons-no left disabled'
		: 'dashicons dashicons-no left';
	const nextClass = isLast
		? 'dashicons dashicons-no right disabled'
		: 'dashicons dashicons-no right';

	const leftDisabled = isFirst ? 'disabled' : '';
	const rightDisabled = isLast ? 'disabled' : '';

	return (
		<span data-test="component-templateHeaderNavigation">
			<button
				data-test="component-showPreviousTemplateButton"
				onClick={handlePreviousTemplate}
				className={prevClass}
				disabled={leftDisabled}
			>
				<span className="screen-reader-text">
					{showPreviousTemplateText}
				</span>
			</button>

			<button
				data-test="component-showNextTemplateButton"
				onClick={handleNextTemplate}
				className={nextClass}
				disabled={rightDisabled}
			>
				<span className="screen-reader-text">
					{showNextTemplateText}
				</span>
			</button>
		</span>
	);
};

TemplateHeaderNavigation.propTypes = {
	templates: PropTypes.array.isRequired,
	templateIndex: PropTypes.number.isRequired,
	template: PropTypes.object,
	navigate: PropTypes.func,
	showPreviousTemplateText: PropTypes.string,
	showNextTemplateText: PropTypes.string,
};

export default TemplateHeaderNavigation;
