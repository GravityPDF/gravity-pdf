import type { Page } from '@playwright/test';
import type Pdf from '@self:playwright/utils/gravitypdf';
import { wpCli } from '@self:playwright/utils/wp-cli';

const FILTER_LISTENER_OPTION = 'gfpdf_e2e_deprecated_filter';

/**
 * Attach a third-party listener to a deprecated filter, one of the signals the System Report and Site Health
 * surfaces detect.
 *
 * A browser can't hook a filter on its own, so an mu-plugin adds one whenever this option is set. The other
 * signals are the templates installLegacyTemplates() writes, and a legacy download URL on a form.
 */
export function recordDeprecatedUsage() {
	wpCli(`wp option update ${FILTER_LISTENER_OPTION} 1`);
}

/**
 * Stop recording the deprecated usage, so the rest of the suite runs against a site without it
 */
export function clearDeprecatedUsage() {
	wpCli(`wp option delete ${FILTER_LISTENER_OPTION}`);
}

const LEGACY_TEMPLATE_ID = 'e2e-legacy';
const LEGACY_TEMPLATE = `${LEGACY_TEMPLATE_ID}.php`;
const BUSINESS_PLUS_TEMPLATE = 'e2e-business-plus.php';

/**
 * Install one legacy template of each kind
 *
 * A v3 template is one carrying no file headers. What separates the two kinds is whether the file hands itself to
 * the Advanced Templating add-on, which only a Business Plus (Tier 2) template does.
 */
export function installLegacyTemplates() {
	wpCli(
		`wp eval 'file_put_contents( GPDFAPI::get_data_class()->template_location . \\"${LEGACY_TEMPLATE}\\", \\"<?php // a plain v3 template\\" ); file_put_contents( GPDFAPI::get_data_class()->template_location . \\"${BUSINESS_PLUS_TEMPLATE}\\", \\"<?php gfpdfe_business_plus::initilise();\\" );'`
	);
}

/**
 * Remove them again, so the rest of the suite doesn't see them in the template list
 */
export function removeLegacyTemplates() {
	wpCli(
		`wp eval '@unlink( GPDFAPI::get_data_class()->template_location . \\"${LEGACY_TEMPLATE}\\" ); @unlink( GPDFAPI::get_data_class()->template_location . \\"${BUSINESS_PLUS_TEMPLATE}\\" );'`
	);
}

const LEGACY_TEMPLATE_PDF = 'e2elegacypdf';

/**
 * Configure a PDF on the form that renders through the legacy template
 *
 * The reports name a legacy template by the forms it is configured on, which is what the PDF puts on the record.
 * @param formId
 */
export function useLegacyTemplateOnForm(formId: number) {
	wpCli(
		`wp eval 'GPDFAPI::add_pdf( ${formId}, [ \\"id\\" => \\"${LEGACY_TEMPLATE_PDF}\\", \\"name\\" => \\"Legacy Template\\", \\"filename\\" => \\"legacy\\", \\"template\\" => \\"${LEGACY_TEMPLATE_ID}\\" ] );'`
	);
}

/**
 * Take it off again, so the form is left as the rest of the suite expects to find it
 * @param formId
 */
export function removeLegacyTemplateFromForm(formId: number) {
	wpCli(
		`wp eval 'GPDFAPI::delete_pdf( ${formId}, \\"${LEGACY_TEMPLATE_PDF}\\" );'`
	);
}

/**
 * Roll the recorded plugin version back, so the next admin page load takes a fresh detection
 *
 * The admin notices read what the last detection recorded rather than detecting on every page load, and a release
 * is when that record is taken — `check_install_status()` runs on `wp_loaded`, ahead of the notices themselves. The
 * version has to differ from PDF_EXTENDED_VERSION for that to fire, so this is well below any release.
 */
export function refreshDeprecatedDetection() {
	wpCli('wp option update gfpdf_current_version 6.0.0');
}

/**
 * Empty the record the notices read
 *
 * Cleanup can't wait for the next admin page load to re-detect: the notice renders from the stale record on
 * whichever page loads first, which for a full-page snapshot elsewhere in the suite means an unrelated baseline.
 */
export function clearDeprecatedDetection() {
	wpCli(
		`wp eval 'GPDFAPI::get_options_class()->update_option( \\"deprecated_features\\", [] );'`
	);
}

/**
 * Set the form's confirmation to one that hands out a legacy `?gf_pdf=1` download URL, or a plain one that doesn't
 *
 * The detector searches the stored form for the URL rather than waiting for someone to follow one, so the
 * confirmation alone is enough to trip it.
 * @param pdf
 * @param formId
 * @param inUse
 */
export async function setLegacyDownloadUrl(
	pdf: Pdf,
	formId: number,
	inUse: boolean
) {
	const message = inUse
		? `<a href="/?gf_pdf=1&fid=${formId}&lid=1&template=zadani.php">Download PDF</a>`
		: 'Thanks for contacting us! We will get in touch with you shortly.';

	await pdf.updateForm(formId, {
		confirmations: [
			{
				id: 'bbb222bbb222b',
				name: 'Default Confirmation',
				type: 'message',
				message,
			},
		],
	});
}

/**
 * Swap the detected form ID for a fixed one wherever the page renders it
 *
 * The ID belongs to a form the run creates, so it differs between environments and would churn the Chromatic
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
