<section class="page-title">
    <div class="page-title-meta">
        <h1>Create Invoice</h1>
        <div class="breadcrumb">Home &gt; Billing &gt; Create Invoice</div>
        <p class="text-muted">Build line items, calculate totals automatically, and issue a printable invoice.</p>
    </div>
    <div class="cluster">
        <a class="btn-secondary" href="/billing">Back to Billing</a>
    </div>
</section>

<form class="card stack" id="invoice-form">
    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <div class="billing-form-grid">
        <label class="field">
            <span class="field-label field-label-required">Payor Type</span>
            <select class="select" name="payor_type" required>
                <option value="adopter">Adopter</option>
                <option value="owner">Owner</option>
                <option value="external">External</option>
            </select>
        </label>
        <label class="field">
            <span class="field-label field-label-required">Payor Name</span>
            <input class="input" type="text" name="payor_name" required>
        </label>
        <label class="field">
            <span class="field-label">Contact</span>
            <input class="input" type="text" name="payor_contact" placeholder="09XXXXXXXXX">
        </label>
        <label class="field">
            <span class="field-label field-label-required">Due Date</span>
            <input class="input" type="date" name="due_date" value="<?= htmlspecialchars(date('Y-m-d', strtotime('+7 days')), ENT_QUOTES, 'UTF-8') ?>" required>
        </label>
        <label class="field billing-form-span-2">
            <span class="field-label">Payor Address</span>
            <input class="input" type="text" name="payor_address">
        </label>
        <label class="field">
            <span class="field-label">Associated Animal ID</span>
            <input class="input" type="number" name="animal_id" min="1">
        </label>
        <label class="field">
            <span class="field-label">Application ID</span>
            <input class="input" type="number" name="application_id" min="1">
        </label>
        <label class="field billing-form-span-2">
            <span class="field-label">Notes</span>
            <textarea class="textarea" name="notes" rows="3"></textarea>
        </label>
        <label class="field billing-form-span-2">
            <span class="field-label">Terms</span>
            <textarea class="textarea" name="terms" rows="3">Payment is due on or before the due date.</textarea>
        </label>
    </div>

    <div class="cluster" style="justify-content: space-between;">
        <h3>Line Items</h3>
        <button class="btn-secondary" type="button" id="invoice-add-line-item">Add Line Item</button>
    </div>

    <div class="billing-line-items" id="invoice-line-items"></div>

    <div class="billing-total-card">
        <div class="billing-total-row"><span>Subtotal</span><strong id="invoice-subtotal">PHP 0.00</strong></div>
        <div class="billing-total-row"><span>Tax</span><strong>PHP 0.00</strong></div>
        <div class="billing-total-row is-grand"><span>Total</span><strong id="invoice-total">PHP 0.00</strong></div>
    </div>

    <div class="cluster">
        <button class="btn-primary" type="submit">Create Invoice</button>
        <a class="btn-secondary" href="/billing">Cancel</a>
    </div>
</form>

<script id="invoice-create-data" type="application/json"><?= json_encode([
    'csrfToken' => $csrfToken,
    'fees' => $fees,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
