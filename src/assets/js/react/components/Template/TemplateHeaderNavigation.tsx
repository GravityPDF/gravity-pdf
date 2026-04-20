/* Dependencies */
import { useEffect } from '@wordpress/element';
import type { MouseEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { chevronLeft, chevronRight } from '@wordpress/icons';
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
	onSelectTemplate: (id: string) => void;
}

const TemplateHeaderNavigation = ({
	templates,
	templateIndex,
	template,
	onSelectTemplate,
}: Props) => {
	const lastIdx = templates.length - 1;
	const isFirst = templates[0]?.id === template?.id;
	const isLast = templates[lastIdx]?.id === template?.id;

	useEffect(() => {
		const handleKeyPress = (e: KeyboardEvent) => {
			if (!isFirst && e.key === 'ArrowLeft') {
				e.preventDefault();
				e.stopPropagation();
				const prevId = templates[templateIndex - 1]?.id;
				if (prevId) {
					onSelectTemplate(prevId);
				}
			}
			if (!isLast && e.key === 'ArrowRight') {
				e.preventDefault();
				e.stopPropagation();
				const nextId = templates[templateIndex + 1]?.id;
				if (nextId) {
					onSelectTemplate(nextId);
				}
			}
		};

		window.addEventListener('keydown', handleKeyPress);
		return () => {
			window.removeEventListener('keydown', handleKeyPress);
		};
	}, [isFirst, isLast, templates, templateIndex, onSelectTemplate]);

	const handlePreviousTemplate = (e: MouseEvent<HTMLButtonElement>) => {
		e.preventDefault();
		e.stopPropagation();

		const prevId = templates[templateIndex - 1]?.id;
		if (prevId) {
			onSelectTemplate(prevId);
		}
	};

	const handleNextTemplate = (e: MouseEvent<HTMLButtonElement>) => {
		e.preventDefault();
		e.stopPropagation();

		const nextId = templates[templateIndex + 1]?.id;
		if (nextId) {
			onSelectTemplate(nextId);
		}
	};

	return (
		<span data-test="component-templateHeaderNavigation">
			<Button
				data-test="component-showPreviousTemplateButton"
				icon={chevronLeft}
				label={__('Show previous template', 'gravity-pdf')}
				onClick={handlePreviousTemplate}
				disabled={isFirst}
				accessibleWhenDisabled
			/>
			<Button
				data-test="component-showNextTemplateButton"
				icon={chevronRight}
				label={__('Show next template', 'gravity-pdf')}
				onClick={handleNextTemplate}
				disabled={isLast}
				accessibleWhenDisabled
			/>
		</span>
	);
};

export default TemplateHeaderNavigation;
