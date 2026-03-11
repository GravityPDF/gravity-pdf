/* Dependencies */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
/* Redux actions */
import { clearAddFontMsg } from '../../actions/fontManager';
/* Utilities */
import { toggleUpdateFont } from '../../utilities/FontManager/toggleUpdateFont';

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
		id: PropTypes.string,
		closeRoute: PropTypes.string,
		clearAddFontMsg: PropTypes.func.isRequired,
		msg: PropTypes.object.isRequired,
		navigate: PropTypes.func.isRequired,
		pathname: PropTypes.string.isRequired,
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
		const {
			id,
			navigate,
			pathname,
			clearAddFontMsg: clear,
			msg: { success, error },
		} = this.props;

		/* Close font manager 'Update Font' column first */
		if (e.key === 'Escape' && id) {
			/* Remove previous msg */
			if ((success && success.addFont) || (error && error.addFont)) {
				clear();
			}

			return toggleUpdateFont(navigate, '', pathname);
		}

		/* Close modal */
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

/**
 * Map redux state to props
 *
 * @param { Object } state
 * @param { Object } state.template
 * @param { Object } state.fontManager
 *
 * @return {{
 *  msg: object
 * }} Redux data
 *
 * @since 6.0
 */
const mapStateToProps = (state) => ({
	msg: state.fontManager.msg,
});

/**
 * Connect and dispatch redux actions as props
 *
 * @since 6.0
 */
export default connect(mapStateToProps, {
	clearAddFontMsg,
})(CloseDialog);
