import * as React from '@wordpress/element';
import type { ReactElement, ReactNode } from 'react';
import { render, RenderOptions } from '@testing-library/react';
import { createRegistry, RegistryProvider } from '@wordpress/data';
import { MemoryRouter } from 'react-router-dom';

import {
	createTemplateStore,
	TEMPLATE_STORE_NAME,
} from '../../../src/assets/js/react/store/templateStore';
import {
	createCoreFontsStore,
	CORE_FONTS_STORE_NAME,
} from '../../../src/assets/js/react/store/coreFontsStore';
import {
	createFontManagerStore,
	FONT_MANAGER_STORE_NAME,
} from '../../../src/assets/js/react/store/fontManagerStore';
import type {
	TemplateState,
	CoreFontState,
	FontManagerState,
} from '../../../src/assets/js/react/types/index';

export {
	screen,
	fireEvent,
	waitFor,
	within,
	act,
} from '@testing-library/react';
export { userEvent } from '@testing-library/user-event';

type Registry = ReturnType<typeof createRegistry>;

export type TestInitialState = {
	template?: Partial<TemplateState>;
	coreFonts?: Partial<CoreFontState>;
	fontManager?: Partial<FontManagerState>;
};

export type TestStore = {
	dispatch: (action: unknown) => unknown;
	registry: Registry;
	getState: () => {
		template: TemplateState;
		coreFonts: CoreFontState;
		fontManager: FontManagerState;
	};
};

function isGenerator(obj: unknown): boolean {
	if (!obj || typeof obj !== 'object') {
		return false;
	}
	const o = obj as Record<PropertyKey, unknown>;
	return (
		typeof o[Symbol.iterator] === 'function' && typeof o.next === 'function'
	);
}

/**
 * Creates an isolated @wordpress/data registry with all three stores registered.
 *
 * Dispatch routing:
 * - Plain actions (from tests or components) broadcast to all stores' original
 *   middleware chains so any reducer can handle them.
 * - Generator actions (from components) are recorded by the spy and then routed
 *   exclusively through the owning store's original middleware chain (so the
 *   redux-routine middleware processes them exactly once).
 *
 * jest.spyOn(store, 'dispatch') intercepts all dispatches because both the
 * patched rawStore.dispatch and test-direct calls go through testStore.dispatch
 * via property lookup.
 * @param initialState
 */
export function createTestStore(
	initialState: TestInitialState = {}
): TestStore {
	const registry = createRegistry();

	registry.register(createTemplateStore(initialState.template));
	registry.register(createCoreFontsStore(initialState.coreFonts));
	registry.register(createFontManagerStore(initialState.fontManager));

	const storeNames = [
		TEMPLATE_STORE_NAME,
		CORE_FONTS_STORE_NAME,
		FONT_MANAGER_STORE_NAME,
	] as const;

	/* Capture original (pre-patch) dispatches for each store */
	const originalDispatches = {} as Record<
		string,
		(action: unknown) => unknown
	>;
	for (const name of storeNames) {
		/* eslint-disable-next-line @typescript-eslint/no-explicit-any */
		const rawStore = (registry as any).stores?.[name]?.store;
		if (rawStore) {
			originalDispatches[name] = rawStore.dispatch.bind(rawStore) as (
				action: unknown
			) => unknown;
		}
	}

	/* testStore.dispatch is the spy interception point.
	   Plain actions are broadcast to all stores so direct test dispatches
	   update state. Generators are NOT broadcast — they are handled by the
	   store-specific originalDispatch via the patch below. */
	const testStore: TestStore = {
		dispatch: (action: unknown) => {
			if (!isGenerator(action) && typeof action !== 'function') {
				for (const name of storeNames) {
					originalDispatches[name]?.(action);
				}
			}
		},
		registry,
		/* getState() reads the current state from each store's raw Redux store.
		   @wordpress/data overrides store.getState() to return just the reducer's
		   state (the `.root` slice), so no further unwrapping is needed. */
		getState: () => {
			/* eslint-disable-next-line @typescript-eslint/no-explicit-any */
			const getRaw = (name: string) =>
				(registry as any).stores?.[name]?.store?.getState() ?? {};
			return {
				template: getRaw(TEMPLATE_STORE_NAME) as TemplateState,
				coreFonts: getRaw(CORE_FONTS_STORE_NAME) as CoreFontState,
				fontManager: getRaw(
					FONT_MANAGER_STORE_NAME
				) as FontManagerState,
			};
		},
	};

	/* Patch each store's rawStore.dispatch to call testStore.dispatch (property
	   lookup, not closure), so jest.spyOn replacement is picked up at call time.
	   Generators additionally need to go through the owning store's original
	   middleware chain so redux-routine processes them. */
	for (const name of storeNames) {
		/* eslint-disable-next-line @typescript-eslint/no-explicit-any */
		const rawStore = (registry as any).stores?.[name]?.store;
		if (rawStore) {
			rawStore.dispatch = (action: unknown) => {
				/* Property lookup: picks up spy replacement at call time */
				testStore.dispatch(action);
				if (isGenerator(action) || typeof action === 'function') {
					return originalDispatches[name]?.(action);
				}
				return action;
			};
		}
	}

	return testStore;
}

export function renderWithStore(
	ui: ReactElement,
	initialState: TestInitialState = {},
	renderOptions: Omit<RenderOptions, 'wrapper'> = {},
	passedStore?: TestStore
): ReturnType<typeof render> & { store: TestStore } {
	const store =
		passedStore !== undefined ? passedStore : createTestStore(initialState);
	const { registry } = store;

	const Wrapper = ({ children }: { children: ReactNode }) => (
		<RegistryProvider value={registry}>{children}</RegistryProvider>
	);

	return {
		...render(ui, { wrapper: Wrapper, ...renderOptions }),
		store,
	};
}

export function renderWithRouter(
	ui: ReactElement,
	{
		route = '/',
		initialState = {},
		store: passedStore,
		renderOptions = {},
	}: {
		route?: string;
		initialState?: TestInitialState;
		store?: TestStore;
		renderOptions?: Omit<RenderOptions, 'wrapper'>;
	} = {}
): ReturnType<typeof render> & { store: TestStore } {
	const store =
		passedStore !== undefined ? passedStore : createTestStore(initialState);
	const { registry } = store;

	const Wrapper = ({ children }: { children: ReactNode }) => (
		<RegistryProvider value={registry}>
			<MemoryRouter
				initialEntries={[route]}
			>
				{children}
			</MemoryRouter>
		</RegistryProvider>
	);

	return {
		...render(ui, { wrapper: Wrapper, ...renderOptions }),
		store,
	};
}

export function findByTestAttr(
	container: HTMLElement,
	val: string
): HTMLElement | null {
	return container.querySelector(`[data-test="${val}"]`);
}
