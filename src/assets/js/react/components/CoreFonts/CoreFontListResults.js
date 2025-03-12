/* Dependencies */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
/* Components */
import ListSpacer from './CoreFontListSpacer';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/**
 * Displays the Console output for our Core Font Downloader
 *
 * @since 5.0
 */
export class CoreFontListResults extends Component {
	/**
	 *
	 * @since 5.0
	 */
	static propTypes = {
		console: PropTypes.object,
		retry: PropTypes.array,
		navigate: PropTypes.func,
		retryText: PropTypes.string,
	};

	/**
	 * @return { JSX.Element } CoreFontListResults Component
	 *
	 * @since 5.0
	 */
	render() {
		const console = this.props.console;
		const lines = Object.keys(console).reverse();
		const retry = this.props.retry.length > 0;

		return !lines.length ? null : (
			<ul
				data-test="component-coreFont-container"
				className="gfpdf-core-font-list-results-container"
				aria-label={GFPDF.coreFontAriaLabel}
			>
				{lines.map((key) => (
					<li
						data-test={console[key].status}
						key={key}
						className={
							'gfpdf-core-font-status-' + console[key].status
						}
					>
						{console[key].message}{' '}
						{key === 'completed' && retry && (
							<Retry
								navigate={this.props.navigate}
								retryText={this.props.retryText}
							/>
						)}
						{key === 'completed' && <ListSpacer />}
					</li>
				))}
			</ul>
		);
	}
}

/**
 * @since 5.0
 */
export class Retry extends Component {
	/**
	 *
	 * @since 5.0
	 */
	static propTypes = {
		navigate: PropTypes.func,
		retryText: PropTypes.string,
	};

	/**
	 * Update the navigation history when the retry link is selected
	 *
	 * @param { Event } e
	 *
	 * @since 5.0
	 */
	handleTriggerRetryFontDownload = (e) => {
		e.preventDefault();
		this.props.navigate('retryDownloadCoreFonts');
	};

	/**
	 * Display a "retry" download link
	 *
	 * @return { JSX.Element } Retry Component
	 *
	 * @since 5.0
	 */
	render() {
		return (
			<button
				data-test="component-retry-link"
				type="button"
				onClick={this.handleTriggerRetryFontDownload}
				aria-live="polite"
				className="gfpdf-core-font-retry-link"
			>
				{this.props.retryText}
			</button>
		);
	}
}
