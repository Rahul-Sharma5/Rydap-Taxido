const fs = require('fs');
const vm = require('vm');
for (const name of ['login','register','verify_otp','sidebar']) {
  const directory = name === 'sidebar' ? 'account' : 'auth';
  const source = fs.readFileSync(`Modules/Taxido/resources/views/front/${directory}/${name}.blade.php`,'utf8');
  let count=0;
  for(const match of source.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/g)) {
    let code=match[1];
    if (!code.trim()) continue;
    code=code.replace(/\{\{[\s\S]*?\}\}/g, 'blade-value');
    let start;
    while((start=code.indexOf('@json('))!==-1) {
      let depth=1, i=start+6, quote=null;
      for(;i<code.length&&depth;i++) {
        const c=code[i];
        if(quote) {if(c==='\\')i++;else if(c===quote)quote=null;}
        else if(c==='\''||c==='"')quote=c;
        else if(c==='(')depth++;
        else if(c===')')depth--;
      }
      if(depth)throw new Error('Unclosed Blade JSON');
      code=code.slice(0,start)+'null'+code.slice(i);
    }
    new vm.Script(code.replace(/^import .*?;$/gm, ''),{filename:name+'.blade.js'});
    count++;
  }
  console.log(`${name}: ${count} inline script parsed successfully`);
}
