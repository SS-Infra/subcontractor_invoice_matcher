<?php
/** @var array  $invoices */
/** @var int    $jobsheet_count */
/** @var ?string $jobsheet_last_sync */
/** @var array  $stats */
$activeTab = 'invoices';
?>
<div class="page-header">
    <div>
        <h1>Invoices</h1>
        <p>Upload subcontractor invoices and compare them against Jotform job sheets and the depot's expected travel time.</p>
    </div>
    <div class="actions">
        <form method="post" action="/jotform/sync" class="inline-form">
            <button type="submit" class="btn">Sync Jotform</button>
        </form>
        <a href="#upload" class="btn primary">+ Upload invoice</a>
    </div>
</div>

<div class="stats">
    <div class="stat">
        <span class="icon">📄</span>
        <div>
            <div class="label">Total invoices</div>
            <div class="value"><?= (int) $stats['invoices'] ?></div>
        </div>
    </div>
    <div class="stat">
        <span class="icon">✓</span>
        <div>
            <div class="label">Lines matched</div>
            <div class="value"><?= (int) $stats['matched'] ?></div>
        </div>
    </div>
    <div class="stat">
        <span class="icon">!</span>
        <div>
            <div class="label">Partial</div>
            <div class="value"><?= (int) $stats['partial'] ?></div>
        </div>
    </div>
    <div class="stat">
        <span class="icon">?</span>
        <div>
            <div class="label">Needs review</div>
            <div class="value"><?= (int) $stats['needs_review'] ?></div>
        </div>
    </div>
</div>

<div class="card">
    <h2>Jotform job sheets</h2>
    <p class="muted">
        Synced job sheets are matched to invoice lines by operator + date. Their postcode drives the travel-time estimate.
    </p>
    <p class="muted-inline">
        <strong><?= (int) $jobsheet_count ?></strong> job sheet<?= $jobsheet_count === 1 ? '' : 's' ?> stored
        <?php if ($jobsheet_last_sync): ?>
            · last sync <strong><?= h($jobsheet_last_sync) ?></strong>
        <?php endif; ?>
    </p>
</div>

<div class="card" id="upload">
    <h2>Upload invoice</h2>
    <p class="muted">PDF only. Lines are extracted and rule-checked on upload.</p>

    <form method="post" action="/invoices/upload" enctype="multipart/form-data" class="stack">
        <div class="row">
            <div class="field">
                <label for="subcontractor_name">Subcontractor</label>
                <input id="subcontractor_name" type="text" name="subcontractor_name" required>
            </div>
            <div class="field">
                <label for="invoice_number">Invoice number</label>
                <input id="invoice_number" type="text" name="invoice_number" required>
            </div>
            <div class="field">
                <label for="invoice_date">Invoice date</label>
                <input id="invoice_date" type="date" name="invoice_date" required>
            </div>
        </div>
        <div class="field">
            <label for="file">PDF file</label>
            <input id="file" type="file" name="file" accept="application/pdf" required>
        </div>
        <div>
            <button type="submit" class="btn primary">Upload &amp; parse</button>
        </div>
    </form>
</div>

<?php if (!$invoices): ?>
    <div class="card">
        <p class="muted-inline">No invoices yet. Upload one above to get started.</p>
    </div>
<?php else: ?>
    <?php foreach ($invoices as $inv): ?>
        <div class="card-row">
            <div class="info">
                <div class="title">
                    <a href="/invoices/<?= (int) $inv['id'] ?>"><?= h($inv['invoice_number']) ?></a>
                </div>
                <div class="meta">
                    <span><?= h($inv['subcontractor_name'] ?? '—') ?></span>
                    <span><?= h($inv['invoice_date']) ?></span>
                    <span>£<?= number_format((float) $inv['total_amount'], 2) ?></span>
                </div>
            </div>
            <div class="actions">
                <a href="/invoices/<?= (int) $inv['id'] ?>" class="btn">Open</a>
                <form method="post" action="/invoices/<?= (int) $inv['id'] ?>/delete"
                      class="inline-form" onsubmit="return confirm('Delete this invoice?');">
                    <button type="submit" class="btn danger">Delete</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
