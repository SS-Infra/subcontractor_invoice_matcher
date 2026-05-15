# Subcontractor Invoice Matcher

A small PHP web app for matching subcontractor invoices against job sheet data
(from Jotform's "Stock Job Form") and yard sign-in data.

The previous incarnation was a FastAPI backend + React frontend and used
Ollama for invoice line extraction. This rewrite drops both: one PHP app,
SQLite for storage, native PDF parsing in PHP.

## Structure

```
public/      Front controller (index.php), .htaccess, CSS
src/         Application code (auth, db, pdf, rules, invoices, operators,
             jotform, travel)
views/       PHP templates (layout, login, invoices, operators)
data/        SQLite DB and uploaded files (created on first run, gitignored)
Dockerfile
docker-compose.yml
```

## Quick start (Docker)

```bash
docker compose up --build
```

Then open <http://localhost:8080>. Default login: **admin / admin123**
(change in `src/bootstrap.php`).

## Quick start (without Docker)

Requires PHP 8.1+ with `pdo_sqlite` and (optionally) the `pdftotext` binary
from `poppler-utils` for best PDF text extraction.

```bash
php -S 0.0.0.0:8080 -t public
```

## Features

- Login (session-based, single hard-coded user).
- Upload an invoice PDF; the app extracts lines and runs rule checks:
  - rate matches the operator's role,
  - yard hours don't exceed the daily max,
  - on-site hours roughly match the standard shift,
  - line totals match `hours × rate`.
- List, view and delete invoices.
- Operator CRUD (name, base rate, travel rate, yard rate, HGV flag, notes).
- Debug JSON endpoints:
  - `POST /debug/parse-invoice` – parse a PDF without storing it.
  - `GET  /debug/test-travel?postcode=BS1+4DJ&claimed=6` – ORS travel-time check.
  - `GET  /debug/jotform/forms` – list Jotform forms for the configured key.
  - `GET  /debug/jotform/stock-job-submissions` – raw stock-job submissions.

## Environment variables

| Variable | Purpose |
| --- | --- |
| `OPENROUTESERVICE_API_KEY` | Required for `/debug/test-travel`. |
| `JOTFORM_API_KEY` | Required for the Jotform debug endpoints. |
| `JOTFORM_BASE_URL` | Defaults to `https://api.jotform.com`. |
| `JOTFORM_STOCK_JOB_FORM_ID` | Form id to fetch submissions for. |

## PDF parsing

The parser:

1. Extracts text with `pdftotext -layout`. If the binary is missing, it
   falls back to a basic PHP extractor that handles uncompressed PDF text
   streams.
2. Walks each non-empty line looking for a recognised role keyword
   (`main operator`, `second operator`, `yard`, `travel driver`,
   `travel passenger`), pulls the numbers out, and maps the trailing
   numbers to `hours_on_site / hours_travel / hours_yard / rate / total`.

Image-only PDFs and exotic layouts will produce no lines – upload still
succeeds, the line list is just empty so the row can be reviewed manually.
