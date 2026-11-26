/* Real Site Editor sidebar behavior on a disposable block-theme installation. */
const { chromium, expect } = require('@playwright/test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
if (process.env.EMWA_TEST_SITE !== '1') throw new Error('Use a disposable installation and EMWA_TEST_SITE=1.');
const base = process.env.EMWA_TEST_URL || 'http://127.0.0.1:8999';
const adminUser = process.env.EMWA_TEST_USER;
const adminPassword = process.env.EMWA_TEST_PASSWORD;
if (!adminUser || !adminPassword) throw new Error('Provide disposable administrator credentials.');
const go = (page, url) => page.goto(base + url, { waitUntil: 'domcontentloaded' });
const login = async (page, user, password) => {
	await go(page, '/wp-login.php');
	await page.locator('#user_login').fill(user);
	await page.locator('#user_pass').fill(password);
	await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.locator('#wp-submit').click()]);
};
const openEditor = async (page) => {
	await go(page, '/wp-admin/site-editor.php');
	await page.locator('.edit-site-sidebar-navigation-screen-main').waitFor();
	await page.evaluate(() => wp.data.dispatch('core/preferences').set('core/edit-site', 'welcomeGuide', false));
};
const setHidden = async (page, menus, patterns, identity = false, styles = false, templates = false) => {
	await go(page, '/wp-admin/options-general.php?page=editorMenuWidgetAccess&tab=menus&role=editor');
	await page.locator('input[name="hidden_appearance[]"][value="menus"]').setChecked(menus);
	await page.locator('input[name="hidden_appearance[]"][value="patterns"]').setChecked(patterns);
	for (const [area, hidden] of Object.entries({ identity, styles, templates })) {
		await page.locator(`input[name="hidden_appearance[]"][value="${area}"]`).setChecked(hidden);
	}
	await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.getByRole('button', { name: 'Save Changes', exact: true }).click()]);
	await expect(page.locator('.notice-success')).toBeVisible();
};
let checks = 0;
const pass = (message) => { ++checks; console.log('PASS: ' + message); };
(async () => {
	const browser = await chromium.launch({ headless: true });
	try {
		const adminContext = await browser.newContext();
		const editorContext = await browser.newContext();
		const admin = await adminContext.newPage();
		const editor = await editorContext.newPage();
		admin.setDefaultTimeout(60000);
		editor.setDefaultTimeout(60000);
		const errors = [];
		admin.on('pageerror', error => errors.push(error.message));
		editor.on('pageerror', error => errors.push(error.message));
		await login(admin, adminUser, adminPassword);
		if (process.env.EMWA_EXPECT_FRESH === '1') {
			await go(admin, '/wp-admin/options-general.php?page=editorMenuWidgetAccess&tab=menus&role=editor');
			for (const area of ['themes', 'customize', 'identity', 'styles', 'patterns', 'templates', 'fonts']) {
				await expect(admin.locator(`input[name="hidden_appearance[]"][value="${area}"]`)).toBeChecked();
			}
			for (const area of ['menus', 'widgets', 'site_editor']) {
				await expect(admin.locator(`input[name="hidden_appearance[]"][value="${area}"]`)).not.toBeChecked();
			}
			await login(editor, 'emwa-browser-editor', 'emwa-editor-test-only');
			await openEditor(editor);
			await expect(editor.locator('#navigation-navigation-item')).toBeVisible();
			await expect(editor.locator('#page-navigation-item')).toBeVisible();
			for (const id of ['identity', 'global-styles', 'patterns', 'template']) {
				await expect(editor.locator(`#${id}-navigation-item`)).toBeHidden();
			}
			pass('a genuine first activation shows Navigation and ordinary Pages while hiding additional design tools');
		}
		await go(admin, '/wp-admin/options-general.php?page=editorMenuWidgetAccess&tab=roles&role=editor');
		await admin.locator('input[name="selected_roles[]"][value="editor"]').check();
		await admin.locator('input[name="grant_appearance"]').check();
		await Promise.all([admin.waitForNavigation({ waitUntil: 'domcontentloaded' }), admin.getByRole('button', { name: 'Save Changes', exact: true }).click()]);
		await setHidden(admin, false, false);
		await login(editor, 'emwa-browser-editor', 'emwa-editor-test-only');
		await openEditor(editor);
		await expect(editor.locator('#navigation-navigation-item')).toBeVisible();
		await expect(editor.locator('#patterns-navigation-item')).toBeVisible();
		pass('unchecked settings leave both Site Editor links visible to Editor');
		await setHidden(admin, true, true);
		await openEditor(editor);
		assert.equal(await editor.locator('#navigation-navigation-item').count(), 1);
		assert.equal(await editor.locator('#patterns-navigation-item').count(), 1);
		await expect(editor.locator('#navigation-navigation-item')).toBeHidden();
		await expect(editor.locator('#patterns-navigation-item')).toBeHidden();
		await expect(editor.locator('#page-navigation-item')).toBeVisible();
		await expect(editor.locator('#template-navigation-item')).toBeVisible();
		pass('checked settings hide Navigation and Patterns while Pages and Templates remain visible');
		const accessible = await editor.locator('.edit-site-sidebar-navigation-screen-main').ariaSnapshot();
		assert.doesNotMatch(accessible, /link "(?:Navigation|Patterns)"/);
		pass('hidden links are removed from the accessible sidebar navigation');
		const origin = await editor.evaluate(() => performance.timeOrigin);
		await editor.locator('#template-navigation-item').click();
		await expect(editor).toHaveURL(/(?:p|path)=(?:%2F|\/)template/);
		await editor.goBack();
		await expect(editor.locator('#page-navigation-item')).toBeVisible();
		await expect(editor.locator('#navigation-navigation-item')).toBeHidden();
		await expect(editor.locator('#patterns-navigation-item')).toBeHidden();
		assert.equal(await editor.evaluate(() => performance.timeOrigin), origin);
		pass('visibility survives client-side navigation and sidebar remounting');
		await openEditor(admin);
		await expect(admin.locator('#navigation-navigation-item')).toBeVisible();
		await expect(admin.locator('#patterns-navigation-item')).toBeVisible();
		assert.equal(await admin.locator('#emwa-site-editor-css').count(), 0);
		pass('Administrator retains both links and receives no visibility stylesheet');
		await setHidden(admin, true, false);
		await openEditor(editor);
		await expect(editor.locator('#navigation-navigation-item')).toBeHidden();
		await expect(editor.locator('#patterns-navigation-item')).toBeVisible();
		pass('Navigation and Patterns visibility can be configured independently');
		await setHidden(admin, false, false, true, true, true);
		await openEditor(editor);
		await expect(editor.locator('#identity-navigation-item')).toBeHidden();
		await expect(editor.locator('#global-styles-navigation-item')).toBeHidden();
		await expect(editor.locator('#template-navigation-item')).toBeHidden();
		await expect(editor.locator('#navigation-navigation-item')).toBeVisible();
		await expect(editor.locator('#patterns-navigation-item')).toBeVisible();
		pass('Identity, Styles and Templates hide controls work without hiding Navigation or Patterns');
		await setHidden(admin, false, false, false, true, false);
		await openEditor(editor);
		await expect(editor.locator('#identity-navigation-item')).toBeVisible();
		await expect(editor.locator('#global-styles-navigation-item')).toBeHidden();
		await expect(editor.locator('#template-navigation-item')).toBeVisible();
		pass('Identity and Styles can be configured independently');
		await openEditor(admin);
		await expect(admin.locator('#identity-navigation-item')).toBeVisible();
		await expect(admin.locator('#global-styles-navigation-item')).toBeVisible();
		pass('Identity and Styles hiding leaves Administrator navigation unchanged');
		await setHidden(admin, true, true);
		const response = await go(editor, '/wp-admin/site-editor.php?p=%2Fnavigation');
		assert.equal(response.status(), 200);
		await expect(editor.locator('.edit-site-sidebar-navigation-screen__title').filter({ hasText: 'Navigation' }).first()).toBeVisible();
		pass('hiding links preserves direct access rather than adding permission restrictions');
		await go(editor, '/wp-admin/index.php');
		assert.equal(await editor.locator('#emwa-site-editor-css').count(), 0);
		await go(editor, '/');
		assert.equal(await editor.locator('#emwa-site-editor-css').count(), 0);
		pass('visibility stylesheet is limited to the Site Editor screen');
		await openEditor(editor);
		await editor.setViewportSize({ width: 1360, height: 1000 });
		const artifacts = path.join(__dirname, 'artifacts');
		fs.mkdirSync(artifacts, { recursive: true });
		await editor.screenshot({ path: path.join(artifacts, 'site-editor-visibility.png'), fullPage: true });
		assert.deepEqual(errors, []);
		pass('Site Editor and settings pages produce no JavaScript errors');
		console.log('Completed ' + checks + ' Site Editor browser checks.');
	} finally { await browser.close(); }
})().catch(error => { console.error(error); process.exit(1); });
