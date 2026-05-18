const fs = require('fs');
const html = fs.readFileSync('landing.html', 'utf8');
const m = html.match(/<script type="text\/babel">([\s\S]*?)<\/script>/);
if (!m) { console.error('No script found'); process.exit(1); }
const code = m[1];
let stack = [];
for (let i = 0; i < code.length; i++) {
  const ch = code[i];
  if (ch === '(' || ch === '{' || ch === '[') stack.push({ ch, i });
  if (ch === ')' || ch === '}' || ch === ']') {
    const last = stack.pop();
    if (!last) { console.error('Unmatched closing', ch, 'at', i); process.exit(1); }
    if ((last.ch === '(' && ch !== ')') || (last.ch === '{' && ch !== '}') || (last.ch === '[' && ch !== ']')) {
      console.error('Mismatched', last.ch, 'with', ch, 'at', i);
      const context = code.slice(Math.max(0, i - 100), Math.min(code.length, i + 100));
      console.error('context:', context);
      process.exit(1);
    }
  }
}
if (stack.length) {
  const last = stack[stack.length - 1];
  console.error('Unmatched opening', last.ch, 'at', last.i);
  const context = code.slice(Math.max(0, last.i - 100), Math.min(code.length, last.i + 100));
  console.error('context:', context);
  process.exit(1);
}
console.log('bracket counts OK');
