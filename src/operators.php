<?php
declare(strict_types=1);

function list_operators(): array
{
    return db()->query('SELECT * FROM operators ORDER BY name')->fetchAll();
}

function get_operator(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM operators WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function create_operator(array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO operators (name, base_rate, travel_rate, yard_rate, has_hgv, notes)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        trim($data['name']),
        to_float($data['base_rate']),
        to_float($data['travel_rate']),
        to_float($data['yard_rate'] ?? RATES['yard']),
        !empty($data['has_hgv']) ? 1 : 0,
        trim($data['notes'] ?? ''),
    ]);
    return (int) db()->lastInsertId();
}

function update_operator(int $id, array $data): void
{
    $existing = get_operator($id);
    if (!$existing) {
        throw new RuntimeException('Operator not found.');
    }
    $stmt = db()->prepare(
        'UPDATE operators
            SET name = ?, base_rate = ?, travel_rate = ?, yard_rate = ?, has_hgv = ?, notes = ?
          WHERE id = ?'
    );
    $stmt->execute([
        trim($data['name']),
        to_float($data['base_rate']),
        to_float($data['travel_rate']),
        to_float($data['yard_rate']),
        !empty($data['has_hgv']) ? 1 : 0,
        trim($data['notes'] ?? ''),
        $id,
    ]);
}

function delete_operator(int $id): void
{
    $stmt = db()->prepare('DELETE FROM operators WHERE id = ?');
    $stmt->execute([$id]);
}
