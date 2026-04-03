function apiRequest(url, options = {}) {
  return window.CatarmanApi.request(url, options);
}

function extractError(payload) {
  return window.CatarmanApi.extractError(payload);
}

function escapeHtml(value) {
  return window.CatarmanDom.escapeHtml(value);
}

function currency(value) {
  return window.CatarmanFormatters.currency(value);
}

document.addEventListener('DOMContentLoaded', () => {
  bindBillingTabs();
  bindBillingDashboard();
  bindInvoiceCreate();
  bindInvoiceShow();
});

function bindBillingTabs() {
  document.querySelectorAll('[data-billing-tab]').forEach((button) => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-billing-tab]').forEach((node) => node.classList.remove('is-active'));
      document.querySelectorAll('#billing-tab-invoices, #billing-tab-payments, #billing-tab-fees').forEach((panel) => panel.classList.remove('is-active'));
      button.classList.add('is-active');
      document.getElementById('billing-tab-' + button.dataset.billingTab).classList.add('is-active');
    });
  });
}

function bindBillingDashboard() {
  const page = document.getElementById('billing-page');
  if (!page) return;

  const data = JSON.parse(document.getElementById('billing-page-data')?.textContent || '{}');
  const csrfToken = data.csrfToken || '';
  const feeForm = document.getElementById('billing-fee-form');
  const feeReset = document.getElementById('billing-fee-reset');

  document.getElementById('billing-invoice-filter-form')?.addEventListener('input', loadInvoices);
  document.getElementById('billing-payment-filter-form')?.addEventListener('input', loadPayments);

  feeForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = new FormData(feeForm);
    const id = formData.get('id');
    const endpoint = id ? `/api/billing/fee-schedule/${id}` : '/api/billing/fee-schedule';
    const method = id ? 'PUT' : 'POST';

    const { data: result } = await apiRequest(endpoint, {
      method,
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      csrfToken,
      body: new URLSearchParams(formData).toString()
    });

    if (result.error) {
      window.toast?.error('Fee save failed', extractError(result));
      return;
    }

    window.toast?.success('Fee saved', result.message);
    resetFeeForm();
    await loadFees();
  });

  feeReset?.addEventListener('click', resetFeeForm);

  loadStats();
  loadInvoices();
  loadPayments();
  loadFees();

  async function loadStats() {
    const { ok, data: result } = await apiRequest('/api/billing/stats');
    if (!ok) return;

    document.getElementById('billing-stat-revenue').textContent = currency(result.data.total_revenue_month);
    document.getElementById('billing-stat-outstanding').textContent = currency(result.data.outstanding_balance);
    document.getElementById('billing-stat-paid-today').textContent = currency(result.data.paid_today);
    document.getElementById('billing-stat-overdue').textContent = currency(result.data.overdue_balance);
    document.getElementById('billing-stat-outstanding-count').textContent = result.data.outstanding_count || 0;
    document.getElementById('billing-stat-overdue-count').textContent = result.data.overdue_count || 0;
  }

  async function loadInvoices() {
    const form = document.getElementById('billing-invoice-filter-form');
    const params = new URLSearchParams(new FormData(form));
    params.set('per_page', '20');
    const { ok, data: result } = await apiRequest('/api/billing/invoices?' + params.toString());
    if (!ok) return;

    const tbody = document.getElementById('billing-invoice-table-body');
    tbody.innerHTML = '';

    const invoices = Array.isArray(result.data) ? result.data : [];

    invoices.forEach((invoice) => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td><strong>${escapeHtml(invoice.invoice_number)}</strong><br><span class="text-muted">${escapeHtml(invoice.issue_date)}</span></td>
        <td>${escapeHtml(invoice.payor_name)}</td>
        <td>${currency(invoice.total_amount)}</td>
        <td>${currency(invoice.balance_due)}</td>
        <td>${escapeHtml(String(invoice.payment_status).toUpperCase())}</td>
        <td><a href="/billing/invoices/${invoice.id}">Open</a></td>
      `;
      tbody.appendChild(row);
    });
  }

  async function loadPayments() {
    const form = document.getElementById('billing-payment-filter-form');
    const params = new URLSearchParams(new FormData(form));
    params.set('per_page', '20');
    const { ok, data: result } = await apiRequest('/api/billing/payments?' + params.toString());
    if (!ok) return;

    const tbody = document.getElementById('billing-payment-table-body');
    tbody.innerHTML = '';

    const payments = Array.isArray(result.data) ? result.data : [];

    payments.forEach((payment) => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td><strong>${escapeHtml(payment.payment_number)}</strong><br><span class="text-muted">${escapeHtml(payment.payment_date)}</span></td>
        <td>${escapeHtml(payment.invoice_number)}</td>
        <td>${escapeHtml(payment.payor_name)}</td>
        <td>${currency(payment.amount)}</td>
        <td>${escapeHtml(payment.payment_method)}</td>
        <td><a href="/api/billing/payments/${payment.id}/receipt">Receipt</a></td>
      `;
      tbody.appendChild(row);
    });
  }

  async function loadFees() {
    const { ok, data: result } = await apiRequest('/api/billing/fee-schedule');
    if (!ok) return;

    const tbody = document.getElementById('billing-fee-table-body');
    tbody.innerHTML = '';

    const fees = Array.isArray(result.data) ? result.data : [];

    fees.forEach((fee) => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${escapeHtml(fee.category)}</td>
        <td>${escapeHtml(fee.name)}</td>
        <td>${currency(fee.amount)}</td>
        <td>${fee.is_active ? 'Yes' : 'No'}</td>
        <td><button class="btn-secondary" type="button">Edit</button></td>
      `;
      row.querySelector('button').addEventListener('click', () => fillFeeForm(fee));
      tbody.appendChild(row);
    });
  }

  function fillFeeForm(fee) {
    feeForm.elements.id.value = fee.id;
    feeForm.elements.category.value = fee.category || '';
    feeForm.elements.name.value = fee.name || '';
    feeForm.elements.amount.value = fee.amount || '';
    feeForm.elements.effective_from.value = fee.effective_from || '';
    feeForm.elements.effective_to.value = fee.effective_to || '';
    feeForm.elements.species_filter.value = fee.species_filter || '';
    feeForm.elements.is_per_day.value = fee.is_per_day ? '1' : '0';
    feeForm.elements.is_active.value = fee.is_active ? '1' : '0';
    feeForm.elements.description.value = fee.description || '';
    document.getElementById('billing-fee-form-title').textContent = `Edit ${fee.name}`;
  }

  function resetFeeForm() {
    feeForm.reset();
    feeForm.elements._token.value = csrfToken;
    feeForm.elements.id.value = '';
    feeForm.elements.effective_from.value = new Date().toISOString().slice(0, 10);
    feeForm.elements.is_per_day.value = '0';
    feeForm.elements.is_active.value = '1';
    document.getElementById('billing-fee-form-title').textContent = 'Add Fee Item';
  }
}

function bindInvoiceCreate() {
  const form = document.getElementById('invoice-form');
  if (!form) return;

  const data = JSON.parse(document.getElementById('invoice-create-data')?.textContent || '{}');
  const csrfToken = data.csrfToken || '';
  const fees = Array.isArray(data.fees) ? data.fees : [];
  const container = document.getElementById('invoice-line-items');
  const addButton = document.getElementById('invoice-add-line-item');

  addButton.addEventListener('click', () => addLineItemRow());
  addLineItemRow();

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const { data: result } = await apiRequest('/api/billing/invoices', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      csrfToken,
      body: new URLSearchParams(new FormData(form)).toString()
    });

    if (result.error) {
      window.toast?.error('Invoice create failed', extractError(result));
      return;
    }

    window.toast?.success('Invoice created', result.message);
    window.CatarmanApp?.navigate?.(result.data.redirect) || (window.location.href = result.data.redirect);
  });

  function addLineItemRow() {
    const index = container.children.length;
    const row = document.createElement('div');
    row.className = 'billing-line-item';
    row.innerHTML = `
      <label class="field">
        <span class="field-label">Fee</span>
        <select class="select" name="line_items[${index}][fee_schedule_id]" data-fee-select>
          <option value="">Custom</option>
          ${fees.map((fee) => `<option value="${fee.id}" data-description="${escapeHtml(fee.name)}" data-amount="${fee.amount}">${escapeHtml(fee.category)} · ${escapeHtml(fee.name)} (${currency(fee.amount)})</option>`).join('')}
        </select>
      </label>
      <label class="field">
        <span class="field-label">Description</span>
        <input class="input" type="text" name="line_items[${index}][description]" required>
      </label>
      <label class="field">
        <span class="field-label">Quantity</span>
        <input class="input" type="number" min="1" step="1" value="1" name="line_items[${index}][quantity]" required>
      </label>
      <label class="field">
        <span class="field-label">Unit Price</span>
        <input class="input" type="number" min="0.01" step="0.01" name="line_items[${index}][unit_price]" required>
      </label>
      <div class="cluster" style="align-self:end;">
        <button class="btn-secondary" type="button" data-remove-line>Remove</button>
      </div>
    `;

    row.querySelector('[data-fee-select]').addEventListener('change', (event) => {
      const option = event.target.selectedOptions[0];
      if (!option || !option.value) return;
      row.querySelector(`input[name="line_items[${index}][description]"]`).value = option.dataset.description || '';
      row.querySelector(`input[name="line_items[${index}][unit_price]"]`).value = option.dataset.amount || '';
      updateTotals();
    });

    row.querySelectorAll('input').forEach((input) => input.addEventListener('input', updateTotals));
    row.querySelector('[data-remove-line]').addEventListener('click', () => {
      row.remove();
      updateTotals();
    });

    container.appendChild(row);
    updateTotals();
  }

  function updateTotals() {
    let subtotal = 0;
    container.querySelectorAll('.billing-line-item').forEach((row) => {
      const qty = Number(row.querySelector('input[name*="[quantity]"]').value || 0);
      const price = Number(row.querySelector('input[name*="[unit_price]"]').value || 0);
      subtotal += qty * price;
    });

    document.getElementById('invoice-subtotal').textContent = currency(subtotal);
    document.getElementById('invoice-total').textContent = currency(subtotal);
  }
}

function bindInvoiceShow() {
  const page = document.getElementById('invoice-show-page');
  if (!page) return;

  const invoiceId = page.dataset.invoiceId;
  const paymentForm = document.getElementById('invoice-payment-form');
  const voidForm = document.getElementById('invoice-void-form');
  const csrfToken = paymentForm?.elements._token.value || '';

  paymentForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const { data: result } = await apiRequest(`/api/billing/invoices/${invoiceId}/payments`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      csrfToken,
      body: new URLSearchParams(new FormData(paymentForm)).toString()
    });

    if (result.error) {
      window.toast?.error('Payment failed', extractError(result));
      return;
    }

    window.toast?.success('Payment recorded', result.message);
    window.CatarmanApp?.reload?.() || window.location.reload();
  });

  voidForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const { data: result } = await apiRequest(`/api/billing/invoices/${invoiceId}/void`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      csrfToken,
      body: new URLSearchParams(new FormData(voidForm)).toString()
    });

    if (result.error) {
      window.toast?.error('Void failed', extractError(result));
      return;
    }

    window.toast?.success('Invoice voided', result.message);
    window.CatarmanApp?.reload?.() || window.location.reload();
  });
}
