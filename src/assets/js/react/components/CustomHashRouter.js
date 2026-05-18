import React from 'react';
import PropTypes from 'prop-types';
import { createHashHistory } from 'history';
import { Router } from 'react-router-dom';

// Create a shared hash history instance
export const sharedHashHistory = createHashHistory({ window });

/**
 * @param {React.ReactNode} children
 *
 * @return {JSX.Element} CustomHashRouter component
 *
 * @since 6.12
 */
function CustomHashRouter({ children }) {
	const historyRef = React.useRef();
	if (historyRef.current === null) {
		historyRef.current = sharedHashHistory;
	}

	const history = historyRef.current ?? sharedHashHistory;
	const [state, setStateImpl] = React.useState({
		action: history.action,
		location: history.location,
	});

	const setState = React.useCallback(
		(newState) => setStateImpl(newState),
		[setStateImpl]
	);

	React.useLayoutEffect(() => history.listen(setState), [history, setState]);

	return (
		<Router
			basename="/"
			location={state.location}
			navigationType={state.action}
			navigator={history}
		>
			{children}
		</Router>
	);
}

CustomHashRouter.propTypes = {
	children: PropTypes.node.isRequired,
};

export default CustomHashRouter;
