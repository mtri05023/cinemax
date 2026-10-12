const {chromium}=require('playwright');
const {execFileSync}=require('node:child_process');
const fs=require('node:fs');
const path=require('node:path');
const php='C:/xampp/php/php.exe';
const fixture=path.join(__dirname,'fixtures.php');
const fixturePath=path.join(__dirname,'fixture.json');
const base='http://localhost/cinemax/cinemax/views/user/index.php';
const checks=[];
function assert(value,message){if(!value)throw Error(message);checks.push(message);}
(async()=>{
  if(fs.existsSync(fixturePath))throw Error('Existing browser fixtures; finish their test before running this one.');
  execFileSync(php,[fixture,'create']);
  let browser;
  try{
    const data=JSON.parse(fs.readFileSync(fixturePath,'utf8'));
    browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
    async function customer(who){const context=await browser.newContext({viewport:{width:1280,height:900}});await context.addCookies([{name:'cinemax_token',value:data.actors[who].token,url:'http://localhost',httpOnly:true,sameSite:'Lax'}]);return context;}
    const a=await customer('a'),b=await customer('b'),page=await a.newPage();
    const errors=[];page.on('pageerror',e=>errors.push(e.message));
    await page.goto(`${base}?page=seat_selection&show_id=${data.show_id}`,{waitUntil:'networkidle'});
    await page.locator(`.seat[data-ticket-id="${data.ticket_ids[0]}"]`).click();
    await page.locator('#btnContinue').click();await page.waitForURL('**/*page=food_selection');
    await page.locator('#finalForm button[type=submit]').click();await page.waitForURL('**/*page=payment');
    const cancel=page.locator('form[action*="cancel_checkout"]');
    const token=await cancel.locator('[name=hold_token]').inputValue();
    const csrf=await cancel.locator('[name=checkout_csrf]').inputValue();
    const state=async()=>await (await a.request.get(`${base}?page=payment&action=checkout_state&hold_token=${token}`)).json();
    page.once('dialog',dialog=>dialog.dismiss());await cancel.locator('button').click();
    assert((await state()).active&&page.url().includes('page=payment'),'Dismissing confirmation keeps the payment and held seats');
    const get=await a.request.get(`${base}?page=payment&action=cancel_checkout`);
    assert(get.status()===405&&(await state()).active,'GET cannot cancel checkout');
    await a.request.post(`${base}?page=payment&action=cancel_checkout`,{form:{hold_token:token,checkout_csrf:'invalid'}});
    assert((await state()).active,'Invalid CSRF does not release seats');
    await b.request.post(`${base}?page=payment&action=cancel_checkout`,{form:{hold_token:token,checkout_csrf:csrf}});
    assert((await state()).active,'Another customer cannot cancel the checkout');
    // Responsive button remains reachable at small and large viewports.
    for(const width of [320,375,430,768,1280]){await page.setViewportSize({width,height:900});const box=await cancel.locator('button').boundingBox();assert(box&&box.x>=0&&box.x+box.width<=width+1,`Cancel button fits ${width}px`);}
    await page.setViewportSize({width:1280,height:900});
    page.once('dialog',dialog=>dialog.accept());await cancel.locator('button').click();await page.waitForURL(`**/*page=seat_selection&show_id=${data.show_id}`);
    assert(!(await state()).active,'Accepting cancellation releases seats immediately and returns to seat selection');
    await page.goto(`${base}?page=payment`,{waitUntil:'networkidle'});
    assert(page.url().includes('page=seat_selection')||page.url().includes('page=movies'),'Cancelled payment cannot be reopened from stale session');
    const other=await b.newPage();await other.goto(`${base}?page=seat_selection&show_id=${data.show_id}`,{waitUntil:'networkidle'});
    const seat=other.locator(`.seat[data-ticket-id="${data.ticket_ids[0]}"]`);
    assert(await seat.getAttribute('data-status')==='available','Another browser sees the cancelled seat as available');
    await seat.click();await other.locator('#btnContinue').click();await other.waitForURL('**/*page=food_selection');
    assert(await other.locator('[name=hold_token]').inputValue()!==token,'Another customer can reserve the released seat');
    assert(errors.length===0,'Payment cancellation has no browser JavaScript errors');
    fs.writeFileSync(path.join(__dirname,'payment-cancel-results.json'),JSON.stringify({passed:checks.length,checks},null,2));
    console.log(`PASS: ${checks.length} payment cancellation browser checks`);
  }finally{if(browser)await browser.close();execFileSync(php,[fixture,'cleanup']);}
})().catch(e=>{console.error(e.message);process.exitCode=1;});
