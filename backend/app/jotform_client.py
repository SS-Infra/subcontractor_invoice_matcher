import os
import httpx


class JotformError(Exception):
    """Raised when Jotform API returns any error."""
    pass


# Load environment variables
JOTFORM_API_KEY = os.getenv("JOTFORM_API_KEY", "").strip()
JOTFORM_BASE_URL = os.getenv("JOTFORM_BASE_URL", "https://api.jotform.com").strip()
STOCK_JOB_FORM_ID = os.getenv("JOTFORM_STOCK_JOB_FORM_ID", "").strip()


def require_api_key():
    """Ensure the API key is configured."""
    if not JOTFORM_API_KEY:
        raise JotformError(
            "JOTFORM_API_KEY is not set. Put it in your backend container environment variables."
        )


# -----------------------------------------------------------
# Base GET wrapper
# -----------------------------------------------------------

async def jotform_get(path: str, params: dict | None = None) -> dict:
    """
    Generic GET request wrapper for Jotform's API.
    Handles errors and returns JSON.
    """
    require_api_key()

    base = (JOTFORM_BASE_URL or "https://api.jotform.com").rstrip("/")
    url = f"{base}{path}"

    headers = {
        "Accept": "application/json",
    }

    if params is None:
        params = {}

    params["apiKey"] = JOTFORM_API_KEY  # Always include API key

    async with httpx.AsyncClient(timeout=15) as client:
        try:
            resp = await client.get(url, params=params)
        except Exception as exc:
            raise JotformError(f"HTTP error calling Jotform: {exc!r}")

    if resp.status_code != 200:
        raise JotformError(
            f"Jotform returned HTTP {resp.status_code}: {resp.text}"
        )

    data = resp.json()

    if data.get("responseCode") != 200:
        raise JotformError(
            f"Jotform API error: {data.get('message')} ({data})"
        )

    return data


# -----------------------------------------------------------
# List all forms the account can see
# -----------------------------------------------------------

async def get_jotform_forms() -> list[dict]:
    """
    Returns a list of forms visible to this API key.
    """

    data = await jotform_get("/user/forms")

    forms = data.get("content", [])
    if not isinstance(forms, list):
        forms = []

    return forms


# -----------------------------------------------------------
# Fetch submissions for the specific “Stock Job Form”
# -----------------------------------------------------------

async def get_stock_job_form_submissions(
    limit: int = 20,
    offset: int = 0,
) -> list[dict]:
    """
    Fetch raw submissions for the 'Stock Job Form'.
    These are not processed - raw JSON from Jotform.

    Requires env var:
        JOTFORM_STOCK_JOB_FORM_ID="241095621872357"
    """

    require_api_key()

    if not STOCK_JOB_FORM_ID:
        raise JotformError("JOTFORM_STOCK_JOB_FORM_ID is not set.")

    path = f"/form/{STOCK_JOB_FORM_ID}/submissions"

    data = await jotform_get(
        path,
        params={
            "limit": limit,
            "offset": offset,
        },
    )

    submissions = data.get("content", [])
    if not isinstance(submissions, list):
        submissions = []

    return submissions
