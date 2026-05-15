<?php
declare(strict_types=1);

// Stock Sweepers depot – Innovation House, GL14 2YD
const DEPOT_LAT = 51.833546;
const DEPOT_LON = -2.511965;

// Boxes around the Severn crossings (M48 + M4) we want ORS to avoid.
const AVOID_SEVERN = [
    'type' => 'MultiPolygon',
    'coordinates' => [
        [[[-2.67, 51.58], [-2.61, 51.58], [-2.61, 51.64], [-2.67, 51.64], [-2.67, 51.58]]],
        [[[-2.80, 51.54], [-2.74, 51.54], [-2.74, 51.60], [-2.80, 51.60], [-2.80, 51.54]]],
    ],
];

function ors_request(string $path, string $method = 'GET', ?array $body = null): array
{
    $key = env('OPENROUTESERVICE_API_KEY');
    if (!$key) {
        throw new RuntimeException('OPENROUTESERVICE_API_KEY is not set.');
    }
    $url = 'https://api.openrouteservice.org' . $path;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: ' . $key,
        ],
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($res === false) {
        throw new RuntimeException("ORS HTTP error: $err");
    }
    $data = json_decode((string) $res, true);
    if ($code !== 200 || !is_array($data)) {
        throw new RuntimeException("ORS returned $code: $res");
    }
    return $data;
}

function estimate_travel_hours(string $postcode): array
{
    $query = $postcode . ', UK';
    $geo   = ors_request('/geocode/search?' . http_build_query(['text' => $query, 'size' => 1]));
    $features = $geo['features'] ?? [];
    if (!$features) {
        return [null, "No coordinates found for '$query'"];
    }

    [$lon, $lat] = $features[0]['geometry']['coordinates'];

    $route = ors_request(
        '/v2/directions/driving-car',
        'POST',
        [
            'coordinates' => [[DEPOT_LON, DEPOT_LAT], [$lon, $lat]],
            'options'     => ['avoid_polygons' => AVOID_SEVERN],
        ]
    );
    $seconds = $route['routes'][0]['summary']['duration'] ?? null;
    if ($seconds === null) {
        return [null, 'ORS returned no duration.'];
    }
    $hours = $seconds / 3600.0;
    $debug = sprintf(
        'ORS driving-car depot (%.6f,%.6f) -> (%.6f,%.6f) %ds ≈ %.2fh (Severn avoided)',
        DEPOT_LAT, DEPOT_LON, $lat, $lon, $seconds, $hours
    );
    return [$hours, $debug];
}

function check_travel_time_claim(string $postcode, float $claimed, float $tolerance = 1.0): array
{
    try {
        [$est, $debug] = estimate_travel_hours($postcode);
    } catch (Throwable $e) {
        return [
            'ok' => true,
            'estimated_hours' => null,
            'claimed_hours'   => $claimed,
            'delta_hours'     => null,
            'tolerance_hours' => $tolerance,
            'debug'           => 'Could not estimate travel time: ' . $e->getMessage(),
        ];
    }

    if ($est === null) {
        return [
            'ok' => true,
            'estimated_hours' => null,
            'claimed_hours'   => $claimed,
            'delta_hours'     => null,
            'tolerance_hours' => $tolerance,
            'debug'           => "Could not estimate travel time: $debug",
        ];
    }

    $delta = $claimed - $est;
    return [
        'ok'              => abs($delta) <= $tolerance,
        'estimated_hours' => $est,
        'claimed_hours'   => $claimed,
        'delta_hours'     => $delta,
        'tolerance_hours' => $tolerance,
        'debug'           => $debug,
    ];
}
