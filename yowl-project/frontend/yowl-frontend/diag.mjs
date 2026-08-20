import { chromium } from 'playwright';
const SHOT = '/tmp/claude-1000/-home-lauret-chacha-Importants-YOWL-COMMUNITY/3ff043d7-ec6f-44ed-ace1-e1dce9dff464/scratchpad';
const browser = await chromium.launch({ channel: 'chrome' });
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });

const reseau = [], soucis = [];
page.on('console', (m) => { if (m.type() === 'error') soucis.push(m.text().slice(0, 160)); });
page.on('pageerror', (e) => soucis.push('pageerror: ' + e.message.slice(0, 160)));
page.on('requestfailed', (r) => { if (r.url().includes('onrender')) reseau.push(`ECHEC ${r.url().slice(38, 90)} ${r.failure()?.errorText}`); });
page.on('response', (r) => { if (r.url().includes('onrender')) reseau.push(`${r.status()} ${r.url().slice(38, 95)}`); });

let ok = false;
for (let essai = 1; essai <= 3 && !ok; essai++) {
  reseau.length = 0; soucis.length = 0;
  try {
    await page.goto('https://my-yowl.vercel.app/feed', { waitUntil: 'domcontentloaded', timeout: 90000 });
    await page.waitForTimeout(7000);
    const t = (await page.locator('body').innerText());
    ok = !/could not be loaded|n'a pas pu être chargé/i.test(t);
    console.log(`essai ${essai} : ${ok ? 'FIL CHARGE' : 'fil en erreur'}`);
  } catch (e) { console.log(`essai ${essai} : ${e.message.slice(0, 70)}`); }
}

console.log('\n--- reseau ---');
[...new Set(reseau)].forEach((l) => console.log('  ' + l));
console.log('\n--- erreurs console ---');
[...new Set(soucis)].slice(0, 8).forEach((l) => console.log('  ' + l));
console.log('\n--- page ---');
console.log('  ' + (await page.locator('body').innerText()).trim().slice(0, 260).replace(/\n+/g, ' | '));
await page.screenshot({ path: `${SHOT}/prod-feed.png` });
await browser.close();
