<?php
/** @var array $operators */
$activeTab = 'operators';
?>
<div class="card">
    <h2>Add operator</h2>
    <p class="muted">Operator pay rates are matched against invoice lines when the subcontractor name on the invoice equals the operator name here.</p>

    <form method="post" action="/operators" class="stack">
        <div class="row three">
            <label class="field">
                <span>Name</span>
                <input type="text" name="name" required>
            </label>
            <label class="field">
                <span>Base rate (£/h)</span>
                <input type="number" step="0.01" name="base_rate" required>
            </label>
            <label class="field">
                <span>Travel rate (£/h)</span>
                <input type="number" step="0.01" name="travel_rate" required>
            </label>
        </div>
        <div class="row three">
            <label class="field">
                <span>Yard rate (£/h)</span>
                <input type="number" step="0.01" name="yard_rate" value="17.00" required>
            </label>
            <label class="field">
                <span>HGV licence</span>
                <select name="has_hgv">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
            </label>
            <label class="field">
                <span>Notes</span>
                <input type="text" name="notes">
            </label>
        </div>
        <div><button type="submit" class="btn">Add operator</button></div>
    </form>
</div>

<div class="card">
    <h2>Operators</h2>
    <?php if (!$operators): ?>
        <p class="muted">No operators yet.</p>
    <?php else: ?>
        <?php foreach ($operators as $op): ?>
            <div style="border:1px solid var(--border);border-radius:0.8rem;padding:1rem;margin-bottom:0.8rem;">
                <form method="post" action="/operators/<?= (int) $op['id'] ?>/update" class="stack">
                    <div class="row three">
                        <label class="field">
                            <span>Name</span>
                            <input type="text" name="name" value="<?= h($op['name']) ?>" required>
                        </label>
                        <label class="field">
                            <span>Base rate (£/h)</span>
                            <input type="number" step="0.01" name="base_rate" value="<?= h((string) $op['base_rate']) ?>" required>
                        </label>
                        <label class="field">
                            <span>Travel rate (£/h)</span>
                            <input type="number" step="0.01" name="travel_rate" value="<?= h((string) $op['travel_rate']) ?>" required>
                        </label>
                    </div>
                    <div class="row three">
                        <label class="field">
                            <span>Yard rate (£/h)</span>
                            <input type="number" step="0.01" name="yard_rate" value="<?= h((string) $op['yard_rate']) ?>" required>
                        </label>
                        <label class="field">
                            <span>HGV licence</span>
                            <select name="has_hgv">
                                <option value="0" <?= !$op['has_hgv'] ? 'selected' : '' ?>>No</option>
                                <option value="1" <?=  $op['has_hgv'] ? 'selected' : '' ?>>Yes</option>
                            </select>
                        </label>
                        <label class="field">
                            <span>Notes</span>
                            <input type="text" name="notes" value="<?= h($op['notes']) ?>">
                        </label>
                    </div>
                    <div class="actions">
                        <button type="submit" class="btn">Save</button>
                    </div>
                </form>
                <form method="post" action="/operators/<?= (int) $op['id'] ?>/delete" class="inline-form"
                      onsubmit="return confirm('Delete this operator?');" style="margin-top:0.4rem;">
                    <button type="submit" class="btn ghost">Delete operator</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
