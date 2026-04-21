/* Dependencies */
import * as React from '@wordpress/element';
import { useNavigate } from 'react-router-dom';
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
	retryText?: string;
}

export const CoreFontListResults = ({
	console: consoleMap = {},
	retry = [],
	retryText,
}: CoreFontListResultsProps) => {
	const lines = Object.keys(consoleMap).reverse();
	const hasRetry = retry.length > 0;

	return !lines.length ? null : (
		<ul
			data-test="component-coreFont-container"
			className="gfpdf-core-font-list-results-container"
			aria-label={GFPDF.coreFontAriaLabel}
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
					{key === 'completed' && hasRetry && (
						<Retry retryText={retryText} />
					)}
					{key === 'completed' && <ListSpacer />}
				</li>
			))}
		</ul>
	);
};

interface RetryProps {
	retryText?: string;
}

export const Retry = ({ retryText }: RetryProps) => {
	const navigate = useNavigate();

	const handleClick = (e: React.MouseEvent<HTMLButtonElement>) => {
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
			{retryText}
		</button>
	);
};
