const { chromium } = require('playwright');
const { execFileSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '../..');
const base = 'http://localhost/cinemax/cinemax/views/admin/index.php';
// Read existing local data only; do not create fixtures or submit admin forms.
const auth = JSON.parse(execFileSync('C:/xampp/php/php.exe', ['-r', `
require 'config/dbConfig.php'; require 'config/JwtHelper.php';
$db=getDBConnection();
$user=$db->query("SELECT u.user_id,u.role_id,u.full_name,u.email FROM users u JOIN roles r ON r.role_id=u.role_id WHERE LOWER(r.role_name)='admin' AND u.status=1 LIMIT 1")->fetch_assoc();
if (!$user) throw new RuntimeException('No active local admin for browser checks');
$user['user_id']=(int)$user['user_id']; $user['role_id']=(int)$user['role_id'];
$hall=$db->query('SELECT h.hall_id FROM halls h JOIN cinemas c ON c.cinema_id=h.cinema_id WHERE h.status=1 AND c.status=1 LIMIT 1')->fetch_assoc();
echo json_encode(['token'=>JwtHelper::encode($user,1800),'hall'=> $hall['hall_id']??null]);
`], { cwd: root, encoding: 'utf8' }));
const pages = ['dashboard','movies','shows','halls','cinemas','users','bookings','combos','promotions','genres','roles','permissions','seat_types','seats','404'];
const widths = [320,375,430,768,1024,1280,1440,1920];
const results = [];
const failures = [];
const errors = [];
const resourceFailures = [];
const interactions = [];
const skipped = [];
function check(value, message) { if (!value) failures.push(message); }
async function settled(page) {
  await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
}
async function audit(page, label) {
  const result = await page.evaluate(() => {
    const outside = [];
    for (const el of document.querySelectorAll('.admin-main *')) {
      if (!el.getClientRects().length || el.closest('.modal,.popup')) continue;
      const r = el.getBoundingClientRect();
      let internalScroll = false;
      for (let parent=el.parentElement; parent && !parent.classList.contains('admin-main'); parent=parent.parentElement) {
        if (['auto','scroll'].includes(getComputedStyle(parent).overflowX)) { internalScroll=true; break; }
      }
      if (!internalScroll && (r.right > innerWidth + 1 || r.left < -1)) outside.push({tag:el.tagName,cls:el.className,right:Math.round(r.right),left:Math.round(r.left)});
    }
    const main = document.querySelector('.admin-main').getBoundingClientRect();
    const sidebar = document.querySelector('#adminSidebar');
    return { width: innerWidth, scroll: document.documentElement.scrollWidth, outside:outside.slice(0,12),
      mainLeft: main.left, sidebarRight: sidebar.getBoundingClientRect().right,
      sidebarInert: sidebar.inert, hiddenOverflow: getComputedStyle(document.body).overflowX === 'hidden' || getComputedStyle(document.querySelector('.admin-main')).overflowX === 'hidden' };
  });
  results.push({label,...result});
  check(result.scroll <= result.width+1 && result.outside.length===0, `${label}: overflow ${JSON.stringify(result)}`);
  check(!result.hiddenOverflow, `${label}: layout hides horizontal overflow`);
  if (result.width>=1200) check(result.mainLeft>=result.sidebarRight-1 && !result.sidebarInert, `${label}: desktop sidebar overlaps content`);
  else check(result.mainLeft===0 && result.sidebarInert, `${label}: drawer default or main width incorrect`);
}
async function auditModals(page, section) {
  // Open dynamic edit/create dialogs through their existing, read-only UI handlers.
  const selectors = {
    cinemas:'[onclick^="editCinema("]', halls:'[onclick^="editHall("]', seat_types:'[onclick^="editSeatType("]',
    seats:'[onclick="openCreateSeatModal()"], [onclick="openAutoCreateModal()"]',
    bookings:'[onclick="openBillModal(this)"]', users:'[onclick="openUpdateUserModal(this)"]',
    roles:'[onclick="openUpdateRoleModal(this)"]', permissions:'[onclick="openUpdatePermissionModal(this)"]',
    promotions:'[onclick="openUpdatePromoModal(this)"]', movies:'[onclick^="openEditMovieModal("]',
    combos:'a[href*="page=combos&edit_id="]', genres:'[onclick="openEditGenreModal(this)"]'
  };
  if (selectors[section]) {
    const buttons=page.locator(selectors[section]);
    const count=await buttons.count();
    if (!count) skipped.push(`${section}: no existing record with an editable/viewable action`);
    for(let i=0;i<Math.min(count,section==='seats'?2:1);i++) {
      await buttons.nth(i).click();
      await page.locator('.modal.active').first().waitFor({timeout:10000});
      interactions.push(`${section}: existing dialog handler opens`);
      await page.evaluate(()=>document.querySelectorAll('.modal.active').forEach(el=>el.classList.remove('active')));
    }
  }
  const ids=await page.locator('.modal').evaluateAll(nodes=>nodes.map(el=>el.id));
  const cdp=await page.context().newCDPSession(page);
  for(const id of ids) {
    // Include every static and dynamically generated dialog, without submitting forms.
    await page.evaluate(id=>document.getElementById(id).classList.add('active'),id);
    for(const zoom of [0.8,1,1.25]) for(const width of widths) {
      await cdp.send('Emulation.setDeviceMetricsOverride',{width:Math.round(width/zoom),height:Math.round(720/zoom),deviceScaleFactor:zoom,mobile:false}); await settled(page);
      const result=await page.locator('#'+id+' .modal-content').evaluate(el=>{
        const r=el.getBoundingClientRect();
        return {left:r.left,right:r.right,top:r.top,bottom:r.bottom,viewport:innerWidth,height:innerHeight,scroll:el.scrollWidth,client:el.clientWidth};
      });
      check(result.left>=-1&&result.right<=result.viewport+1&&result.top>=-1&&result.bottom<=result.height+1&&result.scroll<=result.client+1,
        `${section}/${id}/${width}/zoom-${zoom*100}: modal overflow ${JSON.stringify(result)}`);
      const modal=page.locator('#'+id);
      await modal.evaluate(el=>{const content=el.querySelector('.modal-content');content.scrollTop=content.scrollHeight;});
      const footer=modal.locator('.modal-footer');
      if(await footer.count()) {
        const box=await footer.boundingBox();
        check(box&&box.x>=0&&box.x+box.width<=result.viewport+1&&box.y>=0&&box.y+box.height<=result.height+1,`${section}/${id}/${width}/zoom-${zoom*100}: footer clipped`);
      }
    }
    interactions.push(`${section}/${id}: checked all 8 widths and 3 zoom metrics, footer accessible by scrolling`);
    await page.evaluate(id=>document.getElementById(id).classList.remove('active'),id);
  }
  await cdp.send('Emulation.clearDeviceMetricsOverride');await cdp.detach();await settled(page);
}
(async () => {
  const browser = await chromium.launch({ executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe', headless:true });
  const context = await browser.newContext({ viewport:{width:1440,height:900}, reducedMotion:'reduce' });
  await context.addCookies([{name:'cinemax_token',value:auth.token,url:'http://localhost',httpOnly:true,sameSite:'Lax'}]);
  const page = await context.newPage();
  page.on('pageerror', e => errors.push(`${page.url()}: ${e.message}`));
  page.on('requestfailed', request=>resourceFailures.push({url:request.url(),error:request.failure()?.errorText}));
  try {
    for (const section of pages) {
      await page.goto(`${base}?page=${section}${section==='seats'&&auth.hall?'&hall_id='+auth.hall:''}`,{waitUntil:'networkidle'});
      check(await page.locator('.admin-layout').count()===1, `${section}: admin page loaded`);
      check(!(await page.locator('body').innerText()).includes('Fatal error'), `${section}: PHP error`);
      // Browser zoom changes the CSS viewport and device pixel ratio. Emulate
      // those metrics, rather than CSS zoom (which does not exercise breakpoints).
      const cdp=await context.newCDPSession(page);
      for (const zoom of [0.8,1,1.25]) for (const width of widths) {
        await cdp.send('Emulation.setDeviceMetricsOverride',{width:Math.round(width/zoom),height:Math.round(900/zoom),deviceScaleFactor:zoom,mobile:false});
        await settled(page);
        await audit(page, `${section}/${width}/zoom-${zoom*100}`);
      }
      await cdp.send('Emulation.clearDeviceMetricsOverride'); await cdp.detach();
      await page.setViewportSize({width:375,height:720}); await settled(page);
      await auditModals(page,section);
      if(section==='shows') {
        await page.evaluate(()=>backToTheater());
        if(await page.locator('.theater-btn:not(:disabled)').count()) {
          await page.locator('.theater-btn:not(:disabled)').first().click();
          const showDate=await page.evaluate(()=>window.initialShows.find(show=>Number(show.cinema_id)===Number(selectedCinema))?.show_date);
          if(showDate) {await page.locator('#calendarDate').fill(showDate);await page.locator('#calendarDate').dispatchEvent('change');}
          for(const mode of ['day','week']) {
            await page.locator(`.view-btn[data-view="${mode}"]`).click();
            for(const width of widths) {await page.setViewportSize({width,height:720});await settled(page);await audit(page,`shows/${mode}/${width}`);}
          }
          await page.locator('.view-btn[data-view="day"]').click();
          if(await page.locator('.event').count()) {
            await page.locator('.event').first().click();
            for(const width of widths) {
              await page.setViewportSize({width,height:600});
              const box=await page.locator('#popupEdit').boundingBox();
              check(box&&box.x>=0&&box.y>=0&&box.x+box.width<=width+1&&box.y+box.height<=601,`shows/popupEdit/${width}: overflow`);
            }
            await page.evaluate(()=>closeEditPopup());
          }
          if(await page.locator('.cell').count()) {
            await page.locator('.cell').first().click();
            for(const width of widths) {
              await page.setViewportSize({width,height:600});
              const box=await page.locator('#popupAdd').boundingBox();
              check(box&&box.x>=0&&box.y>=0&&box.x+box.width<=width+1&&box.y+box.height<=601,`shows/popupAdd/${width}: overflow`);
            }
            await page.evaluate(()=>closeAddPopup());
          }
          await page.evaluate(()=>backToTheater());
        } else skipped.push('shows: no active cinemas for calendar interaction');
      }
      // All tables must allow reaching their final column inside the table region.
      const tables=await page.locator('.table-responsive').evaluateAll(nodes=>nodes.map(el=>{
        el.scrollLeft=el.scrollWidth;
        const table=el.querySelector('table');
        return {fits:!table||table.getBoundingClientRect().right<=el.getBoundingClientRect().right+1,focusable:el.tabIndex===0};
      }));
      check(tables.every(t=>t.fits&&t.focusable),`${section}: table final column unreachable`);
      console.log(`${section}: 24 viewport/zoom checks plus dialogs and tables`);
    }
    await page.goto(`${base}?page=dashboard`,{waitUntil:'networkidle'});
    for(const width of [1920,1024,320,1440,430,1280,768,1920]) {
      await page.setViewportSize({width,height:720});await settled(page);
      if(width<1200) {
        await page.locator('#adminMenuToggle').click();
        check(await page.locator('#adminMenuToggle').getAttribute('aria-expanded')==='true',`drawer/${width}: cannot open`);
        const box=await page.locator('#adminSidebar').boundingBox();
        check(box&&box.x===0&&box.width<=width,`drawer/${width}: clipped`);
        await page.keyboard.press('Shift+Tab');
        check(await page.evaluate(()=>document.querySelector('#adminSidebar').contains(document.activeElement)),`drawer/${width}: focus escapes`);
        await page.keyboard.press('Escape');
        check(await page.locator('#adminMenuToggle').getAttribute('aria-expanded')==='false',`drawer/${width}: Escape fails`);
        await page.locator('#adminMenuToggle').click();await page.locator('#adminMenuClose').click();
        await page.locator('#adminMenuToggle').click();
        await page.locator('#adminMenuBackdrop').click({position:{x:width-10,y:300}});
      }
      await audit(page,`continuous-resize/${width}`);
    }
    await page.setViewportSize({width:375,height:720});await settled(page);
    await page.locator('#adminMenuToggle').click();
    const links=await page.locator('#adminSidebar a[href*="index.php?page="]').evaluateAll(nodes=>nodes.map(el=>({href:el.getAttribute('href'),label:el.textContent.trim()})));
    await page.keyboard.press('Escape');
    for(const link of links) {
      await page.locator('#adminMenuToggle').click();
      const item=page.locator('#adminSidebar a').filter({hasText:link.label}).first();
      await item.scrollIntoViewIfNeeded();await item.click();await page.waitForLoadState('networkidle');
      check(page.url().includes(link.href.replace('index.php','')),`sidebar/${link.label}: destination incorrect`);
    }
    interactions.push(`All ${links.length} admin sidebar page links clicked at 375px`);
    for(const section of ['dashboard','movies','seats','shows']) {
      await page.goto(`${base}?page=${section}${section==='seats'&&auth.hall?'&hall_id='+auth.hall:''}`,{waitUntil:'networkidle'});
      for(const width of [375,1440]) {
        await page.setViewportSize({width,height:900}); await settled(page);
        await page.evaluate(()=>scrollTo(0,0)); await settled(page);
        await page.screenshot({path:path.join(__dirname,`admin-${section}-${width}.png`),fullPage:true});
        if(section==='dashboard'&&width===375) {
          await page.locator('#adminMenuToggle').click();
          await page.screenshot({path:path.join(__dirname,'admin-sidebar-375.png')});
          await page.locator('#adminMenuClose').click();
        }
      }
    }
  } finally {
    fs.writeFileSync(path.join(__dirname,'admin-responsive-results.json'),JSON.stringify({zoomMethod:'CDP CSS viewport and device scale emulation; not native browser toolbar zoom',results,failures,errors,resourceFailures,interactions,skipped},null,2));
    await browser.close();
  }
  console.log(JSON.stringify({checks:results.length,failures,errors,interactions,skipped},null,2));
  if (failures.length || errors.length) process.exitCode=1;
})().catch(e=>{console.error(e.message);process.exitCode=1;});
