from datetime import date
from typing import List, Dict
from .models import RoleType


async def parse_invoice_pdf(file_path: str) -> List[Dict]:
    """
    TEMPORARY STUB.

    In production you should:
    - Run OCR or structured PDF parsing
    - Extract per-day lines with:
      work_date, site_location, role, hours_on_site, hours_travel,
      hours_yard, rate_per_hour, line_total
    For now this just returns an empty list so the API works without real parsing.
    """
    # Example of a fake parsed line (delete once real parsing is implemented):
    # return [{
    #     "work_date": date.today(),
    #     "site_location": "Example Site",
    #     "role": RoleType.MAIN_OPERATOR,
    #     "hours_on_site": 8.5,
    #     "hours_travel": 1.0,
    #     "hours_yard": 0.0,
    #     "rate_per_hour": 25.0,
    #     "line_total": 237.5,
    # }]
    return []
