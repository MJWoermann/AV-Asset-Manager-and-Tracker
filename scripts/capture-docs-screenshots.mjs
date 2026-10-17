/**
 * Capture desktop + mobile screenshots for docs/images.
 * Viewports match output aspect ratios so content is not side-cropped.
 *
 * Usage: node scripts/capture-docs-screenshots.mjs
 * Env: BASE_URL, DOCS_USER, DOCS_PASSWORD, SKIP_DESKTOP=1, SKIP_MOBILE=1
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import puppeteer from 'puppeteer';
import sharp from 'sharp';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, '..');
const baseUrl = process.env.BASE_URL || 'http://127.0.0.1:8000';
const email = process.env.DOCS_USER || 'admin@example.com';
const password = process.env.DOCS_PASSWORD || 'ChangeMeNow!';

/** Match existing docs PNG dimensions (aspect-correct capture). */
const DESKTOP = { width: 860, height: 906, deviceScaleFactor: 2 };
const MOBILE = { width: 390, height: 844, deviceScaleFactor: 2, isMobile: true, hasTouch: true };
const DESKTOP_OUT = { width: 860, height: 906 };
const MOBILE_OUT = { width: 780, height: 1688 };

async function settle(ms = 450) {
  await new Promise((r) => setTimeout(r, ms));
}

async function preparePage(page) {
  await page.setRequestInterception(true);
  page.removeAllListeners('request');
  page.on('request', (req) => {
    const url = req.url();
    if (url.includes('fonts.bunny.net') || url.includes('/_boost/') || url.includes('google-analytics')) {
      req.abort().catch(() => {});
      return;
    }
    req.continue().catch(() => {});
  });
}

async function login(page) {
  await preparePage(page);
  await page.goto(`${baseUrl}/login`, { waitUntil: 'networkidle2', timeout: 60000 });
  await page.waitForSelector('input#email', { visible: true, timeout: 20000 });
  await page.evaluate((user, pass) => {
    document.querySelector('#email').value = user;
    document.querySelector('#password').value = pass;
  }, email, password);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 60000 }),
    page.click('button[type="submit"]'),
  ]);
  await settle();
  if (page.url().includes('/login')) {
    throw new Error('Login failed');
  }
}

async function hideChrome(page) {
  await page.addStyleTag({
    content: `
      [x-cloak] { display: none !important; }
      .fixed.bottom-2.left-2, .fixed.bottom-3.left-3 { display: none !important; }
    `,
  }).catch(() => {});
}

async function shot(page, outPath, outSize) {
  await hideChrome(page);
  await settle();
  const tmp = `${outPath}.tmp.png`;
  await page.screenshot({ path: tmp, type: 'png', captureBeyondViewport: false });
  await sharp(tmp)
    .resize(outSize.width, outSize.height, { fit: 'fill' })
    .png()
    .toFile(outPath);
  fs.unlinkSync(tmp);
  console.log('wrote', path.relative(root, outPath));
}

async function gotoPath(page, urlPath) {
  await page.goto(`${baseUrl}${urlPath}`, { waitUntil: 'networkidle2', timeout: 60000 });
  await settle(250);
}

async function openMobileNav(page) {
  await page.evaluate(() => {
    const btn = [...document.querySelectorAll('header button, nav button')]
      .find((b) => b.offsetParent !== null);
    if (btn) {
      btn.click();
    }
  });
  await settle(600);
}

