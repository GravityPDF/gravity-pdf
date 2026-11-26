import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf, { expectValidPdf } from '@self:playwright/utils/gravitypdf';
import type { CapturedMail } from '@self:playwright/utils/gravityforms';
import { withNotification } from '@self:playwright/utils/gravityforms';

/* Attached to the `e2e` notification `withNotification()` adds, which must be saved first: the REST API checks it */
const attach = { notification: ['e2e'] };

/* The PDF a URL points at, whether the site uses pretty or plain permalinks */
function pdfTarget(url: string) {
	const { pathname, searchParams } = new URL(
		url.replace(/&(#038|amp);/g, '&')
	);
	const [, pid, lid] = pathname.match(/\/pdf\/([^/]+)\/(\d+)/) ?? [];

	return {
		pid: pid ?? searchParams.get('pid'),
		lid: Number(lid ?? searchParams.get('lid')),
		expires: searchParams.get('expires'),
		signature: searchParams.get('signature'),
	};
}

function expectPdfAttachments(mail: CapturedMail, filenames: string[]) {
	expect(
		mail.attachments.map(({ path }) => path.split('/').pop()).sort()
	).toEqual(filenames.sort());

	for (const attachment of mail.attachments) {
		expect(attachment.exists).toBe(true);

		const pdf = Buffer.from(attachment.content, 'base64').toString(
			'latin1'
		);
		expect(pdf.length).toBe(attachment.size);
		expectValidPdf(pdf);
	}
}

test.describe('PDF notifications', () => {
	let pdf: Pdf;
	let form: any;

	test.beforeEach(
		async ({
			requestUtils,
			page,
			admin,
		}: {
			requestUtils: RequestUtils;
			page: Page;
			admin: Admin;
		}) => {
			pdf = new Pdf(requestUtils, admin, page);
			form = await pdf.createForm('PDF Notifications');
		}
	);

	test('links to the PDF with a merge tag and the shortcode', async () => {
		const pdfId = await pdf.addPdf(form.id, 'Notification Link');
		const shortcode = (attributes = '') =>
			`[gravitypdf id="${pdfId}" entry="{entry_id}" raw="1"${attributes}]`;
		await pdf.updateForm(
			form.id,
			withNotification(
				[
					`{PDF:pdf:${pdfId}}`,
					`{PDF:pdf:${pdfId}:signed}`,
					shortcode(),
					shortcode(' signed="1"'),
				]
					.map((tag) => `<code>${tag}</code>`)
					.join('')
			)
		);
		const entry = await pdf.createEntry({ form_id: form.id });

		const [[mail]] = pdf.captureNotifications(form.id, entry.id);
		const urls = [...mail.message.matchAll(/<code>(.+?)<\/code>/g)].map(
			([, url]) => pdfTarget(url)
		);

		const unsigned = {
			pid: pdfId,
			lid: Number(entry.id),
			expires: null,
			signature: null,
		};
		const signed = {
			...unsigned,
			expires: expect.stringMatching(/^\d+$/),
			signature: expect.stringMatching(/.+/),
		};

		expect(mail.attachments).toEqual([]);
		expect(urls).toEqual([unsigned, signed, unsigned, signed]);
	});

	test('attaches a PDF', async () => {
		await pdf.updateForm(form.id, withNotification('Single'));
		await pdf.addPdf(form.id, 'Single Attachment', attach);
		const entry = await pdf.createEntry({ form_id: form.id });

		const [mails] = pdf.captureNotifications(form.id, entry.id);

		expect(mails).toHaveLength(1);
		expectPdfAttachments(mails[0], ['Single Attachment.pdf']);
	});

	test('attaches multiple PDFs', async () => {
		await pdf.updateForm(form.id, withNotification('Multiple'));
		await pdf.addPdf(form.id, 'Attachment One', attach);
		await pdf.addPdf(form.id, 'Attachment Two', attach);
		await pdf.addPdf(form.id, 'Not Attached');
		const entry = await pdf.createEntry({ form_id: form.id });

		const [[mail]] = pdf.captureNotifications(form.id, entry.id);

		expectPdfAttachments(mail, [
			'Attachment One.pdf',
			'Attachment Two.pdf',
		]);
	});

	test('attaches a conditional PDF only when its logic matches', async () => {
		await pdf.updateForm(form.id, withNotification('Conditional'));
		await pdf.addPdf(form.id, 'Always Attached', attach);
		await pdf.addPdf(form.id, 'Conditional Attachment', {
			...attach,
			conditional: true,
			conditionalLogic: {
				actionType: 'show',
				logicType: 'all',
				rules: [
					{ fieldId: '1', operator: 'is', value: 'First Choice' },
				],
			},
		});
		const matching = await pdf.createEntry({
			form_id: form.id,
			1: 'First Choice',
		});
		const other = await pdf.createEntry({
			form_id: form.id,
			1: 'Second Choice',
		});

		const [[matched], [unmatched]] = pdf.captureNotifications(
			form.id,
			matching.id,
			other.id
		);

		expectPdfAttachments(matched, [
			'Always Attached.pdf',
			'Conditional Attachment.pdf',
		]);
		expectPdfAttachments(unmatched, ['Always Attached.pdf']);
	});
});
