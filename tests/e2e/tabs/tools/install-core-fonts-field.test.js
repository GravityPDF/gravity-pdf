import { RequestMock } from 'testcafe';
import { baseURL } from '../../auth';
import { fieldHeaderTitle } from '../../utilities/page-model/helpers/field';
import Tools from '../../utilities/page-model/tabs/tools';

const mockSuccess = RequestMock()
	.onRequestTo( `${ baseURL }/wp-admin/admin-ajax.php` )
	.respond( {}, 200, { 'access-Control-Allow-Origin': '*' } );

fixture`Tools tab - Install core fonts field test`.requestHooks( mockSuccess );

test( "should display 'Install Core Fonts' field", async ( t ) => {
	const run = new Tools();
	// Actions
	await run.navigateToToolsTab();

	// Assertions
	await t
		.expect( fieldHeaderTitle( 'Install Core Fonts' ).exists )
		.ok()
		.expect( run.downloadCoreFontsButton.exists )
		.ok();
} );

test( 'should return download core fonts successful response', async ( t ) => {
	const run = new Tools();
	// Actions
	await run.navigateToToolsTab();
	await t.click( run.downloadCoreFontsButton );

	// Assertions
	await t
		.expect( run.downloadSuccess.exists )
		.ok()
		.expect( run.allSuccessfullyInstalled.exists )
		.ok();
} );

const mockGithubError = RequestMock()
	.onRequestTo(
		`${ baseURL }/wp-content/plugins/gravity-pdf/build/payload/core-fonts.json`
	)
	.respond( { ok: false }, 500, {
		'Access-Control-Allow-Origin': '*',
	} );

fixture`Tools tab - Install core fonts field test failure`.requestHooks(
	mockGithubError
);

test( 'should return download core fonts error/failed response', async ( t ) => {
	const run = new Tools();
	// Actions
	await run.navigateToToolsTab();
	await t.click( run.downloadCoreFontsButton );

	// Assertions
	await t.expect( run.downloadFailed.exists ).ok();
} );
