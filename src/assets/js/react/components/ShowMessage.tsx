/* Dependencies */
import { useState, useEffect, useRef } from '@wordpress/element';

/**
 * Renders a message or error, with the option to self-clear itself
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

interface Props {
	text: string;
	error?: boolean;
	delay?: number;
	dismissable?: boolean;
	dismissableCallback?: () => void;
}

const ShowMessage = ({
	text,
	error,
	delay = 4000,
	dismissable = false,
	dismissableCallback,
}: Props) => {
	const [visible, setVisible] = useState(true);
	const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

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
				clearTimeout(timerRef.current ?? undefined);
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

export { ShowMessage };
export default ShowMessage;
