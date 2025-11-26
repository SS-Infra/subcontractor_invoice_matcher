import os
from functools import lru_cache
from typing import Any, Dict, List, Optional

import httpx

# Allow overriding the base URL for EU accounts etc.
# Default is the standard US endpoint.
JOTFORM_BASE_URL = os.getenv("JOTFORM_BASE_URL", "https://api.jotform.com")

STOCK_JOB_FORM_TITLE = "Stock Job Form"


class JotformError(Exception):
    pass


@lru_cache(maxsize=1)
def get_jotform_api_key() -> str:
    api_key = os.getenv("JOTFORM_API_KEY")
    if not api_key:
        raise JotformError(
            "JOTFORM_API_KEY is not set. "
            "Set it on the backend container environment."
        )
    return api_key


async def _jotform_get(path: str, params: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
    api_key = get_jotform_api_key()
    params = params or {}
    params["apiKey"] = api_key

    url = f"{JOTFORM_BASE_URL.rstrip('/')}/{path.lstrip('/')}"
    async with httpx.AsyncClient(timeout=15) as client:
        resp = await client.get(url, params=params)
    try:
        resp.raise_for_status()
    except httpx.HTTPStatusError as exc:
        raise JotformError(
            f"Jotform API error {exc.response.status_code}: {exc.response.text}"
        ) from exc

    data = resp.json()
    return data


async def get_raw_forms_response() -> Dict[str, Any]:
    """
    Return the raw JSON from /user/forms so we can inspect it in debug.
    """
    return await _jotform_get("/user/forms")


async def get_jotform_forms() -> List[Dict[str, Any]]:
    """
    Return the list of forms for the account (just the 'content' array).
    """
    data = await get_raw_forms_response()
    forms = data.get("content") or []
    # Jotform sometimes returns {} when no forms – normalize to list
    if isinstance(forms, dict):
        forms = []
    return forms


async def get_form_id_by_title(title: str) -> str:
    """
    Find a form ID by its title (exact match).

    Raises JotformError if not found.
    """
    forms = await get_jotform_forms()
    for f in forms:
        if f.get("title") == title:
            form_id = f.get("id")
            if form_id:
                return form_id
    raise JotformError(f"Form with title '{title}' not found in Jotform account.")


async def get_stock_job_form_id() -> str:
    """
    Helper to get the form ID for 'Stock Job Form'.
    """
    return await get_form_id_by_title(STOCK_JOB_FORM_TITLE)


async def get_stock_job_form_submissions(
    limit: int = 50,
    offset: int = 0,
) -> List[Dict[str, Any]]:
    """
    Fetch submissions for the 'Stock Job Form'.

    This is a raw view – we'll map these into ShiftRecords later.
    """
    form_id = await get_stock_job_form_id()
    path = f"/form/{form_id}/submissions"
    data = await _jotform_get(
        path,
        params={
            "limit": limit,
            "offset": offset,
        },
    )
    submissions = data.get("content") or []
    if isinstance(submissions, dict):
        submissions = []
    return submissions
