/* Dependencies */
import domReady from '@wordpress/dom-ready';
/* Store — register all @wordpress/data stores before any bootstrap runs */
import './store';
/* Bootstrap */
import { templateBootstrap } from './bootstrap/templateBootstrap';
import { fontManagerBootstrap } from './bootstrap/fontManagerBootstrap';
import coreFontBootstrap from './bootstrap/coreFontBootstrap';
import helpBootstrap from './bootstrap/helpBootstrap';
/* Utilities */
import { addEditButton } from './utilities/PdfSettings/addEditButton';
import shortcodeButton from './utilities/PdfList/shortcodeButton';
/* PostCSS Styling */
import '../../pcss/gfpdf-styles.pcss';

/**
 * JS Entry point for WebPack
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

declare let __webpack_public_path__: string;

/**
 * Our main entry point for our modern unit-tested JS
 * This file gets run through Webpack to built it into valid ES5
 *
 * As we convert more JS to ES6 we'll likely load it from this file (unless we decide to make each feature modular)
 *
 * @since 4.1
 */
domReady(() => {
	'use strict';

	__webpack_public_path__ = GFPDF.pluginUrl + 'build/assets/'; // eslint-disable-line

	/* Initialize the Fancy Template Picker */
	if (GFPDF.templateList !== undefined) {
		// To add to window
		if (!window.Promise) {
			window.Promise = Promise;
		}

		/* A given admin page renders only one of these selects at a time */
		const templateField = document.querySelector<HTMLSelectElement>(
			'#gfpdf_settings\\[template\\], #gfpdf_settings\\[default_template\\]'
		);

		if (templateField) {
			templateBootstrap(templateField);
		}
	}

	/* Initialize the Core Font downloader */
	if (document.getElementById('gfpdf-button-wrapper-install_core_fonts')) {
		coreFontBootstrap();
	}

	/* Initialize the Search Bar for Help Tab */
	if (document.getElementById('gpdf-search')) {
		helpBootstrap();
	}

	/* Bootstrap feature detection: these queries locate Gravity-Forms-rendered
	   settings fields on the page and decide which React feature to mount. */
	const fmGeneralSettingsTab = document.querySelector(
		'#gfpdf-settings-field-wrapper-default_font select'
	);
	const fmToolsTab = document.querySelector(
		'#gfpdf-settings-field-wrapper-manage_fonts'
	);
	const fmPdfSettings = document.querySelector(
		'#gfpdf-settings-field-wrapper-font select'
	);
	const pdfSettingsForm =
		document.querySelector<HTMLElement>('#gfpdf_pdf_form');
	const pdfSettingFieldSets = document.querySelectorAll<HTMLElement>(
		'fieldset.gform-settings-panel--full'
	);
	const gfPdfListForm = document.querySelector('form#gfpdf_list_form');

	/* Initialize font manager under general settings tab */
	if (fmGeneralSettingsTab !== null) {
		fontManagerBootstrap(fmGeneralSettingsTab);
	}

	/* Initialize font manager under tools tab  */
	if (fmToolsTab !== null) {
		fontManagerBootstrap(fmToolsTab);
	}

	/* Initialize font manager under PDF settings */
	if (fmPdfSettings !== null) {
		fontManagerBootstrap(fmPdfSettings);
	}

	/* Initialize additional add/update buttons on PDF setting panels */
	if (pdfSettingsForm && pdfSettingFieldSets.length === 4) {
		addEditButton(pdfSettingFieldSets, pdfSettingsForm);
	}

	/* Enable shortcode field click and auto select feature */
	if (gfPdfListForm !== null) {
		shortcodeButton();
	}
});
