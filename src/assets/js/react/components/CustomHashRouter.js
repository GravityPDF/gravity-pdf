import React from 'react';
import PropTypes from 'prop-types';
import { createHashHistory } from 'history';
import { Router } from 'react-router-dom';

// Module-level singleton so the independent React roots (template, font manager, core fonts) share one hash history
export const sharedHashHistory = createHashHistory({ window });

/**
 * @param {React.ReactNode} children
 *
 * @return {JSX.Element} CustomHashRouter component
 *
 * @since 6.12
 */
function CustomHashRouter({ children }) {
	const [state, setState] = React.useState({
		action: sharedHashHistory.action,
		location: sharedHashHistory.location,
	});

	React.useLayoutEffect(() => sharedHashHistory.listen(setState), []);

	return (
		<Router
			basename="/"
			location={state.location}
			navigationType={state.action}
			navigator={sharedHashHistory}
		>
			{children}
		</Router>
	);
}

CustomHashRouter.propTypes = {
	children: PropTypes.node.isRequired,
};

export default CustomHashRouter;
