const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const { execFileSync } = require('node:child_process');
const base = 'http://localhost/cinemax/cinemax';
const php = 'C:/xampp/php/php.exe';
execFileSync(php, [path.join(__dirname, 'fixtures.php'), 'refresh-auth']);
const data = JSON.parse(fs.readFileSync(path.join(__dirname, 'fixture.json'), 'utf8'));
const results = [];
const errors = [];
function assert(value, message) { if (!value) throw new Error(message); results.push(message); }
async function cookieContext(browser, actor) {
  const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
  await context.addCookies([{ name: 'cinemax_token', value: data.actors[actor].token, url: 'http://localhost', httpOnly: true, sameSite: 'Lax' }]);
  return context;
}
async function go(page, section) {
  await page.goto(`${base}/views/admin/index.php?page=${section}`, { waitUntil: 'networkidle' });
  assert(!(await page.locator('body').innerText()).includes('Fatal error'), `${section}: no PHP error`);
}
async function waitFor(test, timeout = 8000) {
  const start = Date.now();
  while (!(await test())) { if (Date.now() - start > timeout) throw new Error('Condition timed out'); await new Promise(r => setTimeout(r, 100)); }
}
(async () => {
  const browser = await chromium.launch({ executablePath: 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe', headless: true });
  try {
    const admin = await cookieContext(browser, 'admin');
    const a = await cookieContext(browser, 'a');
    const b = await cookieContext(browser, 'b');
    const page = await admin.newPage(); page.on('pageerror', error => errors.push(`admin: ${error.message}`));
    await go(page, `halls&cinema_id=${data.closed_cinema_id}`);
    assert(page.url().includes('page=cinemas'), `Closed cinema hall-management URL is blocked (observed ${page.url()})`);
    await waitFor(() => page.locator('#theatersTableBody tr').filter({ hasText: data.tag }).count().then(n => n >= 2));
    let closedRow = page.locator('#theatersTableBody tr').filter({ has: page.locator(`[onclick="openCinema(${data.closed_cinema_id})"]`) });
    assert(await closedRow.locator('a').count() === 0, 'Closed cinema offers only reopen, no edit/manage links');
    const rejected = await admin.request.post(`${base}/public/assets/api/test_api.php?controller=cinemas&action=update&id=${data.closed_cinema_id}`, { form: { name: `${data.tag} wrong`, address: 'wrong', location_id: 1, status_id: 1 } });
    assert((await rejected.json()).success === false, 'Forged update cannot rename a closed cinema');
    page.once('dialog', dialog => dialog.accept());
    await closedRow.getByRole('button', { name: 'Mở rạp' }).click();
    await waitFor(() => page.locator(`[onclick="editCinema(${data.closed_cinema_id}); return false;"]`).count().then(n => n > 0));
    assert(true, 'Reopen cinema succeeds without edit payload');
    await page.locator(`[onclick="editCinema(${data.cinema_id}); return false;"]`).click();
    await page.locator('#editCinemaModal.active').waitFor();
    assert((await page.locator('#editCinemaName').inputValue()) === `${data.tag} Rạp "Đêm" O'Brien`, 'Cinema name with quotes prefilled from DB before modal opens');
    assert((await page.locator('#editCinemaAddress').inputValue()) === `${data.tag} 123 Nguyễn Du`, 'Cinema address prefilled');
    assert((await page.locator('#editLocationId').inputValue()) !== '' && (await page.locator('#editStatusId').inputValue()) === '1', 'Cinema location/status prefilled');
    await page.locator('#editCinemaName').fill(`${data.tag} Rạp đã sửa`);
    await page.locator('#editCinemaForm button[type=submit]').click();
    await waitFor(() => page.locator('#theatersTableBody').innerText().then(text => text.includes(`${data.tag} Rạp đã sửa`)));
    assert(true, 'Cinema edit saves to DB and appears in list');

    await go(page, `halls&cinema_id=${data.cinema_id}`);
    let hallRow = page.locator('tbody tr').filter({ has: page.locator(`[onclick="reopenHall(${data.closed_hall_id})"]`) });
    assert(await hallRow.locator('a').count() === 0, 'Stopped hall offers reopen only');
    page.once('dialog', dialog => dialog.accept());
    await hallRow.getByRole('button', { name: 'Mở phòng' }).click();
    await page.waitForLoadState('networkidle');
    await waitFor(() => page.locator(`[onclick="editHall(${data.closed_hall_id}); return false;"]`).count().then(n => n > 0));
    await page.locator(`[onclick="editHall(${data.closed_hall_id}); return false;"]`).click();
    await page.locator('#editHallModal.active').waitFor();
    assert(await page.locator('#editHallName').inputValue() === `${data.tag} Phòng đóng`, 'Hall name prefilled from DB');
    assert(await page.locator('#editCinemaId').inputValue() === String(data.cinema_id), 'Hall parent cinema prefilled');
    assert(await page.locator('#editStatusId').inputValue() === '1', 'Hall status prefilled');
    await page.locator('#editHallName').fill(`${data.tag} Phòng đã sửa`);
    await page.locator('#editStatusId').selectOption('0');
    await page.locator('#editHallForm button[type=submit]').click();
    await page.waitForLoadState('networkidle');
    await waitFor(() => page.locator(`[onclick="reopenHall(${data.closed_hall_id})"]`).count().then(n => n > 0));
    assert(true, 'Hall edit saves name and status zero correctly');
    const stoppedSeatPage = await admin.request.get(`${base}/views/admin/index.php?page=seats&hall_id=${data.closed_hall_id}`);
    assert((await stoppedSeatPage.text()).includes('Vui lòng mở lại'), 'Stopped hall seat management is blocked');

    await go(page, 'shows');
    assert(await page.locator('#scheduleOverview').isVisible(), 'Schedule overview visible before choosing a cinema');
    assert(await page.getByRole('heading', { name: 'Tạo Lịch Chiếu Rạp', exact: true }).isVisible(), 'Cinema schedule creation heading updated');
    await page.locator('#overviewSearch').fill(data.tag);
    await page.locator('#overviewDate').fill(data.tomorrow);
    assert(await page.locator('#overviewRows tr:visible').count() === 1, 'Overview filters by date and cinema/movie text');
    await page.screenshot({ path: path.join(__dirname, 'schedule.png'), fullPage: true });
    await page.locator('#overviewRows tr:visible button').click();
    assert(await page.locator('#calendarView').isVisible(), 'Overview links into cinema calendar');
    await page.locator('#calendarDate').fill(data.tomorrow);
    assert(await page.locator('.event').count() === 1, 'Day calendar shows the selected date schedule');
    await page.locator('.event-title').click();
    assert(await page.locator('#edit_showId').inputValue() === String(data.show_id), 'Schedule edit opens with DB show ID');
    assert((await page.locator('#edit_price').inputValue()) !== '', 'Schedule edit price is prefilled');
    await page.getByRole('button', { name: 'Huỷ', exact: true }).last().click();
    await page.evaluate(id => { const s = shows.find(s => Number(s.show_id) === id); s.start_time = '09:30:00'; render(); }, data.show_id);
    assert((await page.locator('.event-time').innerText()).includes('09:30'), 'Off-grid start time remains visible in day calendar');
    await page.getByRole('button', { name: 'Tuần', exact: true }).click();
    assert((await page.locator('.event-meta').innerText()).includes('09:30'), 'Off-grid start time remains visible in week calendar');
    await page.screenshot({ path: path.join(__dirname, 'schedule-calendar.png'), fullPage: true });
    await page.getByRole('button', { name: '← Chọn rạp' }).click();
    assert(await page.locator('#scheduleOverview').isVisible(), 'Returning to cinema chooser restores overview');

    for (const [section, functionName, modalId, fields] of [
      ['users', 'openUpdateUserModal', 'updateUserModal', ['update_user_id', 'update_full_name', 'update_email', 'update_role_id']],
      ['roles', 'openUpdateRoleModal', 'updateRoleModal', ['update_role_id', 'update_role_name']],
      ['permissions', 'openUpdatePermissionModal', 'updatePermissionModal', ['update_permission_id', 'update_module', 'update_action']],
      ['genres', 'openEditGenreModal', 'editGenreModal', ['editGenreName']],
      ['movies', 'openEditMovieModal', 'editMovieModal', ['edit_movie_id', 'edit_title', 'edit_duration_min', 'edit_release_date']],
    ]) {
      await go(page, section);
      const button = page.locator(`[onclick*="${functionName}("]`).first();
      await button.click();
      await page.locator(`#${modalId}.active`).waitFor();
      for (const field of fields) assert((await page.locator(`#${field}`).inputValue()) !== '', `${section}: ${field} prefilled`);
    }
    await go(page, 'promotions');
    await page.locator(`[data-promotion-id="${data.promo_id}"][onclick*="openUpdatePromoModal"]`).click();
    await page.locator('#updatePromoModal.active').waitFor();
    for (const field of ['update_promotion_id','update_code','update_name','update_discount_value','update_start_date','update_end_date','update_usage_limit']) assert((await page.locator(`#${field}`).inputValue()) !== '', `promotions: ${field} prefilled`);
    assert(await page.locator('#update_audience').inputValue() === 'new_member' && await page.locator('#update_per_user_limit').inputValue() === '1', 'New-member promotion prefilled with one use per account');

    const customer = await a.newPage(); customer.on('pageerror', error => errors.push(`customer: ${error.message}`));
    const other = await b.newPage(); other.on('pageerror', error => errors.push(`other customer: ${error.message}`));
    await customer.goto(`${base}/views/user/index.php?page=seat_selection&show_id=${data.show_id}`, { waitUntil: 'networkidle' });
    await customer.locator(`.seat[data-ticket-id="${data.ticket_ids[0]}"]`).click();
    await customer.locator('#btnContinue').click();
    await customer.waitForURL('**/*page=food_selection');
    await customer.locator('#checkoutCountdown').waitFor();
    const firstClock = await customer.locator('#checkoutCountdown').innerText();
    assert(/^\d{2}:\d{2}$/.test(firstClock) && firstClock !== '00:00', 'Food selection starts countdown');
    await other.goto(`${base}/views/user/index.php?page=seat_selection&show_id=${data.show_id}`, { waitUntil: 'networkidle' });
    assert(await other.locator(`.seat[data-ticket-id="${data.ticket_ids[0]}"]`).getAttribute('data-status') === 'held', 'Another browser sees reserved seat as held');
    await customer.locator('#finalForm button[type=submit]').click();
    await customer.waitForURL('**/*page=payment');
    await customer.locator('#checkoutCountdown').waitFor();
    assert(await customer.locator('.momo-mark').isVisible() && await customer.locator('.momo-mark').innerText() === 'MoMo', 'MoMo displays without broken remote logo');
    await customer.locator('.method-item').filter({ has: customer.locator('input[value="MOMO"]') }).click();
    await customer.locator('#promotionCode').fill(data.promo_code);
    await customer.locator('#applyPromotionBtn').click();
    assert((await customer.locator('#promotionMessage').innerText()).includes('Đã áp dụng'), 'Eligible user can apply welcome discount');
    const token = await customer.locator('[name=hold_token]').inputValue();
    await customer.reload({ waitUntil: 'networkidle' });
    assert(await customer.locator('[name=hold_token]').inputValue() === token, 'Payment reload retains same hold token');
    await customer.screenshot({ path: path.join(__dirname, 'payment.png'), fullPage: true });
    customer.once('dialog', dialog => dialog.accept());
    await customer.locator('[onclick="submitPayment()"]:visible').click();
    await customer.waitForURL('**/*page=booking_success&bill_id=*');
    assert((await customer.locator('body').innerText()).includes('Đơn hàng đang chờ xác nhận'), 'Payment submit creates pending order');
    assert(await customer.locator('#checkoutCountdown').isVisible(), 'Pending order keeps countdown');
    const details = JSON.parse(execFileSync(php, [path.join(__dirname, 'fixtures.php'), 'inspect'], { encoding: 'utf8' }));
    assert(details.bills.length === 1 && details.bills[0].status === 'pending' && Number(details.bills[0].total_amount) === 50000, 'Exactly one bill created with server price');
    execFileSync(php, [path.join(__dirname, 'fixtures.php'), 'expire'], { encoding: 'utf8' });
    await customer.evaluate(() => document.dispatchEvent(new Event('visibilitychange')));
    await customer.waitForURL('**/*expired=1');
    assert(!(await customer.url()).includes('page=payment'), 'Expired browser exits payment/pending page');
    await other.reload({ waitUntil: 'networkidle' });
    assert(await other.locator(`.seat[data-ticket-id="${data.ticket_ids[0]}"]`).getAttribute('data-status') === 'available', 'Expired seat becomes available to another browser');
    await customer.goto(`${base}/views/user/index.php?page=promotions`, { waitUntil: 'networkidle' });
    assert(await customer.locator('.promotions-page .promo-card').count() > 0, 'Promotion cards render with account eligibility');
    await customer.screenshot({ path: path.join(__dirname, 'promotions.png'), fullPage: true });
    await customer.setViewportSize({ width: 390, height: 844 });
    await customer.screenshot({ path: path.join(__dirname, 'promotions-mobile.png'), fullPage: true });
    assert(await customer.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), 'Promotion layout fits mobile viewport');
    assert(errors.length === 0, `No browser JavaScript errors (${errors.join('; ')})`);
    fs.writeFileSync(path.join(__dirname, 'results.json'), JSON.stringify({ passed: results.length, checks: results, errors }, null, 2));
    console.log(`PASS: ${results.length} browser workflow checks.`);
  } finally { await browser.close(); }
})().catch(error => {
  fs.writeFileSync(path.join(__dirname, 'failure.json'), JSON.stringify({ error: error.message, stack: error.stack, passed: results, browserErrors: errors }, null, 2));
  console.error(error.message);
  process.exitCode = 1;
});
