/* Dependencies */
import * as React from '@wordpress/element';
import { useRef, useEffect } from '@wordpress/element';
import { NavigateFunction } from 'react-router-dom';
/* Types */
import { TemplateItem } from '../../types';

/**
 * Renders the template navigation header that get displayed on the
 * /template/:id pages.
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

interface Props {
	templates: TemplateItem[];
	templateIndex: number;
	template?: TemplateItem;
	navigate: NavigateFunction;
	showPreviousTemplateText?: string;
	showNextTemplateText?: string;
}

const TemplateHeaderNavigation = ({
	templates,
	templateIndex,
	template,
	navigate,
	showPreviousTemplateText,
	showNextTemplateText,
}: Props) => {
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
		const handleKeyPress = (e: KeyboardEvent) => {
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

	const handlePreviousTemplate = (e: React.MouseEvent<HTMLButtonElement>) => {
		e.preventDefault();
		e.stopPropagation();

		const prevId = templates[templateIndex - 1]?.id;
		if (prevId) {
			navigate('/template/' + prevId);
		}
	};

	const handleNextTemplate = (e: React.MouseEvent<HTMLButtonElement>) => {
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

	return (
		<span data-test="component-templateHeaderNavigation">
			<button
				data-test="component-showPreviousTemplateButton"
				onClick={handlePreviousTemplate}
				className={prevClass}
				disabled={isFirst}
			>
				<span className="screen-reader-text">
					{showPreviousTemplateText}
				</span>
			</button>

			<button
				data-test="component-showNextTemplateButton"
				onClick={handleNextTemplate}
				className={nextClass}
				disabled={isLast}
			>
				<span className="screen-reader-text">
					{showNextTemplateText}
				</span>
			</button>
		</span>
	);
};

export default TemplateHeaderNavigation;
