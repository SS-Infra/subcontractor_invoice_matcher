from __future__ import annotations

from typing import Any, Dict, List
from pypdf import PdfReader
import json
from datetime import datetime

from .ollama_client import call_ollama


def _extract_pdf_text(file_path: str) -> str:
    """Extract all text from a PDF as a single string."""
    reader = PdfReader(file_path)
    parts: List[str] = []
    for page in reader.pages:
        txt = page.extract_text() or ""
        parts.append(txt)
    return "\n\n".join(parts)


def _normalise_date(value: str | None) -> str | None:
    """
    Try to normalise a date to ISO format YYYY-MM-DD.
    Returns None if we can't be sure.
    """
    if not value:
        return None
    value = value.strip()
    # Try a few common formats
    for fmt in ("%Y-%m-%d", "%d/%m/%Y", "%d-%m-%Y", "%d.%m.%Y", "%d %b %Y"):
        try:
            return datetime.strptime(value, fmt).date().isoformat()
        except ValueError:
            continue
    return None


async def parse_invoice_pdf(file_path: str) -> List[Dict[str, Any]]:
    """
    Use Ollama to parse an invoice PDF into line items.

    Returns a list of dicts ready to be passed into models.InvoiceLine()
    (minus the invoice_id, which upload handler will add).
    """
    raw_text = _extract_pdf_text(file_path)

    # You can tweak this prompt to reflect your real subcontractor invoice layout
    prompt = f"""
You are helping to extract structured data from subcontractor invoices.

The text below is the FULL CONTENTS of a PDF invoice sent to our company.
Your job is to read it carefully and return the WORK LINES as JSON.

We care about:
- work_date: date of the work (not the invoice date), in format YYYY-MM-DD if possible
- site_location: short text name/description of the site or job
- role: the role of the person/plant (e.g. "Road sweeper + driver", "Second man", "HGV driver")
- hours_on_site: number of hours physically on site (can be decimal)
- hours_travel: number of hours travelling to/from site (can be decimal)
- hours_yard: number of hours in the yard/prep (can be decimal)
- rate_per_hour: currency-agnostic hourly rate for the main portion of the line
- line_total: total amount charged for this line

If something is not stated, make your best guess from context OR use 0.

Return ONLY valid JSON in this structure (no explanation text):

{{
  "lines": [
    {{
      "work_date": "YYYY-MM-DD or null",
      "site_location": "string",
      "role": "string",
      "hours_on_site": 0.0,
      "hours_travel": 0.0,
      "hours_yard": 0.0,
      "rate_per_hour": 0.0,
      "line_total": 0.0
    }}
  ]
}}

If there are no clear work lines, return: {{"lines": []}}.

INVOICE TEXT STARTS BELOW:
\"\"\"{raw_text}\"\"\"
INVOICE TEXT ENDS.
"""

    try:
        response_text = await call_ollama(prompt)
    except Exception:
        # If Ollama is down, return empty and let humans enter manually
        return []

    try:
        data = json.loads(response_text)
    except json.JSONDecodeError:
        # Model hallucinated extra text or invalid JSON; safest is empty.
        return []

    raw_lines = data.get("lines", []) or []
    parsed_lines: List[Dict[str, Any]] = []

    for raw in raw_lines:
        # Defensive parsing with defaults
        work_date = _normalise_date(raw.get("work_date")) if isinstance(raw, dict) else None

        def _num(key: str) -> float:
            try:
                val = raw.get(key, 0)  # type: ignore[arg-type]
                return float(val)
            except Exception:
                return 0.0

        line: Dict[str, Any] = {
            "work_date": work_date,
            "site_location": (raw.get("site_location") or "").strip() if isinstance(raw, dict) else "",
            "role": (raw.get("role") or "").strip() if isinstance(raw, dict) else "",
            "hours_on_site": _num("hours_on_site"),
            "hours_travel": _num("hours_travel"),
            "hours_yard": _num("hours_yard"),
            "rate_per_hour": _num("rate_per_hour"),
            "line_total": _num("line_total"),
            # Matching-related fields – set conservative defaults, matching
            # logic can later update these.
            "match_status": "NEEDS_REVIEW",
            "match_score": 0.0,
            "match_notes": "Parsed automatically from invoice using Ollama; review required.",
            "jobsheet_id": None,
            "yard_record_id": None,
        }

        parsed_lines.append(line)

    return parsed_lines
