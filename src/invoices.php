<?php
declare(strict_types=1);

function find_or_create_subcontractor(string $name): int
{
    $name = trim($name);
    $stmt = db()->prepare('SELECT id FROM subcontractors WHERE name = ?');
    $stmt->execute([$name]);
    $row = $stmt->fetch();
    if ($row) {
        return (int) $row['id'];
    }
    $ins = db()->prepare('INSERT INTO subcontractors (name) VALUES (?)');
    $ins->execute([$name]);
    return (int) db()->lastInsertId();
}

function save_uploaded_invoice(array $file, string $subcontractor, string $invoiceNumber, string $invoiceDate): int
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed.');
    }

    $iso = normalise_date($invoiceDate);
    if ($iso === null) {
        throw new RuntimeException('Invalid invoice_date format.');
    }

    $safeName  = preg_replace('/[^A-Za-z0-9._-]+/', '_', basename($file['name'])) ?? 'invoice.pdf';
    $stored    = sprintf('%s-%s', date('Ymd-His'), $safeName);
    $storePath = UPLOAD_DIR . '/' . $stored;

    if (!move_uploaded_file($file['tmp_name'], $storePath)) {
        throw new RuntimeException('Could not save uploaded file.');
    }

    $subId = find_or_create_subcontractor($subcontractor);

    $ins = db()->prepare(
        'INSERT INTO invoices (subcontractor_id, invoice_number, invoice_date, total_amount, file_path)
         VALUES (?, ?, ?, 0, ?)'
    );
    $ins->execute([$subId, trim($invoiceNumber), $iso, $storePath]);
    $invoiceId = (int) db()->lastInsertId();

    $lines = parse_invoice_pdf($storePath);
    insert_invoice_lines($invoiceId, $lines);
    refresh_invoice_total($invoiceId);
    run_matching_for_invoice($invoiceId);

    return $invoiceId;
}

function insert_invoice_lines(int $invoiceId, array $lines): void
{
    if (!$lines) {
        return;
    }
    $sql = 'INSERT INTO invoice_lines
        (invoice_id, work_date, site_location, role,
         hours_on_site, hours_travel, hours_yard,
         rate_per_hour, line_total,
         match_status, match_score, match_notes,
         jobsheet_id, yard_record_id)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
    $stmt = db()->prepare($sql);
    foreach ($lines as $l) {
        $stmt->execute([
            $invoiceId,
            $l['work_date'],
            $l['site_location'],
            $l['role'],
            $l['hours_on_site'],
            $l['hours_travel'],
            $l['hours_yard'],
            $l['rate_per_hour'],
            $l['line_total'],
            $l['match_status'],
            $l['match_score'],
            $l['match_notes'],
            $l['jobsheet_id'],
            $l['yard_record_id'],
        ]);
    }
}

function refresh_invoice_total(int $invoiceId): void
{
    $stmt = db()->prepare(
        'UPDATE invoices SET total_amount =
            COALESCE((SELECT SUM(line_total) FROM invoice_lines WHERE invoice_id = ?), 0)
         WHERE id = ?'
    );
    $stmt->execute([$invoiceId, $invoiceId]);
}

function run_matching_for_invoice(int $invoiceId): void
{
    $lines = db()->prepare('SELECT * FROM invoice_lines WHERE invoice_id = ?');
    $lines->execute([$invoiceId]);
    $rows = $lines->fetchAll();

    $inv = get_invoice($invoiceId);
    $operatorName = $inv['subcontractor_name'] ?? '';
    $hasHgv = null;
    if ($operatorName !== '') {
        $op = db()->prepare('SELECT has_hgv FROM operators WHERE name = ?');
        $op->execute([$operatorName]);
        $opRow = $op->fetch();
        if ($opRow) {
            $hasHgv = (bool) $opRow['has_hgv'];
        }
    }

    $upd = db()->prepare(
        'UPDATE invoice_lines
            SET match_status = ?, match_score = ?, match_notes = ?
          WHERE id = ?'
    );

    foreach ($rows as $row) {
        $line = [
            'role'          => $row['role'],
            'hours_on_site' => (float) $row['hours_on_site'],
            'hours_travel'  => (float) $row['hours_travel'],
            'hours_yard'    => (float) $row['hours_yard'],
            'rate_per_hour' => (float) $row['rate_per_hour'],
            'line_total'    => (float) $row['line_total'],
            'match_status'  => $row['match_status'],
            'match_score'   => 0.0,
            'match_notes'   => '',
        ];
        $hasJobsheet   = !empty($row['jobsheet_id']);
        $hasYardRecord = !empty($row['yard_record_id']);
        apply_rules($line, $hasJobsheet, $hasYardRecord, $hasHgv);
        $upd->execute([
            $line['match_status'],
            $line['match_score'],
            $line['match_notes'],
            $row['id'],
        ]);
    }
}

function list_invoices(): array
{
    $sql = 'SELECT i.*, s.name AS subcontractor_name
              FROM invoices i
         LEFT JOIN subcontractors s ON s.id = i.subcontractor_id
          ORDER BY i.created_at DESC, i.id DESC';
    return db()->query($sql)->fetchAll();
}

function get_invoice(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT i.*, s.name AS subcontractor_name
           FROM invoices i
      LEFT JOIN subcontractors s ON s.id = i.subcontractor_id
          WHERE i.id = ?'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_invoice_lines(int $invoiceId): array
{
    $stmt = db()->prepare('SELECT * FROM invoice_lines WHERE invoice_id = ? ORDER BY id');
    $stmt->execute([$invoiceId]);
    return $stmt->fetchAll();
}

function delete_invoice(int $id): void
{
    $inv = get_invoice($id);
    if (!$inv) {
        return;
    }
    if (!empty($inv['file_path']) && is_file($inv['file_path'])) {
        @unlink($inv['file_path']);
    }
    $stmt = db()->prepare('DELETE FROM invoices WHERE id = ?');
    $stmt->execute([$id]);
}
