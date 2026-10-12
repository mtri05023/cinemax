const {chromium}=require('playwright');const {execFileSync}=require('node:child_process');
const fs=require('node:fs'),path=require('node:path');
const php='C:/xampp/php/php.exe',fixture=path.join(__dirname,'fixtures.php'),fixturePath=path.join(__dirname,'fixture.json');
const base='http://localhost/cinemax/cinemax/views/admin/index.php';
const checks=[];function assert(value,message){if(!value)throw Error(message);checks.push(message);}
(async()=>{
  if(fs.existsSync(fixturePath))throw Error('Another fixture test is active.');
  execFileSync(php,[fixture,'create']);let browser;
  try{
    const data=JSON.parse(fs.readFileSync(fixturePath,'utf8'));
    browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
    const ctx=await browser.newContext({viewport:{width:1280,height:900}});
    await ctx.addCookies([{name:'cinemax_token',value:data.actors.admin.token,url:'http://localhost',httpOnly:true,sameSite:'Lax'}]);
    const page=await ctx.newPage(),errors=[];page.on('pageerror',e=>errors.push(e.message));
    await page.goto(`${base}?page=shows`,{waitUntil:'networkidle'});
    assert(await page.locator(`.theater-btn[onclick="selectTheater('${data.empty_cinema_id}')"]`).isDisabled(),'Cinema with no active rooms cannot start schedule creation');
    await page.locator(`.theater-btn[onclick="selectTheater('${data.cinema_id}')"]`).click();
    await page.locator('#calendarDate').fill(data.tomorrow);await page.locator('#calendarDate').dispatchEvent('change');
    await page.locator('.view-btn[data-view="week"]').click();
    await page.locator(`.week-cell[data-date="${data.tomorrow}"][data-time="18:00"]`).click();
    assert(await page.locator('#add_hallId').inputValue()===String(data.hall_id),'Week form selects only a room in the current cinema');
    await page.evaluate(id=>selectTheater(id),data.empty_cinema_id);
    assert(await page.locator('#popupAdd').isHidden()&&await page.locator('#add_hallId').inputValue()==='','Switching to an empty cinema clears stale room and closes add form');
    assert(await page.locator('.week-cell').count()===0,'Week calendar offers no add cells when there are no rooms');
    page.once('dialog',d=>d.accept());
    await page.evaluate(()=>openAddPopup({hallId:'',roomName:'',time:'18:00',date:formatDate(currentDate),isWeek:true}));
    assert(await page.locator('#popupAdd').isHidden(),'Calling the add handler directly cannot open an empty-cinema form');
    const rejected=await ctx.request.post(`${base}?page=shows&action=create`,{form:{movie_id:String(data.movie_id),hall_id:String(data.hall_id),cinema_id:String(data.empty_cinema_id),show_date:data.tomorrow,start_time:'18:00',end_time:'19:30',base_price:'50000'}});
    assert(decodeURIComponent(rejected.url()).replace(/\+/g,' ').includes('Phòng chiếu không thuộc rạp'),'Forged cross-cinema hall is rejected by server');
    await page.evaluate(id=>selectTheater(id),data.cinema_id);
    await page.locator('#calendarDate').fill(data.tomorrow);await page.locator('#calendarDate').dispatchEvent('change');
    await page.locator(`.week-cell[data-date="${data.tomorrow}"][data-time="18:00"]`).click();
    await page.locator('#add_movieSelect').selectOption(String(data.movie_id));await page.locator('#add_price').fill('50000');
    await page.locator('#formAdd button[type=submit]').click();await page.waitForURL('**/*add=1');await page.waitForLoadState('networkidle');
    await page.evaluate(()=>backToTheater());
    const row=page.locator('#overviewRows tr').filter({hasText:data.tag}).filter({hasText:'18:00'});
    assert(await row.count()===1,'Valid room can create a schedule through the week form');
    const deletion=row.getByRole('button',{name:'Xóa lịch chiếu'});
    page.once('dialog',d=>d.dismiss());await deletion.click();assert(await row.count()===1,'Dismissing deletion confirmation keeps the schedule');
    const showId=(await deletion.getAttribute('onclick')).match(/\d+/)[0];
    assert((await ctx.request.get(`${base}?page=shows&action=delete&id=${showId}`)).status()===405,'GET cannot delete a schedule');
    assert((await ctx.request.post(`${base}?page=shows&action=delete`,{form:{show_id:showId,shows_csrf:'invalid'}})).status()===403,'Invalid CSRF cannot delete a schedule');
    page.once('dialog',d=>d.accept());await deletion.click();await page.waitForURL('**/*delete=1');await page.waitForLoadState('networkidle');
    await page.evaluate(()=>backToTheater());assert(await page.locator('#overviewRows tr').filter({hasText:data.tag}).filter({hasText:'18:00'}).count()===0,'Confirmed deletion removes unused schedule and its tickets');
    assert(errors.length===0,'Schedule create/delete UI has no JavaScript errors');
    fs.writeFileSync(path.join(__dirname,'schedule-rules-results.json'),JSON.stringify({passed:checks.length,checks},null,2));console.log(`PASS: ${checks.length} schedule rule browser checks`);
  }finally{if(browser)await browser.close();execFileSync(php,[fixture,'cleanup']);}
})().catch(e=>{console.error(e.message);process.exitCode=1;});
