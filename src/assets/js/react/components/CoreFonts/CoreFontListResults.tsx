/* Dependencies */
import type { MouseEvent } from 'react';
import { useNavigate } from 'react-router';
import { __ } from '@wordpress/i18n';
/* Components */
import ListSpacer from './CoreFontListSpacer';
/* Types */
import type { ConsoleLine } from '../../types';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

interface CoreFontListResultsProps {
	console?: Record<string, ConsoleLine>;
	retry?: string[];
}

export const CoreFontListResults = ({
	console: consoleMap = {},
	retry = [],
}: CoreFontListResultsProps) => {
	const lines = Object.keys(consoleMap).reverse();
	const hasRetry = retry.length > 0;

	return !lines.length ? null : (
		<ul
			data-test="component-coreFont-container"
			className="gfpdf-core-font-list-results-container"
			aria-label={__('Core font installation', 'gravity-pdf')}
		>
			{lines.map((key) => (
				<li
					data-test={consoleMap[key].status}
					key={key}
					className={
						'gfpdf-core-font-status-' + consoleMap[key].status
					}
				>
					{consoleMap[key].message}{' '}
					{key === 'completed' && hasRetry && <Retry />}
					{key === 'completed' && <ListSpacer />}
				</li>
			))}
		</ul>
	);
};

export const Retry = () => {
	const navigate = useNavigate();

	const handleClick = (e: MouseEvent<HTMLButtonElement>) => {
		e.preventDefault();
		navigate('retryDownloadCoreFonts');
	};

	return (
		<button
			data-test="component-retry-link"
			type="button"
			onClick={handleClick}
			aria-live="polite"
			className="gfpdf-core-font-retry-link"
		>
			{__('Retry Failed Downloads?', 'gravity-pdf')}
		</button>
	);
};