async function resolveIds(page) {
  await gotoPath(page, '/assets?per_page=100');
  // Prefer Road Case / rack by name via HTML
  const ids = await page.evaluate(() => {
    const rows = [...document.querySelectorAll('a[href*="/assets/"]')];
    const mapped = rows.map((a) => {
      const href = a.getAttribute('href') || '';
      const m = href.match(/\/assets\/(\d+)/);
      return m ? { id: m[1], text: (a.textContent || '').trim() } : null;
    }).filter(Boolean);
    const unique = [];
    const seen = new Set();
    for (const row of mapped) {
      if (seen.has(row.id)) {
        continue;
      }
      seen.add(row.id);
      unique.push(row);
    }
    return unique;
  });

  await gotoPath(page, '/lists');
  const listId = await page.evaluate(() => {
    const href = [...document.querySelectorAll('a[href*="/lists/"]')]
      .map((a) => a.getAttribute('href') || '')
      .find((h) => /\/lists\/\d+/.test(h));
    return href ? href.match(/\/lists\/(\d+)/)[1] : '1';
  });

  let rackId = process.env.DOCS_RACK_ASSET_ID || null;
  let detailId = process.env.DOCS_DETAIL_ASSET_ID || null;
  for (const row of ids) {
    if (!rackId && /road case|rack\/|rc-/i.test(row.text)) {
      rackId = row.id;
    }
    if (!detailId && /digital mixer|^mixer\b/i.test(row.text)) {
      detailId = row.id;
    }
  }
  if (!rackId) {
    for (const row of ids.slice(0, 30)) {
      await gotoPath(page, `/assets/${row.id}`);
      const hasRack = await page.evaluate(() => !!document.querySelector('a[href*="tab=rack"]'));
      if (hasRack) {
        rackId = row.id;
        break;
      }
    }
  }
  if (!detailId) {
    detailId = ids.find((r) => r.id !== rackId)?.id || ids[0]?.id || '1';
  }
  if (!rackId) {
    rackId = '1';
  }

  return { rackId, detailId, listId, count: ids.length };
}

async function main() {
  const browser = await puppeteer.launch({
    headless: true,
    executablePath: process.env.CHROME_PATH || '/usr/bin/google-chrome-stable',
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
  });
  const absBase = path.join(root, 'docs/images');

  try {
    const desk = await browser.newPage();
    await desk.setViewport(DESKTOP);
    await login(desk);
    const { rackId, detailId, listId, count } = await resolveIds(desk);
    console.log({ rackId, detailId, listId, count });

    const shots = [
      ['features/dashboard', '/dashboard'],
      ['features/profile', '/profile'],
      ['features/scan', '/scan'],
      ['features/reports-compare', '/reports/compare'],
      ['features/reports-unknown-scans', '/reports/unknown-scans'],
      ['features/assets', '/assets'],
      ['features/asset-show', `/assets/${detailId}`],
      ['features/asset-maintenance', `/assets/${detailId}?tab=maintenance`],
      ['features/asset-history', `/assets/${detailId}?tab=history`],
      ['features/lists', '/lists'],
      ['features/list-show', `/lists/${listId}`],
      ['features/import', '/import'],
      ['admin/admin-export-columns', '/admin/export-columns'],
      ['features/locations', '/locations'],
      ['features/item-types', '/item-types'],
      ['features/files', '/files'],
      ['features/asset-rack', `/assets/${rackId}?tab=rack`],
      ['admin/admin-users', '/admin/users'],
      ['admin/admin-storage', '/admin/storage'],
      ['sso/admin-sso', '/admin/sso'],
      ['email/admin-email', '/admin/email'],
      ['features/labels', '/labels'],
      ['features/connection-map', '/reports/connection-map'],
      ['features/rigging', '/rigging'],
    ];

    if (process.env.SKIP_DESKTOP !== '1') {
      for (const [stem, urlPath] of shots) {
        const out = path.join(absBase, `${stem}-desktop.png`);
        fs.mkdirSync(path.dirname(out), { recursive: true });
        try {
          await gotoPath(desk, urlPath);
          await shot(desk, out, DESKTOP_OUT);
        } catch (err) {
          console.warn('skip desktop', stem, err.message);
        }
      }
    }

    const cookies = await desk.cookies();
    await desk.close();

    if (process.env.SKIP_MOBILE === '1') {
      return;
    }

    const mob = await browser.newPage();
    await mob.setViewport(MOBILE);
    await preparePage(mob);
    await mob.setCookie(...cookies);
    await gotoPath(mob, '/dashboard');
    if (mob.url().includes('/login')) {
      await login(mob);
    }

    for (const [stem, urlPath] of shots) {
      const out = path.join(absBase, `${stem}-mobile.png`);
      fs.mkdirSync(path.dirname(out), { recursive: true });
      try {
        await gotoPath(mob, urlPath);
        await shot(mob, out, MOBILE_OUT);
      } catch (err) {
        console.warn('skip mobile', stem, err.message);
      }
    }

    await gotoPath(mob, '/dashboard');
    await openMobileNav(mob);
    await shot(mob, path.join(absBase, 'features/mobile-nav-menu.png'), MOBILE_OUT);
    await mob.close();
  } finally {
    await browser.close();
  }
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
