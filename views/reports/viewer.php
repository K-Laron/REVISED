<section class="page-title" id="report-viewer-page">
    <div class="page-title-meta">
        <h1>Report Viewer</h1>
        <div class="breadcrumb">Home &gt; Reports &gt; Viewer</div>
        <p class="text-muted">Review the generated report as a PDF document, then go back to the builder with the same filters intact.</p>
    </div>
</section>

<section class="reports-grid reports-grid-secondary reports-viewer-layout">
    <article class="card stack">
        <div class="cluster reports-viewer-header">
            <div class="stack">
                <h3 id="report-viewer-title">Waiting for a report selection</h3>
                <p class="text-muted" id="report-viewer-description">Open <a href="/reports">Reports &amp; Analytics</a>, choose a report, then open the viewer from there.</p>
            </div>
            <div class="cluster reports-actions">
                <a class="btn-secondary" href="/reports" id="report-viewer-back">Back to Reports</a>
                <a class="btn-secondary" href="#" id="report-viewer-export-csv">Export CSV</a>
                <a class="btn-secondary" href="#" id="report-viewer-open-pdf" target="_blank" rel="noopener">Open PDF</a>
                <a class="btn-secondary" href="#" id="report-viewer-download-pdf">Download PDF</a>
            </div>
        </div>
        <div class="cluster" id="report-viewer-filters"></div>
        <div id="report-viewer-empty" class="notification-empty">No report selected yet.</div>
        <iframe id="report-viewer-frame" class="reports-document-frame" title="Report PDF preview" hidden></iframe>
    </article>
</section>
