from datetime import date
from typing import List, Dict


async def fetch_yard_signins_for_date(subcontractor_name: str, work_date: date) -> List[Dict]:
    """
    Placeholder for yard sign-in integration.
    For now this always returns empty, meaning 'no yard sign-in found'.
    You can later wire this up to CSV, DB, or another system.
    """
    return []
