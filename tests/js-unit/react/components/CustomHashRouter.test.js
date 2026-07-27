import React, { act } from 'react';
import { mount } from 'enzyme';
import { Route, Routes } from 'react-router-dom';
import CustomHashRouter, {
	sharedHashHistory,
} from '../../../../src/assets/js/react/components/CustomHashRouter';

describe('Components - CustomHashRouter.js', () => {
	let wrapper;

	const renderRoutes = () =>
		mount(
			<CustomHashRouter>
				<Routes>
					<Route path="/template" element={<div>list</div>} />
					<Route path="/template/:id" element={<div>single</div>} />
					<Route path="*" element={<div>empty</div>} />
				</Routes>
			</CustomHashRouter>
		);

	// act() flushes the history subscription, update() re-reads the tree Enzyme cached at mount
	const navigate = (path) => {
		act(() => sharedHashHistory.push(path));
		wrapper.update();
	};

	// sharedHashHistory is a module singleton, so reset the location between tests
	beforeEach(() => {
		sharedHashHistory.replace('/template');
	});

	afterEach(() => {
		wrapper.unmount();
	});

	test('renders the route matching the current hash location', () => {
		wrapper = renderRoutes();

		expect(wrapper.text()).toBe('list');
	});

	test('renders the new route when the shared history navigates', () => {
		wrapper = renderRoutes();

		navigate('/template/zadani');

		expect(wrapper.text()).toBe('single');
	});

	test('falls back to the wildcard route when no path matches', () => {
		wrapper = renderRoutes();

		navigate('/not-a-route');

		expect(wrapper.text()).toBe('empty');
	});

	// Hash history is used specifically so routing never alters the URL the backend sees
	test('keeps navigation in the fragment, leaving the path untouched', () => {
		const { pathname, search } = window.location;

		wrapper = renderRoutes();
		navigate('/template/zadani');

		expect(wrapper.text()).toBe('single');
		expect(window.location.hash).toBe('#/template/zadani');
		expect(window.location.pathname).toBe(pathname);
		expect(window.location.search).toBe(search);
	});
});
