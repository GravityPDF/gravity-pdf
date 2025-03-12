import { Selector, RequestLogger } from 'testcafe';
import { baseURL } from '../auth';
import { link } from '../utilities/page-model/helpers/field';
import AdvancedCheck from '../utilities/page-model/helpers/advanced-check';
// new Page model. Might be used soon as default
import Page from '../models/Page';

let shortcodeHolder;
let downloadUrl;
const run = new AdvancedCheck();
const downloadLogger = RequestLogger( downloadUrl, {
	logResponseBody: true,
	logResponseHeaders: true,
} );

fixture`PDF shortcode - Confirmation Type TEXT, PAGE and REDIRECT text`;

test( 'should check shortcode confirmation type TEXT is working correctly', async ( t ) => {
	// Actions
	await run.copyDownloadShortcode(
		'gf_edit_forms&view=settings&subview=PDF&id=3'
	);
	shortcodeHolder = await run.shortcodeBox.getAttribute(
		'data-clipboard-text'
	);
	await run.navigateConfirmationSection(
		'gf_edit_forms&view=settings&subview=confirmation&id=3'
	);
	await t
		.click( run.confirmationTextCheckbox )
		.click( run.wysiwgEditorTextTab )
		.click( run.wysiwgEditor )
		.pressKey( 'ctrl+a' )
		.pressKey( 'backspace' )
		.typeText( run.wysiwgEditor, shortcodeHolder, { paste: true } )
		.click( run.saveConfirmationButton );

	const url = await run.previewLink.href;
	await t
		.navigateTo( url )
		.typeText( run.formInputField, 'test', { paste: true } )
		.click( run.submitButton );
	downloadUrl = await Selector( '.gravitypdf-download-link' ).getAttribute(
		'href'
	);

	downloadLogger.clear();
	await t
		.addRequestHooks( downloadLogger )
		.click( link( '.gform_confirmation_wrapper ', 'Download PDF' ) )
		.wait( 500 )
		.removeRequestHooks( downloadLogger );

	// Assertions
	await t
		.expect(
			downloadLogger.contains(
				( r ) =>
					r.response.headers[ 'content-disposition' ] ===
					'attachment; filename="Sample.pdf"'
			)
		)
		.ok()
		.expect(
			downloadLogger.contains(
				( r ) =>
					r.response.headers[ 'content-type' ] === 'application/pdf'
			)
		)
		.ok();
} );

test( 'should check if the shortcode confirmation type PAGE is working correctly', async ( t ) => {
	// Actions
	// copy pdf short code
	await run.copyDownloadShortcode(
		'gf_edit_forms&view=settings&subview=PDF&id=3'
	);
	shortcodeHolder = await run.shortcodeBox.getAttribute(
		'data-clipboard-text'
	);
	await t
		.setNativeDialogHandler( () => true )
		.navigateTo( `${ baseURL }/wp-admin/edit.php?post_type=page` );

	// Create a new page and insert a shortcode block
	const name = 'Shortcode confrimation type Page';
	const page = new Page( name );
	await page.add();
	await page.insertBlock( 'shortcode', {
		target: '.block-editor-plain-text',
		content: shortcodeHolder,
	} );
	await page.saveChanges();
	await page.publishChanges();

	// update form settings
	await run.navigateConfirmationSection(
		'gf_edit_forms&view=settings&subview=confirmation&id=3'
	);

	// get first child button
	const pageItem = Selector(
		'div[aria-labelledby="gform-page-label"].gform-dropdown__container'
	).find( 'button' );

	await t
		.click( run.confirmationPageCheckbox )
		.click( run.confirmationPageSelectBox )
		.click( pageItem )
		.click( run.queryStringInputBox )
		.pressKey( 'ctrl+a' )
		.pressKey( 'backspace' )
		.typeText( run.queryStringInputBox, 'entry={entry_id}', {
			paste: true,
		} )
		.click( run.saveConfirmationButton );

	const url = await run.previewLink.href;
	await t
		.navigateTo( url )
		.typeText( run.formInputField, 'test', { paste: true } )
		.click( run.submitButton );
	downloadUrl = await Selector( '.gravitypdf-download-link' ).getAttribute(
		'href'
	);

	downloadLogger.clear();
	await t
		.addRequestHooks( downloadLogger )
		.click( link( '.entry-content', 'Download PDF' ) )
		.wait( 500 )
		.removeRequestHooks( downloadLogger );

	// Assertions
	await t
		.expect(
			downloadLogger.contains(
				( r ) =>
					r.response.headers[ 'content-disposition' ] ===
					'attachment; filename="Sample.pdf"'
			)
		)
		.ok()
		.expect(
			downloadLogger.contains(
				( r ) =>
					r.response.headers[ 'content-type' ] === 'application/pdf'
			)
		)
		.ok();

	// Cleanup
	await page.delete();
} );

test( 'should check if the shortcode confirmation type REDIRECT download is working correctly', async ( t ) => {
	// Actions
	await run.copyDownloadShortcode(
		'gf_edit_forms&view=settings&subview=PDF&id=3'
	);
	shortcodeHolder = await run.shortcodeBox.getAttribute(
		'data-clipboard-text'
	);
	await run.navigateConfirmationSection(
		'gf_edit_forms&view=settings&subview=confirmation&id=3'
	);
	await t
		.click( run.confirmationRedirectCheckbox )
		.click( run.redirectInputBox )
		.pressKey( 'ctrl+a' )
		.pressKey( 'backspace' )
		.typeText( run.redirectInputBox, shortcodeHolder, { paste: true } )
		.click( run.saveConfirmationButton );

	const url = await run.previewLink.href;
	await t.navigateTo( url );

	downloadLogger.clear();
	downloadUrl = `${ baseURL }/?gf_page=preview&id=3`;
	await t
		.addRequestHooks( downloadLogger )
		.typeText( run.formInputField, 'test', { paste: true } )
		.click( run.submitButton );

	// Assertions
	await t
		.expect(
			downloadLogger.contains(
				( r ) =>
					r.response.headers[ 'content-disposition' ] ===
					'attachment; filename="Sample.pdf"'
			)
		)
		.ok()
		.expect(
			downloadLogger.contains(
				( r ) =>
					r.response.headers[ 'content-type' ] === 'application/pdf'
			)
		)
		.ok();
} );
