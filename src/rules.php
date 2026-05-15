<?php
declare(strict_types=1);

function expected_rate_for_role(string $role, ?bool $hasHgv): float
{
    if ($role === 'travel_driver') {
        if ($hasHgv === true)  return RATES['travel_driver_hgv'];
        if ($hasHgv === false) return RATES['travel_driver_non_hgv'];
        return RATES['travel_driver'];
    }
    return RATES[$role] ?? 0.0;
}

function apply_rules(array &$line, bool $hasJobsheet, bool $hasYardRecord, ?bool $hasHgv): void
{
    $issues = [];

    if (!$hasJobsheet && $line['hours_on_site'] > 0) {
        $issues[] = 'Missing job sheet';
    }
    if ($line['hours_yard'] > 0 && !$hasYardRecord) {
        $issues[] = 'No yard sign-in';
    }

    $expected = expected_rate_for_role($line['role'], $hasHgv);
    if ($expected > 0 && abs($line['rate_per_hour'] - $expected) > 0.01) {
        $issues[] = sprintf('Rate mismatch: expected %.2f, got %.2f', $expected, $line['rate_per_hour']);
    }

    if ($line['hours_yard'] > YARD_DAY_MAX_HOURS + 0.1) {
        $issues[] = sprintf('Yard hours exceed max %.1fh', YARD_DAY_MAX_HOURS);
    }

    if ($line['hours_on_site'] > 0 && abs($line['hours_on_site'] - FULL_SHIFT_HOURS) > 0.5) {
        $issues[] = sprintf(
            'On-site hours %.2f differ from standard %.1fh (±0.5h)',
            $line['hours_on_site'],
            FULL_SHIFT_HOURS
        );
    }

    $calc = ($line['hours_on_site'] + $line['hours_travel'] + $line['hours_yard']) * $line['rate_per_hour'];
    if (abs($calc - $line['line_total']) > 0.5) {
        $issues[] = sprintf('Maths error: expected %.2f, got %.2f', $calc, $line['line_total']);
    }

    $line['match_notes'] = implode('; ', $issues);
    $line['match_score'] = max(0.0, 1.0 - 0.1 * count($issues));

    if (in_array('Missing job sheet', $issues, true) || in_array('No yard sign-in', $issues, true)) {
        $line['match_status'] = 'NEEDS_REVIEW';
    } elseif ($issues) {
        $line['match_status'] = 'PARTIAL';
    } elseif ($hasJobsheet || $hasYardRecord) {
        $line['match_status'] = 'MATCHED';
    } else {
        $line['match_status'] = 'NEEDS_REVIEW';
    }
}
