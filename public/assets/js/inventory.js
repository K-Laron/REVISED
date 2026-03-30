document.addEventListener('DOMContentLoaded', () => {
  bindInventoryPage();
});

function bindInventoryPage() {
  const page = document.getElementById('inventory-page');
  if (!page) return;

  const formatters = window.CatarmanInventoryFormatters;
  const inventoryRender = window.CatarmanInventoryRender;
  const rawData = document.getElementById('inventory-page-data')?.textContent || '{}';
  const pageData = JSON.parse(rawData);
  const csrfToken = pageData.csrfToken || '';

  const state = {
    items: [],
    selectedItemId: null,
    activeCategoryId: ''
  };

  const filterForm = document.getElementById('inventory-filter-form');
  const tableBody = document.getElementById('inventory-table-body');
  const alertBar = document.getElementById('inventory-alert-bar');
  const drawer = document.getElementById('inventory-detail-drawer');
  const drawerTitle = document.getElementById('inventory-detail-title');
  const drawerSubtitle = document.getElementById('inventory-detail-subtitle');
  const drawerBody = document.getElementById('inventory-detail-body');

  const itemModal = document.getElementById('inventory-item-modal');
  const itemModalTitle = document.getElementById('inventory-item-modal-title');
  const itemForm = document.getElementById('inventory-item-form');
  const deleteButton = document.getElementById('inventory-delete-button');
  const quantityField = itemForm.elements.quantity_on_hand;
  const quantityHelp = document.getElementById('inventory-quantity-help');

  const stockModal = document.getElementById('inventory-stock-modal');
  const stockModalTitle = document.getElementById('inventory-stock-modal-title');
  const stockModalSubtitle = document.getElementById('inventory-stock-modal-subtitle');
  const stockForm = document.getElementById('inventory-stock-form');

  const categoryModal = document.getElementById('inventory-category-modal');
  const categoryForm = document.getElementById('inventory-category-form');
  const itemQueryKey = 'item';

  document.querySelectorAll('#inventory-category-tabs [data-category-id]').forEach((button) => {
    button.addEventListener('click', () => {
      state.activeCategoryId = button.dataset.categoryId || '';
      document.querySelectorAll('#inventory-category-tabs [data-category-id]').forEach((node) => {
        node.classList.toggle('is-active', node === button);
        node.setAttribute('aria-selected', node === button ? 'true' : 'false');
      });
      document.getElementById('inventory-items-panel')?.setAttribute('aria-labelledby', button.id);
      loadItems();
    });
  });

  filterForm.addEventListener('input', loadItems);
  filterForm.addEventListener('submit', (event) => {
    event.preventDefault();
    loadItems();
  });
  filterForm.addEventListener('reset', () => {
    state.activeCategoryId = '';
    document.querySelectorAll('#inventory-category-tabs [data-category-id]').forEach((node) => {
      node.classList.toggle('is-active', node.dataset.categoryId === '');
      node.setAttribute('aria-selected', node.dataset.categoryId === '' ? 'true' : 'false');
    });
    document.getElementById('inventory-items-panel')?.setAttribute('aria-labelledby', 'inventory-category-all');
    setTimeout(loadItems, 0);
  });

  document.querySelector('[data-open-item-modal]')?.addEventListener('click', () => openItemModal());
  document.querySelector('[data-open-category-modal]')?.addEventListener('click', () => {
    categoryForm.reset();
    categoryModal.hidden = false;
    categoryModal.setAttribute('aria-hidden', 'false');
  });

  document.querySelectorAll('[data-close-item-modal]').forEach((button) => {
    button.addEventListener('click', () => {
      itemModal.hidden = true;
      itemModal.setAttribute('aria-hidden', 'true');
    });
  });
  document.querySelectorAll('[data-close-stock-modal]').forEach((button) => {
    button.addEventListener('click', () => {
      stockModal.hidden = true;
      stockModal.setAttribute('aria-hidden', 'true');
    });
  });
  document.querySelectorAll('[data-close-category-modal]').forEach((button) => {
    button.addEventListener('click', () => {
      categoryModal.hidden = true;
      categoryModal.setAttribute('aria-hidden', 'true');
    });
  });
  document.querySelectorAll('[data-close-inventory-drawer]').forEach((button) => {
    button.addEventListener('click', closeDrawer);
  });
  drawer?.addEventListener('click', (event) => {
    if (event.target === drawer) {
      closeDrawer();
    }
  });
  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    if (!itemModal.hidden || !stockModal.hidden || !categoryModal.hidden) return;
    if (!drawer.hidden) {
      closeDrawer();
    }
  });
  window.addEventListener('popstate', () => {
    const itemId = currentDrawerItemId();
    if (itemId === null) {
      closeDrawer(false);
      return;
    }

    if (Number(state.selectedItemId) === itemId && !drawer.hidden) {
      return;
    }

    openDrawerById(itemId, false);
  });

  itemForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const itemId = itemForm.elements.id.value;
    const method = itemId ? 'PUT' : 'POST';
    const endpoint = itemId ? `/api/inventory/${itemId}` : '/api/inventory';
    const formData = new FormData(itemForm);

    if (itemId) {
      formData.delete('quantity_on_hand');
    }

    const response = await fetch(endpoint, {
      method,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-CSRF-TOKEN': csrfToken
      },
      body: new URLSearchParams(formData).toString()
    });
    const result = await response.json();

    if (!response.ok) {
      window.toast?.error('Item save failed', formatters.extractError(result));
      return;
    }

    window.toast?.success('Inventory item saved', result.message);
    itemModal.hidden = true;
    await loadStats();
    await loadAlerts();
    await loadItems();
    if (result.data?.id) {
      openDrawerById(result.data.id);
    }
  });

  deleteButton.addEventListener('click', async () => {
    const itemId = itemForm.elements.id.value;
    if (!itemId) return;

    const response = await fetch(`/api/inventory/${itemId}`, {
      method: 'DELETE',
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken
      }
    });
    const result = await response.json();

    if (!response.ok) {
      window.toast?.error('Delete failed', formatters.extractError(result));
      return;
    }

    window.toast?.success('Inventory item deleted', result.message);
    itemModal.hidden = true;
    closeDrawer();
    await loadStats();
    await loadAlerts();
    await loadItems();
  });

  stockForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const itemId = stockForm.elements.item_id.value;
    const action = stockForm.elements.action.value;
    const endpoint = `/api/inventory/${itemId}/${action}`;

    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-CSRF-TOKEN': csrfToken
      },
      body: new URLSearchParams(new FormData(stockForm)).toString()
    });
    const result = await response.json();

    if (!response.ok) {
      window.toast?.error('Stock update failed', formatters.extractError(result));
      return;
    }

    window.toast?.success('Stock updated', result.message);
    stockModal.hidden = true;
    await loadStats();
    await loadAlerts();
    await loadItems();
    openDrawerById(itemId);
  });

  categoryForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const response = await fetch('/api/inventory/categories', {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-CSRF-TOKEN': csrfToken
      },
      body: new URLSearchParams(new FormData(categoryForm)).toString()
    });
    const result = await response.json();

    if (!response.ok) {
      window.toast?.error('Category save failed', formatters.extractError(result));
      return;
    }

    window.toast?.success('Category saved', result.message);
    categoryModal.hidden = true;
    window.CatarmanApp?.reload?.() || window.location.reload();
  });

  document.querySelectorAll('[data-alert-filter]').forEach((button) => {
    button.addEventListener('click', () => {
      filterForm.elements.status.value = button.dataset.alertFilter || '';
      loadItems();
    });
  });

  loadStats();
  loadAlerts();
  loadItems().then(() => {
    const initialItemId = currentDrawerItemId();
    if (initialItemId !== null) {
      openDrawerById(initialItemId, false);
    }
  });

  async function loadStats() {
    const response = await fetch('/api/inventory/stats', {
      headers: { Accept: 'application/json' }
    });
    const result = await response.json();
    if (!response.ok) return;

    document.getElementById('inventory-stat-total-items').textContent = result.data.total_items ?? 0;
    document.getElementById('inventory-stat-total-units').textContent = formatters.formatNumber(result.data.total_units ?? 0);
    document.getElementById('inventory-stat-low-stock').textContent = result.data.low_stock_count ?? 0;
    document.getElementById('inventory-stat-expiring').textContent = result.data.expiring_count ?? 0;
    document.getElementById('inventory-stat-value').textContent = formatters.currency(result.data.estimated_value ?? 0);
  }

  async function loadAlerts() {
    const response = await fetch('/api/inventory/alerts', {
      headers: { Accept: 'application/json' }
    });
    const result = await response.json();
    if (!response.ok) return;

    const lowStockCount = Array.isArray(result.data?.low_stock) ? result.data.low_stock.length : 0;
    const expiringCount = Array.isArray(result.data?.expiring) ? result.data.expiring.length : 0;

    document.getElementById('inventory-alert-low-stock-count').textContent = lowStockCount;
    document.getElementById('inventory-alert-expiring-count').textContent = expiringCount;
    alertBar.hidden = lowStockCount === 0 && expiringCount === 0;
  }

  async function loadItems() {
    const params = new URLSearchParams(new FormData(filterForm));
    params.set('per_page', '50');
    if (state.activeCategoryId) {
      params.set('category_id', state.activeCategoryId);
    }

    const response = await fetch('/api/inventory?' + params.toString(), {
      headers: { Accept: 'application/json' }
    });
    const result = await response.json();

    if (!response.ok) {
      window.toast?.error('Inventory load failed', formatters.extractError(result));
      return;
    }

    state.items = Array.isArray(result.data) ? result.data : [];
    renderItems();

    if (state.selectedItemId !== null) {
      const selected = state.items.find((item) => Number(item.id) === Number(state.selectedItemId));
      if (selected) {
        openDrawerById(selected.id, false);
      } else {
        closeDrawer();
      }
    }
  }

  function renderItems() {
    tableBody.innerHTML = '';

    if (state.items.length === 0) {
      tableBody.innerHTML = inventoryRender.renderEmptyStateRow();
      return;
    }

    state.items.forEach((item) => {
      const row = document.createElement('tr');
      row.dataset.state = formatters.itemState(item);
      row.innerHTML = inventoryRender.renderTableRow(item);

      row.addEventListener('click', (event) => {
        if (event.target.closest('button')) return;
        openDrawerById(item.id);
      });

      row.querySelector('[data-action="stock-in"]').addEventListener('click', (event) => {
        event.stopPropagation();
        openStockModal(item, 'stock-in');
      });
      row.querySelector('[data-action="stock-out"]').addEventListener('click', (event) => {
        event.stopPropagation();
        openStockModal(item, 'stock-out');
      });
      row.querySelector('[data-action="view"]').addEventListener('click', (event) => {
        event.stopPropagation();
        openDrawerById(item.id);
      });

      tableBody.appendChild(row);
    });
  }

  async function openDrawerById(itemId, syncHistory = true) {
    const response = await fetch(`/api/inventory/${itemId}`, {
      headers: { Accept: 'application/json' }
    });
    const result = await response.json();

    if (!response.ok) {
      window.toast?.error('Item load failed', formatters.extractError(result));
      return;
    }

    const item = result.data;
    state.selectedItemId = Number(item.id);
    if (syncHistory) {
      syncDrawerHistory(state.selectedItemId);
    }
    drawer.hidden = false;
    drawer.setAttribute('aria-hidden', 'false');
    drawerTitle.textContent = item.name || 'Inventory Item';
    drawerSubtitle.textContent = `${item.sku} - ${item.category_name || 'Uncategorized'} - ${item.unit_of_measure || ''}`;
    drawerBody.innerHTML = inventoryRender.renderDrawer(item, csrfToken);
    bindDrawerActions(item);
  }

  function bindDrawerActions(item) {
    drawerBody.querySelector('[data-open-edit-item]')?.addEventListener('click', () => openItemModal(item));
    drawerBody.querySelector('[data-open-stock-in]')?.addEventListener('click', () => openStockModal(item, 'stock-in'));
    drawerBody.querySelector('[data-open-stock-out]')?.addEventListener('click', () => openStockModal(item, 'stock-out'));

    drawerBody.querySelector('[data-adjust-form]')?.addEventListener('submit', async (event) => {
      event.preventDefault();
      const form = event.currentTarget;

      const response = await fetch(`/api/inventory/${item.id}/adjust`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          'X-CSRF-TOKEN': csrfToken
        },
        body: new URLSearchParams(new FormData(form)).toString()
      });
      const result = await response.json();

      if (!response.ok) {
        window.toast?.error('Adjustment failed', formatters.extractError(result));
        return;
      }

      window.toast?.success('Inventory adjusted', result.message);
      await loadStats();
      await loadAlerts();
      await loadItems();
      openDrawerById(item.id);
    });
  }

  function openItemModal(item = null) {
    itemForm.reset();
    itemForm.elements._token.value = csrfToken;
    itemForm.elements.id.value = item?.id || '';
    itemForm.elements.sku.value = item?.sku || '';
    itemForm.elements.name.value = item?.name || '';
    itemForm.elements.category_id.value = item?.category_id || '';
    itemForm.elements.unit_of_measure.value = item?.unit_of_measure || 'pcs';
    itemForm.elements.cost_per_unit.value = item?.cost_per_unit || '';
    itemForm.elements.reorder_level.value = item?.reorder_level ?? 0;
    itemForm.elements.quantity_on_hand.value = item?.quantity_on_hand ?? 0;
    itemForm.elements.storage_location.value = item?.storage_location || '';
    itemForm.elements.supplier_name.value = item?.supplier_name || '';
    itemForm.elements.supplier_contact.value = item?.supplier_contact || '';
    itemForm.elements.expiry_date.value = item?.expiry_date || '';
    itemForm.elements.is_active.value = item?.is_active ? '1' : '0';

    itemModalTitle.textContent = item ? `Edit ${item.name}` : 'Add Inventory Item';
    deleteButton.hidden = !item;
    quantityField.disabled = !!item;
    quantityHelp.textContent = item
      ? 'Quantity changes are recorded through stock-in, stock-out, or adjustment.'
      : 'Used only when creating a new item.';
    itemModal.hidden = false;
    itemModal.setAttribute('aria-hidden', 'false');
  }

  function openStockModal(item, action) {
    stockForm.reset();
    stockForm.elements._token.value = csrfToken;
    stockForm.elements.item_id.value = item.id;
    stockForm.elements.action.value = action;
    stockForm.elements.reason.value = action === 'stock-in' ? 'purchase' : 'usage';
    stockModalTitle.textContent = action === 'stock-in' ? `Stock In: ${item.name}` : `Stock Out: ${item.name}`;
    stockModalSubtitle.textContent = `${item.sku} - On hand: ${formatters.formatNumber(item.quantity_on_hand)} ${item.unit_of_measure}`;
    stockModal.hidden = false;
    stockModal.setAttribute('aria-hidden', 'false');
  }

  function closeDrawer(syncHistory = true) {
    state.selectedItemId = null;
    if (syncHistory) {
      syncDrawerHistory(null, true);
    }
    drawer.hidden = true;
    drawer.setAttribute('aria-hidden', 'true');
  }

  function currentDrawerItemId() {
    const value = new URL(window.location.href).searchParams.get(itemQueryKey);
    if (!value) {
      return null;
    }

    const itemId = Number(value);
    return Number.isFinite(itemId) && itemId > 0 ? itemId : null;
  }

  function syncDrawerHistory(itemId, replace = false) {
    const url = new URL(window.location.href);
    const currentItemId = currentDrawerItemId();

    if (itemId === null) {
      if (currentItemId === null) {
        return;
      }
      url.searchParams.delete(itemQueryKey);
    } else {
      if (currentItemId === itemId) {
        return;
      }
      url.searchParams.set(itemQueryKey, String(itemId));
    }

    window.history[replace ? 'replaceState' : 'pushState'](
      { inventoryItemId: itemId },
      '',
      url
    );
  }
}
