<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS subcontractors (
            id   INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE
        );

        CREATE TABLE IF NOT EXISTS invoices (
            id               INTEGER PRIMARY KEY AUTOINCREMENT,
            subcontractor_id INTEGER REFERENCES subcontractors(id) ON DELETE SET NULL,
            invoice_number   TEXT NOT NULL,
            invoice_date     TEXT NOT NULL,
            total_amount     REAL NOT NULL DEFAULT 0,
            file_path        TEXT NOT NULL,
            created_at       TEXT NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS invoice_lines (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            invoice_id      INTEGER NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
            work_date       TEXT,
            site_location   TEXT NOT NULL DEFAULT '',
            role            TEXT NOT NULL DEFAULT '',
            hours_on_site   REAL NOT NULL DEFAULT 0,
            hours_travel    REAL NOT NULL DEFAULT 0,
            hours_yard      REAL NOT NULL DEFAULT 0,
            rate_per_hour   REAL NOT NULL DEFAULT 0,
            line_total      REAL NOT NULL DEFAULT 0,
            match_status    TEXT NOT NULL DEFAULT 'NEEDS_REVIEW',
            match_score     REAL NOT NULL DEFAULT 0,
            match_notes     TEXT NOT NULL DEFAULT '',
            jobsheet_id     TEXT,
            yard_record_id  TEXT
        );

        CREATE TABLE IF NOT EXISTS operators (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            name        TEXT NOT NULL UNIQUE,
            base_rate   REAL NOT NULL,
            travel_rate REAL NOT NULL,
            yard_rate   REAL NOT NULL DEFAULT 17.0,
            has_hgv     INTEGER NOT NULL DEFAULT 0,
            notes       TEXT NOT NULL DEFAULT ''
        );
    SQL);

    return $pdo;
}
