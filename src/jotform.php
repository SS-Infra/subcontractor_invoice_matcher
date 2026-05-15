<?php
declare(strict_types=1);

class JotformError extends RuntimeException {}

function jotform_get(string $path, array $params = []): array
{
    $key = env('JOTFORM_API_KEY');
    if (!$key) {
        throw new JotformError('JOTFORM_API_KEY is not set.');
    }
    $base = rtrim(env('JOTFORM_BASE_URL', 'https://api.jotform.com'), '/');
    $params['apiKey'] = $key;
    $url = $base . $path . '?' . http_build_query($params);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        throw new JotformError("HTTP error calling Jotform: $err");
    }
    if ($code !== 200) {
        throw new JotformError("Jotform returned HTTP $code: $body");
    }

    $data = json_decode((string) $body, true);
    if (!is_array($data) || ($data['responseCode'] ?? null) !== 200) {
        throw new JotformError('Jotform API error: ' . ($data['message'] ?? 'unknown'));
    }
    return $data;
}

function jotform_list_forms(): array
{
    $data = jotform_get('/user/forms');
    $forms = $data['content'] ?? [];
    return is_array($forms) ? $forms : [];
}

function jotform_stock_job_submissions(int $limit = 20, int $offset = 0): array
{
    $formId = env('JOTFORM_STOCK_JOB_FORM_ID');
    if (!$formId) {
        throw new JotformError('JOTFORM_STOCK_JOB_FORM_ID is not set.');
    }
    $data = jotform_get("/form/$formId/submissions", [
        'limit'  => $limit,
        'offset' => $offset,
    ]);
    $rows = $data['content'] ?? [];
    return is_array($rows) ? $rows : [];
}
