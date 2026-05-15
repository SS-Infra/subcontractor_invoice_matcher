<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path   = rtrim($path, '/') ?: '/';

try {
    route($method, $path);
} catch (Throwable $e) {
    http_response_code(500);
    if (str_starts_with($path, '/api/') || str_starts_with($path, '/debug/')) {
        json_response(['error' => $e->getMessage()], 500);
    }
    flash('Error: ' . $e->getMessage());
    redirect('/');
}

function route(string $method, string $path): void
{
    // Public routes
    if ($path === '/login' && $method === 'GET') {
        render('login', ['error' => null]);
        return;
    }
    if ($path === '/login' && $method === 'POST') {
        $ok = attempt_login($_POST['username'] ?? '', $_POST['password'] ?? '');
        if ($ok) {
            redirect('/');
        }
        render('login', ['error' => 'Invalid username or password.']);
        return;
    }
    if ($path === '/logout') {
        logout();
        redirect('/login');
    }

    require_auth();

    // Invoices
    if ($path === '/' && $method === 'GET') {
        render('invoices_index', [
            'invoices'           => list_invoices(),
            'jobsheet_count'     => jobsheet_count(),
            'jobsheet_last_sync' => jobsheet_last_synced(),
            'stats'              => invoice_stats(),
        ]);
        return;
    }
    if ($path === '/invoices/upload' && $method === 'POST') {
        $id = save_uploaded_invoice(
            $_FILES['file'] ?? [],
            $_POST['subcontractor_name'] ?? '',
            $_POST['invoice_number'] ?? '',
            $_POST['invoice_date'] ?? ''
        );
        flash('Invoice uploaded.');
        redirect('/invoices/' . $id);
    }
    if (preg_match('#^/invoices/(\d+)$#', $path, $m) && $method === 'GET') {
        $id  = (int) $m[1];
        $inv = get_invoice($id);
        if (!$inv) {
            http_response_code(404);
            echo 'Invoice not found.';
            return;
        }
        render('invoice_show', [
            'invoice' => $inv,
            'lines'   => get_invoice_lines($id),
        ]);
        return;
    }
    if (preg_match('#^/invoices/(\d+)/delete$#', $path, $m) && $method === 'POST') {
        delete_invoice((int) $m[1]);
        flash('Invoice deleted.');
        redirect('/');
    }
    if (preg_match('#^/invoices/(\d+)/rematch$#', $path, $m) && $method === 'POST') {
        run_matching_for_invoice((int) $m[1]);
        flash('Re-ran matching.');
        redirect('/invoices/' . (int) $m[1]);
    }

    // Jotform sync
    if ($path === '/jotform/sync' && $method === 'POST') {
        $summary = sync_jotform_jobsheets();
        flash(sprintf(
            'Synced job sheets: +%d new, %d updated, %d skipped (total now %d).',
            $summary['inserted'], $summary['updated'], $summary['rejected'], $summary['total']
        ));
        redirect('/');
    }

    // Operators
    if ($path === '/operators' && $method === 'GET') {
        render('operators', ['operators' => list_operators()]);
        return;
    }
    if ($path === '/operators' && $method === 'POST') {
        create_operator($_POST);
        flash('Operator added.');
        redirect('/operators');
    }
    if (preg_match('#^/operators/(\d+)/update$#', $path, $m) && $method === 'POST') {
        update_operator((int) $m[1], $_POST);
        flash('Operator updated.');
        redirect('/operators');
    }
    if (preg_match('#^/operators/(\d+)/delete$#', $path, $m) && $method === 'POST') {
        delete_operator((int) $m[1]);
        flash('Operator removed.');
        redirect('/operators');
    }

    // Debug / integrations (JSON)
    if ($path === '/debug/parse-invoice' && $method === 'POST') {
        $file = $_FILES['file'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            json_response(['error' => 'No file uploaded.'], 400);
        }
        $name   = preg_replace('/[^A-Za-z0-9._-]+/', '_', basename($file['name'])) ?? 'debug.pdf';
        $target = DEBUG_DIR . '/' . date('Ymd-His-') . $name;
        move_uploaded_file($file['tmp_name'], $target);
        $lines = parse_invoice_pdf($target);
        json_response([
            'file_path'  => $target,
            'line_count' => count($lines),
            'lines'      => $lines,
        ]);
    }
    if ($path === '/debug/test-travel' && $method === 'GET') {
        $postcode  = (string) ($_GET['postcode'] ?? '');
        $claimed   = (float) ($_GET['claimed']  ?? 0);
        $tolerance = (float) ($_GET['tolerance'] ?? 1);
        if ($postcode === '') {
            json_response(['error' => 'postcode is required.'], 400);
        }
        json_response(check_travel_time_claim($postcode, $claimed, $tolerance));
    }
    if ($path === '/debug/jotform/forms' && $method === 'GET') {
        $forms = jotform_list_forms();
        json_response([
            'count' => count($forms),
            'forms' => array_map(
                fn ($f) => [
                    'id'         => $f['id']         ?? null,
                    'title'      => $f['title']      ?? null,
                    'status'     => $f['status']     ?? null,
                    'created_at' => $f['created_at'] ?? null,
                ],
                $forms
            ),
        ]);
    }
    if ($path === '/debug/jotform/stock-job-submissions' && $method === 'GET') {
        $limit  = (int) ($_GET['limit']  ?? 10);
        $offset = (int) ($_GET['offset'] ?? 0);
        $subs   = jotform_stock_job_submissions($limit, $offset);
        json_response([
            'count'       => count($subs),
            'limit'       => $limit,
            'offset'      => $offset,
            'submissions' => $subs,
        ]);
    }

    http_response_code(404);
    echo 'Not found.';
}
