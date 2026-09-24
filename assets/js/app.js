(() => {
  /* ─── Shared utilities (unchanged) ─── */
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const toastRegion = document.querySelector('#toast-region');
  const toast = (message, error = false) => {
    if (!toastRegion) return;
    const node = document.createElement('div');
    node.className = `toast-message${error ? ' error' : ''}`;
    node.textContent = message;
    toastRegion.appendChild(node);
    window.setTimeout(() => node.remove(), 3600);
  };
  const post = async (action, data) => {
    const body = new FormData();
    body.append('action', action);
    body.append('csrf_token', csrf);
    Object.entries(data || {}).forEach(([key, value]) => body.append(key, value == null ? '' : String(value)));
    const response = await fetch('api/index.php', { method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const payload = await response.json().catch(() => ({ success: false, message: 'Something went wrong. Please try again.' }));
    if (!response.ok || !payload.success) throw new Error(payload.message || 'Unable to save. Please try again.');
    return payload;
  };
  const esc = (str) => { const d = document.createElement('div'); d.textContent = str; return d.innerHTML; };

  /* ─── Drawer management ─── */
  const backdrop = document.querySelector('[data-drawer-backdrop]');
  const transactionDrawer = document.querySelector('[data-transaction-drawer]');
  const categoryDrawer = document.querySelector('[data-category-drawer]');
  const closeDrawers = () => {
    [transactionDrawer, categoryDrawer].forEach(d => d?.classList.remove('open'));
    backdrop?.classList.remove('open');
  };
  const openDrawer = (drawer) => {
    drawer?.classList.add('open');
    backdrop?.classList.add('open');
    drawer?.querySelector('input:not([type="hidden"])')?.focus();
  };

  /* ─── Category data ─── */
  let categories = window.__MT_CATS || [];

  /* ─── Category Card Selector ─── */
  const catSelector = (() => {
    const container = document.querySelector('[data-cat-selector]');
    if (!container) return null;
    const mainPanel = container.querySelector('[data-cat-main]');
    const subPanel = container.querySelector('[data-cat-sub]');
    const chosenPanel = container.querySelector('[data-cat-chosen]');
    const catGrid = container.querySelector('[data-cat-grid]');
    const subcatGrid = container.querySelector('[data-subcat-grid]');
    const input = document.querySelector('[data-category-input]');
    let currentType = 'expense';
    let currentParent = null;

    function createCard(cat) {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'cat-card';
      btn.style.setProperty('--cat-color', cat.color);
      btn.innerHTML = `<span class="cat-card-icon">${esc(cat.icon)}</span><span class="cat-card-name">${esc(cat.name)}</span>`;
      btn.addEventListener('click', () => onCardClick(cat));
      return btn;
    }

    function createAddCard(label) {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'cat-card cat-card-add';
      btn.innerHTML = `<span class="cat-card-icon"><i class="bi bi-plus-lg"></i></span><span class="cat-card-name">${esc(label || 'Create New')}</span>`;
      return btn;
    }

    function showMain() {
      const topLevel = categories.filter(c =>
        c.type === currentType && c.parent_id === null && Number(c.is_active) === 1
      );
      catGrid.innerHTML = '';
      topLevel.forEach(cat => catGrid.appendChild(createCard(cat)));

      const addBtn = createAddCard('Create New');
      addBtn.addEventListener('click', () => openCategoryDrawer(currentType, null));
      catGrid.appendChild(addBtn);

      const label = container.querySelector('[data-cat-label]');
      if (label) label.textContent = currentType === 'income' ? 'Where did this money come from?' : 'What did you spend it on?';
      mainPanel.hidden = false;
      subPanel.hidden = true;
      chosenPanel.hidden = true;
      currentParent = null;
    }

    function showSub(parent) {
      currentParent = parent;
      const children = categories.filter(c =>
        Number(c.parent_id) === Number(parent.id) && Number(c.is_active) === 1
      );
      subcatGrid.innerHTML = '';
      children.forEach(child => subcatGrid.appendChild(createCard(child)));

      /* "Just <parent>" option */
      const justParent = document.createElement('button');
      justParent.type = 'button';
      justParent.className = 'cat-card cat-card-parent-only';
      justParent.style.setProperty('--cat-color', parent.color);
      justParent.innerHTML = `<span class="cat-card-icon">${esc(parent.icon)}</span><span class="cat-card-name">Just "${esc(parent.name)}"</span>`;
      justParent.addEventListener('click', () => select(parent, null));
      subcatGrid.appendChild(justParent);

      /* "+ Add Subcategory" */
      const addSub = createAddCard('Add Sub');
      addSub.addEventListener('click', () => openCategoryDrawer(parent.type, parent));
      subcatGrid.appendChild(addSub);

      container.querySelector('[data-subcat-title]').textContent = parent.name;
      container.querySelector('[data-subcat-parent-icon]').textContent = parent.icon;
      mainPanel.hidden = true;
      subPanel.hidden = false;
      chosenPanel.hidden = true;
    }

    function onCardClick(cat) {
      const children = categories.filter(c =>
        Number(c.parent_id) === Number(cat.id) && Number(c.is_active) === 1
      );
      if (children.length > 0 && cat.parent_id === null) {
        showSub(cat);
      } else {
        const parent = cat.parent_id ? categories.find(c => Number(c.id) === Number(cat.parent_id)) : null;
        select(cat, parent);
      }
    }

    function select(cat, parent) {
      if (input) input.value = cat.id;
      container.querySelector('[data-chosen-icon]').textContent = cat.icon;
      container.querySelector('[data-chosen-name]').textContent = cat.name;
      const pathEl = container.querySelector('[data-chosen-path]');
      if (parent) {
        pathEl.textContent = parent.name + ' › ' + cat.name;
      } else {
        pathEl.textContent = '';
      }
      mainPanel.hidden = true;
      subPanel.hidden = true;
      chosenPanel.hidden = false;
    }

    function reset(type) {
      currentType = type;
      if (input) input.value = '';
      showMain();
    }

    function addCategory(cat) {
      categories.push(cat);
      if (cat.parent_id === null && cat.type === currentType) {
        showMain();
      } else if (currentParent && Number(cat.parent_id) === Number(currentParent.id)) {
        showSub(currentParent);
      }
      /* Auto-select the new category */
      const parent = cat.parent_id ? categories.find(c => Number(c.id) === Number(cat.parent_id)) : null;
      select(cat, parent);
    }

    /* Back button */
    container.querySelector('[data-cat-back]')?.addEventListener('click', () => showMain());
    /* Change button */
    container.querySelector('[data-cat-change]')?.addEventListener('click', () => showMain());

    return { reset, addCategory, showMain };
  })();

  /* ─── Open Category Drawer ─── */
  function openCategoryDrawer(type, parent) {
    const form = document.querySelector('[data-category-form]');
    if (!form) return;
    form.reset();
    form.querySelector('[name="id"]').value = '';
    form.querySelector('[name="parent_id"]').value = parent ? parent.id : '';
    form.querySelector('[name="type"]').value = type || 'income';
    form.querySelector('[name="icon"]').value = '💰';
    form.querySelector('[name="color"]').value = '#6366F1';

    const typeLabel = form.querySelector('[data-cat-type-label]');
    const typeSelect = form.querySelector('[name="type"]');
    const title = document.querySelector('[data-cat-drawer-title]');
    const eyebrow = document.querySelector('[data-cat-drawer-eyebrow]');
    const submitBtn = form.querySelector('[data-cat-submit-btn]');
    const previewName = document.querySelector('[data-preview-name]');
    const previewIcon = document.querySelector('[data-preview-icon]');
    const previewType = document.querySelector('[data-preview-type]');

    document.querySelectorAll('[data-icon]').forEach(b => b.classList.remove('selected'));
    document.querySelectorAll('[data-color]').forEach(b => b.classList.remove('selected'));

    if (parent) {
      /* Subcategory mode */
      title.textContent = 'Add subcategory';
      eyebrow.textContent = parent.name.toUpperCase();
      submitBtn.textContent = 'Create subcategory';
      typeSelect.value = parent.type;
      typeLabel.style.display = 'none';
      previewName.textContent = 'New subcategory';
      previewType.textContent = parent.name;
    } else {
      title.textContent = 'Create a category';
      eyebrow.textContent = 'MAKE IT YOURS';
      submitBtn.textContent = 'Create category';
      typeLabel.style.display = '';
      previewName.textContent = 'New category';
      previewType.textContent = type === 'expense' ? 'Expense category' : 'Income source';
    }
    previewIcon.textContent = '💰';
    openDrawer(categoryDrawer);
  }

  /* ─── Transaction Drawer Open ─── */
  document.querySelectorAll('[data-open-transaction]').forEach(button =>
    button.addEventListener('click', () => {
      const type = button.dataset.openTransaction || 'expense';
      const form = document.querySelector('[data-transaction-form]');
      if (!form) return;
      form.reset();
      form.querySelector('[name="type"]').value = type;
      form.querySelector('[name="transaction_date"]').value = new Date().toISOString().slice(0, 10);
      form.querySelector('[name="category_id"]').value = '';
      document.querySelector('[data-drawer-eyebrow]').textContent = type === 'income' ? 'MONEY IN' : 'MONEY OUT';
      document.querySelector('[data-drawer-title]').textContent = type === 'income' ? 'How much came in?' : 'What did you spend?';
      document.querySelector('[data-submit-transaction]').textContent = type === 'income' ? 'Add money' : 'Record expense';
      if (catSelector) catSelector.reset(type);
      openDrawer(transactionDrawer);
    })
  );

  /* ─── Close drawers ─── */
  document.querySelectorAll('[data-close-drawer],[data-close-category]').forEach(b => b.addEventListener('click', closeDrawers));
  backdrop?.addEventListener('click', closeDrawers);

  /* ─── Quick amounts ─── */
  document.querySelectorAll('[data-quick-amount]').forEach(button =>
    button.addEventListener('click', () => {
      const amount = document.querySelector('[data-transaction-form] [name="amount"]');
      if (amount) amount.value = button.dataset.quickAmount;
    })
  );

  /* ─── Transaction Submit ─── */
  document.querySelector('[data-transaction-form]')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const submit = form.querySelector('[data-submit-transaction]');
    const data = Object.fromEntries(new FormData(form).entries());
    if (!data.category_id) { toast('Please select a category.', true); return; }
    submit.disabled = true;
    try {
      await post('create_transaction', data);
      toast(data.type === 'income' ? 'Money added successfully.' : 'Expense recorded.');
      window.setTimeout(() => window.location.reload(), 550);
    } catch (error) { toast(error.message, true); submit.disabled = false; }
  });

  /* ─── Category Drawer Open (from categories page) ─── */
  document.querySelectorAll('[data-open-category]').forEach(button =>
    button.addEventListener('click', () => {
      const type = button.dataset.openCategory || 'income';
      openCategoryDrawer(type, null);
    })
  );

  /* ─── Add Subcategory (from categories page tree) ─── */
  document.querySelectorAll('[data-add-sub]').forEach(button =>
    button.addEventListener('click', () => {
      try {
        const parent = JSON.parse(button.dataset.addSub);
        openCategoryDrawer(parent.type, parent);
      } catch (e) { /* ignore */ }
    })
  );

  /* ─── Icon picker ─── */
  const categoryForm = document.querySelector('[data-category-form]');
  document.querySelectorAll('[data-icon]').forEach(button =>
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-icon]').forEach(i => i.classList.remove('selected'));
      button.classList.add('selected');
      if (categoryForm) categoryForm.querySelector('[name="icon"]').value = button.dataset.icon;
      const pi = document.querySelector('[data-preview-icon]');
      if (pi) pi.textContent = button.dataset.icon;
    })
  );

  /* ─── Color picker ─── */
  document.querySelectorAll('[data-color]').forEach(button =>
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-color]').forEach(c => c.classList.remove('selected'));
      button.classList.add('selected');
      if (categoryForm) categoryForm.querySelector('[name="color"]').value = button.dataset.color;
    })
  );

  /* ─── Category form live preview ─── */
  categoryForm?.querySelector('[name="name"]')?.addEventListener('input', (e) => {
    const pn = document.querySelector('[data-preview-name]');
    if (pn) pn.textContent = e.target.value || 'New category';
  });
  categoryForm?.querySelector('[name="type"]')?.addEventListener('change', (e) => {
    const pt = document.querySelector('[data-preview-type]');
    if (pt) pt.textContent = e.target.value === 'income' ? 'Income source' : 'Expense category';
  });

  /* ─── Category Form Submit ─── */
  categoryForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const data = Object.fromEntries(new FormData(categoryForm).entries());
    const action = data.id ? 'update_category' : 'create_category';
    const submitBtn = categoryForm.querySelector('[data-cat-submit-btn]');
    if (submitBtn) submitBtn.disabled = true;
    try {
      const result = await post(action, data);
      toast(action === 'create_category' ? 'Category created.' : 'Category updated.');
      if (action === 'create_category' && result.data && catSelector) {
        /* Add to JS data + auto-select in the transaction drawer */
        catSelector.addCategory(result.data);
        closeDrawers();
        /* Re-open only the transaction drawer if we came from there */
        if (transactionDrawer?.classList.contains('open') === false && document.querySelector('[data-cat-selector]')) {
          openDrawer(transactionDrawer);
        }
      } else {
        /* Editing or page-level creation → reload to refresh PHP tree */
        window.setTimeout(() => window.location.reload(), 550);
      }
    } catch (error) {
      toast(error.message, true);
    }
    if (submitBtn) submitBtn.disabled = false;
  });

  /* ─── Edit category (pre-fill drawer) ─── */
  document.querySelectorAll('[data-edit-category]').forEach(button =>
    button.addEventListener('click', () => {
      try {
        const cat = JSON.parse(button.dataset.editCategory);
        const form = document.querySelector('[data-category-form]');
        if (!form) return;
        form.querySelector('[name="id"]').value = cat.id;
        form.querySelector('[name="parent_id"]').value = cat.parent_id || '';
        form.querySelector('[name="name"]').value = cat.name;
        form.querySelector('[name="type"]').value = cat.type;
        form.querySelector('[name="icon"]').value = cat.icon;
        form.querySelector('[name="color"]').value = cat.color;
        const title = document.querySelector('[data-cat-drawer-title]');
        if (title) title.textContent = 'Edit category';
        const eyebrow = document.querySelector('[data-cat-drawer-eyebrow]');
        if (eyebrow) eyebrow.textContent = 'EDITING';
        const submitBtn = form.querySelector('[data-cat-submit-btn]');
        if (submitBtn) submitBtn.textContent = 'Save changes';
        const typeLabel = form.querySelector('[data-cat-type-label]');
        if (typeLabel) typeLabel.style.display = cat.parent_id ? 'none' : '';
        const pn = document.querySelector('[data-preview-name]');
        if (pn) pn.textContent = cat.name;
        const pi = document.querySelector('[data-preview-icon]');
        if (pi) pi.textContent = cat.icon;
        const pt = document.querySelector('[data-preview-type]');
        if (pt) pt.textContent = cat.type === 'income' ? 'Income source' : 'Expense category';
        document.querySelectorAll('[data-icon]').forEach(i => i.classList.toggle('selected', i.dataset.icon === cat.icon));
        document.querySelectorAll('[data-color]').forEach(c => c.classList.toggle('selected', c.dataset.color === cat.color));
        openDrawer(categoryDrawer);
      } catch (e) { /* ignore */ }
    })
  );

  /* ─── Archive category ─── */
  document.querySelectorAll('[data-archive-cat]').forEach(button =>
    button.addEventListener('click', async () => {
      const id = button.dataset.archiveCat;
      if (!id || !confirm('Archive this category? Existing transactions will be kept.')) return;
      try {
        await post('archive_category', { id });
        toast('Category archived.');
        window.setTimeout(() => window.location.reload(), 550);
      } catch (error) { toast(error.message, true); }
    })
  );

  /* ─── Tree toggle (categories management page) ─── */
  document.querySelectorAll('[data-toggle-tree]').forEach(button =>
    button.addEventListener('click', () => {
      const item = button.closest('.cat-tree-item');
      if (!item) return;
      const children = item.querySelector('[data-tree-children]');
      if (!children) return;
      const isHidden = children.hidden;
      children.hidden = !isHidden;
      const icon = button.querySelector('i');
      if (icon) {
        icon.className = isHidden ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
      }
    })
  );

  /* ─── Profile & password forms (unchanged) ─── */
  document.querySelector('[data-profile-form]')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    try { await post('update_profile', Object.fromEntries(new FormData(event.currentTarget).entries())); toast('Profile updated.'); }
    catch (error) { toast(error.message, true); }
  });
  document.querySelector('[data-password-form]')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    try { await post('change_password', Object.fromEntries(new FormData(event.currentTarget).entries())); event.currentTarget.reset(); toast('Password updated.'); }
    catch (error) { toast(error.message, true); }
  });

  /* ─── Activity search & filter (unchanged) ─── */
  const search = document.querySelector('[data-activity-search]');
  const typeSelect = document.querySelector('[data-activity-type]');
  const filterRows = () => {
    const q = (search?.value || '').toLowerCase();
    const type = typeSelect?.value || 'all';
    document.querySelectorAll('[data-activity-row]').forEach(row => {
      row.hidden = (type !== 'all' && row.dataset.type !== type) || (q && !row.dataset.search.includes(q));
    });
  };
  search?.addEventListener('input', filterRows);
  typeSelect?.addEventListener('change', filterRows);

  /* ─── Chart (unchanged) ─── */
  const flow = document.querySelector('#flow-chart');
  if (flow && window.Chart) new Chart(flow, {
    type: 'doughnut',
    data: { labels: ['Came in', 'Went out'], datasets: [{ data: [Number(flow.dataset.income), Number(flow.dataset.expense)], backgroundColor: ['#22c55e', '#f05252'], borderWidth: 0 }] },
    options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { legend: { labels: { color: '#9aa4b2', usePointStyle: true, padding: 20 } } } }
  });
})();