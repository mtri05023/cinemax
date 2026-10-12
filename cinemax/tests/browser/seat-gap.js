const {chromium}=require('playwright');
const {execFileSync}=require('node:child_process');
const fs=require('node:fs');const path=require('node:path');
const fixture=path.join(__dirname,'fixtures.php'),fixturePath=path.join(__dirname,'fixture.json');
const php='C:/xampp/php/php.exe',base='http://localhost/cinemax/cinemax/views/user/index.php';
const checks=[];function assert(value,message){if(!value)throw Error(message);checks.push(message);}
(async()=>{
  if(fs.existsSync(fixturePath))throw Error('Existing browser fixtures must be cleaned by their owning test.');
  execFileSync(php,[fixture,'create']);let browser;
  try{
    const data=JSON.parse(fs.readFileSync(fixturePath,'utf8'));
    browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
    const ctx=await browser.newContext({viewport:{width:1280,height:900}});
    await ctx.addCookies([{name:'cinemax_token',value:data.actors.a.token,url:'http://localhost',httpOnly:true,sameSite:'Lax'}]);
    const page=await ctx.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
    await page.goto(`${base}?page=seat_selection&show_id=${data.show_id}`,{waitUntil:'networkidle'});
    const seat=n=>page.locator(`.seat[data-ticket-id="${data.ticket_ids[n-1]}"]`);
    const button=page.locator('#btnContinue'),warning=page.locator('#seatGapError');
    await seat(2).click();assert(await button.isDisabled()&&(await warning.innerText()).includes('A1'),'Left singleton blocks continue and identifies A1');
    await seat(1).click();assert(await button.isEnabled()&&await warning.isHidden(),'Selecting the isolated seat restores continue');
    await page.evaluate(id=>removeTicket(String(id)),data.ticket_ids[0]);
    assert(await button.isDisabled(),'Removing a seat updates the singleton warning');
    await seat(2).click();await seat(11).click();
    assert(await button.isDisabled()&&(await warning.innerText()).includes('A12'),'Right singleton blocks continue and identifies A12');
    await seat(12).click();assert(await button.isEnabled(),'Selecting the final pair is allowed');
    await seat(11).click();await seat(12).click();await seat(5).click();
    assert(await button.isEnabled(),'A single selected ticket with at least two empty seats each side is allowed');
    await seat(7).click();assert(await button.isDisabled()&&(await warning.innerText()).includes('A6'),'Singleton between selected groups is blocked');
    await seat(6).click();assert(await button.isEnabled(),'Filling the gap between groups clears the warning');
    const csrf=await page.locator('[name=checkout_csrf]').inputValue();
    const forged=await ctx.request.post(`${base}?page=food_selection`,{form:{checkout_csrf:csrf,showtime_id:String(data.show_id),'ticket_ids[]':String(data.ticket_ids[1])}});
    assert((await forged.text()).includes('Không được để trống 1 ghế lẻ'),'Forged POST cannot bypass the server rule');
    await page.reload({waitUntil:'networkidle'});await seat(5).click();await button.click();
    await page.waitForURL('**/*page=food_selection');assert(await page.locator('[name=hold_token]').inputValue(),'Valid selection reaches food selection with a hold');
    assert(errors.length===0,'Seat gap selection has no JavaScript errors');
    fs.writeFileSync(path.join(__dirname,'seat-gap-results.json'),JSON.stringify({passed:checks.length,checks},null,2));
    console.log(`PASS: ${checks.length} seat gap browser checks`);
  }finally{if(browser)await browser.close();execFileSync(php,[fixture,'cleanup']);}
})().catch(e=>{console.error(e.message);process.exitCode=1;});
