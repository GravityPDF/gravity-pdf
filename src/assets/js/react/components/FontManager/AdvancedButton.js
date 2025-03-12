/* Dependencies */
import React, { Component } from 'react';
import PropTypes from 'prop-types';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * AdvancedButton component
 *
 * @since 6.0
 */
export class AdvancedButton extends Component {
	/**
	 * PropTypes
	 *
	 * @since 6.0
	 */
	static propTypes = {
		navigate: PropTypes.func,
	};

	/**
	 * Handle advanced button click and open the font manager modal
	 *
	 * @param { Event } e
	 *
	 * @since 6.0
	 */
	handleClick = (e) => {
		e.preventDefault();

		this.props.navigate('/fontmanager/');
	};

	/**
	 * Display advanced button UI
	 *
	 * @since 6.0
	 */
	render() {
		return (
			<button
				data-test="component-AdvancedButton"
				type="button"
				className="button gfpdf-button"
				onClick={this.handleClick}
			>
				{GFPDF.manage}
			</button>
		);
	}
}

export default AdvancedButton;
