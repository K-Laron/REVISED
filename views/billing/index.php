<section class="page-title" id="billing-page">
    <div class="page-title-meta">
        <h1>Billing &amp; Invoicing</h1>
        <div class="breadcrumb">Home &gt; Billing</div>
        <p class="text-muted">Track invoices, payments, outstanding balances, and the fee schedule in one place.</p>
    </div>
    <div class="cluster">
        <?php if (($can ?? static fn (): bool => false)('billing.create')): ?>
            <a class="btn-primary" href="/billing/invoices/create">Create Invoice</a>
        <?php endif; ?>
    </div>
</section>

<section class="billing-stats-grid">
    <article class="card billing-stat-card">
        <span class="field-label">Revenue This Month</span>
        <strong id="billing-stat-revenue">PHP 0.00</strong>
        <small class="text-muted">Fully paid invoices this month</small>
    </article>
    <article class="card billing-stat-card">
        <span class="field-label">Outstanding</span>
        <strong id="billing-stat-outstanding">PHP 0.00</strong>
        <small class="text-muted"><span id="billing-stat-outstanding-count">0</span> open invoices</small>
    </article>
    <article class="card billing-stat-card">
        <span class="field-label">Paid Today</span>
        <strong id="billing-stat-paid-today">PHP 0.00</strong>
        <small class="text-muted">Payments posted today</small>
    </article>
    <article class="card billing-stat-card">
        <span class="field-label">Overdue</span>
        <strong id="billing-stat-overdue">PHP 0.00</strong>
        <small class="text-muted"><span id="billing-stat-overdue-count">0</span> overdue invoices</small>
    </article>
</section>

<section class="card stack">
    <div class="billing-tabs">
        <button class="tab-button is-active" type="button" data-billing-tab="invoices">Invoices</button>
        <button class="tab-button" type="button" data-billing-tab="payments">Payments</button>
        <button class="tab-button" type="button" data-billing-tab="fees">Fee Schedule</button>
    </div>

    <div class="tab-panel is-active" id="billing-tab-invoices">
        <form class="billing-filter-grid" id="billing-invoice-filter-form">
            <label class="field billing-filter-span-2">
                <span class="field-label">Search</span>
                <input class="input" type="search" name="search" placeholder="Invoice number or payor">
            </label>
            <label class="field">
                <span class="field-label">Status</span>
                <select class="select" name="payment_status">
                    <option value="">All</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="partial">Partial</option>
                    <option value="paid">Paid</option>
                    <option value="void">Void</option>
                </select>
            </label>
            <label class="field">
                <span class="field-label">Date From</span>
                <input class="input" type="date" name="date_from">
            </label>
            <label class="field">
                <span class="field-label">Date To</span>
                <input class="input" type="date" name="date_to">
            </label>
            <div class="cluster" style="align-self: end;">
                <button class="btn-secondary" type="reset">Reset</button>
            </div>
        </form>

        <div class="billing-table-wrap">
            <table class="billing-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Payor</th>
                        <th>Total</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="billing-invoice-table-body"></tbody>
            </table>
        </div>
    </div>

    <div class="tab-panel" id="billing-tab-payments">
        <form class="billing-filter-grid" id="billing-payment-filter-form">
            <label class="field billing-filter-span-2">
                <span class="field-label">Search</span>
                <input class="input" type="search" name="search" placeholder="Payment number, invoice, or payor">
            </label>
            <label class="field">
                <span class="field-label">Method</span>
                <select class="select" name="payment_method">
                    <option value="">All</option>
                    <option value="Cash">Cash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="GCash">GCash</option>
                    <option value="Maya">Maya</option>
                    <option value="Check">Check</option>
                </select>
            </label>
            <label class="field">
                <span class="field-label">Date From</span>
                <input class="input" type="date" name="date_from">
            </label>
            <label class="field">
                <span class="field-label">Date To</span>
                <input class="input" type="date" name="date_to">
            </label>
        </form>

        <div class="billing-table-wrap">
            <table class="billing-table">
                <thead>
                    <tr>
                        <th>Payment</th>
                        <th>Invoice</th>
                        <th>Payor</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Receipt</th>
                    </tr>
                </thead>
                <tbody id="billing-payment-table-body"></tbody>
            </table>
        </div>
    </div>

    <div class="tab-panel" id="billing-tab-fees">
        <div class="billing-fee-layout">
            <form class="card stack" id="billing-fee-form">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" value="">
                <div class="cluster" style="justify-content: space-between;">
                    <h3 id="billing-fee-form-title">Add Fee Item</h3>
                    <button class="btn-secondary" type="button" id="billing-fee-reset">Clear</button>
                </div>
                <label class="field">
                    <span class="field-label">Category</span>
                    <input class="input" type="text" name="category" required>
                </label>
                <label class="field">
                    <span class="field-label">Name</span>
                    <input class="input" type="text" name="name" required>
                </label>
                <label class="field">
                    <span class="field-label">Amount</span>
                    <input class="input" type="number" step="0.01" min="0.01" name="amount" required>
                </label>
                <label class="field">
                    <span class="field-label">Effective From</span>
                    <input class="input" type="date" name="effective_from" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
                </label>
                <label class="field">
                    <span class="field-label">Effective To</span>
                    <input class="input" type="date" name="effective_to">
                </label>
                <label class="field">
                    <span class="field-label">Species Filter</span>
                    <select class="select" name="species_filter">
                        <option value="">Any</option>
                        <option value="Dog">Dog</option>
                        <option value="Cat">Cat</option>
                    </select>
                </label>
                <label class="field">
                    <span class="field-label">Per Day</span>
                    <select class="select" name="is_per_day">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                </label>
                <label class="field">
                    <span class="field-label">Active</span>
                    <select class="select" name="is_active">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </label>
                <label class="field billing-filter-span-2">
                    <span class="field-label">Description</span>
                    <textarea class="textarea" name="description" rows="3"></textarea>
                </label>
                <button class="btn-primary" type="submit">Save Fee</button>
            </form>

            <div class="card stack">
                <h3>Fee Schedule</h3>
                <div class="billing-table-wrap">
                    <table class="billing-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Name</th>
                                <th>Amount</th>
                                <th>Active</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="billing-fee-table-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<script id="billing-page-data" type="application/json"><?= json_encode([
    'csrfToken' => $csrfToken,
    'fees' => $fees,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
