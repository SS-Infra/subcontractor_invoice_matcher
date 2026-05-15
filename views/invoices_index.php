<?php
/** @var array  $invoices */
/** @var int    $jobsheet_count */
/** @var ?string $jobsheet_last_sync */
$activeTab = 'invoices';
?>
<div class="card">
    <h2>Jotform job sheets</h2>
    <p class="muted">
        We match each invoice line against the operator's Jotform submission for that day and use its
        site postcode to estimate the expected round-trip travel time (OpenRouteService, Severn crossings avoided).
        Sync periodically so new job sheets are available for matching.
    </p>
    <p class="muted" style="margin-bottom:0.9rem;">
        Job sheets stored: <strong><?= (int) $jobsheet_count ?></strong>
        <?php if ($jobsheet_last_sync): ?>
            · last sync <strong><?= h($jobsheet_last_sync) ?></strong>
        <?php endif; ?>
    </p>
    <form method="post" action="/jotform/sync" class="inline-form">
        <button type="submit" class="btn">Sync Jotform now</button>
    </form>
</div>

<div class="card">
    <h2>Upload invoice</h2>
    <p class="muted">Upload a PDF invoice from a subcontractor. We extract the lines directly from the PDF and run the same rule checks the original tool did.</p>

    <form method="post" action="/invoices/upload" enctype="multipart/form-data" class="stack">
        <div class="row three">
            <label class="field">
                <span>Subcontractor</span>
                <input type="text" name="subcontractor_name" required>
            </label>
            <label class="field">
                <span>Invoice number</span>
                <input type="text" name="invoice_number" required>
            </label>
            <label class="field">
                <span>Invoice date</span>
                <input type="date" name="invoice_date" required>
            </label>
        </div>
        <label class="field">
            <span>PDF file</span>
            <input type="file" name="file" accept="application/pdf" required>
        </label>
        <div><button type="submit" class="btn">Upload &amp; parse</button></div>
    </form>
</div>

<div class="card">
    <h2>Invoices</h2>

    <?php if (!$invoices): ?>
        <p class="muted">No invoices yet. Upload one above to get started.</p>
    <?php else: ?>
        <table class="data">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Subcontractor</th>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th style="text-align:right;">Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td><?= (int) $inv['id'] ?></td>
                        <td><?= h($inv['subcontractor_name'] ?? '') ?></td>
                        <td><a href="/invoices/<?= (int) $inv['id'] ?>"><?= h($inv['invoice_number']) ?></a></td>
                        <td><?= h($inv['invoice_date']) ?></td>
                        <td style="text-align:right;">£<?= number_format((float) $inv['total_amount'], 2) ?></td>
                        <td>
                            <form method="post" action="/invoices/<?= (int) $inv['id'] ?>/delete" class="inline-form"
                                  onsubmit="return confirm('Delete this invoice?');">
                                <button type="submit" class="btn ghost">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
