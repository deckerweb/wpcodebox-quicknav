/* Native editor navigation stays available when QuickNav search is disabled. */
(() => {
'use strict';
const config=window.WCBQNNavigation;const nativeList=document.getElementById('root');if(!config||!nativeList)return;
	if (config.newIntent) {
		let done = false;
		const observer = new MutationObserver(() => {
			const button = document.querySelector('#root .list-actions > div:first-child');
			if (button && !done) { done = true; observer.disconnect(); button.click(); }
		});
		observer.observe(nativeList, { childList: true, subtree: true });
		setTimeout(() => observer.disconnect(), 15000);
	}
})();
