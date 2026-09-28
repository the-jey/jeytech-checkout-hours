/** Native inventory verification on the extracted release and a real MySQL-compatible database. */
import { chromium } from 'playwright';
import fs from 'node:fs/promises';
import path from 'node:path';
import assert from 'node:assert/strict';

const locale = process.argv[2] || 'en';
const base = `http://127.0.0.1:${locale === 'fr' ? 9433 : 9432}`;
const site = `/home/jey/.local/state/jeytech/wordpress-mysql/sites/ch-${locale}`;
const fixture = JSON.parse(await fs.readFile(path.join(site, 'wp-content/ch-dev', `.demo-${locale}.json`)));
const closedText = locale === 'fr' ? 'Les commandes sont actuellement fermées' : 'Checkout is currently closed';
const reports = [];
const browser = await chromium.launch({ executablePath: '/usr/bin/google-chrome', args: ['--no-sandbox'] });
const control = await browser.newContext();
const metrics = async () => {
  const response = await control.request.get(base + '/wp-json/jeytech-ch-dev/v1/state');
  assert.equal(response.status(), 200);
  return response.json();
};

try {
  const initial = await metrics();
  assert.match(initial.database, /MariaDB/);
  for (const kind of ['classic', 'blocks']) {
    for (const mode of ['closed', 'open', 'warn']) {
      const set = await control.request.post(base + '/wp-json/jeytech-ch-dev/v1/state', { data: { state: mode } });
      assert.equal(set.status(), 200);
      const context = await browser.newContext({ viewport: { width: 1440, height: 1100 } });
      const page = await context.newPage();
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      try {
        await page.goto(base + '/?add-to-cart=' + fixture.productId);
        await page.goto(kind === 'classic' ? fixture.classicCheckout : fixture.checkout);
        const fields = kind === 'classic'
          ? { first: '#billing_first_name', last: '#billing_last_name', address: '#billing_address_1', postcode: '#billing_postcode', city: '#billing_city', phone: '#billing_phone', email: '#billing_email' }
          : { first: '#billing-first_name', last: '#billing-last_name', address: '#billing-address_1', postcode: '#billing-postcode', city: '#billing-city', phone: '#billing-phone', email: '#email' };
        for (const [key, value] of Object.entries({ first: 'Camille', last: 'Demo', address: '12 rue de la Démo', postcode: '75001', city: 'Paris', phone: '0612345678', email: 'customer@example.test' })) {
          await page.locator(fields[key]).fill(value);
        }
        await page.locator(kind === 'classic' ? '#payment_method_bacs' : '#radio-control-wc-payment-method-options-bacs').check();
        await page.locator(fields.email).blur();
        if (kind === 'classic') await page.waitForResponse(response => response.url().includes('wc-ajax=update_order_review'), { timeout: 2500 }).catch(() => {});
        if (mode === 'warn') await page.getByText(locale === 'fr' ? 'Vous pouvez tout de même commander' : 'You can still place an order', { exact: false }).first().waitFor();
        const before = await metrics();
        const submitted = page.waitForResponse(response => kind === 'classic'
          ? response.url().includes('wc-ajax=checkout')
          : /\/wc\/store\/v1\/checkout(?:\?|$)/.test(response.url()) && response.request().method() === 'POST');
        await page.locator(kind === 'classic' ? '#place_order' : '.wc-block-components-checkout-place-order-button').click();
        const response = await submitted;
        if (mode === 'closed') {
          assert.match(JSON.stringify(await response.json()), new RegExp(closedText));
          if (kind === 'blocks') assert.equal(response.status(), 403);
          await page.getByText(closedText, { exact: false }).first().waitFor();
          const after = await metrics();
          assert.deepEqual(after.orders, before.orders);
          assert.equal(after.stock, before.stock);
          assert.equal(after.gatewayCalls, before.gatewayCalls);
          assert.deepEqual(after.reservedStock, before.reservedStock);
          reports.push({ kind, mode, locale, nativeSubmission: true, managedStock: true, stockBefore: before.stock, stockAfter: after.stock, noPaymentCall: true, noOrderMutation: true, reservationsPreserved: true });
        } else {
          await page.waitForURL('**/order-received/**', { timeout: 30000, waitUntil: 'domcontentloaded' });
          const after = await metrics();
          assert.equal(after.gatewayCalls, before.gatewayCalls + 1);
          assert.equal(after.stock, before.stock - 1);
          const orderId = Number(new URL(page.url()).pathname.match(/order-received\/(\d+)/)[1]);
          assert.ok(after.orders.includes(orderId));
          assert.ok(!after.reservedStock.some(row => Number(row.order_id) === orderId));
          await page.reload({ waitUntil: 'domcontentloaded' });
          const repeated = await metrics();
          assert.equal(repeated.gatewayCalls, after.gatewayCalls);
          assert.equal(repeated.stock, after.stock);
          reports.push({ kind, mode, locale, nativeSubmission: true, managedStock: true, orderId, orderReceived: true, stockBefore: before.stock, stockAfter: after.stock, stockReducedExactlyOnce: true, gatewayCalledOnce: true, reservationReleased: true, receiptReloadPreservesStockAndGatewayCount: true });
        }
        assert.deepEqual(errors, []);
        await fs.mkdir('dev/outputs', { recursive: true });
        await page.screenshot({ path: `dev/outputs/mysql-stock-${kind}-${mode}-${locale}.png`, fullPage: true });
        console.log(JSON.stringify(reports.at(-1)));
      } catch (error) {
        await fs.mkdir('dev/outputs', { recursive: true });
        await page.screenshot({ path: `dev/outputs/mysql-stock-${kind}-${mode}-${locale}-failure.png`, fullPage: true });
        throw error;
      } finally { await context.close(); }
    }
  }
  const result = { testedAt: new Date().toISOString(), archiveSha256: '7a5d962ca3224700ae817530cbdc17172780574908e7a1118790fc0eeb758c49', versions: fixture.versions, storage: fixture.storage, database: initial.database, reports, failures: 0, noMailOrExternalPayment: true };
  await fs.writeFile(`dev/results/browser-stock-mysql-${locale}.json`, JSON.stringify(result, null, 2) + '\n');
} finally { await control.close(); await browser.close(); }
