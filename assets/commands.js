/* Native palette commands only: no alternate palette, shortcuts or upstream writes. */
(() => {
	'use strict';
	const wp = window.wp;
	if (!wp || !wp.commands || !wp.data || !wp.domReady || !window.WCBQNCommands) return;
	wp.domReady(() => {
		const actions = wp.data.dispatch(wp.commands.store);
		if (!actions || typeof actions.registerCommand !== 'function') return;
		for (const item of window.WCBQNCommands.items || []) {
			let target;
			try { target = new URL(item.url, window.location.href); } catch (_) { continue; }
			if (target.origin !== window.location.origin || !/^https?:$/.test(target.protocol)) continue;
			actions.registerCommand({
				name: item.name, label: item.label, category: 'navigate',
				/** Follow native navigation or focus search in place. @param {Object} context Native palette controls. @returns {void} */
				callback: (context = {}) => {
					if (typeof context.close === 'function') context.close();
					if (item.focusSearch && window.location.pathname === target.pathname && new URLSearchParams(window.location.search).get('page') === 'wpcodebox2') {
						const input = document.getElementById('wcbqn-search-query');
						if (input) {

							input.focus(); return;
						}
					}
					window.location.assign(target.href);
				}
			});
		}
	});
})();
