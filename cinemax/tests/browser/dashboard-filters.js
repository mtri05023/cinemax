const {chromium}=require('playwright');const {execFileSync}=require('node:child_process');const fs=require('node:fs'),path=require('node:path');
const php='C:/xampp/php/php.exe',fixture=path.join(__dirname,'fixtures.php'),fixturePath=path.join(__dirname,'fixture.json');
const base='http://localhost/cinemax/cinemax/views/admin/index.php';const checks=[];
function assert(value,message){if(!value)throw Error(message);checks.push(message);}
(async()=>{
  if(fs.existsSync(fixturePath))throw Error('Another browser fixture test is active.');
  execFileSync(php,[fixture,'create']);let browser;
  try{
    execFileSync(php,[fixture,'create-license-movie']);execFileSync(php,[fixture,'dashboard-facts']);
    const data=JSON.parse(fs.readFileSync(fixturePath,'utf8'));
    browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
    const ctx=await browser.newContext({viewport:{width:1440,height:900},reducedMotion:'reduce'});
    await ctx.addCookies([{name:'cinemax_token',value:data.actors.admin.token,url:'http://localhost',httpOnly:true,sameSite:'Lax'}]);
    const page=await ctx.newPage(),errors=[];page.on('pageerror',e=>errors.push(e.message));
    const values=()=>page.locator('.stat-value').allTextContents();
    await page.goto(`${base}?page=dashboard&period=month&month=2004-02`,{waitUntil:'networkidle'});
    const month=await values();assert(month[0].includes('150.000')&&month[1].trim()==='3'&&month[3].trim()==='2','Monthly view displays correct paid revenue, tickets and orders');
    for(const id of ['ranking-cinemas','ranking-halls','ranking-movies']) assert((await page.locator('#'+id).innerText()).includes(data.tag),`${id}: paid fixture cinema/room/movie appears in ranking`);
    assert(await page.locator('.dashboard-native-chart rect').count()===29,'Leap-year month chart renders locally without a CDN');
    assert((await page.locator('.dashboard-chart-peak-date').textContent()).includes('11/02')&&(await page.locator('.dashboard-chart-peak-amount').textContent()).includes('100.000'),'Monthly peak column displays the correct payment day and exact revenue automatically');
    assert(await page.locator('.dashboard-chart-value-amount').count()===2&&(await page.locator('.dashboard-chart-value-date').allTextContents()).some(label=>label.includes('10/02'))&&(await page.locator('.dashboard-chart-value-amount').allTextContents()).some(label=>label.includes('50.000')),'All positive revenue columns show their dates and exact amounts');
    for (const [metric,count] of [['revenue',2],['tickets',3],['members',0],['orders',2]]) {
      await page.locator(`.stat-card-link[href*="metric=${metric}"]`).click();await page.waitForLoadState('networkidle');
      assert(new URL(page.url()).searchParams.get('month')==='2004-02',`${metric}: card navigation preserves selected period`);
      assert(await page.locator('#statDetailsTable tbody tr').count()===(count||1),`${metric}: detail table displays matching records or empty state`);
      if (count) assert((await page.locator('#statDetailsTable').innerText()).includes(data.tag),`${metric}: paid fixture records are visible`);
      assert(!(await page.locator('body').innerText()).includes('Fatal error'),`${metric}: detail page loads successfully`);
      for (const width of [320,1440]) {await page.setViewportSize({width,height:900});assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),`${metric}: detail table stays inside layout at ${width}px`);}
      await page.getByRole('link',{name:'Về thống kê',exact:true}).click();await page.waitForLoadState('networkidle');
    }
    await page.locator('#statsPeriod').selectOption('day');await page.locator('#statsDate').fill('2004-02-10');
    assert(await page.locator('#statsMonth').isHidden(),'Changing period shows only relevant filter controls');
    await page.locator('#dashboardFilters button[type=submit]').click();await page.waitForLoadState('networkidle');
    const day=await values();assert(day[0].includes('50.000')&&day[1].trim()==='1','Day filter uses payment date and updates KPI values');
    assert(await page.locator('.dashboard-native-chart rect').count()===24,'Daily chart shows 24 hours instead of one oversized column');
    await page.locator('.dashboard-native-chart rect').nth(10).focus();
    assert((await page.locator('#chartReadout').innerText()).includes('10:00')&&(await page.locator('#chartReadout').innerText()).includes('50.000'),'Selecting a column displays its hour and exact revenue');
    for(const width of [375,1440]) {await page.setViewportSize({width,height:900});await page.screenshot({path:path.join(__dirname,`dashboard-daily-${width}.png`),fullPage:true});}
    await page.locator('#statsPeriod').selectOption('range');await page.locator('#statsFrom').fill('2004-02-10');await page.locator('#statsTo').fill('2004-02-11');
    await page.locator('#dashboardFilters button[type=submit]').click();await page.waitForLoadState('networkidle');
    assert((await values())[0].includes('150.000'),'Date range updates total and rankings');
    for(const width of [320,375,430,768,1024,1280,1440,1920]) {
      await page.setViewportSize({width,height:900});
      await page.waitForFunction(()=>{const svg=document.querySelector('.dashboard-native-chart');return Math.abs(svg.viewBox.baseVal.width-Math.max(240,Math.round(svg.getBoundingClientRect().width)))<2;});
      assert(await page.locator('.dashboard-chart').evaluate(el=>el.scrollWidth<=el.clientWidth+1),`Complete chart fits its panel without horizontal scrolling at ${width}px`);
      assert(await page.locator('.dashboard-chart-peak-amount').evaluate(el=>{const label=el.getBoundingClientRect(),chart=el.closest('svg').getBoundingClientRect();return label.left>=chart.left&&label.right<=chart.right&&label.top>=chart.top;}),`Peak revenue label stays visible at ${width}px`);
      assert(await page.locator('.dashboard-chart-value-amount').evaluateAll(elements=>{const boxes=elements.map(el=>el.getBoundingClientRect());return boxes.every((a,i)=>boxes.every((b,j)=>i===j||a.right<=b.left||b.right<=a.left||a.bottom<=b.top||b.bottom<=a.top));}),`Revenue labels do not overlap at ${width}px`);
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1);
      assert(!overflow,`Dashboard filter/ranking layout fits ${width}px`);
    }
    for(const width of [375,1440]) {await page.setViewportSize({width,height:900});await page.evaluate(()=>scrollTo(0,0));await page.screenshot({path:path.join(__dirname,`dashboard-filter-${width}.png`),fullPage:true});}
    await page.goto(`${base}?page=dashboard&period=day&date=2003-01-01`,{waitUntil:'networkidle'});
    assert((await values())[0].trim()==='0 ₫'&&await page.locator('.dashboard-empty').count()===2,'Empty period shows zero and clear empty ranking states');
    await page.goto(`${base}?page=dashboard&period=range&from=2004-02-11&to=2004-02-10`,{waitUntil:'networkidle'});
    assert((await page.locator('[role=alert]').innerText()).includes('Ngày kết thúc'),'Invalid range displays a validation message');
    assert(errors.length===0,'Dashboard filters and chart have no JavaScript errors');
    fs.writeFileSync(path.join(__dirname,'dashboard-filters-results.json'),JSON.stringify({passed:checks.length,checks},null,2));console.log(`PASS: ${checks.length} dashboard browser checks`);
  }finally{if(browser)await browser.close();execFileSync(php,[fixture,'cleanup']);}
})().catch(e=>{console.error(e.message);process.exitCode=1;});
