# Subcontractor Invoice Matcher

A small PHP web app for matching subcontractor invoices against job sheet data
(from Jotform's "Stock Job Form") and yard sign-in data.

The previous incarnation was a FastAPI backend + React frontend and used
Ollama for invoice line extraction. This rewrite drops both: one PHP app,
SQLite for storage, native PDF parsing in PHP. Designed to run on standard
shared PHP hosting (it's deployed on **20i**).

## Structure

```
public/      Front controller (index.php), .htaccess, CSS
src/        Application code (auth, db, pdf, rules, invoices, operators,
            jotform, travel)
views/      PHP templates (layout, login, invoices, operators)
data/       SQLite DB and uploaded files (created on first run, gitignored)
.env        Optional secrets file, loaded by src/bootstrap.php
```

## Running locally

Requires PHP 8.1+ with `pdo_sqlite`.

```bash
php -S 0.0.0.0:8080 -t public
```

Open <http://localhost:8080>. Default login: **admin / admin123**
(change in `src/bootstrap.php`).

## Deploying to 20i

1. In the 20i control panel, set the PHP version to **8.1 or newer** and
   confirm `pdo_sqlite` is enabled.
2. Upload the repository to the hosting account. Two layouts work:
   - **Recommended:** point the domain's document root at the
     `public/` directory (Manage Hosting → Web → Directories).
     Keep `src/`, `views/` and `data/` *above* the web root so the
     SQLite DB and uploads aren't directly downloadable.
   - **Or:** copy the contents of `public/` into `public_html/` and put
     `src/`, `views/` and `data/` next to it. Then edit
     `public_html/index.php` so the `require` points at the real
     location of `src/bootstrap.php`.
3. Create `data/`, `data/uploads/` and `data/debug/`; make them writable
   by the web user (chmod 775 via the file manager).
4. Copy `.env.example` to `.env` (outside the web root) and fill in
   `JOTFORM_API_KEY`, `JOTFORM_STOCK_JOB_FORM_ID`, and
   `OPENROUTESERVICE_API_KEY` if you want those integrations live. Or
   set them as environment variables in the 20i panel — either works.
5. Enable Let's Encrypt for the domain.
6. Change the default password in `src/bootstrap.php` before going live.

`public/.htaccess` already routes everything through `index.php`, so no
extra server configuration is needed.

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

## Configuration

Either set these as real environment variables, or put them in `.env`
in the project root:

| Variable | Purpose |
| --- | --- |
| `OPENROUTESERVICE_API_KEY` | Required for `/debug/test-travel`. |
| `JOTFORM_API_KEY` | Required for the Jotform debug endpoints. |
| `JOTFORM_BASE_URL` | Defaults to `https://api.jotform.com`. |
| `JOTFORM_STOCK_JOB_FORM_ID` | Form id to fetch submissions for. |

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
