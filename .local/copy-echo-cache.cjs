const cacache = require('C:/Program Files/nodejs/node_modules/npm/node_modules/cacache');
const path = require('path');
(async () => {
 const source = 'C:/Users/Satyajeet/AppData/Local/npm-cache/_cacache';
 const dest = path.resolve('.local/npm-cache/_cacache');
 const entries = await cacache.ls(source);
 let copied=0;
 for (const [key, entry] of Object.entries(entries)) {
   if (!/^make-fetch-happen:request-cache:https:\/\/registry\.npmjs\.org\/(laravel-echo|pusher-js|tweetnacl)(\/|$)/.test(key)) continue;
   const result = await cacache.get(source,key);
   await cacache.put(dest,key,result.data,{metadata:entry.metadata});
   copied++;
 }
 console.log('Copied cached package entries:',copied);
})().catch(e=>{console.error(e.message);process.exit(1)});

