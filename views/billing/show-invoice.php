<section class="page-title" id="invoice-show-page" data-invoice-id="<?= (int) $invoice['id'] ?>">
    <div class="page-title-meta">
        <h1><?= htmlspecialchars($invoice['invoice_number'], ENT_QUOTES, 'UTF-8') ?></h1>
        <div class="breadcrumb">Home &gt; Billing &gt; <?= htmlspecialchars($invoice['invoice_number'], ENT_QUOTES, 'UTF-8') ?></div>
        <p class="text-muted">Invoice detail, payment history, and downloadable PDF.</p>
    </div>
    <div class="cluster">
        <a class="btn-secondary" href="/api/billing/invoices/<?= (int) $invoice['id'] ?>/pdf">Download PDF</a>
        <a class="btn-secondary" href="/billing">Back to Billing</a>
    </div>
</section>

<section class="billing-show-grid">
    <article class="card stack">
        <div class="cluster" style="justify-content: space-between;">
            <h3>Invoice Summary</h3>
            <span class="badge <?= htmlspecialchars($invoice['payment_status'] === 'paid' ? 'badge-success' : ($invoice['payment_status'] === 'partial' ? 'badge-warning' : ($invoice['payment_status'] === 'void' ? 'badge-danger' : 'badge-info')), ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars(strtoupper((string) $invoice['payment_status']), ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>
        <dl class="detail-grid">
            <div><dt>Payor</dt><dd><?= htmlspecialchars($invoice['payor_name'], ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Issue Date</dt><dd><?= htmlspecialchars($invoice['issue_date'], ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Due Date</dt><dd><?= htmlspecialchars($invoice['due_date'], ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Contact</dt><dd><?= htmlspecialchars((string) ($invoice['payor_contact'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div class="animal-detail-span-2"><dt>Address</dt><dd><?= htmlspecialchars((string) ($invoice['payor_address'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></dd></div>
        </dl>

        <div class="billing-table-wrap">
            <table class="billing-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoice['line_items'] as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) $item['quantity'] ?></td>
                            <td><?= htmlspecialchars(number_format((float) $item['unit_price'], 2), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(number_format((float) $item['total_price'], 2), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="billing-total-card">
            <div class="billing-total-row"><span>Subtotal</span><strong>PHP <?= htmlspecialchars(number_format((float) $invoice['subtotal'], 2), ENT_QUOTES, 'UTF-8') ?></strong></div>
            <div class="billing-total-row"><span>Total</span><strong>PHP <?= htmlspecialchars(number_format((float) $invoice['total_amount'], 2), ENT_QUOTES, 'UTF-8') ?></strong></div>
            <div class="billing-total-row"><span>Paid</span><strong>PHP <?= htmlspecialchars(number_format((float) $invoice['amount_paid'], 2), ENT_QUOTES, 'UTF-8') ?></strong></div>
            <div class="billing-total-row is-grand"><span>Balance Due</span><strong>PHP <?= htmlspecialchars(number_format((float) $invoice['balance_due'], 2), ENT_QUOTES, 'UTF-8') ?></strong></div>
        </div>
    </article>

    <article class="card stack">
        <h3>Record Payment</h3>
        <form class="stack" id="invoice-payment-form">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <label class="field">
                <span class="field-label">Amount</span>
                <input class="input" type="number" min="0.01" step="0.01" name="amount" value="<?= htmlspecialchars(number_format((float) $invoice['balance_due'], 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="field">
                <span class="field-label">Payment Method</span>
                <select class="select" name="payment_method">
                    <option value="Cash">Cash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="GCash">GCash</option>
                    <option value="Maya">Maya</option>
                    <option value="Check">Check</option>
                </select>
            </label>
            <label class="field">
                <span class="field-label">Reference Number</span>
                <input class="input" type="text" name="reference_number">
            </label>
            <label class="field">
                <span class="field-label">Payment Date</span>
                <input class="input" type="datetime-local" name="payment_date" value="<?= htmlspecialchars(date('Y-m-d\TH:i'), ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="field">
                <span class="field-label">Notes</span>
                <textarea class="textarea" name="notes" rows="3"></textarea>
            </label>
            <button class="btn-primary" type="submit">Record Payment</button>
        </form>

        <h3>Void Invoice</h3>
        <form class="stack" id="invoice-void-form">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <label class="field">
                <span class="field-label">Reason</span>
                <textarea class="textarea" name="voided_reason" rows="3" placeholder="Required if the invoice should be voided"></textarea>
            </label>
            <button class="btn-secondary" type="submit">Void Invoice</button>
        </form>
    </article>
</section>

<section class="card stack">
    <h3>Payment History</h3>
    <div class="billing-table-wrap">
        <table class="billing-table">
            <thead>
                <tr>
                    <th>Payment #</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Date</th>
                    <th>Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($invoice['payments'] === []): ?>
                    <tr><td colspan="5" class="text-muted">No payments recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($invoice['payments'] as $payment): ?>
                        <tr>
                            <td><?= htmlspecialchars($payment['payment_number'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>PHP <?= htmlspecialchars(number_format((float) $payment['amount'], 2), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($payment['payment_method'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($payment['payment_date'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><a href="/api/billing/payments/<?= (int) $payment['id'] ?>/receipt">Receipt</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
