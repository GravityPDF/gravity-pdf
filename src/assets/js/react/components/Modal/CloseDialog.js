/* Dependencies */
import React, { Component } from 'react';
import PropTypes from 'prop-types';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * CloseDialog component
 *
 * @since 6.0
 */
export class CloseDialog extends Component {
	/**
	 * PropTypes
	 *
	 * @since 6.0
	 */
	static propTypes = {
		closeRoute: PropTypes.string,
		navigate: PropTypes.func.isRequired,
	};

	/**
	 * Assign keydown listener to document on mount
	 *
	 * @since 6.0
	 */
	componentDidMount() {
		document.addEventListener('keydown', this.handleKeyPress, false);
	}

	/**
	 * Remove keydown listener to document on mount
	 *
	 * @since 6.0
	 */
	componentWillUnmount() {
		document.removeEventListener('keydown', this.handleKeyPress, false);
	}

	/**
	 * Check if Escape key pressed and current event target isn't our search box,
	 * or the search box is blank already
	 *
	 * @param { KeyboardEvent } e
	 *
	 * @since 6.0
	 */
	handleKeyPress = (e) => {
		if (
			e.key === 'Escape' &&
			(e.target.className !== 'wp-filter-search' || e.target.value === '')
		) {
			this.handleCloseDialog();
		}
	};

	/**
	 * Close the modal
	 *
	 * @since 6.0
	 */
	handleCloseDialog = () => {
		/* trigger router */
		this.props.navigate(this.props.closeRoute || '/');
	};

	/**
	 * Display the modal close dialog UI
	 *
	 * @since 6.0
	 */
	render() {
		return (
			<button
				type="button"
				data-test="component-CloseDialog"
				className="close dashicons dashicons-no"
				onClick={this.handleCloseDialog}
				aria-label="close"
			>
				<span className="screen-reader-text">{GFPDF.closeDialog}</span>
			</button>
		);
	}
}

export default CloseDialog;
