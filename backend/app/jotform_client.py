import os
from functools import lru_cache
from typing import Any, Dict, List, Optional

import httpx

# You can override this via env:
#   JOTFORM_BASE_URL=https://eu-api.jotform.com
JOTFORM_BASE_URL = os.getenv("JOTFORM_BASE_URL", "https://api.jotform.com")

STOCK_JOB_FORM_TITLE = "Stock Job Form"


class JotformError(Exception):
    """Custom error type for Jotform-related issues."""
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

    return resp.json()


# -------------------------
# Public helpers
# -------------------------


async def get_raw_forms_response() -> Dict[str, Any]:
    """
    Raw /user/forms payload from Jotform.
    Useful for debugging (we surface this in /debug/jotform/forms).
    """
    return await _jotform_get("/user/forms")


async def get_jotform_forms() -> List[Dict[str, Any]]:
    """
    Return a normalized list of forms for this account.
    """
    data = await get_raw_forms_response()

    # Jotform's "content" key holds forms
    forms = data.get("content") or []

    # Sometimes content can be {} if empty – normalize to list
    if isinstance(forms, dict):
        forms = []

    return forms


async def get_form_id_by_title(title: str) -> str:
    """
    Find a form ID by its exact title.
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
    Convenience helper for your 'Stock Job Form'.
    """
    return await get_form_id_by_title(STOCK_JOB_FORM_TITLE)


async def get_stock_job_form_submissions(
    limit: int = 50,
    offset: int = 0,
) -> List[Dict[str, Any]]:
    """
    Fetch submissions for 'Stock Job Form'.
    Raw Jotform payload – we’ll map into ShiftRecords later.
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
