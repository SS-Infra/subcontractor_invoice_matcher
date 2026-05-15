<?php
declare(strict_types=1);

/**
 * Native (Ollama-free) invoice PDF parsing.
 *
 * Pipeline:
 *   1. Extract raw text from the PDF.
 *   2. Walk the text line-by-line and heuristically pull out one
 *      InvoiceLine per row using regex.
 *
 * Text extraction uses the `pdftotext` binary from poppler-utils. If it
 * isn't installed, we fall back to a very rough native stream extractor
 * that handles simple, uncompressed PDFs and ignores anything else.
 */

function extract_text_from_pdf(string $path): string
{
    if (!is_file($path)) {
        return '';
    }

    $pdftotext = trim((string) @shell_exec('command -v pdftotext'));
    if ($pdftotext !== '') {
        $cmd = escapeshellcmd($pdftotext) . ' -layout ' . escapeshellarg($path) . ' -';
        $out = @shell_exec($cmd . ' 2>/dev/null');
        if (is_string($out) && $out !== '') {
            return $out;
        }
    }

    return extract_text_native($path);
}

function extract_text_native(string $path): string
{
    $raw = @file_get_contents($path);
    if ($raw === false) {
        return '';
    }

    // Pull (text) operands out of BT/ET blocks. This is intentionally
    // simple: it handles uncompressed text streams from common invoice
    // templates and silently skips compressed or image-only PDFs.
    $chunks = [];
    if (preg_match_all('/BT(.*?)ET/s', $raw, $blocks)) {
        foreach ($blocks[1] as $block) {
            if (preg_match_all('/\((.*?)\)\s*Tj/s', $block, $m)) {
                foreach ($m[1] as $piece) {
                    $chunks[] = stripcslashes($piece);
                }
            }
        }
    }
    return implode("\n", $chunks);
}

/**
 * Heuristic line extractor.
 *
 * For each non-empty text line we look for: an optional date, optional
 * site location, a role keyword, three sets of hour columns, an hourly
 * rate and a line total. Anything that does not contain at least one
 * numeric pair plus a recognised role is skipped.
 */
function parse_invoice_text(string $text): array
{
    $lines = preg_split('/\r?\n/', $text) ?: [];
    $out = [];

    $roleMap = [
        'main operator'    => 'main_operator',
        'main op'          => 'main_operator',
        'operator'         => 'main_operator',
        'second operator'  => 'second_operator',
        'second op'        => 'second_operator',
        '2nd operator'     => 'second_operator',
        'yard'             => 'yard',
        'travel driver'    => 'travel_driver',
        'driver'           => 'travel_driver',
        'travel passenger' => 'travel_passenger',
        'passenger'        => 'travel_passenger',
        'travel'           => 'travel_driver',
    ];

    foreach ($lines as $raw) {
        $line = trim(preg_replace('/\s+/', ' ', $raw) ?? '');
        if ($line === '' || mb_strlen($line) < 8) {
            continue;
        }

        $role = '';
        $roleKeyword = '';
        foreach ($roleMap as $needle => $canonical) {
            if (stripos($line, $needle) !== false) {
                $role = $canonical;
                $roleKeyword = $needle;
                break;
            }
        }
        if ($role === '') {
            continue;
        }

        // Pull numbers in order. Currency / £ are stripped first.
        $cleaned = str_replace(['£', '$', ','], ['', '', ''], $line);
        if (!preg_match_all('/-?\d+(?:\.\d+)?/', $cleaned, $nums)) {
            continue;
        }
        $numbers = array_map('floatval', $nums[0]);
        if (count($numbers) < 3) {
            continue;
        }

        // Date: first DD/MM/YYYY or YYYY-MM-DD we see.
        $workDate = null;
        if (preg_match('#\b(\d{1,2}[/-]\d{1,2}[/-]\d{2,4}|\d{4}-\d{2}-\d{2})\b#', $line, $dm)) {
            $workDate = normalise_date($dm[1]);
        }

        // Site location: text before the role keyword, after the date if any.
        $location = '';
        $rolePos = stripos($line, $roleKeyword);
        if ($rolePos !== false) {
            $before = trim(substr($line, 0, $rolePos));
            if ($workDate !== null && preg_match('#\b(\d{1,2}[/-]\d{1,2}[/-]\d{2,4}|\d{4}-\d{2}-\d{2})\b#', $before, $dm, PREG_OFFSET_CAPTURE)) {
                $before = trim(substr($before, $dm[0][1] + strlen($dm[0][0])));
            }
            $location = trim(preg_replace('/[\s\-:]+$/', '', $before) ?? '');
        }

        // Heuristic column layout:
        //   hours_on_site, hours_travel, hours_yard, rate, total
        // Fewer numbers => zero-pad the missing ones, line_total = last number.
        $nums = $numbers;
        $total = array_pop($nums);
        $rate  = $nums ? array_pop($nums) : 0.0;
        $yard  = $nums ? array_pop($nums) : 0.0;
        $trav  = $nums ? array_pop($nums) : 0.0;
        $site  = $nums ? array_pop($nums) : 0.0;

        // Sanity: skip rows where total looks like it's just the rate.
        if ($total <= 0 || $rate <= 0) {
            continue;
        }

        $out[] = [
            'work_date'     => $workDate,
            'site_location' => $location,
            'role'          => $role,
            'hours_on_site' => $site,
            'hours_travel'  => $trav,
            'hours_yard'    => $yard,
            'rate_per_hour' => $rate,
            'line_total'    => $total,
            'match_status'  => 'NEEDS_REVIEW',
            'match_score'   => 0.0,
            'match_notes'   => '',
            'jobsheet_id'   => null,
            'yard_record_id'=> null,
        ];
    }

    return $out;
}

function parse_invoice_pdf(string $path): array
{
    $text = extract_text_from_pdf($path);
    if (trim($text) === '') {
        return [];
    }
    return parse_invoice_text($text);
}
