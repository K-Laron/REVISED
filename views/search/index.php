<section class="page-title" id="search-page">
    <div class="page-title-meta">
        <h1>Global Search</h1>
        <div class="breadcrumb">Home &gt; Search</div>
        <p class="text-muted">Cross-module search for records you already have access to.</p>
    </div>
</section>

<section class="search-shell card stack">
    <div class="cluster" style="justify-content: space-between; align-items: end;">
        <div>
            <h3>Search Query</h3>
            <p class="text-muted">Search across animals, adopters, adoptions, billing, inventory, and medical records.</p>
        </div>
        <div class="badge badge-info" id="search-total-badge">Ready</div>
    </div>
    <form class="search-form" id="search-form">
        <div class="search-filter-layout">
            <label class="field search-filter-span-2">
                <span class="field-label">Find Records</span>
                <div class="search-input-row">
                    <input class="input" type="search" name="q" value="<?= htmlspecialchars((string) ($searchQuery ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Try an animal ID, adopter name, invoice number, or SKU" minlength="2" required>
                    <button class="btn-primary" type="submit">Search</button>
                </div>
            </label>
            <label class="field">
                <span class="field-label">Results Per Module</span>
                <select class="input" name="per_section">
                    <?php $selectedPerSection = (int) ($searchFilters['per_section'] ?? 5); ?>
                    <?php foreach ([3, 5, 10] as $size): ?>
                        <option value="<?= $size ?>" <?= $selectedPerSection === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span class="field-label">Date From</span>
                <input class="input" type="date" name="date_from" value="<?= htmlspecialchars((string) ($searchFilters['date_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="field">
                <span class="field-label">Date To</span>
                <input class="input" type="date" name="date_to" value="<?= htmlspecialchars((string) ($searchFilters['date_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
        </div>
        <fieldset class="search-module-filters">
            <legend class="field-label">Modules</legend>
            <div class="search-module-list">
                <?php $selectedModules = array_values(array_filter((array) ($searchFilters['modules'] ?? []))); ?>
                <?php foreach (($availableSearchModules ?? []) as $module): ?>
                    <?php
                        $moduleKey = (string) ($module['key'] ?? '');
                        $isSelected = $selectedModules === [] || in_array($moduleKey, $selectedModules, true);
                    ?>
                    <label class="search-module-chip">
                        <input type="checkbox" name="modules[]" value="<?= htmlspecialchars($moduleKey, ENT_QUOTES, 'UTF-8') ?>" <?= $isSelected ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars((string) ($module['label'] ?? $moduleKey), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <section class="search-secondary-shell">
            <div>
                <h4>Module-Specific Filters</h4>
                <p class="text-muted">Only filters for selected modules are applied.</p>
            </div>
            <div class="search-secondary-grid">
                <?php foreach (($availableSearchSecondaryFilters ?? []) as $filter): ?>
                    <?php
                        $filterKey = (string) ($filter['key'] ?? '');
                        $filterModule = (string) ($filter['module'] ?? '');
                        $selectedValue = (string) ($searchFilters[$filterKey] ?? '');
                    ?>
                    <label class="field search-secondary-field" data-module-filter="<?= htmlspecialchars($filterModule, ENT_QUOTES, 'UTF-8') ?>">
                        <span class="field-label"><?= htmlspecialchars((string) ($filter['label'] ?? $filterKey), ENT_QUOTES, 'UTF-8') ?></span>
                        <select class="input" name="<?= htmlspecialchars($filterKey, ENT_QUOTES, 'UTF-8') ?>">
                            <option value="">All</option>
                            <?php foreach (($filter['options'] ?? []) as $option): ?>
                                <option value="<?= htmlspecialchars((string) ($option['value'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $selectedValue === (string) ($option['value'] ?? '') ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) ($option['label'] ?? $option['value'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>
        <div class="cluster search-form-actions">
            <button class="btn-secondary" type="reset">Clear Filters</button>
        </div>
    </form>
</section>

<section class="search-empty card stack" id="search-empty-state"<?= ($searchQuery ?? '') !== '' ? ' hidden' : '' ?>>
    <h3>Start with a keyword</h3>
    <p class="text-muted">Use at least 2 characters. The search respects your current access rights.</p>
</section>

<section class="search-empty card stack" id="search-loading-state" hidden>
    <h3>Searching</h3>
    <p class="text-muted">Scanning the accessible modules now.</p>
</section>

<section class="search-empty card stack" id="search-no-results" hidden>
    <h3>No results found</h3>
    <p class="text-muted">Try a broader keyword or a different identifier.</p>
</section>

<section class="search-results" id="search-results"></section>

<script id="search-page-data" type="application/json"><?= json_encode([
    'csrfToken' => $csrfToken,
    'initialQuery' => $searchQuery ?? '',
    'initialFilters' => $searchFilters ?? ['modules' => [], 'per_section' => 5],
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
