(() => {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const toastRegion = document.querySelector('#toast-region');
  const toast = (message, error = false) => {
    if (!toastRegion) return;
    const node = document.createElement('div'); node.className = `toast-message${error ? ' error' : ''}`; node.textContent = message; toastRegion.appendChild(node);
    window.setTimeout(() => node.remove(), 3600);
  };
  const post = async (action, data) => {
    const body = new FormData(); body.append('action', action); body.append('csrf_token', csrf); Object.entries(data || {}).forEach(([key, value]) => body.append(key, value == null ? '' : String(value)));
    const response = await fetch('api/index.php', { method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const payload = await response.json().catch(() => ({ success: false, message: 'Something went wrong. Please try again.' }));
    if (!response.ok || !payload.success) throw new Error(payload.message || 'Unable to save. Please try again.');
    return payload;
  };
  const backdrop = document.querySelector('[data-drawer-backdrop]');
  const transactionDrawer = document.querySelector('[data-transaction-drawer]');
  const categoryDrawer = document.querySelector('[data-category-drawer]');
  const closeDrawers = () => { [transactionDrawer, categoryDrawer].forEach((drawer) => drawer?.classList.remove('open')); backdrop?.classList.remove('open'); };
  const openDrawer = (drawer) => { drawer?.classList.add('open'); backdrop?.classList.add('open'); drawer?.querySelector('input:not([type="hidden"])')?.focus(); };
  document.querySelectorAll('[data-open-transaction]').forEach((button) => button.addEventListener('click', () => {
    const type = button.dataset.openTransaction || 'expense'; const form = document.querySelector('[data-transaction-form]'); if (!form) return;
    form.reset(); form.querySelector('[name="type"]').value = type; form.querySelector('[name="transaction_date"]').value = new Date().toISOString().slice(0, 10);
    document.querySelector('[data-drawer-eyebrow]').textContent = type === 'income' ? 'MONEY IN' : 'MONEY OUT'; document.querySelector('[data-drawer-title]').textContent = type === 'income' ? 'How much came in?' : 'What did you spend?'; document.querySelector('[data-submit-transaction]').textContent = type === 'income' ? 'Add money' : 'Record expense';
    form.querySelectorAll('[data-category-select] option').forEach((option) => { option.hidden = option.value !== '' && option.dataset.type !== type; }); openDrawer(transactionDrawer);
  }));
  document.querySelectorAll('[data-close-drawer],[data-close-category]').forEach((button) => button.addEventListener('click', closeDrawers)); backdrop?.addEventListener('click', closeDrawers);
  document.querySelectorAll('[data-quick-amount]').forEach((button) => button.addEventListener('click', () => { const amount = document.querySelector('[data-transaction-form] [name="amount"]'); if (amount) amount.value = button.dataset.quickAmount; }));
  document.querySelector('[data-transaction-form]')?.addEventListener('submit', async (event) => { event.preventDefault(); const form = event.currentTarget; const submit = form.querySelector('[data-submit-transaction]'); submit.disabled = true; try { const data = Object.fromEntries(new FormData(form).entries()); await post('create_transaction', data); toast(data.type === 'income' ? 'Money added successfully.' : 'Expense recorded.'); window.setTimeout(() => window.location.reload(), 550); } catch (error) { toast(error.message, true); submit.disabled = false; } });
  const categoryForm = document.querySelector('[data-category-form]');
  document.querySelectorAll('[data-open-category]').forEach((button) => button.addEventListener('click', () => { categoryForm?.reset(); if (categoryForm) { categoryForm.querySelector('[name="type"]').value = button.dataset.openCategory || 'income'; categoryForm.querySelector('[name="icon"]').value = '💰'; categoryForm.querySelector('[name="color"]').value = '#6366F1'; categoryForm.querySelector('[name="id"]').value = ''; } document.querySelector('[data-preview-name]').textContent = 'New category'; document.querySelector('[data-preview-icon]').textContent = '💰'; openDrawer(categoryDrawer); }));
  document.querySelectorAll('[data-icon]').forEach((button) => button.addEventListener('click', () => { document.querySelectorAll('[data-icon]').forEach((item) => item.classList.remove('selected')); button.classList.add('selected'); categoryForm.querySelector('[name="icon"]').value = button.dataset.icon; document.querySelector('[data-preview-icon]').textContent = button.dataset.icon; }));
  document.querySelectorAll('[data-color]').forEach((button) => button.addEventListener('click', () => { document.querySelectorAll('[data-color]').forEach((item) => item.classList.remove('selected')); button.classList.add('selected'); categoryForm.querySelector('[name="color"]').value = button.dataset.color; }));
  categoryForm?.querySelector('[name="name"]')?.addEventListener('input', (event) => { document.querySelector('[data-preview-name]').textContent = event.target.value || 'New category'; });
  categoryForm?.querySelector('[name="type"]')?.addEventListener('change', (event) => { document.querySelector('[data-preview-type]').textContent = event.target.value === 'income' ? 'Income source' : 'Expense category'; });
  categoryForm?.addEventListener('submit', async (event) => { event.preventDefault(); const data = Object.fromEntries(new FormData(categoryForm).entries()); const action = data.id ? 'update_category' : 'create_category'; try { await post(action, data); toast(action === 'create_category' ? 'Category created.' : 'Category updated.'); window.setTimeout(() => window.location.reload(), 550); } catch (error) { toast(error.message, true); } });
  document.querySelector('[data-profile-form]')?.addEventListener('submit', async (event) => { event.preventDefault(); try { await post('update_profile', Object.fromEntries(new FormData(event.currentTarget).entries())); toast('Profile updated.'); } catch (error) { toast(error.message, true); } });
  document.querySelector('[data-password-form]')?.addEventListener('submit', async (event) => { event.preventDefault(); try { await post('change_password', Object.fromEntries(new FormData(event.currentTarget).entries())); event.currentTarget.reset(); toast('Password updated.'); } catch (error) { toast(error.message, true); } });
  const search = document.querySelector('[data-activity-search]'); const typeSelect = document.querySelector('[data-activity-type]'); const filterRows = () => { const q = (search?.value || '').toLowerCase(); const type = typeSelect?.value || 'all'; document.querySelectorAll('[data-activity-row]').forEach((row) => { row.hidden = (type !== 'all' && row.dataset.type !== type) || (q && !row.dataset.search.includes(q)); }); }; search?.addEventListener('input', filterRows); typeSelect?.addEventListener('change', filterRows);
  const flow = document.querySelector('#flow-chart'); if (flow && window.Chart) new Chart(flow, { type: 'doughnut', data: { labels: ['Came in', 'Went out'], datasets: [{ data: [Number(flow.dataset.income), Number(flow.dataset.expense)], backgroundColor: ['#22c55e', '#f05252'], borderWidth: 0 }] }, options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { legend: { labels: { color: '#9aa4b2', usePointStyle: true, padding: 20 } } } } });
})();