/* Read-only enhancement; native list and editor remain owned by WPCodeBox. */
(() => {
	'use strict';
	const config = window.WCBQNSearch;
	const nativeList = document.getElementById('root');
	const wrap = nativeList && nativeList.parentElement;
	const aside = wrap;
	if (!config || !wrap || document.getElementById('wcbqn-search')) return;
	config.folder = Number(config.folder) || 0;
	const panel = document.createElement('section');
	panel.id = 'wcbqn-search';
	panel.setAttribute('aria-label', config.label);
	panel.dataset.theme = document.body.classList.contains('wpcb-dark-body') || window.WPCB_DARK_MODE === true || (typeof window.WPCB_DARK_MODE === 'undefined' && config.darkMode) ? 'dark' : 'light';
	// Follow the native theme switch without writing any upstream setting.
	new MutationObserver(() => { panel.dataset.theme = document.body.classList.contains('wpcb-dark-body') ? 'dark' : 'light'; }).observe(document.body, { attributes: true, attributeFilter: ['class'] });
	const label = document.createElement('label');
	label.className = 'wcbqn-sr-only'; label.htmlFor = 'wcbqn-search-query'; label.textContent = config.label;
	const controls = document.createElement('div'); controls.className = 'wcbqn-search-controls';
	const input = document.createElement('input');
	input.type = 'search'; input.id = 'wcbqn-search-query'; input.maxLength = 100;
	input.autocomplete = 'off'; input.placeholder = config.label + '…'; input.setAttribute('aria-describedby', 'wcbqn-search-help');
	input.setAttribute('aria-controls', 'wcbqn-search-results');
	const clear = document.createElement('button'); clear.type = 'button';
	clear.className = 'button'; clear.textContent = config.clear; clear.hidden = true;
	controls.append(input, clear);
	const filters = document.createElement('div'); filters.className = 'wcbqn-search-filters';
	/** Build a labeled native filter. @param {string} id Control ID. @param {string} caption Label. @param {Object} options Value/text map. @returns {HTMLSelectElement} Filter. */
	function selectFilter(id, caption, options) {
		const holder = document.createElement('label'); holder.htmlFor = id; const captionText = document.createElement('span'); captionText.className = 'wcbqn-sr-only'; captionText.textContent = caption; holder.append(captionText);
		const select = document.createElement('select'); select.id = id;
		for (const [value, text] of Object.entries(options)) { const option = document.createElement('option'); option.value = value; option.textContent = text; select.append(option); }
		holder.append(select); filters.append(holder); return select;
	}
	const type = selectFilter('wcbqn-search-type', config.typeLabel, config.types);
	const state = selectFilter('wcbqn-search-state', config.statusLabel, config.states);
	const help = document.createElement('p'); help.className = 'wcbqn-sr-only'; help.id = 'wcbqn-search-help'; help.textContent = config.help;
	const status = document.createElement('p'); status.className = 'wcbqn-search-status';
	status.setAttribute('role', 'status'); status.setAttribute('aria-live', 'polite');
	const list = document.createElement('ul'); list.id = 'wcbqn-search-results'; list.hidden = true;
	panel.append(label, controls, filters, help, status, list); wrap.insertBefore(panel, nativeList);
	let timer; let controller; let generation = 0; let writing = false; let pendingFocus = null; let announcement = '';

	/** Restore native navigation without changing upstream state. */
	function restore() {
		aside.classList.remove('wcbqn-searching'); list.hidden = true;
		list.replaceChildren(); status.textContent = ''; panel.removeAttribute('aria-busy');
	}
	/** Clear query and invalidate responses, including already completed requests. */
	function reset() {
		config.folder = 0; input.value = ''; type.value = 'all'; state.value = 'all'; generation++; clearTimeout(timer);
		if (controller) controller.abort(); clear.hidden = true; restore(); input.focus();
	}
	/** Render fresh metadata for the newest query. @param {string} query Search words. @param {number} current Request generation. @returns {Promise<void>} */
	async function search(query, current) {
		controller = new AbortController();
		panel.setAttribute('aria-busy', 'true'); status.textContent = config.loading;
		try {
			const response = await fetch(config.url, {
				method: 'POST', credentials: 'same-origin', signal: controller.signal,
				body: new URLSearchParams({ action: 'wcbqn_search_scripts', nonce: config.nonce, query, type: type.value, state: state.value, folder: String(config.folder || 0) })
			});
			const result = await response.json();
			if (current !== generation) return;
			if (!response.ok || !result.success || !Array.isArray(result.data.items)) throw new Error('Search unavailable');
			const fragment = document.createDocumentFragment();
			for (const item of result.data.items) {
				const url = new URL(item.url, window.location.href);
				if (url.origin !== window.location.origin || !/^https?:$/.test(url.protocol)) continue;
				const row = document.createElement('li'); const link = document.createElement('a');
				link.href = url.href; link.textContent = item.title;
				const meta = document.createElement('span'); meta.textContent = [item.type, item.status, item.path].filter(Boolean).join(' · ');
				const star = document.createElement('button'); star.type = 'button'; star.className = 'wcbqn-search-star';
				star.dataset.id = String(item.id); star.disabled = writing;
				const selected = Boolean(item.favorite);
				star.textContent = selected ? '★' : '☆'; star.setAttribute('aria-pressed', String(selected));
				star.setAttribute('aria-label', `${selected ? config.removeFavorite : config.addFavorite}: ${item.title}`);
				star.title = selected ? config.removeFavorite : config.addFavorite;
				star.addEventListener('click', () => saveFavorite(star, item, !selected));
				const text = document.createElement('div'); text.append(link, meta);
				row.append(text, star); fragment.append(row);
			}
			list.replaceChildren(fragment); list.hidden = result.data.items.length === 0;
			aside.classList.add('wcbqn-searching');
			status.textContent = result.data.total ? config.count.replace('%d', String(result.data.total)) : config.empty;
			if (result.data.total > 50) status.textContent += ' ' + config.limited;
			if (announcement) { status.textContent = announcement + ' ' + status.textContent; announcement = ''; }
			if (pendingFocus !== null) { const star = list.querySelector(`[data-id="${Number(pendingFocus)}"]`); if (star) star.focus(); pendingFocus = null; }
		} catch (error) {
			if (current !== generation || error.name === 'AbortError') return;
			restore(); status.textContent = config.error;
		} finally {
			if (current === generation) panel.removeAttribute('aria-busy');
		}
	}
	/** Debounce reads and invalidate stale responses. @param {number} delay Debounce milliseconds. @param {boolean} preserveFocus Retain a pending favorite button focus. @returns {void} */
	function refresh(delay = 250, preserveFocus = false) {
		if (!preserveFocus) { pendingFocus = null; announcement = ''; }
		generation++; clearTimeout(timer); if (controller) controller.abort();
		const query = input.value.trim(); clear.hidden = !config.folder && !input.value && type.value === 'all' && state.value === 'all';
		restore();
		if (config.folder || query || type.value !== 'all' || state.value !== 'all') { const current = generation; timer = setTimeout(() => search(query, current), delay); }
	}
	/** Save an explicit personal state. @param {HTMLButtonElement} button Trigger. @param {Object} item Search record. @param {boolean} selected Desired state. @returns {Promise<void>} */
	async function saveFavorite(button, item, selected) {
		if (writing) return;
		const focusButton = document.activeElement === button; const view = [input.value, type.value, state.value].join('\n');
		let failureMessage = config.favoriteError;
		writing = true; list.querySelectorAll('button').forEach(star => { star.disabled = true; });
		try {
			const response = await fetch(config.url, {
				method: 'POST', credentials: 'same-origin',
				body: new URLSearchParams({ action: 'wcbqn_search_favorite', nonce: config.favoriteNonce, id: String(item.id), selected: selected ? '1' : '0' })
			});
			const result = await response.json();
			if (!response.ok || !result.success) {
				if (result.data && typeof result.data.message === 'string') failureMessage = result.data.message;
				throw new Error('Favorite unavailable');
			}
			if (focusButton && view === [input.value, type.value, state.value].join('\n')) pendingFocus = item.id;
			document.dispatchEvent(new CustomEvent('wcbqn-favorite-changed',{detail:{id:Number(item.id),favorite:Boolean(result.data.favorite),source:'search'}}));
			announcement = config.favoriteSaved; refresh(0, true);
		} catch (_) { status.textContent = failureMessage; }
		finally { writing = false; list.querySelectorAll('button').forEach(star => { star.disabled = false; }); }
	}
	document.addEventListener('wcbqn-favorite-changed',event=>{if(event.detail?.source==='workspace')refresh(0);});
	input.addEventListener('input', () => refresh());
	type.addEventListener('change', () => refresh(0)); state.addEventListener('change', () => refresh(0));

	panel.addEventListener('keydown', (event) => {
		if (event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); reset(); }
		if (event.key === 'Enter' && event.target === input) event.preventDefault();
		if (event.key === 'ArrowDown' && event.target === input) {
			const first = list.querySelector('a'); if (first) { event.preventDefault(); first.focus(); }
		}
	});
	clear.addEventListener('click', reset);
	if (config.folder) refresh(0);
	if (window.location.hash === '#wcbqn-search-query' || config.folder) input.focus();
})();
