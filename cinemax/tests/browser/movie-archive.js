const {chromium}=require('playwright');const {execFileSync}=require('node:child_process');
const fs=require('node:fs'),path=require('node:path');const php='C:/xampp/php/php.exe';
const fixture=path.join(__dirname,'fixtures.php'),fixturePath=path.join(__dirname,'fixture.json');
const base='http://localhost/cinemax/cinemax/views/admin/index.php';
const checks=[];function assert(value,message){if(!value)throw Error(message);checks.push(message);}
(async()=>{
  if(fs.existsSync(fixturePath))throw Error('Another browser fixture test is active.');
  execFileSync(php,[fixture,'create']);let browser;
  try{
    execFileSync(php,[fixture,'create-license-movie']);const data=JSON.parse(fs.readFileSync(fixturePath,'utf8'));
    browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
    const ctx=await browser.newContext({viewport:{width:1280,height:900}});
    await ctx.addCookies([{name:'cinemax_token',value:data.actors.admin.token,url:'http://localhost',httpOnly:true,sameSite:'Lax'}]);
    const page=await ctx.newPage(),errors=[];page.on('pageerror',e=>errors.push(e.message));
    await page.goto(`${base}?page=movies&q=${data.tag}`,{waitUntil:'networkidle'});
    const row=()=>page.locator('.data-table tbody tr').filter({hasText:data.tag+' Phim bản quyền'});
    page.once('dialog',d=>d.dismiss());await row().getByRole('button',{name:'Ẩn phim'}).click();
    assert(await row().getByRole('button',{name:'Ẩn phim'}).count()===1,'Dismissing archive confirmation keeps movie active');
    page.once('dialog',d=>d.accept());await row().getByRole('button',{name:'Ẩn phim'}).click();await page.waitForURL('**/*delete=1');
    assert(await row().count()===1&&(await row().innerText()).includes('Đã ẩn'),'Archived movie remains in admin list');
    assert(await row().getByRole('button',{name:'Kích hoạt sau gia hạn'}).count()===1,'Archived movie exposes explicit renewal/reactivation action');
    const csrf=await row().locator('[name=movies_csrf]').inputValue();
    const response=await ctx.request.post(`${base}?page=movies&action=restore&id=${data.movie_id}`,{form:{movies_csrf:csrf}});
    assert(decodeURIComponent(response.url()).replace(/\+/g,' ').includes('xác nhận'),'Server rejects reactivation without license attestation');
    await page.goto(`${base}?page=shows`,{waitUntil:'networkidle'});
    assert(await page.locator('#overviewRows tr').filter({hasText:data.tag}).count()===1,'Archived movie retains its historical schedule');
    assert(await page.locator(`#add_movieSelect option[value="${data.movie_id}"]`).count()===0,'Archived movie is excluded from schedule creation');
    const publicContext=await browser.newContext();await publicContext.addCookies([{name:'cinemax_token',value:data.actors.a.token,url:'http://localhost',httpOnly:true,sameSite:'Lax'}]);
    const publicPage=await publicContext.newPage();await publicPage.goto(`http://localhost/cinemax/cinemax/views/user/index.php?page=seat_selection&show_id=${data.show_id}`,{waitUntil:'networkidle'});
    assert(await publicPage.locator('.seat').count()===0,'Direct old show URL cannot sell seats while movie is archived');
    await publicContext.close();
    await page.goto(`${base}?page=movies&q=${data.tag}&status_text=${encodeURIComponent('Đã ẩn')}`,{waitUntil:'networkidle'});
    assert(await row().count()===1,'Archived filter finds the movie');
    page.once('dialog',d=>d.accept());await row().getByRole('button',{name:'Kích hoạt sau gia hạn'}).click();await page.waitForURL('**/*restored=1');
    assert(await row().getByRole('button',{name:'Ẩn phim'}).count()===1,'License confirmation reactivates original movie record');
    assert(errors.length===0,'Archive/reactivate UI has no JavaScript errors');
    fs.writeFileSync(path.join(__dirname,'movie-archive-results.json'),JSON.stringify({passed:checks.length,checks},null,2));console.log(`PASS: ${checks.length} movie archive browser checks`);
  }finally{if(browser)await browser.close();execFileSync(php,[fixture,'cleanup']);}
})().catch(e=>{console.error(e.message);process.exitCode=1;});
