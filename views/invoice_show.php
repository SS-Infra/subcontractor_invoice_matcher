<?php
/** @var array $invoice */
/** @var array $lines */
$activeTab = 'invoices';

$badge = function (string $status): string {
    return match ($status) {
        'MATCHED' => 'matched',
        'PARTIAL' => 'partial',
        default   => 'review',
    };
};
?>
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
        <div>
            <h2>Invoice <?= h($invoice['invoice_number']) ?></h2>
            <p class="muted">
                <?= h($invoice['subcontractor_name'] ?? '—') ?> ·
                <?= h($invoice['invoice_date']) ?> ·
                Total <strong>£<?= number_format((float) $invoice['total_amount'], 2) ?></strong>
            </p>
        </div>
        <div class="actions">
            <a href="/" class="btn ghost">Back</a>
        </div>
    </div>
</div>

<div class="card">
    <h2>Lines</h2>
    <?php if (!$lines): ?>
        <p class="muted">No lines were extracted from this PDF. The file may be image-only or use a layout the parser doesn&apos;t recognise.</p>
    <?php else: ?>
        <table class="data">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Site</th>
                    <th>Role</th>
                    <th>Site h</th>
                    <th>Travel h</th>
                    <th>Yard h</th>
                    <th>Rate</th>
                    <th style="text-align:right;">Total</th>
                    <th>Status</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lines as $l): ?>
                    <tr>
                        <td><?= h($l['work_date'] ?? '') ?></td>
                        <td><?= h($l['site_location']) ?></td>
                        <td><?= h($l['role']) ?></td>
                        <td><?= number_format((float) $l['hours_on_site'], 2) ?></td>
                        <td><?= number_format((float) $l['hours_travel'], 2) ?></td>
                        <td><?= number_format((float) $l['hours_yard'], 2) ?></td>
                        <td>£<?= number_format((float) $l['rate_per_hour'], 2) ?></td>
                        <td style="text-align:right;">£<?= number_format((float) $l['line_total'], 2) ?></td>
                        <td><span class="badge <?= $badge($l['match_status']) ?>"><?= h($l['match_status']) ?></span></td>
                        <td><?= h($l['match_notes']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
