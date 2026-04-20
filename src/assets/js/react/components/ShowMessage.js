/* Dependencies */
import React, { useState, useEffect, useRef } from 'react';
import PropTypes from 'prop-types';

/**
 * Renders a message or error, with the option to self-clear itself
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * React Component
 *
 * @param {Object} root0
 * @param {*}      root0.text
 * @param {*}      root0.error
 * @param {*}      root0.delay
 * @param {*}      root0.dismissable
 * @param {*}      root0.dismissableCallback
 * @since 4.1
 */
const ShowMessage = ({
	text,
	error,
	delay = 4000,
	dismissable = false,
	dismissableCallback,
}) => {
	const [visible, setVisible] = useState(true);
	const timerRef = useRef(null);

	const clearExistingTimer = () => {
		if (timerRef.current !== null) {
			clearTimeout(timerRef.current);
			timerRef.current = null;
		}
	};

	const startTimer = () => {
		clearExistingTimer();
		timerRef.current = setTimeout(() => {
			setVisible(false);
			timerRef.current = null;
			if (dismissableCallback) {
				dismissableCallback();
			}
		}, delay);
	};

	/* On mount, maybe set dismissable timer; clear timer on unmount */
	useEffect(() => {
		if (dismissable) {
			startTimer();
		}
		return () => {
			if (dismissable) {
				clearTimeout(timerRef.current);
			}
		};
	}, []); // eslint-disable-line react-hooks/exhaustive-deps

	/* When hidden and then re-rendered with new text, show again */
	useEffect(() => {
		if (!visible) {
			setVisible(true);
			if (dismissable) {
				startTimer();
			}
		}
	}, [text]); // eslint-disable-line react-hooks/exhaustive-deps

	const classes = 'notice inline' + (error ? ' error' : '');

	return visible ? (
		<div data-test="component-showMessage" className={classes}>
			<p>{text}</p>
		</div>
	) : (
		<div />
	);
};

ShowMessage.propTypes = {
	text: PropTypes.string.isRequired,
	error: PropTypes.bool,
	delay: PropTypes.number,
	dismissable: PropTypes.bool,
	dismissableCallback: PropTypes.func,
};

export { ShowMessage };
export default ShowMessage;
