/* Local-only filtering and copying; generated files are never changed here. */
(() => {
'use strict';
const config=window.WCBQNFunctionality;if(!config)return;
/** Normalize titles and paths for case/accent-insensitive matching. @param {string} value Text to normalize. @returns {string} Searchable text. */
const normalize=value=>value.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLocaleLowerCase();
for(const card of document.querySelectorAll('.wcbqn-fp-inventory')){
 const controls=card.querySelector('.wcbqn-fp-controls'),search=card.querySelector('[data-wcbqn-fp-search]'),filter=card.querySelector('[data-wcbqn-fp-filter]');
 const rows=[...card.querySelectorAll('[data-wcbqn-fp-row]')],empty=card.querySelector('[data-wcbqn-fp-empty]'),count=card.querySelector('[data-wcbqn-fp-count]');
 const messages=card.querySelector('[data-wcbqn-copy-status]');
 /** Apply independent text and status filters within this plugin only. @returns {void} */
 function apply(){
  if(!search||!filter)return;
  const words=normalize(search.value.trim()).split(/\s+/).filter(Boolean);let shown=0;
  for(const row of rows){const haystack=normalize(row.dataset.search||'');const state=filter.value;
   const matches=words.every(word=>haystack.includes(word))&&(state==='all'||(state==='enabled'&&row.dataset.included==='1')||(state==='commented'&&row.dataset.included==='0')||(state==='missing'&&row.dataset.missing==='1'));
   row.hidden=!matches;if(matches)shown++;
  }
  if(empty)empty.hidden=shown!==0;if(count)count.textContent=config.shown.replace('%1$d',String(shown)).replace('%2$d',String(rows.length));
 }
 if(controls){controls.hidden=false;search.addEventListener('input',apply);filter.addEventListener('change',apply);card.querySelector('[data-wcbqn-fp-clear]').addEventListener('click',()=>{search.value='';filter.value='all';apply();search.focus();});apply();}
 for(const button of card.querySelectorAll('[data-wcbqn-copy-path]')){
  button.hidden=false;
  button.addEventListener('click',async()=>{
   card.querySelectorAll('.wcbqn-copy-fallback').forEach(input=>input.remove());const path=button.dataset.wcbqnCopyPath;
   try{if(!navigator.clipboard?.writeText)throw new Error('Clipboard unavailable');await navigator.clipboard.writeText(path);messages.textContent=config.copied;}
   catch(_){
    // In insecure local HTTP contexts expose a selectable fallback instead of claiming success.
    const input=document.createElement('input');input.type='text';input.readOnly=true;input.className='wcbqn-copy-fallback';input.value=path;input.setAttribute('aria-label',config.pathLabel);button.parentElement.append(input);input.focus();input.select();
    let copied=false;try{copied=document.execCommand('copy');}catch(_){}
    if(copied){input.remove();button.focus();messages.textContent=config.copied;}else{messages.textContent=config.copyError;}
   }
  });
 }
}
})();
