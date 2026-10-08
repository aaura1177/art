#!/usr/bin/env node
/**
 * Print HTML to PDF with Chrome, preferring CSS @page size (matches retail_print).
 * Usage: node gs1_chrome_pdf.js <input.html> <output.pdf> [chromeBinary]
 */
const fs = require('fs');
const path = require('path');
const { pathToFileURL } = require('url');

async function main() {
  const htmlPath = process.argv[2];
  const pdfPath = process.argv[3];
  const chromePath = process.argv[4] || process.env.GS1_CHROME_PATH || '';
  if (!htmlPath || !pdfPath) {
    console.error('Usage: node gs1_chrome_pdf.js <input.html> <output.pdf> [chromeBinary]');
    process.exit(2);
  }
  if (!fs.existsSync(htmlPath)) {
    console.error('HTML not found:', htmlPath);
    process.exit(1);
  }

  let puppeteer;
  const moduleRoots = [
    path.join(__dirname, '..', 'tools', 'chrome-pdf', 'node_modules'),
    path.join(__dirname, '..', 'node_modules'),
    '/tmp/puppeteer-pdf/node_modules',
  ];
  for (const root of moduleRoots) {
    try {
      puppeteer = require(path.join(root, 'puppeteer-core'));
      break;
    } catch (_) {
      try {
        puppeteer = require(path.join(root, 'puppeteer'));
        break;
      } catch (__) {}
    }
  }
  if (!puppeteer) {
    console.error('puppeteer-core not found. Run: cd tools/chrome-pdf && npm i puppeteer-core --ignore-scripts');
    process.exit(1);
  }

  const defaultChrome = path.join(__dirname, '..', 'bin', 'chrome');
  const executablePath = chromePath || process.env.GS1_CHROME_PATH || defaultChrome;

  if (!executablePath || !fs.existsSync(executablePath)) {
    console.error('Chrome binary not found:', executablePath);
    process.exit(1);
  }

  const browser = await puppeteer.launch({
    executablePath,
    headless: 'new',
    args: ['--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu', '--font-render-hinting=none'],
  });

  try {
    const page = await browser.newPage();
    const url = pathToFileURL(path.resolve(htmlPath)).href;
    await page.goto(url, { waitUntil: 'networkidle0', timeout: 120000 });
    try {
      await page.evaluate(async () => {
        if (!document.fonts) return;
        await Promise.all([
          document.fonts.load('400 12pt LabelSans'),
          document.fonts.load('700 12pt LabelSans'),
          document.fonts.load('italic 400 12pt LabelSans'),
          document.fonts.load('italic 700 12pt LabelSans'),
          document.fonts.load('400 12pt LabelSerif'),
          document.fonts.load('700 12pt LabelSerif'),
        ]);
        await document.fonts.ready;
      });
    } catch (_) {}
    await new Promise((r) => setTimeout(r, 400));

    // DejaVu metrics differ from Times — nudge weight-note and footer clear of collisions
    // for headless PDF only (print preview in real browsers stays as-authored).
    await page.evaluate(() => {
      const pxPerMm = 96 / 25.4;
      document.querySelectorAll('.inner').forEach((inner) => {
        const specs = inner.querySelector('.specs');
        const note = inner.querySelector('.weight-note');
        if (specs && note) {
          let maxRight = 0;
          specs.querySelectorAll('td.v').forEach((td) => {
            maxRight = Math.max(maxRight, td.offsetLeft + td.offsetWidth);
          });
          const leftPx = specs.offsetLeft + maxRight + 2.5 * pxPerMm;
          const maxLeft = inner.clientWidth - note.offsetWidth - 1 * pxPerMm;
          note.style.left = Math.min(leftPx, maxLeft) + 'px';
        }

        const footer = inner.querySelector('.footer');
        const seal = inner.querySelector('.seal');
        if (footer && seal) {
          const sealW = seal.offsetWidth || 0;
          const clear = Math.max(8 * pxPerMm, sealW + 4 * pxPerMm);
          footer.style.width = Math.max(40 * pxPerMm, inner.clientWidth - clear) + 'px';
        }
      });
    });

    await page.pdf({
      path: pdfPath,
      printBackground: true,
      preferCSSPageSize: true,
      margin: { top: 0, right: 0, bottom: 0, left: 0 },
    });
  } finally {
    await browser.close();
  }
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
