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

/**
 * Apply rule checks to one invoice line.
 *
 * $context may include:
 *   - expected_round_trip_hours: float|null  (2 × ORS one-way)
 *   - travel_debug: string                   (free-form notes for UI)
 *   - travel_tolerance:           float
 */
function apply_rules(
    array &$line,
    bool $hasJobsheet,
    bool $hasYardRecord,
    ?bool $hasHgv,
    array $context = []
): void {
    $issues = [];

    if (!$hasJobsheet && $line['hours_on_site'] > 0) {
        $issues[] = 'Missing job sheet';
    }
    if ($line['hours_yard'] > 0 && !$hasYardRecord) {
        $issues[] = 'No yard sign-in';
    }

    // Travel-time check: compare claimed hours_travel (round-trip) against
    // 2 × ORS one-way estimate ± tolerance.
    $expected = $context['expected_round_trip_hours'] ?? null;
    $tol      = $context['travel_tolerance'] ?? TRAVEL_TOLERANCE_HOURS;
    if ($expected !== null && $line['hours_travel'] > 0) {
        $delta = $line['hours_travel'] - $expected;
        if (abs($delta) > $tol) {
            $issues[] = sprintf(
                'Travel mismatch: claimed %.2fh, expected %.2fh round-trip (Δ %+.2fh, tol %.1fh)',
                $line['hours_travel'], $expected, $delta, $tol
            );
        }
    } elseif ($expected === null && $line['hours_travel'] > 0 && $hasJobsheet) {
        $issues[] = 'Travel: no postcode on matched job sheet';
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
