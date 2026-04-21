import * as React from '@wordpress/element';
import { useLocation, useNavigate, useParams } from 'react-router-dom';

// Higher Order Component
const withRouterHooks = <P extends object>(
	WrappedComponent: React.ComponentType<P>
) => {
	return (
		props: Omit<P, 'navigate' | 'location' | 'pathname' | 'params'>
	) => {
		const navigate = useNavigate();
		const location = useLocation();
		const { pathname } = location;
		const params = useParams();

		return (
			<WrappedComponent
				{...(props as P)}
				navigate={navigate}
				location={location}
				pathname={pathname}
				params={params}
			/>
		);
	};
};

export default withRouterHooks;
