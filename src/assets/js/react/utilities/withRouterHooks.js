import React from 'react';
import { useLocation, useNavigate, useParams } from 'react-router-dom';

// Higher Order Component
const withRouterHooks = (WrappedComponent) => {
	return (props) => {
		const navigate = useNavigate();
		const location = useLocation();
		const { pathname } = location;
		const params = useParams();

		return (
			<WrappedComponent
				{...props}
				navigate={navigate}
				location={location}
				pathname={pathname}
				params={params}
			/>
		);
	};
};

export default withRouterHooks;
