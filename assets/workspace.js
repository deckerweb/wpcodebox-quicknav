/* Keep the editor instance and native save controls intact across view changes. */
(() => {
'use strict';
const config=window.WCBQNWorkspace, root=document.getElementById('root');
if(!config||!root||document.getElementById('wcbqn-workspace'))return;
const bar=document.createElement('nav');bar.id='wcbqn-workspace';bar.setAttribute('aria-label',config.label);
const link=document.createElement('a');link.href=config.overviewUrl;link.textContent=config.overview;bar.append(link);
const context=document.createElement('span');context.className='wcbqn-editor-context';const title=document.createElement('strong'),type=document.createElement('span');context.append(title,type);bar.append(context);
const star=document.createElement('button');star.type='button';star.className='wcbqn-editor-star';bar.append(star);
const copyLink=document.createElement('button');copyLink.type='button';copyLink.id='wcbqn-copy-snippet-link';copyLink.textContent=config.copyLink;bar.append(copyLink);
const linkFallback=document.createElement('input');linkFallback.type='text';linkFallback.readOnly=true;linkFallback.hidden=true;linkFallback.className='wcbqn-link-fallback';linkFallback.setAttribute('aria-label',config.linkLabel);bar.append(linkFallback);
const favorites=new Set(config.favorites.map(Number));let currentId=0,writing=false,scheduled=false;
const full=document.createElement('button'),focus=document.createElement('button'),exit=document.createElement('button');
for(const [button,label] of [[full,config.fullscreen],[focus,config.focus],[exit,config.exit]]){button.type='button';button.textContent=label;bar.append(button);}
full.setAttribute('aria-pressed','false');focus.setAttribute('aria-pressed','false');exit.hidden=true;
const status=document.createElement('span');status.className='screen-reader-text';status.setAttribute('role','status');bar.append(status);
root.parentElement.insertBefore(bar,document.getElementById('wcbqn-search')||root);
const settingsLayoutObserved=new WeakSet(),settingsLayoutObserver=typeof ResizeObserver==='function'?new ResizeObserver(schedule):null;
let mode='',origin=null,scroll=0,viewQueue=Promise.resolve(),pendingRestore=Boolean(config.rememberView&&config.lastView),copyTimer;
/** Read the active local row and live native title/type without accessing editor internals. @returns {void} */
function updateContext(){
 scheduled=false;
 fitWorkspace();
 const active=root.querySelector('[id^="snippet-"].active'),field=root.querySelector('#title');
 const heading=root.querySelector('.edit-snippet-form h3')?.textContent||'';
 // A collapsed folder omits its active row; the native editor URL still identifies the saved local snippet.
 const requested=new URL(window.location.href).searchParams.get('snippet')||'';
 const candidate=active&&/^snippet-[1-9][0-9]*$/.test(active.id)?Number(active.id.slice(8)):(/^[1-9][0-9]*$/.test(requested)?Number(requested):0);
 const local=root.querySelector('.editor-wrap [title^="Save snippet ("]')&&field&&!/^New Snippet/i.test(heading)&&(config.localSnippetIds||[]).includes(candidate);
 const previousId=currentId;
 currentId=local?candidate:0;
 if(previousId!==currentId){linkFallback.hidden=true;copyLink.textContent=config.copyLink;clearTimeout(copyTimer);}
 title.textContent=field?.value.trim()||(currentId?config.untitled:config.newSnippet);
 title.title=title.textContent;
 const code=[...(active?.classList||[])].find(value=>value.startsWith('type-'))?.slice(5);
 let nativeType='';
 for(const wrap of root.querySelectorAll('.edit-snippet-form [id="title-wrap"]')){
  if(wrap.firstElementChild?.textContent.trim()==='Type')nativeType=wrap.textContent.replace(/^\s*Type\s*/,'').trim();
 }
 type.textContent=nativeType||(code?config.types[code]||code:'');
 copyLink.disabled=!currentId;copyLink.title=currentId?config.copyLink:config.copyLinkHelp;
 if(!currentId)linkFallback.hidden=true;
 const selected=favorites.has(currentId);star.textContent=selected?'★':'☆';star.disabled=writing||!currentId;
 star.setAttribute('aria-pressed',String(selected));star.setAttribute('aria-label',(selected?config.removeFavorite:config.addFavorite)+': '+title.textContent);star.title=currentId?(selected?config.removeFavorite:config.addFavorite):config.favoriteHelp;
 if(pendingRestore&&root.querySelector('.editor-wrap')){pendingRestore=false;requestAnimationFrame(()=>view(config.lastView,true,false));}
}
/** Share the remaining viewport across native panes without moving React or editor nodes. */
function fitWorkspace(){
 const shell=root.querySelector('.snippet-list-wrap'),editor=root.querySelector('.editor-wrap');
 if(!shell||!editor)return;
 document.body.classList.add('wcbqn-editor-layout');
 const search=document.getElementById('wcbqn-search');
 for(const element of [bar,search]){if(element&&settingsLayoutObserver&&!settingsLayoutObserved.has(element)){settingsLayoutObserver.observe(element);settingsLayoutObserved.add(element);}}
 const results=search?.querySelector('#wcbqn-search-results');
 if(search){
  search.classList.add('wcbqn-layout-budget');
  if(results){
   const base=root.getBoundingClientRect().top-results.getBoundingClientRect().height;
   const limit=Math.max(0,Math.min(260,Math.floor(window.innerHeight-base-212)))+'px';
   if(search.style.getPropertyValue('--wcbqn-results-height')!==limit)search.style.setProperty('--wcbqn-results-height',limit);
  }
 }
 const height=Math.max(160,Math.floor(window.innerHeight-Math.max(0,root.getBoundingClientRect().top)-12))+'px';
 if(root.style.getPropertyValue('--wcbqn-editor-height')!==height)root.style.setProperty('--wcbqn-editor-height',height);
 const panel=root.querySelector('.settings-form');
 if(!panel||!panel.querySelector(':scope > header')||!panel.querySelector(':scope > footer'))return;
 const value=Math.max(0,Math.ceil(panel.getBoundingClientRect().top))+'px';
 if(panel.style.getPropertyValue('--wcbqn-settings-top')!==value)panel.style.setProperty('--wcbqn-settings-top',value);
 panel.classList.add('wcbqn-settings-panel');
}
window.addEventListener('resize',schedule,{passive:true});
/** Coalesce native React changes and ignore Monaco editing churn. @returns {void} */
function schedule(){if(!scheduled){scheduled=true;requestAnimationFrame(updateContext);}}
new MutationObserver(records=>{if(records.some(record=>!record.target.closest?.('.monaco-editor')))schedule();}).observe(root,{childList:true,subtree:true,attributes:true,attributeFilter:['class']});
root.addEventListener('input',event=>{if(event.target.id==='title')schedule();});
document.addEventListener('wcbqn-favorite-changed',event=>{const id=Number(event.detail?.id);if(!Number.isSafeInteger(id)||id<1)return;if(event.detail.favorite)favorites.add(id);else favorites.delete(id);schedule();});
star.addEventListener('click',async()=>{
 if(writing||!currentId)return;
 const id=currentId,selected=!favorites.has(id);let failureMessage=config.favoriteError;writing=true;updateContext();
 try{
  const response=await fetch(config.ajaxUrl,{method:'POST',credentials:'same-origin',body:new URLSearchParams({action:'wcbqn_search_favorite',nonce:config.favoriteNonce,id:String(id),selected:selected?'1':'0'})});
  const result=await response.json();if(!response.ok||!result.success){if(typeof result.data?.message==='string')failureMessage=result.data.message;throw new Error('Favorite unavailable');}
  document.dispatchEvent(new CustomEvent('wcbqn-favorite-changed',{detail:{id,favorite:Boolean(result.data.favorite),source:'workspace'}}));status.textContent=config.favoriteSaved;
 }catch(_){status.textContent=failureMessage;}
 finally{writing=false;updateContext();}
});
updateContext();
/** Change display classes only; preserve DOM, native pane state and unsaved content. @param {string} next View name or empty for normal. */
function view(next,keyboard=false,persist=true){
 if(!mode&&next){origin=document.activeElement;scroll=window.scrollY;}
 mode=next;document.body.classList.toggle('wcbqn-fullscreen',Boolean(mode));document.body.classList.toggle('wcbqn-focus',mode==='focus');
 full.setAttribute('aria-pressed',String(mode==='full'));focus.setAttribute('aria-pressed',String(mode==='focus'));exit.hidden=!mode;
 status.textContent=mode?config.active:config.normal;
 window.dispatchEvent(new Event('resize'));
 if(persist&&config.rememberView){const selected=mode;viewQueue=viewQueue.catch(()=>{}).then(async()=>{const response=await fetch(config.ajaxUrl,{method:'POST',credentials:'same-origin',keepalive:true,body:new URLSearchParams({action:'wcbqn_workspace_view',nonce:config.viewNonce,view:selected})});const result=await response.json();if(!response.ok||!result.success)status.textContent=config.viewSaveError;}).catch(()=>{status.textContent=config.viewSaveError;});}
 if(mode){if(!keyboard||document.activeElement?.offsetParent===null)exit.focus();}else{window.scrollTo(0,scroll);if(origin&&origin.isConnected)origin.focus();}
}
/** Copy a canonical local editor URL, never request nonces or transient filters. @returns {Promise<void>} */
copyLink.addEventListener('click',async()=>{
 if(!currentId)return;
 const url=new URL(config.editorUrl);url.searchParams.set('snippet',String(currentId));linkFallback.hidden=true;
 let copied=false;
 try{if(!navigator.clipboard?.writeText)throw new Error('Clipboard unavailable');await navigator.clipboard.writeText(url.href);copied=true;}
 catch(_){linkFallback.value=url.href;linkFallback.hidden=false;linkFallback.focus();linkFallback.select();try{copied=document.execCommand('copy');}catch(_){}if(copied){linkFallback.hidden=true;copyLink.focus();}}
 status.textContent=copied?config.copiedLink:config.copyLinkError;
 if(copied){clearTimeout(copyTimer);copyLink.textContent=config.copiedLink;copyTimer=setTimeout(()=>{copyLink.textContent=config.copyLink;},1800);}
});
full.addEventListener('click',()=>view(mode==='full'?'':'full'));focus.addEventListener('click',()=>view(mode==='focus'?'':'focus'));exit.addEventListener('click',()=>view(''));
// The modifier set avoids native save/format/pane shortcuts; physical codes work with Option layouts.
const mac=/Mac|iPhone|iPad/.test(navigator.platform),primary=mac?'Command':'Ctrl';
const bindings={full:config.shortcuts.full,focus:config.shortcuts.focus};
const keyLabel=key=>key?(mac?'Control + Option + Shift + ':'Ctrl + Alt + Shift + ')+key.toUpperCase():config.off;
for(const [button,key] of [[full,bindings.full],[focus,bindings.focus]]){
 button.title=button.textContent+' ('+keyLabel(key)+')';
 if(key)button.setAttribute('aria-keyshortcuts','Control+Alt+Shift+'+key.toUpperCase());
}
const help=document.createElement('a');help.id='wcbqn-shortcut-help';help.className='wcbqn-help';help.href='https://docs.wpcodebox.com/editor';help.target='_blank';help.rel='noopener noreferrer';help.textContent=config.help;help.setAttribute('aria-haspopup','dialog');help.setAttribute('aria-controls','wcbqn-shortcut-dialog');bar.append(help);
const dialog=document.createElement('dialog');dialog.id='wcbqn-shortcut-dialog';dialog.hidden=true;dialog.setAttribute('aria-labelledby','wcbqn-shortcut-title');
const header=document.createElement('header'),heading=document.createElement('h2');heading.id='wcbqn-shortcut-title';heading.textContent=config.help;
const closeForm=document.createElement('form');closeForm.method='dialog';const closeButton=document.createElement('button');closeButton.type='submit';closeButton.textContent=config.close;closeButton.autofocus=true;closeForm.append(closeButton);header.append(heading,closeForm);dialog.append(header);
const body=document.createElement('div');body.className='wcbqn-shortcut-body';dialog.append(body);
/** Render a compact accessible list with no executable HTML from labels. @param {string} label Section title. @param {Array} rows Action/key pairs. @returns {void} */
function shortcutSection(label,rows){
 const section=document.createElement('section'),h=document.createElement('h3'),list=document.createElement('dl');h.textContent=label;section.append(h,list);
 for(const [action,key,target] of rows){const term=document.createElement('dt'),value=document.createElement('dd'),kbd=document.createElement('kbd');term.textContent=action;kbd.textContent=key;value.append(kbd);if(target){const open=document.createElement('button');open.type='button';open.className='wcbqn-shortcut-action';open.textContent=config.openView;open.setAttribute('aria-label',action+': '+config.openView);open.addEventListener('click',()=>{pendingView=target;HTMLDialogElement.prototype.close.call(dialog);});value.append(open);}list.append(term,value);}
 body.append(section);
}
shortcutSection(config.helpQuickNav,[[config.fullscreen,keyLabel(bindings.full),'full'],[config.focus,keyLabel(bindings.focus),'focus'],[config.exit,'Escape']]);
shortcutSection(config.helpNative,[[config.saveSnippet,primary+' + S'],[config.formatCode,primary+' + B'],[config.runSnippet,'Shift + F10'],[config.toggleSettings,'Control + 1'],[config.toggleList,'Control + 2'],[config.togglePanes,primary+' + `'],[config.findCode,primary+' + F'],[config.lineComment,primary+' + /'],[config.commandPalette,'F1']]);
const note=document.createElement('p');note.className='wcbqn-shortcut-note';note.textContent=config.helpNote;body.append(note);
const links=document.createElement('p');links.className='wcbqn-shortcut-links';
for(const [label,url] of [[config.helpPreferences,config.preferencesUrl],[config.helpDocs,help.href]]){const a=document.createElement('a');a.textContent=label;a.href=url;a.target='_blank';a.rel='noopener noreferrer';links.append(a);}
body.append(links);root.parentElement.append(dialog);
let helpOrigin=null,pendingView=null;
help.addEventListener('click',event=>{
 if(event.ctrlKey||event.metaKey||event.shiftKey||event.altKey)return;
 try{helpOrigin=document.activeElement;dialog.hidden=false;HTMLDialogElement.prototype.showModal.call(dialog);event.preventDefault();}
 catch(_){dialog.hidden=true;/* The real documentation link remains the fallback. */}
});
dialog.addEventListener('keydown',event=>event.stopPropagation());
dialog.addEventListener('close',()=>{dialog.hidden=true;if(pendingView){const selected=pendingView;pendingView=null;view(selected);return;}if(helpOrigin?.isConnected)helpOrigin.focus();});
dialog.addEventListener('click',event=>{if(event.target!==dialog)return;const r=dialog.getBoundingClientRect();if(event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom)HTMLDialogElement.prototype.close.call(dialog);});
/** Match only exact physical-letter bindings without IME, AltGraph or key-repeat side effects.
 * @param {KeyboardEvent} event Incoming keyboard event. @param {Object} keys Effective view keys.
 * @param {boolean} isMac Whether Option is the platform Alt key (Firefox also reports it as AltGraph).
 * @returns {string} View name, or empty when the event must remain untouched.
 */
function shortcutAction(event,keys,isMac=false){
 if(event.defaultPrevented||event.repeat||event.isComposing||(!isMac&&event.getModifierState?.('AltGraph'))||!event.ctrlKey||!event.altKey||!event.shiftKey||event.metaKey)return '';
 const key=/^Key[A-Z]$/.test(event.code)?event.code.slice(3).toLowerCase():event.key.toLowerCase();
 return keys.full&&key===keys.full?'full':keys.focus&&key===keys.focus?'focus':'';
}
// Only matched view combinations are intercepted, before Monaco handles the key.
document.addEventListener('keydown',event=>{
 const next=shortcutAction(event,bindings,mac);
 if(!next||document.querySelector('dialog[open],.react-confirm-alert-overlay,[role="dialog"]'))return;
 event.preventDefault();event.stopImmediatePropagation();view(mode===next?'':next,true);
},true);
// Let Monaco, native dialogs and menus consume their first Escape before leaving.
document.addEventListener('keydown',event=>{if(event.key==='Escape'&&mode&&!event.defaultPrevented&&!document.querySelector('dialog[open],.react-confirm-alert-overlay,[role="dialog"],.monaco-editor .suggest-widget.visible')){event.preventDefault();view('');}});
})();
