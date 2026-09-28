import { chromium } from 'playwright';
import fs from 'node:fs/promises';
import assert from 'node:assert/strict';

const locale=process.argv[2]||'en';
const port=locale==='fr'?9421:9420;
const base=`http://127.0.0.1:${port}`;
await fs.mkdir('dev/outputs',{recursive:true});
const browser=await chromium.launch({executablePath:'/usr/bin/google-chrome',args:['--no-sandbox']});
const reports=process.argv.includes('--resume')?JSON.parse(await fs.readFile(`dev/.browser-${locale}-checkpoint.json`)):[];
const closedText=locale==='fr'?'Les commandes sont actuellement fermées':'Checkout is currently closed';
try {
 console.log(JSON.stringify({locale,phase:'start'}));
 const fixture=JSON.parse(await fs.readFile(`dev/.demo-${locale}.json`));
 const admin=await browser.newContext({viewport:{width:1440,height:1100}});
 const ap=await admin.newPage();
 const state=async(value)=>{const response=await admin.request.post(base+'/wp-json/jeytech-ch-dev/v1/state',{data:{state:value}});assert.equal(response.status(),200);return response.json();};
 const metrics=async()=>{const response=await admin.request.get(base+'/wp-json/jeytech-ch-dev/v1/state');assert.equal(response.status(),200);return response.json();};
 if(!reports.some(r=>r.kind==='settings')){
 await state('closed');
 await ap.goto(base+'/wp-login.php');await ap.locator('#user_login').fill('admin');await ap.locator('#user_pass').fill('password');await Promise.all([ap.waitForNavigation(),ap.locator('#wp-submit').click()]);
 console.log(JSON.stringify({locale,phase:'admin-login'}));
 await ap.goto(base+'/wp-admin/admin.php?page=jeytech-checkout-hours');
 const root=ap.locator('.jeytech-ch-admin');await root.waitFor();
 assert.match(await root.innerText(),locale==='fr'?/Horaires de la semaine/:/Weekly schedule/);
 const beforeSave=await metrics();
 await ap.locator('[name="jeytech_ch_settings[schedule][1][0][start]"]').fill('09:00');
 await ap.locator('[name="jeytech_ch_settings[schedule][1][0][end]"]').fill('09:00');
 await Promise.all([ap.waitForNavigation(),ap.locator('#submit').click()]);
 assert.deepEqual((await metrics()).settings,beforeSave.settings);
 assert.match(await root.innerText(),locale==='fr'?/doivent être différentes/:/must differ/);
 await state('closed');await ap.reload();
 const closedSettings=(await metrics()).settings;
 const nonce=await ap.locator('[name="_wpnonce"]').getAttribute('value');assert.ok(nonce);
 const badNonce=await admin.request.post(base+'/wp-admin/options.php',{form:{option_page:'jeytech_ch',action:'update',_wpnonce:'forged','jeytech_ch_settings[enabled]':'1'}});
 assert.equal(badNonce.status(),403);assert.deepEqual((await metrics()).settings,closedSettings);
 await root.screenshot({path:`.wordpress-org/screenshot-${locale==='fr'?4:1}.png`});
 await ap.setViewportSize({width:390,height:844});assert.ok(await ap.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));await root.screenshot({path:`dev/outputs/settings-${locale}-mobile.png`});
 reports.push({kind:'settings',locale,invalidSavePreservesSettings:true,invalidNonce403:true,mobileOverflow:false});
 console.log(JSON.stringify(reports.at(-1)));
 await fs.writeFile(`dev/.browser-${locale}-checkpoint.json`,JSON.stringify(reports));
 }
 await admin.close();

 async function fill(page,kind){
  const fields=kind==='classic'?{first:'#billing_first_name',last:'#billing_last_name',address:'#billing_address_1',postcode:'#billing_postcode',city:'#billing_city',phone:'#billing_phone',email:'#billing_email'}:{first:'#billing-first_name',last:'#billing-last_name',address:'#billing-address_1',postcode:'#billing-postcode',city:'#billing-city',phone:'#billing-phone',email:'#email'};
  for(const[key,value]of Object.entries({first:'Camille',last:'Demo',address:'12 rue de la Démo',postcode:'75001',city:'Paris',phone:'0612345678',email:'customer@example.test'}))await page.locator(fields[key]).fill(value);
  await page.locator(kind==='classic'?'#payment_method_bacs':'#radio-control-wc-payment-method-options-bacs').check();
  await page.locator(fields.email).blur();
  if(kind==='classic')await page.waitForResponse(r=>r.url().includes('wc-ajax=update_order_review'),{timeout:2500}).catch(()=>{});
 }
 // Separate anonymous sessions exercise native order placement and its real payment gateway.
 for(const kind of ['classic','blocks'])for(const mode of ['closed','open','warn']){
  if(reports.some(r=>r.kind===kind&&r.mode===mode))continue;
  const control=await browser.newContext();const set=await control.request.post(base+'/wp-json/jeytech-ch-dev/v1/state',{data:{state:mode}});assert.equal(set.status(),200);
  const context=await browser.newContext({viewport:{width:1440,height:1100}});const page=await context.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.goto(base+'/?add-to-cart='+(mode==='closed'?fixture.productId:fixture.positiveProductId));await page.goto(kind==='classic'?fixture.classicCheckout:fixture.checkout);await fill(page,kind);
  if(mode==='warn')await page.getByText(locale==='fr'?'Vous pouvez tout de même commander':'You can still place an order',{exact:false}).first().waitFor({timeout:20000});
  const before=await (await control.request.get(base+'/wp-json/jeytech-ch-dev/v1/state')).json();
  const submitted=page.waitForResponse(r=>kind==='classic'?r.url().includes('wc-ajax=checkout'):/\/wc\/store\/v1\/checkout(?:\?|$)/.test(r.url())&&r.request().method()==='POST');
  await (kind==='classic'?page.locator('#place_order'):page.locator('.wc-block-components-checkout-place-order-button')).click();
  const response=await submitted;
  if(mode==='closed'){
   const data=await response.json();assert.match(JSON.stringify(data),new RegExp(closedText));if(kind==='blocks')assert.equal(response.status(),403);
   await page.getByText(closedText,{exact:false}).first().waitFor();
   const after=await (await control.request.get(base+'/wp-json/jeytech-ch-dev/v1/state')).json();assert.deepEqual(after.orders,before.orders);assert.equal(after.stock,before.stock);assert.equal(after.gatewayCalls,before.gatewayCalls);
   assert.ok((await page.locator('body').innerText()).includes(locale==='fr'?'Carnet de démonstration':'JeyTech demo notebook'));
   await page.screenshot({path:`.wordpress-org/screenshot-${(locale==='fr'?3:0)+(kind==='classic'?2:3)}.png`,fullPage:true});
   await page.setViewportSize({width:390,height:844});assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));await page.screenshot({path:`dev/outputs/checkout-${kind}-${locale}-mobile.png`,fullPage:true});
   reports.push({kind,mode,locale,realNativeSubmission:true,noPaymentCall:true,noOrderMutation:true,noStockLoss:true,cartPreserved:true,http:response.status(),jsErrors:errors});
  }else{
   await page.waitForURL('**/order-received/**',{timeout:25000,waitUntil:'domcontentloaded'});
   const after=await (await control.request.get(base+'/wp-json/jeytech-ch-dev/v1/state')).json();assert.equal(after.gatewayCalls,before.gatewayCalls+1);assert.equal(after.stock,before.stock);
   await page.screenshot({path:`dev/outputs/checkout-${kind}-${mode}-${locale}.png`,fullPage:true});
   reports.push({kind,mode,locale,realNativeSubmission:true,gatewayCalledOnce:true,positiveFixture:'Non-stock virtual product: Playground SQLite cannot execute WooCommerce stock reservations. Managed stock is checked on rejected real submissions.',orderReceived:true,jsErrors:errors});
  }
  assert.deepEqual(errors,[]);await context.close();await control.close();console.log(JSON.stringify(reports.at(-1)));await fs.writeFile(`dev/.browser-${locale}-checkpoint.json`,JSON.stringify(reports));
 }
 // Replay an open classic page from cache after the schedule changes to closed.
 const control=await browser.newContext();await control.request.post(base+'/wp-json/jeytech-ch-dev/v1/state',{data:{state:'open'}});
 const context=await browser.newContext({viewport:{width:1440,height:1100}});const page=await context.newPage();await page.goto(base+'/?add-to-cart='+fixture.productId);
 const openResponse=await page.goto(fixture.classicCheckout);const cachedHtml=await openResponse.text();await control.request.post(base+'/wp-json/jeytech-ch-dev/v1/state',{data:{state:'closed'}});
 await page.route(fixture.classicCheckout,route=>route.fulfill({status:200,contentType:'text/html',body:cachedHtml}));await page.reload();
 await page.locator('[data-jeytech-ch-status]').filter({hasText:closedText}).first().waitFor();await fill(page,'classic');
 const before=await (await control.request.get(base+'/wp-json/jeytech-ch-dev/v1/state')).json();
 const rejected=page.waitForResponse(r=>r.url().includes('wc-ajax=checkout'));await page.locator('#place_order').click();assert.match(JSON.stringify(await(await rejected).json()),new RegExp(closedText));
 const after=await(await control.request.get(base+'/wp-json/jeytech-ch-dev/v1/state')).json();assert.equal(after.gatewayCalls,before.gatewayCalls);assert.equal(after.stock,before.stock);
 reports.push({kind:'cached-classic',locale,staleOpenPageRejected:true,liveNoticeUpdated:true,noCharge:true});await context.close();
 // Native non-AJAX classic checkout with JavaScript disabled.
 const nojs=await browser.newContext({javaScriptEnabled:false});const np=await nojs.newPage();await np.goto(base+'/?add-to-cart='+fixture.productId);await np.goto(fixture.classicCheckout);
 for(const[id,value]of Object.entries({billing_first_name:'Camille',billing_last_name:'Demo',billing_address_1:'12 rue de la Démo',billing_postcode:'75001',billing_city:'Paris',billing_phone:'0612345678',billing_email:'customer@example.test'}))await np.locator('#'+id).fill(value);
 await np.locator('#payment_method_bacs').check();await Promise.all([np.waitForNavigation(),np.locator('#place_order').click()]);assert.match(await np.locator('body').innerText(),new RegExp(closedText));
 const end=await(await control.request.get(base+'/wp-json/jeytech-ch-dev/v1/state')).json();assert.equal(end.gatewayCalls,after.gatewayCalls);assert.equal(end.stock,after.stock);reports.push({kind:'no-js-classic',locale,serverRejected:true,noCharge:true});
 await nojs.close();await control.close();
 await fs.writeFile(`dev/results/browser-${locale}.json`,JSON.stringify({testedAt:new Date().toISOString(),versions:fixture.versions,reports},null,2)+'\n');
}catch(error){await fs.writeFile(`dev/outputs/browser-${locale}-error.txt`,String(error.stack));throw error;}finally{await browser.close();}
