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

// For each line, fetch the matched jobsheet's postcode + cached travel
// estimate so we can show "expected" next to "claimed" travel hours.
$travelInfo = [];
foreach ($lines as $l) {
    $info = ['postcode' => '', 'expected' => null];
    if (!empty($l['jobsheet_id'])) {
        $js = db()->prepare('SELECT site_postcode FROM jobsheets WHERE id = ?');
        $js->execute([$l['jobsheet_id']]);
        $row = $js->fetch();
        if ($row && trim((string) $row['site_postcode']) !== '') {
            $info['postcode'] = (string) $row['site_postcode'];
            $tc = db()->prepare('SELECT one_way_hours FROM travel_cache WHERE postcode = ?');
            $tc->execute([normalise_postcode($info['postcode'])]);
            $cached = $tc->fetch();
            if ($cached && $cached['one_way_hours'] !== null) {
                $info['expected'] = ((float) $cached['one_way_hours']) * 2.0;
            }
        }
    }
    $travelInfo[(int) $l['id']] = $info;
}
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
            <form method="post" action="/invoices/<?= (int) $invoice['id'] ?>/rematch" class="inline-form">
                <button type="submit" class="btn ghost">Re-run matching</button>
            </form>
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
                    <th>Travel h (claimed / expected)</th>
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
                        <td>
                            <?= number_format((float) $l['hours_travel'], 2) ?>
                            <?php $ti = $travelInfo[(int) $l['id']] ?? null; ?>
                            <?php if ($ti && $ti['expected'] !== null): ?>
                                <span style="color:var(--muted);">/ <?= number_format($ti['expected'], 2) ?></span>
                                <div style="color:var(--muted);font-size:0.72rem;"><?= h($ti['postcode']) ?></div>
                            <?php elseif (!empty($l['jobsheet_id'])): ?>
                                <div style="color:var(--muted);font-size:0.72rem;">no postcode on job sheet</div>
                            <?php endif; ?>
                        </td>
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
