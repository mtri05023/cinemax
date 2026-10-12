const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const snippets = JSON.parse(fs.readFileSync(path.join(__dirname, 'admin-js.json'), 'utf8'));
snippets.forEach((source, index) => new vm.Script(source, { filename: `admin-render-${index}.js` }));
console.log(`PASS: ${snippets.length} admin script/edit handlers parse correctly.`);
