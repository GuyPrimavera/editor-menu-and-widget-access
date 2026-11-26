/* Real Unicode search, translated number formatting and WordPress user-locale/RTL flows. */
const { chromium, expect } = require('@playwright/test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { execFileSync } = require('node:child_process');
const site = process.env.EMWA_TEST_PATH;
if (process.env.EMWA_TEST_SITE !== '1' || !site || !site.startsWith('/tmp/')) throw new Error('Use a disposable /tmp WordPress site and EMWA_TEST_SITE=1.');
const base = process.env.EMWA_TEST_URL || 'http://127.0.0.1:8999';
const username = process.env.EMWA_TEST_USER;
const password = process.env.EMWA_TEST_PASSWORD;
if (!username || !password) throw new Error('Disposable admin credentials required.');
const wp = (...args) => execFileSync('wp', ['--path=' + site, ...args], { encoding: 'utf8' }).trim();
let originalLocale = '';
try { originalLocale = wp('user', 'meta', 'get', username, 'locale'); } catch (_) {}
let count = 0;
const pass = (message) => { ++count; console.log('PASS: ' + message); };
(async () => {
	const browser = await chromium.launch({ headless: true });
	try {
		const context = await browser.newContext({ viewport: { width: 1360, height: 1000 } });
		const page = await context.newPage();
		const errors = [];
		page.on('pageerror', error => errors.push(error.message));
		const sandbox = await context.newPage();
		for (const [locale, label, query] of [['fr-FR', 'Édition', 'édition'], ['de-DE', 'Überblick', 'ÜBERBLICK'], ['tr-TR', 'İşlemler', 'işlemler'], ['fr-FR', 'Édition', 'e\u0301dition']]) {
			await sandbox.setContent(`<html lang="${locale}"><body><div class="emwa-settings"><input id="emwa-search"><table class="emwa-rules"><tbody><tr data-emwa-group="x" data-emwa-search="${label}"><td>${label}</td></tr></tbody></table><span class="emwa-search-status"></span></div></body></html>`);
			await sandbox.addScriptTag({ path: path.join(__dirname, '../assets/admin/settings.js') });
			await sandbox.locator('#emwa-search').fill(query);
			await expect(sandbox.locator('tr')).toBeVisible();
			pass(`Unicode search matches ${locale} label with query ${query}`);
		}
		await sandbox.setContent('<html lang="ar-u-nu-arab" dir="rtl"><body><div class="emwa-settings"><input id="emwa-search"><table class="emwa-rules"><tbody>' + '<tr data-emwa-group="x" data-emwa-search="entry"><td>entry</td></tr>'.repeat(1000) + '</tbody></table><span class="emwa-search-status"></span></div></body></html>');
		await sandbox.addScriptTag({ path: path.join(__dirname, '../assets/admin/settings.js') });
		assert.equal(await sandbox.locator('.emwa-search-status').textContent(), '١٬٠٠٠ / ١٬٠٠٠');
		pass('Arabic item totals respect the requested native numeral system and thousands separators');
		await sandbox.setContent('<html lang="en"><body><div class="emwa-settings"><input id="emwa-search"><table class="emwa-rules"><tbody><tr data-emwa-group="a" data-emwa-search="apple"><td>apple</td></tr><tr data-emwa-group="b" data-emwa-search="banana"><td>banana</td></tr></tbody></table><span class="emwa-search-status"></span></div></body></html>');
		await sandbox.evaluate(() => { window.emwaSettings = { itemCount: '%2$s total; %1$s displayed' }; });
		await sandbox.addScriptTag({ path: path.join(__dirname, '../assets/admin/settings.js') });
		await sandbox.locator('#emwa-search').fill('apple');
		assert.equal(await sandbox.locator('.emwa-search-status').textContent(), '2 total; 1 displayed');
		pass('translators can reorder displayed and total count placeholders');
		await sandbox.close();
		await page.goto(base + '/wp-login.php');
		await page.locator('#user_login').fill(username);
		await page.locator('#user_pass').fill(password);
		await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.locator('#wp-submit').click()]);
		const settings = tab => base + '/wp-admin/options-general.php?page=editorMenuWidgetAccess&role=editor&tab=' + tab;
		for (const [locale, htmlLang, heading, query, label] of [['it_IT', 'it-IT', 'Accesso degli editor', 'édition', 'Édition'], ['tr_TR', 'tr', 'Editor Access', 'işlemler', 'İşlemler']]) {
			wp('user', 'meta', 'update', username, 'locale', locale);
			await page.goto(settings('menus'), { waitUntil: 'domcontentloaded' });
			await expect(page.locator('html')).toHaveAttribute('lang', htmlLang);
			await expect(page.locator('.emwa-settings h1')).toHaveText(heading);
			await page.locator('#emwa-search').fill(query);
			await expect(page.locator('.emwa-rules tbody tr:visible')).toHaveCount(1);
			await expect(page.locator('.emwa-rules tbody tr:visible')).toContainText(label);
			pass(`WordPress user locale ${locale} loads the expected plugin language and Unicode search behavior`);
		}
		wp('user', 'meta', 'update', username, 'locale', 'ar');
		await page.goto(settings('menus'), { waitUntil: 'domcontentloaded' });
		await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
		assert.equal(await page.locator('.emwa-settings').evaluate(e => getComputedStyle(e).direction), 'rtl');
		const child = page.locator('.emwa-depth-1').first();
		assert.equal(await child.evaluate(e => getComputedStyle(e).paddingRight), '28px');
		assert.equal(await page.locator('.emwa-route').first().evaluate(e => getComputedStyle(e).direction), 'ltr');
		assert.equal(await page.locator('.emwa-search-status').evaluate(e => getComputedStyle(e).direction), 'ltr');
		assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
		pass('Arabic RTL menus mirror indentation, isolate technical routes and fit the desktop viewport');
		const artifacts = path.join(__dirname, 'artifacts');
		fs.mkdirSync(artifacts, { recursive: true });
		await page.screenshot({ path: path.join(artifacts, 'rtl-menus.png'), fullPage: true });
		await page.goto(settings('roles'), { waitUntil: 'domcontentloaded' });
		assert.match(await page.locator('.emwa-role-card-active').evaluate(e => getComputedStyle(e).boxShadow), /-3px/);
		pass('active role highlighting follows the right edge in RTL');
		await page.goto(settings('help'), { waitUntil: 'domcontentloaded' });
		await expect(page.locator('#emwa-import')).toHaveAttribute('dir', 'ltr');
		await expect(page.locator('#emwa-diagnostics')).toHaveAttribute('dir', 'ltr');
		pass('Arabic Help keeps JSON and diagnostic data left-to-right');
		await page.setViewportSize({ width: 390, height: 844 });
		await page.goto(settings('menus'), { waitUntil: 'domcontentloaded' });
		assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
		await page.locator('#emwa-search').fill('إعدادات');
		await expect(page.locator('.emwa-rules tbody tr:visible')).toHaveCount(1);
		await page.screenshot({ path: path.join(artifacts, 'rtl-mobile.png'), fullPage: true });
		pass('Arabic menu search and the complete RTL layout work at a mobile viewport');
		assert.deepEqual(errors, []);
		pass('localized settings flows produce no JavaScript errors');
		console.log(`Completed ${count} localisation browser checks.`);
	} finally {
		if (originalLocale) wp('user', 'meta', 'update', username, 'locale', originalLocale);
		else wp('user', 'meta', 'delete', username, 'locale');
		await browser.close();
	}
})().catch(error => { console.error(error); process.exitCode = 1; });
