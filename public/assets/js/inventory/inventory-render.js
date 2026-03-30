(function () {
  if (window.CatarmanInventoryRender) {
    return;
  }

  const formatters = window.CatarmanInventoryFormatters;

  function renderBadges(item) {
    const badges = [];
    badges.push(`<span class="inventory-badge" data-tone="neutral">${formatters.escapeHtml(item.is_active ? 'Active' : 'Inactive')}</span>`);

    if (item.is_low_stock) {
      badges.push('<span class="inventory-badge" data-tone="warning">Low Stock</span>');
    }

    if (item.is_expiring) {
      const tone = item.expiry_date && new Date(item.expiry_date) <= formatters.addDays(new Date(), 7) ? 'danger' : 'warning';
      badges.push(`<span class="inventory-badge" data-tone="${tone}">Expiring</span>`);
    }

    return `<div class="inventory-status-badges">${badges.join('')}</div>`;
  }

  function renderEmptyStateRow() {
    return `
      <tr>
        <td colspan="7">
          <div class="inventory-empty-state">No inventory items matched the current filters.</div>
        </td>
      </tr>
    `;
  }

  function renderTableRow(item) {
    return `
      <td><strong>${formatters.escapeHtml(item.sku)}</strong></td>
      <td>
        <div class="inventory-row-main">
          <strong>${formatters.escapeHtml(item.name)}</strong>
          <span class="text-muted">${formatters.escapeHtml(item.category_name || 'Uncategorized')}</span>
        </div>
      </td>
      <td><strong>${formatters.formatNumber(item.quantity_on_hand)}</strong><br><span class="text-muted">Reorder ${formatters.formatNumber(item.reorder_level)}</span></td>
      <td>${formatters.escapeHtml(item.unit_of_measure || '-')}</td>
      <td>${formatters.formatDate(item.expiry_date, 'No expiry')}</td>
      <td>${renderBadges(item)}</td>
      <td>
        <div class="inventory-action-group">
          <button class="btn-secondary" type="button" data-action="stock-in" aria-label="Stock in ${formatters.escapeHtml(item.name)}">+</button>
          <button class="btn-secondary" type="button" data-action="stock-out" aria-label="Stock out ${formatters.escapeHtml(item.name)}">-</button>
          <button class="btn-secondary" type="button" data-action="view">View</button>
        </div>
      </td>
    `;
  }

  function renderDrawer(item, csrfToken) {
    const transactions = Array.isArray(item.transactions) ? item.transactions : [];

    return `
      <div class="inventory-detail-summary">
        <div class="inventory-detail-stat">
          <span class="field-label">Quantity On Hand</span>
          <strong>${formatters.formatNumber(item.quantity_on_hand)} ${formatters.escapeHtml(item.unit_of_measure || '')}</strong>
        </div>
        <div class="inventory-detail-stat">
          <span class="field-label">Reorder Level</span>
          <strong>${formatters.formatNumber(item.reorder_level)}</strong>
        </div>
        <div class="inventory-detail-stat">
          <span class="field-label">Storage Location</span>
          <strong>${formatters.escapeHtml(item.storage_location || 'Not set')}</strong>
        </div>
        <div class="inventory-detail-stat">
          <span class="field-label">Supplier</span>
          <strong>${formatters.escapeHtml(item.supplier_name || 'Not set')}</strong>
        </div>
        <div class="inventory-detail-stat">
          <span class="field-label">Expiry Date</span>
          <strong>${formatters.formatDate(item.expiry_date, 'No expiry')}</strong>
        </div>
        <div class="inventory-detail-stat">
          <span class="field-label">Unit Cost</span>
          <strong>${formatters.currency(item.cost_per_unit || 0)}</strong>
        </div>
      </div>

      <div class="inventory-detail-block">
        <div class="cluster" style="justify-content: space-between;">
          <h4>Stock Actions</h4>
          <button class="btn-secondary" type="button" data-open-edit-item>Edit Item</button>
        </div>
        <div class="cluster">
          <button class="btn-primary" type="button" data-open-stock-in>Quick Stock In</button>
          <button class="btn-secondary" type="button" data-open-stock-out>Quick Stock Out</button>
        </div>
      </div>

      <div class="inventory-detail-block">
        <h4>Adjust Count</h4>
        <form class="inventory-inline-form" data-adjust-form>
          <input type="hidden" name="_token" value="${formatters.escapeHtml(csrfToken)}">
          <label class="field">
            <span class="field-label">Actual Quantity</span>
            <input class="input" type="number" min="0" max="100000" name="quantity" value="${formatters.escapeHtml(String(item.quantity_on_hand || 0))}" required>
          </label>
          <label class="field">
            <span class="field-label">Reason</span>
            <select class="select" name="reason" required>
              <option value="count_correction">count_correction</option>
              <option value="transfer">transfer</option>
              <option value="wastage">wastage</option>
              <option value="usage">usage</option>
            </select>
          </label>
          <label class="field inventory-form-span-2">
            <span class="field-label">Notes</span>
            <textarea class="textarea" name="notes" rows="3" placeholder="Explain the adjustment"></textarea>
          </label>
          <button class="btn-primary inventory-form-span-2" type="submit">Apply Adjustment</button>
        </form>
      </div>

      <div class="inventory-detail-block">
        <h4>Recent Transactions</h4>
        <div class="inventory-history-list">
          ${transactions.length
            ? transactions.map((transaction) => `
                <div class="inventory-history-entry">
                  <strong>${formatters.escapeHtml(formatters.formatTransactionType(transaction.transaction_type))} - ${formatters.escapeHtml(formatters.signedQuantity(transaction.quantity))}</strong>
                  <span class="text-muted">${formatters.escapeHtml(transaction.transacted_at || transaction.created_at || '')}</span>
                  <span>${formatters.escapeHtml(transaction.reason || 'No reason provided')}</span>
                  <span class="text-muted">Before ${formatters.formatNumber(transaction.quantity_before)} / After ${formatters.formatNumber(transaction.quantity_after)}</span>
                  ${transaction.notes ? `<span>${formatters.escapeHtml(transaction.notes)}</span>` : ''}
                </div>
              `).join('')
            : '<div class="inventory-empty-state">No stock transactions recorded yet.</div>'}
        </div>
      </div>
    `;
  }

  window.CatarmanInventoryRender = {
    renderDrawer,
    renderEmptyStateRow,
    renderTableRow
  };
})();
