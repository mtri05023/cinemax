(() => {
  const config = window.checkoutTimer;
  if (!config) return;
  let deadline = Date.now() + Number(config.remaining_seconds) * 1000;
  let checking = false;
  let expired = false;
  const stateUrl = new URL('index.php', window.location.href);
  stateUrl.search = new URLSearchParams({ page: 'payment', action: 'checkout_state', hold_token: config.hold_token, bill_id: config.bill_id });
  async function check() {
    if (checking || expired) return;
    checking = true;
    try {
      const response = await fetch(stateUrl, { cache: 'no-store' });
      const state = await response.json();
      if (state.paid) {
        window.location.replace(`index.php?page=booking_success&bill_id=${Number(config.bill_id)}`);
        return;
      }
      if (!state.active) {
        expired = true;
        document.querySelectorAll('#paymentForm button, #finalForm button').forEach(button => button.disabled = true);
        window.location.replace(config.show_id ? `index.php?page=seat_selection&show_id=${Number(config.show_id)}&expired=1` : 'index.php?page=movies&expired=1');
        return;
      }
      deadline = Date.now() + Number(state.remaining_seconds) * 1000;
    } catch (error) {
      // Never extend a deadline on network failure. The server also rejects late POSTs.
      if (Date.now() >= deadline) document.querySelectorAll('#paymentForm button, #finalForm button').forEach(button => button.disabled = true);
    } finally { checking = false; }
  }
  function tick() {
    const seconds = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
    document.getElementById('checkoutCountdown').textContent = `${String(Math.floor(seconds / 60)).padStart(2,'0')}:${String(seconds % 60).padStart(2,'0')}`;
    if (seconds === 0) check();
  }
  document.querySelectorAll('#paymentForm, #finalForm').forEach(form => form.addEventListener('submit', event => {
    if (expired || Date.now() >= deadline) { event.preventDefault(); check(); }
  }));
  document.addEventListener('visibilitychange', () => { if (!document.hidden) check(); });
  tick(); setInterval(tick, 1000); setInterval(check, 10000);
})();
