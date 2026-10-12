(() => {
  const sidebar = document.getElementById('adminSidebar');
  const toggle = document.getElementById('adminMenuToggle');
  const close = document.getElementById('adminMenuClose');
  const backdrop = document.getElementById('adminMenuBackdrop');
  const main = document.querySelector('.admin-main');
  if (!sidebar || !toggle || !close || !backdrop || !main) return;
  main.querySelectorAll('.table-responsive, .editor-area').forEach(region => {
    region.tabIndex = 0;
    region.setAttribute('role', 'region');
    if (!region.hasAttribute('aria-label')) region.setAttribute('aria-label',
      region.classList.contains('editor-area') ? 'Sơ đồ ghế, có thể cuộn ngang' : 'Bảng quản trị, có thể cuộn ngang');
  });
  const mobile = window.matchMedia('(max-width: 1199px)');
  let opened = false;
  function setOpen(value, restoreFocus = true) {
    opened = value && mobile.matches;
    sidebar.classList.toggle('active', opened);
    toggle.setAttribute('aria-expanded', String(opened));
    backdrop.hidden = !opened;
    document.body.classList.toggle('admin-menu-open', opened);
    main.inert = opened;
    sidebar.inert = mobile.matches && !opened;
    if (opened) close.focus();
    else if (restoreFocus && mobile.matches) toggle.focus();
  }
  toggle.addEventListener('click', () => setOpen(!opened));
  close.addEventListener('click', () => setOpen(false));
  backdrop.addEventListener('click', () => setOpen(false));
  sidebar.addEventListener('click', event => {
    if (opened && event.target.closest('a')) setOpen(false);
  });
  document.addEventListener('keydown', event => {
    if (!opened) return;
    if (event.key === 'Escape') setOpen(false);
    if (event.key !== 'Tab') return;
    const items = [...sidebar.querySelectorAll('a[href], button, input, select, textarea, [tabindex="0"]')]
      .filter(item => !item.disabled && item.getClientRects().length);
    const first = items[0], last = items[items.length - 1];
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
  });
  mobile.addEventListener('change', () => {
    const focusedSidebar = sidebar.contains(document.activeElement);
    setOpen(false, false);
    if (focusedSidebar) {
      if (mobile.matches) toggle.focus();
      else sidebar.querySelector('a[href]')?.focus();
    }
  });
  setOpen(false, false);
})();
