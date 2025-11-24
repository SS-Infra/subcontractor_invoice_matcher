from __future__ import annotations

from datetime import datetime, date, timedelta
from typing import List, Dict
import re

from pypdf import PdfReader
from .models import RoleType


WEEKDAY_NAMES = ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"]


def _parse_date_range(text: str) -> list[date]:
    """
    Parse a line like:
      "for works completed between the 17th and 21st of November 2025"
    and return a list of dates in that range.
    """
    pattern = re.compile(
        r"between the (\d{1,2})(?:st|nd|rd|th)? and (\d{1,2})(?:st|nd|rd|th)? of ([A-Za-z]+) (\d{4})",
        re.IGNORECASE,
    )
    m = pattern.search(text)
    if not m:
        return []

    start_day = int(m.group(1))
    end_day = int(m.group(2))
    month_name = m.group(3)
    year = int(m.group(4))

    month_num = datetime.strptime(month_name, "%B").month
    start_date = date(year, month_num, start_day)
    end_date = date(year, month_num, end_day)

    result = []
    current = start_date
    while current <= end_date:
        result.append(current)
        current += timedelta(days=1)
    return result


def _map_weekday_to_date_in_range(weekday_name: str, date_range: list[date]) -> date | None:
    weekday_name = weekday_name.lower()
    for d in date_range:
        if d.strftime("%A").lower() == weekday_name:
            return d
    return None


async def parse_invoice_pdf(file_path: str) -> List[Dict]:
    """
    Parse an invoice like the one provided:
      - Lines such as:
            Monday yard
            9 17.00 153.00

            Tuesday drive hours
            Cardiff redrow homes
            3 17.00 51.00

    Output: list of dicts with the fields required to create InvoiceLine entries.
    """
    reader = PdfReader(file_path)
    full_text = ""
    for page in reader.pages:
        full_text += page.extract_text() + "\n"

    lines = [ln.strip() for ln in full_text.splitlines() if ln.strip()]

    # Build date range from header
    date_range = _parse_date_range(full_text)

    parsed_lines: List[Dict] = []

    desc_buffer: List[str] = []

    number_line_regex = re.compile(
        r"^(?P<qty>\d+(?:\.\d+)?)\s+(?P<unit>\d+(?:\.\d+)?)\s+(?P<amount>\d+(?:\.\d+)?)$"
    )

    for ln in lines:
        m = number_line_regex.match(ln)
        if m:
            # We've hit a "qty rate amount" row -> close off a description block
            if not desc_buffer:
                continue

            desc_text = " ".join(desc_buffer)
            desc_lower = desc_text.lower()

            qty = float(m.group("qty"))
            unit_price = float(m.group("unit"))
            amount = float(m.group("amount"))

            # Determine role & hours
            role = RoleType.MAIN_OPERATOR
            hours_on_site = 0.0
            hours_travel = 0.0
            hours_yard = 0.0

            if "yard" in desc_lower:
                role = RoleType.YARD
                hours_yard = qty
            elif "drive" in desc_lower or "driver" in desc_lower:
                role = RoleType.TRAVEL_DRIVER
                hours_travel = qty
            elif "passenger" in desc_lower:
                role = RoleType.TRAVEL_PASSENGER
                hours_travel = qty
            else:
                # default to main operator work hours
                role = RoleType.MAIN_OPERATOR
                hours_on_site = qty

            # Determine site location:
            # if multiple description lines, assume last line is the location.
            if len(desc_buffer) > 1:
                site_location = desc_buffer[-1]
            else:
                site_location = desc_buffer[0]

            # Extract weekday token to map to actual date in the range
            weekday_in_desc = None
            for wd in WEEKDAY_NAMES:
                if desc_buffer[0].split()[0].lower().startswith(wd[:3]):
                    weekday_in_desc = wd
                    break

            if date_range and weekday_in_desc:
                work_date = _map_weekday_to_date_in_range(weekday_in_desc, date_range) or date.today()
            else:
                work_date = date.today()

            parsed_lines.append(
                {
                    "work_date": work_date,
                    "site_location": site_location,
                    "role": role,
                    "hours_on_site": hours_on_site,
                    "hours_travel": hours_travel,
                    "hours_yard": hours_yard,
                    "rate_per_hour": unit_price,
                    "line_total": amount,
                }
            )

            desc_buffer = []
        else:
            # keep building description
            desc_buffer.append(ln)

    return parsed_lines
