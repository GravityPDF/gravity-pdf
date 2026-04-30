import util from 'util';
import { setAutoFreeze } from 'immer';
import '@testing-library/jest-dom';
// @ts-ignore no type declarations for css.escape
import CSS from 'css.escape'; // eslint-disable-line
import type { GFPDFGlobal } from '../../src/assets/js/react/types/global';

Object.defineProperty(global, 'TextEncoder', {
	value: util.TextEncoder,
});

/* Stub window.matchMedia because @wordpress/components Modal (used by
   ConfirmDialog) queries it on mount, and jsdom doesn't provide it. */
Object.defineProperty(window, 'matchMedia', {
	writable: true,
	value: jest.fn().mockImplementation((query: string) => ({
		matches: false,
		media: query,
		onchange: null,
		addListener: jest.fn(),
		removeListener: jest.fn(),
		addEventListener: jest.fn(),
		removeEventListener: jest.fn(),
		dispatchEvent: jest.fn(),
	})),
});

/* jsdom doesn't implement URL.createObjectURL/revokeObjectURL — the
   FontPreview component uses them to build @font-face src for unsaved
   draft variants. */
if (typeof URL.createObjectURL === 'undefined') {
	(
		URL as unknown as { createObjectURL: (b: Blob) => string }
	).createObjectURL = () => 'blob:mock';
	(
		URL as unknown as { revokeObjectURL: (u: string) => void }
	).revokeObjectURL = () => undefined;
}

/* jsdom doesn't implement Element.scrollIntoView. FontList scrolls the
   active descendant into view when the list selection changes. */
if (typeof Element.prototype.scrollIntoView === 'undefined') {
	Element.prototype.scrollIntoView = () => undefined;
}

/* Provide a no-op fetch so generator actions don't crash when jsdom
   doesn't include a native fetch implementation. Individual tests that
   need specific API responses should mock their API modules directly. */
if (typeof window.fetch === 'undefined') {
	window.fetch = jest.fn().mockResolvedValue({
		ok: true,
		status: 200,
		text: async () => '[]',
		json: async () => [],
	} as unknown as Response);
}

/* Prevent immer from freezing state objects, which would break tests that
   mutate initialState directly before passing it to reducers. */
setAutoFreeze(false);

// Runtime config expected by store initialisation and bootstrap code.
// Translated UI strings are no longer mocked here — components now call
// __() from @wordpress/i18n which returns the English string in the test
// environment.
window.GFPDF = {
	templateList: [{ id: 'zadani' }, { id: 'rubix' }, { id: 'focus-gravity' }],
	activeTemplate: '',
	userCapabilities: { administrator: true },
} as unknown as GFPDFGlobal;

(
	window as unknown as { gfpdf_migration_multisite_ids: unknown[] }
).gfpdf_migration_multisite_ids = [];
