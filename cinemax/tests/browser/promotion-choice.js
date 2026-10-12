const {chromium}=require('playwright');const {execFileSync}=require('node:child_process');
const fs=require('node:fs'),path=require('node:path');
const php='C:/xampp/php/php.exe',fixture=path.join(__dirname,'fixtures.php'),fixturePath=path.join(__dirname,'fixture.json');
const base='http://localhost/cinemax/cinemax/views/user/index.php';
const checks=[];function assert(value,message){if(!value)throw Error(message);checks.push(message);}
(async()=>{
  if(fs.existsSync(fixturePath))throw Error('Another browser fixture test is active.');
  execFileSync(php,[fixture,'create']);let browser;
  try{
    const data=JSON.parse(fs.readFileSync(fixturePath,'utf8'));
    execFileSync(php,[fixture,'mark-existing-member']);
    browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
    const errors=[];
    async function customer(who,index){
      const context=await browser.newContext({viewport:{width:1280,height:900}});
      // Remote payment logos do not affect promotion behavior; avoid CDN timeouts.
      await context.route('https://cdn-icons-png.flaticon.com/**',route=>route.abort());
      await context.addCookies([{name:'cinemax_token',value:data.actors[who].token,url:'http://localhost',httpOnly:true,sameSite:'Lax'}]);
      const page=await context.newPage();page.on('pageerror',e=>errors.push(e.message));
      await page.goto(`${base}?page=promotions`,{waitUntil:'networkidle'});
      assert(await page.locator(`.promo-copy[data-code="${data.promo_code}"]`).count()===(who==='a'?1:0),`${who}: promotion page filters welcome code by purchase history`);
      assert(await page.locator(`.promo-copy[data-code="${data.general_code}"]`).count()===1,`${who}: general eligible code remains visible`);
      await page.goto(`${base}?page=seat_selection&show_id=${data.show_id}`,{waitUntil:'networkidle'});
      await page.locator(`.seat[data-ticket-id="${data.ticket_ids[index]}"]`).click();await page.locator('#btnContinue').click();await page.waitForURL('**/*page=food_selection');
      await page.locator('#finalForm button[type=submit]').click();await page.waitForURL('**/*page=payment');return page;
    }
    const fresh=await customer('a',0),existing=await customer('b',11);
    async function codes(page){return page.locator('#promotionSelect option').evaluateAll(nodes=>nodes.map(e=>e.value));}
    const newCodes=await codes(fresh),oldCodes=await codes(existing);
    assert(newCodes.includes(data.promo_code)&&newCodes.includes(data.general_code),'New member can choose welcome or general promotion');
    assert(!oldCodes.includes(data.promo_code)&&oldCodes.includes(data.general_code),'Existing member only sees eligible general promotions');
    assert(!newCodes.includes(data.minimum_code)&&!oldCodes.includes(data.minimum_code),'Order below minimum excludes the code for both customers');
    await fresh.locator(`.voucher-card[data-code="${data.promo_code}"]`).click();
    assert(await fresh.locator('#promotionCodeInput').inputValue()===data.promo_code,'Choosing a promotion applies it to the checkout form');
    assert((await fresh.locator('#finalTotal').innerText()).includes('40.000'),'Welcome promotion computes the discounted total');
    await fresh.locator(`.voucher-card[data-code="${data.general_code}"]`).click();
    assert(await fresh.locator('#promotionCodeInput').inputValue()===data.general_code&&(await fresh.locator('#finalTotal').innerText()).includes('45.000'),'Changing promotion replaces rather than stacks the discount');
    await fresh.locator('#clearPromotionBtn').click();
    assert(await fresh.locator('#promotionCodeInput').inputValue()===''&&await fresh.locator('#discountRow').isHidden(),'Clearing promotion restores the original total');
    await existing.locator('#promotionCode').fill(data.promo_code);await existing.locator('#applyPromotionBtn').click();
    assert(await existing.locator('#promotionCodeInput').inputValue()==='','Manual entry cannot apply welcome code for an existing member');
    await existing.locator(`.voucher-card[data-code="${data.general_code}"]`).click();
    assert(await existing.locator('#promotionCodeInput').inputValue()===data.general_code,'Existing member can apply their eligible general code');
    for(const width of [320,375,430,768,1280]){
      await fresh.setViewportSize({width,height:900});
      const box=await fresh.locator('.promotion-box').boundingBox();assert(box&&box.x>=0&&box.x+box.width<=width+1,`Promotion cards fit ${width}px`);
    }
    await fresh.locator(`.voucher-card[data-code="${data.promo_code}"]`).click();
    for(const width of [375,1280]) {
      await fresh.setViewportSize({width,height:900});
      await fresh.locator('.promotion-box').screenshot({path:path.join(__dirname,`promotion-cards-${width}.png`)});
    }
    assert(errors.length===0,'Promotion choice has no browser JavaScript errors');
    fs.writeFileSync(path.join(__dirname,'promotion-choice-results.json'),JSON.stringify({passed:checks.length,checks},null,2));console.log(`PASS: ${checks.length} promotion-choice browser checks`);
  }finally{if(browser)await browser.close();execFileSync(php,[fixture,'cleanup']);}
})().catch(e=>{console.error(e.message);process.exitCode=1;});
