import * as React from '@wordpress/element';
import { createHashHistory } from 'history';
import { Router } from 'react-router-dom';

// Create a shared hash history instance
export const sharedHashHistory = createHashHistory({ window });

/**
 * @since 6.12
 */
interface Props {
	children: React.ReactNode;
}

function CustomHashRouter({ children }: Props) {
	const historyRef = React.useRef<ReturnType<
		typeof createHashHistory
	> | null>(null);
	if (historyRef.current === null) {
		historyRef.current = sharedHashHistory;
	}

	const history = historyRef.current ?? sharedHashHistory;
	const [state, setStateImpl] = React.useState({
		action: history.action,
		location: history.location,
	});

	const setState = React.useCallback(
		(newState: typeof state) => setStateImpl(newState),
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

export default CustomHashRouter;
