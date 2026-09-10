import util from 'util';
import Enzyme from 'enzyme';
import Adapter from '@cfaester/enzyme-adapter-react-18';
import CSS from 'css.escape'; // eslint-disable-line
import '@testing-library/jest-dom';

Object.defineProperty(global, 'TextEncoder', {
	value: util.TextEncoder,
});

// jsdom ships neither, and @wordpress/components reaches for both on mount
window.matchMedia =
	window.matchMedia ||
	((query) => ({
		matches: false,
		media: query,
		onchange: null,
		addListener: () => {},
		removeListener: () => {},
		addEventListener: () => {},
		removeEventListener: () => {},
		dispatchEvent: () => false,
	}));

global.ResizeObserver =
	global.ResizeObserver ||
	class {
		observe() {}
		unobserve() {}
		disconnect() {}
	};

Enzyme.configure({
	adapter: new Adapter(),
	disableLifecycleMethods: true,
});

// setup global defaults that our tests/legacy code expect is present
window.GFPDF = {
	templateList: [{ id: 'zadani' }, { id: 'rubix' }, { id: 'focus-gravity' }],
	activeTemplate: '',
	noResultText:
		"It doesn't look like there are any topics related to your issue.",
	getSearchResultError: 'An error occurred. Please try again',
	licenseDeactivationError:
		'An error occurred and your license key may not have been correctly deactivated. Login to your GravityPDF.com account and check if your site has been unlinked from the key.',
	userCapabilities: { administrator: true },
	manage: 'Advanced',
	closeDialog: 'Close dialog',
	searchBoxResetTitle: 'Clear search.',
};

window.gfpdf_migration_multisite_ids = [];
