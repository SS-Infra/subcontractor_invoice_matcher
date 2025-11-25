from __future__ import annotations

from datetime import datetime
from typing import Any, Dict, List, Optional

from pypdf import PdfReader

from .ollama_client import call_ollama_chat, parse_json_from_model_output, OllamaError


def extract_text_from_pdf(file_path: str) -> str:
    """
    Extract raw text from a PDF using pypdf.

    This will not be perfect for all layouts, but it's a solid starting point.
    """
    reader = PdfReader(file_path)
    chunks: List[str] = []

    for page in reader.pages:
        text = page.extract_text() or ""
        chunks.append(text)

    return "\n\n".join(chunks)


def _normalise_date(value: Optional[str]) -> Optional[str]:
    if not value:
        return None

    value = value.strip()

    # Try a few common formats
    for fmt in ("%Y-%m-%d", "%d/%m/%Y", "%d-%m-%Y", "%d/%m/%y"):
        try:
            dt = datetime.strptime(value, fmt)
            return dt.date().isoformat()
        except ValueError:
            continue

    # If nothing matches, just return None and let the DB accept NULL
    return None


def _to_float(value: Any, default: float = 0.0) -> float:
    if value is None or value == "":
        return default
    try:
        return float(value)
    except (TypeError, ValueError):
        return default


def _coerce_line(raw_line: Dict[str, Any]) -> Dict[str, Any]:
    """
    Coerce a raw dictionary from the model into the shape expected by InvoiceLineBase.
    """
    work_date = _normalise_date(raw_line.get("work_date"))

    return {
        "work_date": work_date,
        "site_location": str(raw_line.get("site_location") or "").strip(),
        "role": str(raw_line.get("role") or "").strip(),
        "hours_on_site": _to_float(raw_line.get("hours_on_site"), 0.0),
        "hours_travel": _to_float(raw_line.get("hours_travel"), 0.0),
        "hours_yard": _to_float(raw_line.get("hours_yard"), 0.0),
        "rate_per_hour": _to_float(raw_line.get("rate_per_hour"), 0.0),
        "line_total": _to_float(raw_line.get("line_total"), 0.0),
        # Matching fields will be filled later when we cross-check with Jotform / job sheets
        "match_status": "NEEDS_REVIEW",
        "match_score": 0.0,
        "match_notes": "",
        "jobsheet_id": None,
        "yard_record_id": None,
    }


async def parse_invoice_pdf(file_path: str) -> List[Dict[str, Any]]:
    """
    Main entrypoint: extracts text from the PDF, sends it to Ollama, and returns
    a list of dictionaries ready to be used to create InvoiceLine rows.

    This function is async because the Ollama call is async.
    """
    raw_text = extract_text_from_pdf(file_path)

    if not raw_text.strip():
        # No text? Probably an image-only PDF. For now just return no lines.
        return []

    # Truncate giant invoices so we don't blow context. Adjust if needed.
    max_chars = 8000
    if len(raw_text) > max_chars:
        raw_text = raw_text[:max_chars]

    schema_description = """
You must extract the invoice lines and output ONLY valid JSON in the following schema:

{
  "lines": [
    {
      "work_date": "YYYY-MM-DD or null",
      "site_location": "string",
      "role": "string",
      "hours_on_site": number,
      "hours_travel": number,
      "hours_yard": number,
      "rate_per_hour": number,
      "line_total": number
    }
  ]
}

Rules:
- Use decimal hours for all hour fields (e.g. 7.5 for 7 hours 30 minutes).
- If a field is not present, use 0 for numeric fields and null for work_date.
- DO NOT include any extra fields.
- DO NOT include any explanation or text outside the JSON.
"""

    prompt = f"""{schema_description}

Here is the raw text of the invoice:

```INVOICE_TEXT
{raw_text}
```"""

    try:
        model_output = await call_ollama_chat(prompt, temperature=0.0)
        parsed = parse_json_from_model_output(model_output)
    except OllamaError as e:
        # For now, fail soft: log and return no lines.
        # Later we might want to surface this to the UI.
        print(f"[invoice_parser] Ollama error while parsing invoice: {e}")
        return []

    lines_raw = parsed.get("lines")
    if not isinstance(lines_raw, list):
        print(f"[invoice_parser] Parsed JSON missing 'lines' array: {parsed!r}")
        return []

    lines: List[Dict[str, Any]] = []
    for raw_line in lines_raw:
        if isinstance(raw_line, dict):
            lines.append(_coerce_line(raw_line))

    return lines
