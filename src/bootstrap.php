<?php
declare(strict_types=1);

date_default_timezone_set('Europe/London');
ini_set('display_errors', '1');
error_reporting(E_ALL);

session_start();

const BASE_DIR    = __DIR__ . '/..';
const DATA_DIR    = BASE_DIR . '/data';
const UPLOAD_DIR  = DATA_DIR . '/uploads';
const DEBUG_DIR   = DATA_DIR . '/debug';
const VIEW_DIR    = BASE_DIR . '/views';
const DB_PATH     = DATA_DIR . '/db.sqlite3';

foreach ([DATA_DIR, UPLOAD_DIR, DEBUG_DIR] as $d) {
    if (!is_dir($d)) {
        mkdir($d, 0775, true);
    }
}

// Load BASE_DIR/.env (KEY=value, one per line, # for comments) into the
// process environment. Lets 20i users configure secrets via a single file
// when they can't set env vars in the control panel.
$envFile = BASE_DIR . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        $k = trim($k);
        $v = trim($v, " \t\"'");
        if ($k !== '' && getenv($k) === false) {
            putenv("$k=$v");
            $_ENV[$k] = $v;
        }
    }
}

const APP_USER = 'admin';
const APP_PASS = 'admin123';

const RATES = [
    'main_operator'         => 25.0,
    'second_operator'       => 18.0,
    'yard'                  => 17.0,
    'travel_driver'         => 17.0,
    'travel_driver_hgv'     => 17.0,
    'travel_driver_non_hgv' => 17.0,
    'travel_passenger'      => 13.0,
];
const YARD_DAY_MAX_HOURS    = 9.0;
const FULL_SHIFT_HOURS      = 8.5;
const TRAVEL_TOLERANCE_HOURS = 1.0;   // round-trip ± this is acceptable
const TRAVEL_CACHE_TTL_DAYS  = 30;    // re-query ORS after this many days

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/pdf.php';
require_once __DIR__ . '/rules.php';
require_once __DIR__ . '/invoices.php';
require_once __DIR__ . '/operators.php';
require_once __DIR__ . '/jotform.php';
require_once __DIR__ . '/travel.php';
require_once __DIR__ . '/jobsheets.php';
