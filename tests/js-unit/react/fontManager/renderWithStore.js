import React from 'react';
import { RegistryProvider, createRegistry } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { render } from '@testing-library/react';
import { store as fontStore } from '../../../../src/assets/js/react/fontManager/store';
import { enableMockApi } from '../../../../src/assets/js/react/fontManager/api';
import { resetState } from '../../../../src/assets/js/react/fontManager/api/mock/state';

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
