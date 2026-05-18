const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright-chromium');
(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();
  page.on('console', msg => console.log('CONSOLE:', msg.type(), msg.text()));
  page.on('pageerror', err => console.log('PAGEERROR:', err.toString()));
  page.on('requestfailed', req => console.log('REQUESTFAILED:', req.url(), req.failure()?.errorText));
  const fileUrl = 'file://' + path.join(process.cwd(), 'landing.html').replace(/\\/g, '/');
  console.log('URL', fileUrl);
  const response = await page.goto(fileUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });
  console.log('status', response && response.status());
  await page.waitForTimeout(5000);
  await browser.close();
})();
