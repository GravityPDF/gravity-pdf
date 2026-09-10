/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __ } from '@wordpress/i18n';

/**
 * The attribute that marks an optgroup as ours
 *
 * 6.x matched the User-Defined Fonts group by its translated label, which broke the moment an add-on injected
 * a group of its own — and quietly dropped that group on the next rebuild. The attribute says who owns each
 * group, so a group without it is left exactly where it was.
 *
 * @since 7.0
 */
export const GROUP_ATTRIBUTE = 'data-gfpdf-font-group';

/**
 * The value that opens the Font Manager instead of becoming the font
 *
 * @since 7.0
 */
export const INSTALL_SENTINEL = 'gfpdf-install-fonts';

/**
 * Rebuild every Gravity PDF optgroup of one select from the font list
 *
 * @param {HTMLSelectElement} select
 * @param {Object}            fonts  The `GET /fonts/` payload
 *
 * @since 7.0
 */
export function syncSelect(select, fonts) {
	if (!select || !fonts) {
		return;
	}

	const chosen = select.value;

	select
		.querySelectorAll(`optgroup[${GROUP_ATTRIBUTE}]`)
		.forEach((group) => group.remove());

	groupsOf(fonts)
		.filter((group) => group.fonts.length > 0)
		.reverse()
		.forEach((group) => {
			select.insertBefore(optgroup(group), select.firstChild);
		});

	/* A saved font that is no longer installed keeps its place rather than silently re-pointing */
	if (chosen && !hasValue(select, chosen)) {
		select.insertBefore(notInstalled(chosen), select.firstChild);
	}

	select.value = chosen;
}

/**
 * Add the option that opens the Font Manager at the packs view
 *
 * It is never the select's value: choosing it puts the previous one back and opens the modal. That is the
 * discoverability path for every pack, so it lives on the dropdown rather than only inside the manager.
 *
 * @param {HTMLSelectElement} select
 * @param {Function}          onOpen
 * @param {Function}          previous The font the select held before the sentinel was chosen
 *
 * @since 7.0
 */
export function addInstallSentinel(select, onOpen, previous) {
	if (!select || hasValue(select, INSTALL_SENTINEL)) {
		return;
	}

	const option = document.createElement('option');

	option.value = INSTALL_SENTINEL;
	option.text = __('Install new fonts…', 'gravity-pdf');
	option.setAttribute(GROUP_ATTRIBUTE, 'sentinel');

	select.appendChild(option);

	select.addEventListener('change', () => {
		if (select.value !== INSTALL_SENTINEL) {
			return;
		}

		select.value = previous();
		onOpen();
	});
}

/**
 * Point the select at one font, and tell the page it changed
 *
 * @param {HTMLSelectElement} select
 * @param {string}            id
 *
 * @since 7.0
 */
export function setSelectValue(select, id) {
	if (!select || !hasValue(select, id)) {
		return;
	}

	select.value = id;
	select.dispatchEvent(new Event('change', { bubbles: true }));
}

function groupsOf(fonts) {
	return [
		{
			id: 'bundled',
			label: __('Bundled', 'gravity-pdf'),
			fonts: fonts.bundled ?? [],
		},
		...(fonts.groups ?? []).map((group) => ({
			id: `${group.source}/${group.entry}`,
			label: group.label,
			fonts: group.fonts,
		})),
		{
			id: 'custom',
			label: __('User-Defined Fonts', 'gravity-pdf'),
			fonts: fonts.custom ?? [],
		},
	];
}

function optgroup(group) {
	const element = document.createElement('optgroup');

	element.setAttribute('label', group.label);
	element.setAttribute(GROUP_ATTRIBUTE, group.id);

	group.fonts.forEach((font) => {
		const option = document.createElement('option');

		option.value = font.id;
		option.text = font.enabled
			? font.label
			: /* translators: %s: a font name */
				`${font.label} ${__('(hidden on this site)', 'gravity-pdf')}`;

		element.appendChild(option);
	});

	return element;
}

function notInstalled(value) {
	const option = document.createElement('option');

	option.value = value;
	option.text = `${value} ${__('(not installed)', 'gravity-pdf')}`;
	option.setAttribute(GROUP_ATTRIBUTE, 'missing');

	return option;
}

function hasValue(select, value) {
	return [...select.options].some((option) => option.value === value);
}
