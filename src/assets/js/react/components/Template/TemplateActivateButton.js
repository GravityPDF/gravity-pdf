/* Dependencies */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
/* Redux actions */
import { selectTemplate } from '../../actions/templates';

/**
 * Renders the button used to trigger the current active PDF template
 * On click it triggers our Redux action.
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * React Component
 *
 * @since 4.1
 */
export class TemplateActivateButton extends Component {
	/**
	 * @since 4.1
	 */
	static propTypes = {
		navigate: PropTypes.func,
		onTemplateSelect: PropTypes.func,
		template: PropTypes.object,
		buttonText: PropTypes.string,
	};

	/**
	 * Update our route and trigger a Redux action to select the current template
	 *
	 * @param {Object} e Event
	 *
	 * @since 4.1
	 */
	handleSelectTemplate = (e) => {
		e.preventDefault();
		e.stopPropagation();

		this.props.navigate('/');
		this.props.onTemplateSelect(this.props.template.id);
	};

	/**
	 * @since 4.1
	 */
	render() {
		return (
			<button
				data-test="component-templateActivateButton"
				type="button"
				onClick={this.handleSelectTemplate}
				className="button activate"
				aria-label={this.props.buttonText + ' ' + GFPDF.template}
			>
				{this.props.buttonText}
			</button>
		);
	}
}

/**
 * TemplateActivateButton
 * Map actions to props
 *
 * @param { Function } dispatch Redux dispatcher
 *
 * @return {{ onTemplateSelect: Function }} mapped dispatch
 *
 * @since 4.1
 */
export const mapDispatchToProps = (dispatch) => {
	return {
		onTemplateSelect: (id) => dispatch(selectTemplate(id)),
	};
};

/**
 * Maps our Redux store to our React component
 *
 * @since 4.1
 */
export default connect(null, mapDispatchToProps)(TemplateActivateButton);
