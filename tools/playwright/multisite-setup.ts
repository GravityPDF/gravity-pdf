import { test as setup } from '@playwright/test';
import { SUBSITE } from '@self:playwright/utils/multisite';
import { wpCli } from '@self:playwright/utils/wp-cli';

setup('setup', async ({ request }, testInfo) => {
	const storageStatePath = testInfo.project.metadata
		.storageStatePath as string;
	const url = `${testInfo.project.use.baseURL}/${SUBSITE}/`.replace(
		'http://',
		''
	);

	process.env.WP_BASE_URL = testInfo.project.use.baseURL as string;
	process.env.STORAGE_STATE_PATH = storageStatePath;

	const addUser = (name: string, role: string) =>
		`(wp user get ${name} --url=${url} --quiet || wp user create ${name} ${name}@example.com --role=${role} --user_pass=password --url=${url} --quiet)`;

	// Each command skips what an earlier local run already created
	wpCli(
		'wp plugin activate gravityforms gravity-pdf --network --quiet',
		`(wp site list --field=url | grep -q /${SUBSITE}/ || wp site create --slug=${SUBSITE} --title=Subsite --quiet)`,
		addUser('siteadmin', 'administrator'),
		addUser('siteeditor', 'editor')
	);

	const { RequestUtils } =
		await import('@wordpress/e2e-test-utils-playwright');

	// Authenticate and save the storageState to disk.
	await new RequestUtils(request, { storageStatePath }).setupRest();
});
