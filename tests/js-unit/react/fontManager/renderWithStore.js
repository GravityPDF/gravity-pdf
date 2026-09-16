import React from 'react';
import { RegistryProvider, createRegistry } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import apiFetch from '@wordpress/api-fetch';
import { render, screen } from '@testing-library/react';
import { store as fontStore } from '../../../../src/assets/js/react/fontManager/store';
import { mockMiddleware } from './mock';
import { resetState } from './mock/state';

/*
 * Registered once for the whole suite: `apiFetch` has no way to remove a middleware, so re-registering per
 * render would stack a copy of the mocked server on every test.
 */
let registered = false;

function enableMockApi() {
	if (registered) {
		return;
	}

	apiFetch.use(mockMiddleware);
	registered = true;
}

/**
 * Render one panel against a registry of its own
 *
 * A registry per test rather than the global one, so a resolver that answered in the last test does not decide
 * what this one sees.
 *
 * @param {JSX.Element} ui              What to render
 * @param {Object}      options         Options
 * @param {string}      options.hash    The route to open on
 * @param {?Function}   options.prepare Arranges the mocked server before the first request
 *
 * @return {Object} The registry, and what `render()` returned
 */
export function renderWithStore(
	ui,
	{ hash = '#/fontmanager/', prepare = null } = {}
) {
	enableMockApi();
	resetState();

	/* After the reset, so a case can arrange the server before the first request reaches it */
	if (prepare) {
		prepare();
	}

	window.location.hash = hash;

	const registry = createRegistry();

	registry.register(fontStore);
	registry.register(noticesStore);

	return {
		registry,
		...render(<RegistryProvider value={registry}>{ui}</RegistryProvider>),
	};
}

/**
 * Open the sidebar's "+ Add new font" menu
 *
 * Shared because the toggle has no accessible name of its own, so reaching it means naming a class
 * `@wordpress/components` owns — which is worth having in one place rather than in every suite that opens it.
 * The `findByText` first is the readiness wait: the sidebar draws its groups once the font list resolves.
 *
 * @param {Object} user A `userEvent` session
 */
export async function openAddMenu(user) {
	await screen.findByText('Bundled');

	await user.click(
		document.querySelector('.components-dropdown-menu__toggle')
	);
}
