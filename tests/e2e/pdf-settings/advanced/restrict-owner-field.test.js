import { fieldLabel } from '../../utilities/page-model/helpers/field';
import Pdf from '../../utilities/page-model/helpers/pdf';

const run = new Pdf();

fixture`PDF advanced settings - Restrict owner field test`;

test("should display 'Restrict Owner' field", async (t) => {
	// Actions
	await run.navigatePdfSection(
		'gf_edit_forms&view=settings&subview=PDF&id=4'
	);

	// Assertions
	await t
		.expect(fieldLabel('Restrict Owner').exists)
		.ok()
		.expect(run.restrictOwnerCheckbox.exists)
		.ok();
});

test('should save toggled checkbox value', async (t) => {
	// Actions & Assertions
	await run.navigatePdfSection(
		'gf_edit_forms&view=settings&subview=PDF&id=4'
	);

	// ensure that the end scenario will make the restrict owner option not toggled
	if (await run.restrictOwnerCheckbox.checked) {
		await t.click(run.restrictOwnerCheckbox).click(run.saveSettings);
		await t.expect(await run.restrictOwnerCheckbox.checked).notOk();
	}

	// toggle on
	await t.click(run.restrictOwnerCheckbox).click(run.saveSettings);
	await t.expect(await run.restrictOwnerCheckbox.checked).ok();

	// toggle off
	await t.click(run.restrictOwnerCheckbox).click(run.saveSettings);
	await t.expect(await run.restrictOwnerCheckbox.checked).notOk();
});
