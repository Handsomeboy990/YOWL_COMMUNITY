import { readFileSync } from 'fs';
import { parse } from '@vue/compiler-sfc';

const ACCENT = /[àâäéèêëîïôöùûüçœÀÂÄÉÈÊËÎÏÔÖÙÛÜÇŒ]/;
const fichier = process.argv[2];
const { descriptor } = parse(readFileSync(fichier, 'utf8'));
const source = descriptor.template.content;
const vus = new Set();

for (const m of source.matchAll(/>([^<>{}]{3,})</g)) {
  const t = m[1].trim();
  if (t && ACCENT.test(t)) vus.add(JSON.stringify(t.replace(/\s+/g, ' ')));
}
for (const m of source.matchAll(/\s((?:label|placeholder|title|hint|aria-label|alt))="([^"]{3,})"/g)) {
  if (ACCENT.test(m[2])) vus.add(JSON.stringify(m[1] + '=' + m[2]));
}
[...vus].forEach((v) => console.log(v));
