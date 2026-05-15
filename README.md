# Subcontractor Invoice Matcher

A small PHP web app for matching subcontractor invoices against job sheet data
(from Jotform's "Stock Job Form") and yard sign-in data.

The previous incarnation was a FastAPI backend + React frontend and used
Ollama for invoice line extraction. This rewrite drops both: one PHP app,
SQLite for storage, native PDF parsing in PHP. Designed to run on standard
shared PHP hosting (it's deployed on **20i**).

## Structure

```
index.php    Front controller (lives at the repo root so it Just Works
             when the doc root is the cloned repo / public_html).
.htaccess    URL rewriting + access controls.
styles.css   Single stylesheet.
src/         Application code (auth, db, pdf, rules, invoices, operators,
             jotform, travel, jobsheets). Web-blocked via .htaccess.
views/       PHP templates. Web-blocked via .htaccess.
data/        SQLite DB and uploaded files. Created on first run.
             Gitignored and web-blocked.
.env         Optional secrets file, loaded by src/bootstrap.php.
```

## Running locally

Requires PHP 8.1+ with `pdo_sqlite`.

```bash
php -S 0.0.0.0:8080
```

Open <http://localhost:8080>. Default login: **admin / admin123**
(change in `src/bootstrap.php`).

## Deploying to 20i

1. In the 20i control panel, set the PHP version to **8.1 or newer** and
   confirm `pdo_sqlite` is enabled.
2. Clone the repo straight into `public_html` (Git Version Control →
   Clone a Repository → path `public_html`). Use the HTTPS URL once
   you've signed in to GitHub; for SSH URLs add 20i's deploy key (View
   SSH Keys) to the repo on GitHub.
3. The repo's `.htaccess` does two things automatically:
   - routes everything through `index.php`,
   - blocks direct access to `src/`, `views/`, `data/` and dotfiles.
   No document-root reconfiguration needed.
4. Copy `.env.example` to `.env` in the repo root and fill in
   `JOTFORM_API_KEY`, `JOTFORM_STOCK_JOB_FORM_ID`, and
   `OPENROUTESERVICE_API_KEY` if you want those integrations live, or
   set them as environment variables in the 20i panel.
5. Make sure `data/`, `data/uploads/` and `data/debug/` are writable
   by the web user (the app creates them on first request; if that
   fails on your hosting plan, create them via the file manager with
   chmod 775).
6. Enable Let's Encrypt for the domain.
7. Change the default password in `src/bootstrap.php` before going live.

## Features

- Login (session-based, single hard-coded user).
- Upload an invoice PDF; the app extracts lines and runs rule checks:
  - rate matches the operator's role,
  - yard hours don't exceed the daily max,
  - on-site hours roughly match the standard shift,
  - line totals match `hours × rate`,
  - **claimed travel time matches a 2 × OpenRouteService one-way
    estimate (depot → site) within ±1h, using the site postcode from
    the matched Jotform job sheet**.
- List, view and delete invoices, or re-run matching on an existing one.
- Operator CRUD (name, base rate, travel rate, yard rate, HGV flag, notes).
- "Sync Jotform" button pulls Stock Job submissions into a local
  `jobsheets` table so matching is fast and offline-safe. Travel-time
  estimates are cached per postcode for `TRAVEL_CACHE_TTL_DAYS` days.
- Debug JSON endpoints:
  - `POST /debug/parse-invoice` – parse a PDF without storing it.
  - `GET  /debug/test-travel?postcode=BS1+4DJ&claimed=6` – ORS travel-time check.
  - `GET  /debug/jotform/forms` – list Jotform forms for the configured key.
  - `GET  /debug/jotform/stock-job-submissions` – raw stock-job submissions.

## Configuration

Either set these as real environment variables, or put them in `.env`
in the project root:

| Variable | Purpose |
| --- | --- |
| `OPENROUTESERVICE_API_KEY` | Required for `/debug/test-travel`. |
| `JOTFORM_API_KEY` | Required for the Jotform debug endpoints. |
| `JOTFORM_BASE_URL` | Defaults to `https://api.jotform.com`. |
| `JOTFORM_STOCK_JOB_FORM_ID` | Form id to fetch submissions for. |

## Travel-time matching

Every time an invoice is processed (on upload, or via the "Re-run
matching" button on the invoice page) each line is paired with the
Jotform job sheet that:

1. has the same `operator_name` as the invoice's subcontractor, and
2. has the same `work_date` (with a ±1 day fallback), with site-name
   token overlap as a tie-breaker.

The matched job sheet's postcode is fed to OpenRouteService (Severn
crossings avoided) to get a one-way drive time from the depot. The
invoice's `hours_travel` is then compared against `2 × one-way`. Lines
outside `TRAVEL_TOLERANCE_HOURS` (default ±1h) are flagged with a
`Travel mismatch` note and the invoice line drops to `PARTIAL`.

Estimates are cached in `travel_cache` so repeated invoices to the same
postcode don't re-hit ORS; the cache expires after
`TRAVEL_CACHE_TTL_DAYS` (default 30 days). Both knobs live in
`src/bootstrap.php`.

## PDF parsing

The parser:

1. Tries `pdftotext -layout` if the binary is available (it usually isn't
   on shared hosting like 20i — that's fine).
2. Falls back to a basic PHP extractor that pulls text out of
   uncompressed PDF streams.
3. Walks each line looking for a recognised role keyword
   (`main operator`, `second operator`, `yard`, `travel driver`,
   `travel passenger`), pulls the numbers out, and maps the trailing
   numbers to `hours_on_site / hours_travel / hours_yard / rate / total`.

Image-only PDFs and exotic layouts will produce no lines – upload still
succeeds, the line list is just empty so the row can be reviewed manually.
If you need to handle complex PDFs on 20i, drop in `smalot/pdfparser`
via Composer and swap it into `extract_text_from_pdf()` in `src/pdf.php`.
