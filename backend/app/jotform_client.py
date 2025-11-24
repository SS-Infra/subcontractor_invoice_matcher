import httpx
from datetime import date
from typing import List, Dict
from .config import settings

BASE_URL = "https://api.jotform.com"


async def fetch_jobsheets_for_date(subcontractor_name: str, work_date: date) -> List[Dict]:
    """
    Placeholder implementation.
    You need to adapt this to your actual Jotform fields.
    """
    if settings.JOTFORM_API_KEY == "changeme":
        # Jotform not configured yet
        return []

    params = {
        "apiKey": settings.JOTFORM_API_KEY,
    }
    url = f"{BASE_URL}/form/{settings.JOTFORM_FORM_ID}/submissions"

    async with httpx.AsyncClient() as client:
        resp = await client.get(url, params=params, timeout=30)
        resp.raise_for_status()
        data = resp.json()

    # TODO: filter by subcontractor name & date based on your form structure
    return data.get("content", [])
