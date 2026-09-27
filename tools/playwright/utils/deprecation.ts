import type { Page } from '@playwright/test';
import type Pdf from '@self:playwright/utils/gravitypdf';
import { wpCli } from '@self:playwright/utils/wp-cli';
import { withConfirmation } from '@self:playwright/utils/gravityforms';

const FILTER_LISTENER_OPTION = 'gfpdf_e2e_deprecated_filter';

/*
 * Attach a third-party listener to a deprecated filter, one of the signals the System Report and Site Health
 * surfaces detect.
 *
 * A browser can't hook a filter on its own, so an mu-plugin adds one whenever this option is set. The other
 * signals are the templates below, and a legacy download URL on a form.
 */
const RECORD_DEPRECATED_USAGE = `wp option update ${FILTER_LISTENER_OPTION} 1`;
const CLEAR_DEPRECATED_USAGE = `wp option delete ${FILTER_LISTENER_OPTION}`;

const LEGACY_TEMPLATE_ID = 'e2e-legacy';
const LEGACY_TEMPLATE = `${LEGACY_TEMPLATE_ID}.php`;
const BUSINESS_PLUS_TEMPLATE = 'e2e-business-plus.php';

/*
 * Install one legacy template of each kind
 *
 * A v3 template is one carrying no file headers. What separates the two kinds is whether the file hands itself to
 * the Advanced Templating add-on, which only a Business Plus (Tier 2) template does.
 */
const INSTALL_LEGACY_TEMPLATES = `wp eval 'file_put_contents( GPDFAPI::get_data_class()->template_location . \\"${LEGACY_TEMPLATE}\\", \\"<?php // a plain v3 template\\" ); file_put_contents( GPDFAPI::get_data_class()->template_location . \\"${BUSINESS_PLUS_TEMPLATE}\\", \\"<?php gfpdfe_business_plus::initilise();\\" );'`;
const REMOVE_LEGACY_TEMPLATES = `wp eval '@unlink( GPDFAPI::get_data_class()->template_location . \\"${LEGACY_TEMPLATE}\\" ); @unlink( GPDFAPI::get_data_class()->template_location . \\"${BUSINESS_PLUS_TEMPLATE}\\" );'`;

const LEGACY_TEMPLATE_PDF = 'e2elegacypdf';

/*
 * Configure a PDF on the form that renders through the legacy template
 *
 * The reports name a legacy template by the forms it is configured on, which is what the PDF puts on the record.
 */
const addLegacyTemplatePdf = (formId: number) =>
	`wp eval 'GPDFAPI::add_pdf( ${formId}, [ \\"id\\" => \\"${LEGACY_TEMPLATE_PDF}\\", \\"name\\" => \\"Legacy Template\\", \\"filename\\" => \\"legacy\\", \\"template\\" => \\"${LEGACY_TEMPLATE_ID}\\" ] );'`;

/*
 * Take a detection now, which the admin notices then read
 *
 * The plugin takes one on a version change; running it here keeps that change, and the upgrade routines it sets off
 * on whichever admin page loads next, out of the specs running alongside.
 */
const RUN_DEPRECATED_DETECTION = `wp eval '\\GFPDF\\Statics\\Deprecation::refresh_signals();'`;

/*
 * Empty the record the notices read
 *
 * Cleanup can't wait for the next admin page load to re-detect: the notice renders from the stale record on
 * whichever page loads first, which for a full-page snapshot elsewhere in the suite means an unrelated baseline.
 */
const CLEAR_DEPRECATED_DETECTION = `wp eval 'GPDFAPI::get_options_class()->update_option( \\"deprecated_features\\", [] );'`;

/**
 * Plant every server-side signal the detection looks for, with the legacy template configured on `formId`, and detect
 * them
 * @param formId
 */
export function installDeprecatedUsage(formId: number) {
	wpCli(
		RECORD_DEPRECATED_USAGE,
		INSTALL_LEGACY_TEMPLATES,
		addLegacyTemplatePdf(formId),
		RUN_DEPRECATED_DETECTION
	);
}

/**
 * Take the site-wide ones away again and empty the detection record. The form's own go with the form.
 */
export function removeDeprecatedUsage() {
	wpCli(
		CLEAR_DEPRECATED_USAGE,
		REMOVE_LEGACY_TEMPLATES,
		CLEAR_DEPRECATED_DETECTION
	);
}

/**
 * Set the form's confirmation to one that hands out a legacy `?gf_pdf=1` download URL
 *
 * The detector searches the stored form for the URL rather than waiting for someone to follow one, so the
 * confirmation alone is enough to trip it.
 * @param pdf
 * @param formId
 */
export async function setLegacyDownloadUrl(pdf: Pdf, formId: number) {
	await pdf.updateForm(
		formId,
		withConfirmation({
			message: `<a href="/?gf_pdf=1&fid=${formId}&lid=1&template=zadani.php">Download PDF</a>`,
		})
	);
}

/**
 * Swap the detected form ID for a fixed one wherever the page renders it
 *
 * The ID belongs to a form the run creates, so it differs between environments and would churn the visual
 * baseline of every surface that names it. A constant keeps the layout identical too, which masking the region
 * alone would not — a second digit would still shift the text that follows.
 * @param page
 */
export async function maskFormIds(page: Page) {
	await page.evaluate(() => {
		const MASK = '0';

		// The system report links each ID to that form's PDF settings
		Array.from(
			document.querySelectorAll<HTMLAnchorElement>(
				'a[href*="subview=PDF&id="]'
			)
		).forEach((link) => {
			link.textContent = MASK;
		});

		// Site Health and the Info tab render the same list as plain text
		const walker = document.createTreeWalker(
			document.body,
			NodeFilter.SHOW_TEXT
		);
		const nodes: Text[] = [];

		while (walker.nextNode()) {
			nodes.push(walker.currentNode as Text);
		}

		nodes.forEach((node) => {
			if (node.nodeValue) {
				// Both the legacy download URLs and the legacy templates name the forms they were found on
				node.nodeValue = node.nodeValue.replace(
					/(form IDs? )[\d, ]+/g,
					`$1${MASK}`
				);
			}
		});
	});
}
