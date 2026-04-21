/* Dependencies */
import { createSelector } from 'reselect';
/* Utilities */
import versionCompare from '../utilities/versionCompare';
/* Types */
import { TemplateItem, TemplateState } from '../types';

/**
 * Uses the Redux Reselect library to sort, filter and search our templates.
 * It also checks if the PDF templates are compatible with the current version of Gravity PDF
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/* Assign specific parts of the Redux store to constants (note, we are returning functions) */
const getTemplates = (state: { template: TemplateState }) =>
	state.template.list;
const getSearch = (state: { template: TemplateState }) => state.template.search;
const getActiveTemplate = (state: { template: TemplateState }) =>
	state.template.activeTemplate;

export const searchTemplates = (
	term: string,
	templates: TemplateItem[]
): TemplateItem[] => {
	/*
	 * Escape the term string for RegExp meta characters
	 * Consider spaces as word delimiters and match the whole string
	 */

	/* eslint-disable */
	term = term.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
	term = term.replace(/ /g, ')(?=.*');

	const match = new RegExp('^(?=.*' + term + ').+', 'i');
	/* eslint-enable */

	/* Filter through the templates. Any templates return "true" in out match.test() statement will be included */
	const results = templates.filter((template) => {
		/* Do very basic HTML tag removal from the fields we are interested in */
		const name = template.template.replace(/(<([^>]+)>)/gi, '');
		const description = template.description.replace(/(<([^>]+)>)/gi, '');
		const author = template.author.replace(/(<([^>]+)>)/gi, '');
		const group = template.group.replace(/(<([^>]+)>)/gi, '');

		/* Check if our matching term(s) are found in the string */
		return match.test(
			[name, template.id, group, description, author].toString()
		);
	});

	return results;
};

export const sortTemplates = (
	templates: TemplateItem[],
	activeTemplate: string
): TemplateItem[] => {
	/* Sort out template list using our comparator function */
	return templates.sort((a, b) => {
		/* Shift new templates to the bottom (only on install) */
		if (a.new === true && b.new === true) {
			return 0; // equal
		}

		if (a.new === true) {
			return 1;
		}

		if (b.new === true) {
			return -1;
		}

		/* Hoist the active template above the rest */
		if (activeTemplate === a.id) {
			return -1;
		}

		if (activeTemplate === b.id) {
			return 1;
		}

		/* Order alphabetically by the group name */
		if (a.group < b.group) {
			return -1; // before
		}

		if (a.group > b.group) {
			return 1; // after
		}

		/* Then order alphabetically by the template name */
		if (a.template < b.template) {
			return -1; // before
		}

		if (a.template > b.template) {
			return 1; // after
		}

		return 0; // equal
	});
};

export const addCompatibilityCheck = (
	templates: TemplateItem[]
): TemplateItem[] => {
	/* Apply this function to all templates */
	return templates.map((template) => {
		/* Get the PDF version and check it against the Gravity PDF version */
		const requiredVersion = template.required_pdf_version;
		if (versionCompare(requiredVersion, GFPDF.currentVersion, '>')) {
			/* Not compatible, so let's mark it */
			return {
				...template,
				compatible: false,
				error: GFPDF.requiresGravityPdfVersion.replace(
					/%s/g,
					requiredVersion
				),
				long_error:
					GFPDF.templateNotCompatibleWithGravityPdfVersion.replace(
						/%s/g,
						requiredVersion
					),
			};
		}
		/* If versionCompare() passed we'll mark as true */
		return { ...template, compatible: true };
	});
};

export default createSelector(
	[getTemplates, getSearch, getActiveTemplate],
	(templates, search, activeTemplate) => {
		templates = addCompatibilityCheck(templates);

		if (search) {
			templates = searchTemplates(search, templates);
		}

		return sortTemplates(templates, activeTemplate);
	}
);
