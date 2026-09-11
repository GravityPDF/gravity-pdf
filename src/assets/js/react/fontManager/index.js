/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __ } from '@wordpress/i18n';
import { createRoot } from '@wordpress/element';
import { dispatch, select, subscribe } from '@wordpress/data';
import { OVERLAY_ID, STORE_NAME } from './constants';
import { enablePendingRoutes } from './api';
import { registerFontStore } from './store';
import FontManager from './components/FontManager';
import ManageFontsButton from './components/ManageFontsButton';
import { paths } from './utils/paths';
import {
	addInstallSentinel,
	setSelectValue,
	syncSelect,
} from './utils/selectBox';
import './style.pcss';

/**
 * The Font Manager's entry point
 *
 * One modal per page however many anchors asked for it: the three places a font dropdown appears each get
 * their own button, and all of them route into the same overlay through the same hash.
 *
 * @since 7.0
 */

/* PHASE 6: `/fonts/settings` has no route yet, so the Language tab alone reads fixtures. See `./api`. */
enablePendingRoutes();

const ANCHORS = [
	'#gfpdf-settings-field-wrapper-default_font select',
	'#gfpdf-settings-field-wrapper-font select',
	'#gfpdf-settings-field-wrapper-manage_fonts',
];

/**
 * Mount the buttons, the overlay and the store
 *
 * @since 7.0
 */
export function bootstrap() {
	const anchors = ANCHORS.map((selector) =>
		document.querySelector(selector)
	).filter(Boolean);

	if (anchors.length === 0) {
		return;
	}

	registerFontStore();

	const fontSelect = anchors.find((anchor) => anchor.nodeName === 'SELECT');

	anchors.forEach((anchor) => mountButton(anchor));
	mountModal(fontSelect ?? null);

	if (!fontSelect) {
		return;
	}

	dispatch(STORE_NAME).setActiveFont(fontSelect.value);

	addInstallSentinel(fontSelect, () => {
		window.location.hash = '/fontmanager/browse';
	});

	watchFontList(fontSelect);
}

function mountButton(anchor) {
	const wrapper = document.createElement('span');

	wrapper.className = 'gfpdf-manage-fonts';

	/* Beside a dropdown, inside the Tools tab's own container */
	if (anchor.nodeName === 'SELECT') {
		anchor.parentNode.insertBefore(wrapper, anchor.nextSibling);
	} else {
		anchor.appendChild(wrapper);
	}

	createRoot(wrapper).render(
		<ManageFontsButton
			label={__('Advanced', 'gravity-pdf')}
			onClick={() => {
				window.location.hash = paths.home();
			}}
		/>
	);
}

function mountModal(fontSelect) {
	let overlay = document.getElementById(OVERLAY_ID);

	if (!overlay) {
		overlay = document.createElement('div');
		overlay.id = OVERLAY_ID;
		document.body.appendChild(overlay);
	}

	createRoot(overlay).render(
		<FontManager
			onActive={
				fontSelect ? (id) => setSelectValue(fontSelect, id) : null
			}
		/>
	);
}

/**
 * Keep the dropdown in step with the font list, without it having to ask
 *
 * @param {HTMLSelectElement} fontSelect
 *
 * @since 7.0
 */
function watchFontList(fontSelect) {
	let last = null;

	subscribe(() => {
		const fonts = select(STORE_NAME).getFonts();

		if (fonts && fonts !== last) {
			last = fonts;

			syncSelect(fontSelect, fonts);
		}
	}, STORE_NAME);
}

if (typeof document !== 'undefined') {
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bootstrap);
	} else {
		bootstrap();
	}
}
