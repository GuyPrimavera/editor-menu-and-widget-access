/* Progressive enhancement; every settings action works without JavaScript. */
(() => {
	'use strict';

	const root = document.querySelector('.emwa-settings');

	if (!root) return;

	const strings = window.emwaSettings || {};
	let locale = (document.documentElement.lang || 'en').replace(/_/g, '-');
	try { locale = Intl.getCanonicalLocales(locale)[0]; } catch (_) { locale = 'en'; }
	const numberFormat = new Intl.NumberFormat(locale);
	const fold = (value) => value.normalize('NFC').toLocaleLowerCase(locale);
	let dirty = false;
	let submitting = false;

	root.querySelectorAll('.emwa-edit-form').forEach((form) => {
		form.addEventListener('change', (event) => {
			if (!event.target.name || event.target.closest('.emwa-view-control')) return;
			dirty = true;
			const status = form.querySelector('.emwa-dirty');
			if (status) status.textContent = strings.unsaved || '';
		});
		form.addEventListener('input', (event) => { if (event.target.name && !event.target.closest('.emwa-view-control')) dirty = true; });
		form.addEventListener('submit', () => { submitting = true; });
	});

	window.addEventListener('beforeunload', (event) => {
		if (!dirty || submitting) return;
		event.preventDefault();
		event.returnValue = '';
	});

	root.querySelectorAll('[data-emwa-confirm]').forEach((button) => {
		button.addEventListener('click', (event) => {
			const message = button.dataset.emwaConfirm === 'reset' ? strings.confirmReset : button.dataset.emwaConfirm === 'permissions' ? strings.confirmPermissions : button.dataset.emwaConfirm === 'copy' ? strings.confirmCopy : strings.confirmRestore;
			if (!window.confirm(message)) event.preventDefault();
		});
	});

	const rows = Array.from(root.querySelectorAll('.emwa-rules tbody tr[data-emwa-group]'));
	const collapsed = new Set();
	const search = root.querySelector('#emwa-search');
	const showAll = root.querySelector('#emwa-show-all');
	const tools = root.querySelector('.emwa-tools');

	const refresh = () => {
		const query = fold((search ? search.value : '').trim());
		let count = 0;

		rows.forEach((row) => {
			const available = !showAll || showAll.checked || row.dataset.emwaRoleAvailable !== '0';
			const matches = !query || fold(row.dataset.emwaSearch).includes(query);
			const child = Number(row.dataset.emwaDepth) > 0;
			row.hidden = !available || !matches || (!query && child && collapsed.has(row.dataset.emwaGroup));
			if (!row.hidden) count += 1;
		});

		const status = root.querySelector('.emwa-search-status');

		if (status) {
			const counts = [numberFormat.format(count), numberFormat.format(rows.length)];
			status.textContent = (strings.itemCount || '%1$s / %2$s').replace(/%([12])\$s/g, (_, index) => counts[Number(index) - 1]);
		}

		root.querySelectorAll('[data-emwa-toggle]').forEach((button) => {
			const isCollapsed = collapsed.has(button.dataset.emwaToggle);
			button.hidden = !rows.some((row) => row.dataset.emwaGroup === button.dataset.emwaToggle && Number(row.dataset.emwaDepth) > 0 && (!showAll || showAll.checked || row.dataset.emwaRoleAvailable !== '0'));
			button.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
			button.textContent = isCollapsed ? strings.expand : strings.collapse;
		});
	};

	if (tools) tools.hidden = false;

	if (search) search.addEventListener('input', refresh);

	if (showAll) showAll.addEventListener('change', () => {
		const url = new URL(window.location.href);
		if (showAll.checked) url.searchParams.set('show_all', '1');
		else url.searchParams.delete('show_all');
		window.history.replaceState(null, '', url);
		refresh();
	});

	root.querySelectorAll('[data-emwa-bulk]').forEach((button) => {
		button.addEventListener('click', () => {
			rows.filter((row) => !row.hidden).forEach((row) => {
				const checkbox = row.querySelector('input[type=checkbox]');
				if (checkbox && !checkbox.disabled) {
					checkbox.checked = button.dataset.emwaBulk === 'hide';
					checkbox.dispatchEvent(new Event('change', { bubbles: true }));
				}
			});
		});
	});

	root.querySelectorAll('[data-emwa-toggle]').forEach((button) => {
		if (!rows.some((row) => row.dataset.emwaGroup === button.dataset.emwaToggle && Number(row.dataset.emwaDepth) > 0)) return;
		button.hidden = false;
		button.addEventListener('click', () => {
			const id = button.dataset.emwaToggle;
			if (collapsed.has(id)) collapsed.delete(id); else collapsed.add(id);
			refresh();
		});
	});

	root.querySelectorAll('[data-emwa-collapse]').forEach((button) => {
		button.addEventListener('click', () => {
			collapsed.clear();
			if (button.dataset.emwaCollapse === 'collapse') rows.forEach((row) => collapsed.add(row.dataset.emwaGroup));
			refresh();
		});
	});

	root.querySelectorAll('[data-emwa-copy]').forEach((button) => {
		button.hidden = false;
		button.addEventListener('click', async () => {
			const text = document.getElementById(button.dataset.emwaCopy);
			if (!text) return;
			try {
				await navigator.clipboard.writeText(text.value);
				const status = root.querySelector('.emwa-copy-status');
				if (status) status.textContent = strings.copied || '';
			} catch (_) {
				text.focus();
				text.select();
			}
		});
	});
	
	refresh();
})();
