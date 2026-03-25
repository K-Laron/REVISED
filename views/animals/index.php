<section class="page-title" id="animal-list-page" data-can-update="<?= ($can ?? static fn (): bool => false)('animals.update') ? 'true' : 'false' ?>">
    <div class="page-title-meta">
        <h1>Animals</h1>
        <div class="breadcrumb">Home &gt; Animals</div>
        <p class="text-muted">Browse and manage animal intake records with filters, search, and quick actions.</p>
    </div>
    <div class="cluster">
        <button class="btn-secondary" type="button" data-open-scanner aria-label="Open animal QR scanner" aria-haspopup="dialog" aria-controls="qr-scanner-modal">Scan QR</button>
        <?php if (($can ?? static fn (): bool => false)('animals.create')): ?>
            <a class="btn-primary" href="/animals/create">New Intake</a>
        <?php endif; ?>
    </div>
</section>

<section class="card stack">
    <form class="animal-filter-grid" id="animal-filter-form">
        <label class="field animal-filter-search">
            <span class="field-label">Search</span>
            <input class="input" type="search" name="search" placeholder="Name or animal ID">
        </label>
        <label class="field">
            <span class="field-label">Species</span>
            <select class="select" name="species">
                <option value="">All</option>
                <option>Dog</option>
                <option>Cat</option>
                <option>Other</option>
            </select>
        </label>
        <label class="field">
            <span class="field-label">Status</span>
            <select class="select" name="status">
                <option value="">All</option>
                <option>Available</option>
                <option>Under Medical Care</option>
                <option>In Adoption Process</option>
                <option>Adopted</option>
                <option>Deceased</option>
                <option>Transferred</option>
                <option>Quarantine</option>
            </select>
        </label>
        <label class="field">
            <span class="field-label">Gender</span>
            <select class="select" name="gender">
                <option value="">All</option>
                <option>Male</option>
                <option>Female</option>
            </select>
        </label>
        <label class="field">
            <span class="field-label">Size</span>
            <select class="select" name="size">
                <option value="">All</option>
                <option>Small</option>
                <option>Medium</option>
                <option>Large</option>
                <option>Extra Large</option>
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
        <div class="cluster" style="align-self:end;">
            <button class="btn-secondary" type="reset">Reset</button>
        </div>
    </form>

    <div class="animal-table-wrap">
        <table class="animal-table">
            <thead>
                <tr>
                    <th>Animal</th>
                    <th>Species / Breed</th>
                    <th>Gender</th>
                    <th>Status</th>
                    <th>Intake</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="animal-table-body"></tbody>
        </table>
        <div class="animal-card-list" id="animal-card-list"></div>
    </div>

    <div class="cluster" style="justify-content: space-between;">
        <div class="text-muted" id="animal-pagination-summary"></div>
        <div class="cluster" id="animal-pagination-controls"></div>
    </div>
</section>

<div class="scanner-modal" id="qr-scanner-modal" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="qr-scanner-title" aria-describedby="qr-scanner-description">
    <div class="scanner-panel card stack">
        <div class="cluster" style="justify-content: space-between;">
            <div>
                <h3 id="qr-scanner-title">Scan Animal QR</h3>
                <p class="text-muted" id="qr-scanner-description">Use the device camera or paste the QR payload or animal ID manually.</p>
            </div>
            <button class="btn-secondary" type="button" data-close-scanner aria-label="Close animal QR scanner">Close</button>
        </div>
        <div id="qr-reader" class="qr-reader" aria-live="polite"></div>
        <div class="cluster">
            <label class="sr-only" for="manual-qr-value">Manual QR payload or animal ID</label>
            <input class="input" id="manual-qr-value" type="text" placeholder="Paste QR payload or animal ID">
            <button class="btn-primary" type="button" id="manual-qr-submit" aria-label="Open animal from QR or ID">Open</button>
        </div>
    </div>
</div>
