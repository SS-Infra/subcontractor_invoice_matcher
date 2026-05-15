<?php
declare(strict_types=1);

const UK_POSTCODE_REGEX =
    '/\b([A-PR-UWYZ][A-HK-Y]?\d[A-Z\d]?)\s*(\d[A-BD-HJLNP-UW-Z]{2})\b/i';

/**
 * Walk a Jotform submission's `answers` map and pull out the fields we
 * care about. Jotform forms vary, so we use a best-effort heuristic:
 *   - dates come from any answer whose value parses as a date,
 *   - the operator/name field is matched by question text,
 *   - the postcode is the first UK-format postcode we find anywhere,
 *   - the site name is the address line or any "site" labelled field.
 */
function extract_jobsheet_fields(array $submission): array
{
    $answers = $submission['answers'] ?? [];
    if (!is_array($answers)) {
        $answers = [];
    }

    $workDate = null;
    $operator = '';
    $site     = '';
    $postcode = '';

    foreach ($answers as $a) {
        if (!is_array($a)) {
            continue;
        }
        $name  = strtolower((string) ($a['name'] ?? ''));
        $label = strtolower((string) ($a['text'] ?? ''));
        $type  = (string) ($a['type'] ?? '');
        $value = $a['answer'] ?? $a['prettyFormat'] ?? null;

        $flat = jobsheet_flatten_value($value);

        // Postcode – first UK postcode we see anywhere in the answers,
        // checked before label matching so we don't lose it on the way past.
        if ($postcode === '' && preg_match(UK_POSTCODE_REGEX, $flat, $m)) {
            $postcode = normalise_postcode($m[1] . ' ' . $m[2]);
        }

        $isDate = $type === 'control_datetime' || $type === 'control_dateformat'
            || str_contains($label, 'date') || str_contains($name, 'date')
            || (is_array($value) && isset($value['year'], $value['month'], $value['day']));

        // Date – use the question label, the answer type, or anything that parses.
        if ($workDate === null && $isDate) {
            if (is_array($value) && isset($value['year'], $value['month'], $value['day'])) {
                $workDate = sprintf(
                    '%04d-%02d-%02d',
                    (int) $value['year'], (int) $value['month'], (int) $value['day']
                );
            } else {
                $workDate = normalise_date($flat);
            }
            continue;   // don't reuse a date answer as a name/site
        }

        if ($flat === '') {
            continue;
        }

        // Operator name.
        if ($operator === '') {
            foreach (['operator', 'driver', 'subcontractor', 'employee'] as $needle) {
                if (str_contains($label, $needle) || str_contains($name, $needle)) {
                    $operator = $flat;
                    break;
                }
            }
            if ($operator === '' && (str_contains($label, 'name') || str_contains($name, 'name'))) {
                $operator = $flat;
            }
        }

        // Site / address.
        if ($site === '') {
            foreach (['site', 'address', 'location', 'jobsite', 'job site'] as $needle) {
                if (str_contains($label, $needle) || str_contains($name, $needle)) {
                    $site = $flat;
                    break;
                }
            }
        }
    }

    return [
        'id'            => (string) ($submission['id'] ?? ''),
        'work_date'     => $workDate,
        'operator_name' => trim($operator),
        'site_name'     => trim($site),
        'site_postcode' => $postcode,
    ];
}

function jobsheet_flatten_value($value): string
{
    if ($value === null) {
        return '';
    }
    if (is_string($value)) {
        return trim($value);
    }
    if (is_numeric($value)) {
        return (string) $value;
    }
    if (is_array($value)) {
        $parts = [];
        array_walk_recursive($value, function ($v) use (&$parts) {
            if (is_string($v) && trim($v) !== '') {
                $parts[] = trim($v);
            } elseif (is_numeric($v)) {
                $parts[] = (string) $v;
            }
        });
        return implode(' ', $parts);
    }
    return '';
}

/**
 * Pull the latest submissions out of Jotform and upsert them into the
 * `jobsheets` table. Returns a small summary the UI shows the user.
 */
