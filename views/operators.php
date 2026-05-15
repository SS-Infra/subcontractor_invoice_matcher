<?php
/** @var array $operators */
$activeTab = 'operators';
?>
<div class="page-header">
    <div>
        <h1>Operators</h1>
        <p>Pay rates per operator. Matched against invoice lines when the subcontractor name on the invoice equals the operator name here.</p>
    </div>
    <div class="actions">
        <a href="#new-op" class="btn primary">+ New operator</a>
    </div>
</div>

<div class="card" id="new-op">
    <h2>Add operator</h2>
    <p class="muted">Set pay rates and the HGV flag. Notes are free-form.</p>

    <form method="post" action="/operators" class="stack">
        <div class="row">
            <div class="field">
                <label for="name">Name</label>
                <input id="name" type="text" name="name" required>
            </div>
            <div class="field">
                <label for="base_rate">Base rate (£/h)</label>
                <input id="base_rate" type="number" step="0.01" name="base_rate" required>
            </div>
            <div class="field">
                <label for="travel_rate">Travel rate (£/h)</label>
                <input id="travel_rate" type="number" step="0.01" name="travel_rate" required>
            </div>
        </div>
        <div class="row">
            <div class="field">
                <label for="yard_rate">Yard rate (£/h)</label>
                <input id="yard_rate" type="number" step="0.01" name="yard_rate" value="17.00" required>
            </div>
            <div class="field">
                <label for="has_hgv">HGV licence</label>
                <select id="has_hgv" name="has_hgv">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
            </div>
            <div class="field">
                <label for="notes">Notes</label>
                <input id="notes" type="text" name="notes">
            </div>
        </div>
        <div>
            <button type="submit" class="btn primary">Add operator</button>
        </div>
    </form>
</div>

<?php if (!$operators): ?>
    <div class="card">
        <p class="muted-inline">No operators yet.</p>
    </div>
<?php else: ?>
    <?php foreach ($operators as $op): ?>
        <details class="op-row">
            <summary>
                <div>
                    <div class="op-name"><?= h($op['name']) ?></div>
                    <div class="op-meta">
                        Base £<?= number_format((float) $op['base_rate'], 2) ?>/h
                        · Travel £<?= number_format((float) $op['travel_rate'], 2) ?>/h
                        · Yard £<?= number_format((float) $op['yard_rate'], 2) ?>/h
                        <?php if ($op['has_hgv']): ?> · HGV<?php endif; ?>
                        <?php if (trim((string) $op['notes']) !== ''): ?>
                            · <?= h($op['notes']) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="actions">
                    <span class="btn">Edit</span>
                    <form method="post" action="/operators/<?= (int) $op['id'] ?>/delete"
                          class="inline-form"
                          onclick="event.stopPropagation();"
                          onsubmit="return confirm('Delete this operator?');">
                        <button type="submit" class="btn danger">Delete</button>
                    </form>
                </div>
            </summary>
            <div class="op-form">
                <form method="post" action="/operators/<?= (int) $op['id'] ?>/update" class="stack">
                    <div class="row">
                        <div class="field">
                            <label>Name</label>
                            <input type="text" name="name" value="<?= h($op['name']) ?>" required>
                        </div>
                        <div class="field">
                            <label>Base rate (£/h)</label>
                            <input type="number" step="0.01" name="base_rate" value="<?= h((string) $op['base_rate']) ?>" required>
                        </div>
                        <div class="field">
                            <label>Travel rate (£/h)</label>
                            <input type="number" step="0.01" name="travel_rate" value="<?= h((string) $op['travel_rate']) ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="field">
                            <label>Yard rate (£/h)</label>
                            <input type="number" step="0.01" name="yard_rate" value="<?= h((string) $op['yard_rate']) ?>" required>
                        </div>
                        <div class="field">
                            <label>HGV licence</label>
                            <select name="has_hgv">
                                <option value="0" <?= !$op['has_hgv'] ? 'selected' : '' ?>>No</option>
                                <option value="1" <?=  $op['has_hgv'] ? 'selected' : '' ?>>Yes</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Notes</label>
                            <input type="text" name="notes" value="<?= h($op['notes']) ?>">
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="btn primary">Save changes</button>
                    </div>
                </form>
            </div>
        </details>
    <?php endforeach; ?>
<?php endif; ?>
