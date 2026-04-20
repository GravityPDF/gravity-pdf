/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';
import { useNavigate } from 'react-router-dom';
/* Components */
import ListSpacer from './CoreFontListSpacer';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/**
 * Displays the Console output for our Core Font Downloader
 *
 * @param {Object} root0
 * @param {*}      root0.console
 * @param {*}      root0.retry
 * @param {*}      root0.retryText
 * @since 5.0
 */
export const CoreFontListResults = ({
	console: consoleMap,
	retry,
	retryText,
}) => {
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

CoreFontListResults.propTypes = {
	console: PropTypes.object,
	retry: PropTypes.array,
	retryText: PropTypes.string,
};

/**
 * Display a "retry" download link
 *
 * @param {Object} root0
 * @param {*}      root0.retryText
 * @since 5.0
 */
export const Retry = ({ retryText }) => {
	const navigate = useNavigate();

	const handleClick = (e) => {
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

Retry.propTypes = {
	retryText: PropTypes.string,
};