function sync_jotform_jobsheets(int $maxBatches = 10, int $batchSize = 100): array
{
    $offset    = 0;
    $inserted  = 0;
    $updated   = 0;
    $rejected  = 0;
    $batches   = 0;

    $stmt = db()->prepare(
        'INSERT INTO jobsheets
            (id, work_date, operator_name, site_name, site_postcode, raw_json, synced_at)
         VALUES (?, ?, ?, ?, ?, ?, datetime("now"))
         ON CONFLICT(id) DO UPDATE SET
            work_date     = excluded.work_date,
            operator_name = excluded.operator_name,
            site_name     = excluded.site_name,
            site_postcode = excluded.site_postcode,
            raw_json      = excluded.raw_json,
            synced_at     = excluded.synced_at'
    );

    while ($batches < $maxBatches) {
        $rows = jotform_stock_job_submissions($batchSize, $offset);
        if (!$rows) {
            break;
        }
        $batches++;

        foreach ($rows as $row) {
            $fields = extract_jobsheet_fields($row);
            if ($fields['id'] === '') {
                $rejected++;
                continue;
            }
            $existing = db()->prepare('SELECT 1 FROM jobsheets WHERE id = ?');
            $existing->execute([$fields['id']]);
            $isUpdate = (bool) $existing->fetchColumn();

            $stmt->execute([
                $fields['id'],
                $fields['work_date'],
                $fields['operator_name'],
                $fields['site_name'],
                $fields['site_postcode'],
                json_encode($row, JSON_UNESCAPED_SLASHES),
            ]);

            $isUpdate ? $updated++ : $inserted++;
        }

        if (count($rows) < $batchSize) {
            break;
        }
        $offset += $batchSize;
    }

    return [
        'inserted' => $inserted,
        'updated'  => $updated,
        'rejected' => $rejected,
        'batches'  => $batches,
        'total'    => (int) db()->query('SELECT COUNT(*) FROM jobsheets')->fetchColumn(),
    ];
}

function jobsheet_count(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM jobsheets')->fetchColumn();
}

function jobsheet_last_synced(): ?string
{
    $row = db()->query('SELECT MAX(synced_at) AS t FROM jobsheets')->fetch();
    return $row && $row['t'] ? (string) $row['t'] : null;
}

/**
 * Find the jobsheet that best fits an invoice line for a given operator.
 *
 * Match strategy, most -> least specific:
 *   1. same operator + same date + best site_location overlap,
 *   2. same operator + same date,
 *   3. same operator + ±1 day window.
 */
function find_jobsheet_for_line(string $operatorName, ?string $workDate, string $siteLocation): ?array
{
    $operatorName = trim($operatorName);
    if ($operatorName === '') {
        return null;
    }

    $opLike = '%' . strtolower($operatorName) . '%';

    if ($workDate) {
        $stmt = db()->prepare(
            'SELECT * FROM jobsheets
              WHERE work_date = ?
                AND LOWER(operator_name) LIKE ?'
        );
        $stmt->execute([$workDate, $opLike]);
        $rows = $stmt->fetchAll();
    } else {
        $rows = [];
    }

    if (!$rows && $workDate) {
        $stmt = db()->prepare(
            "SELECT * FROM jobsheets
              WHERE LOWER(operator_name) LIKE ?
                AND work_date BETWEEN date(?, '-1 day') AND date(?, '+1 day')"
        );
        $stmt->execute([$opLike, $workDate, $workDate]);
        $rows = $stmt->fetchAll();
    }

    if (!$rows) {
        return null;
    }

    if (count($rows) === 1 || $siteLocation === '') {
        return $rows[0];
    }

    // Pick the jobsheet whose site_name shares the most lowercase tokens
    // with the invoice line's site_location.
    $needle = preg_split('/\W+/', strtolower($siteLocation)) ?: [];
    $needle = array_filter($needle, fn ($t) => $t !== '' && strlen($t) > 2);

    $best = null;
    $bestScore = -1;
    foreach ($rows as $row) {
        $hay   = preg_split('/\W+/', strtolower((string) $row['site_name'])) ?: [];
        $score = count(array_intersect($needle, $hay));
        if ($score > $bestScore) {
            $best = $row;
            $bestScore = $score;
        }
    }
    return $best ?: $rows[0];
}
