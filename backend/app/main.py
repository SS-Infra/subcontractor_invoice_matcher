from __future__ import annotations

import os
from datetime import datetime
from typing import List

import httpx
from fastapi import (
    FastAPI,
    Depends,
    UploadFile,
    File,
    HTTPException,
    Form,
)
from fastapi.middleware.cors import CORSMiddleware
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import select

from .database import Base, engine, get_db
from . import models, schemas
from .invoice_parser import parse_invoice_pdf as ollama_parse_invoice_pdf  # used only by debug endpoint
from .services.travel_estimator import check_travel_time_claim
from .jotform_client import (  # NEW
    get_jotform_forms,
    get_stock_job_form_submissions,
    JotformError,
)

# -------------------------------------------------------------------
# App setup
# -------------------------------------------------------------------

app = FastAPI(title="Subcontractor Invoice Matcher")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.on_event("startup")
async def on_startup() -> None:
    async with engine.begin() as conn:
        await conn.run_sync(Base.metadata.create_all)


# Ollama config (used by debug test)
OLLAMA_BASE_URL = os.getenv("OLLAMA_BASE_URL", "http://localhost:11434")
OLLAMA_MODEL = os.getenv("OLLAMA_MODEL", "llama3")


# -------------------------------------------------------------------
# TEMP STUBS used by the main upload flow (no Ollama here yet)
# -------------------------------------------------------------------

async def parse_invoice_pdf(file_path: str):
    """
    TEMP: stub – returns no lines.
    The real Ollama parser is available via the /debug/parse-invoice endpoint.
    """
    return []


async def run_matching_for_invoice(db: AsyncSession, invoice_id: int) -> None:
    """
    TEMP: stub for future matching logic.
    """
    return None


# -------------------------------------------------------------------
# INVOICE ENDPOINTS
# -------------------------------------------------------------------


@app.post("/invoices/upload", response_model=schemas.InvoiceRead)
async def upload_invoice(
    subcontractor_name: str = Form(...),
    invoice_number: str = Form(...),
    invoice_date: str = Form(...),
    file: UploadFile = File(...),
    db: AsyncSession = Depends(get_db),
) -> schemas.InvoiceRead:
    """
    Upload an invoice PDF and create the invoice record.
    Parsing + matching is currently stubbed.
    """

    upload_dir = os.path.join("data", "uploads")
    os.makedirs(upload_dir, exist_ok=True)
    file_path = os.path.join(upload_dir, file.filename)

    # Save the uploaded file
    with open(file_path, "wb") as f:
        f.write(await file.read())

    # Ensure subcontractor exists
    result = await db.execute(
        select(models.Subcontractor).where(
            models.Subcontractor.name == subcontractor_name
        )
    )
    subcontractor = result.scalar_one_or_none()
    if subcontractor is None:
        subcontractor = models.Subcontractor(name=subcontractor_name)
        db.add(subcontractor)
        await db.flush()

    # Parse date – expect ISO format from frontend
    try:
        inv_date = datetime.fromisoformat(invoice_date).date()
    except ValueError:
        raise HTTPException(status_code=400, detail="Invalid invoice_date format")

    # Create invoice
    invoice = models.Invoice(
        subcontractor_id=subcontractor.id,
        invoice_number=invoice_number,
        invoice_date=inv_date,
        total_amount=0.0,
        file_path=file_path,
    )
    db.add(invoice)
    await db.flush()

    # STUB: currently no parsed lines stored by default
    lines_data = await parse_invoice_pdf(file_path)
    for line_data in lines_data:
        line = models.InvoiceLine(invoice_id=invoice.id, **line_data)
        db.add(line)

    await db.commit()
    await db.refresh(invoice)

    # Future: run matching
    await run_matching_for_invoice(db, invoice.id)
    await db.refresh(invoice)

    # IMPORTANT: do NOT touch invoice.lines here (async lazy-load causes MissingGreenlet)
    return schemas.InvoiceRead(
        id=invoice.id,
        invoice_number=invoice.invoice_number,
        invoice_date=invoice.invoice_date,
        total_amount=invoice.total_amount,
        subcontractor_name=subcontractor.name if invoice.subcontractor else "",
        lines=[],  # we’ll wire this properly once we load lines explicitly
    )


@app.get("/invoices", response_model=List[schemas.InvoiceRead])
async def list_invoices(db: AsyncSession = Depends(get_db)) -> List[schemas.InvoiceRead]:
    result = await db.execute(select(models.Invoice))
    invoices = result.scalars().all()

    output: List[schemas.InvoiceRead] = []
    for inv in invoices:
        output.append(
            schemas.InvoiceRead(
                id=inv.id,
                invoice_number=inv.invoice_number,
                invoice_date=inv.invoice_date,
                total_amount=inv.total_amount,
                subcontractor_name=inv.subcontractor.name
                if inv.subcontractor
                else "",
                lines=[],  # avoid async lazy-load of inv.lines for now
            )
        )
    return output


# -------------------------------------------------------------------
# OPERATOR ENDPOINTS
# -------------------------------------------------------------------


@app.get("/operators", response_model=List[schemas.OperatorRead])
async def list_operators(db: AsyncSession = Depends(get_db)) -> List[schemas.OperatorRead]:
    result = await db.execute(select(models.Operator))
    operators = result.scalars().all()
    return operators


@app.post("/operators", response_model=schemas.OperatorRead)
async def create_operator(
    payload: schemas.OperatorCreate, db: AsyncSession = Depends(get_db)
) -> schemas.OperatorRead:

    existing_q = await db.execute(
        select(models.Operator).where(models.Operator.name == payload.name)
    )
    existing = existing_q.scalar_one_or_none()
    if existing:
        raise HTTPException(status_code=400, detail="Operator with that name already exists")

    op = models.Operator(
        name=payload.name,
        base_rate=payload.base_rate,
        travel_rate=payload.travel_rate,
        yard_rate=payload.yard_rate,
        has_hgv=payload.has_hgv,
        notes=payload.notes or "",
    )
    db.add(op)
    await db.commit()
    await db.refresh(op)
    return op


@app.put("/operators/{operator_id}", response_model=schemas.OperatorRead)
async def update_operator(
    operator_id: int,
    payload: schemas.OperatorUpdate,
    db: AsyncSession = Depends(get_db),
) -> schemas.OperatorRead:

    op = await db.get(models.Operator, operator_id)
    if op is None:
        raise HTTPException(status_code=404, detail="Operator not found")

    if payload.name is not None:
        op.name = payload.name
    if payload.base_rate is not None:
        op.base_rate = payload.base_rate
    if payload.travel_rate is not None:
        op.travel_rate = payload.travel_rate
    if payload.yard_rate is not None:
        op.yard_rate = payload.yard_rate
    if payload.has_hgv is not None:
        op.has_hgv = payload.has_hgv
    if payload.notes is not None:
        op.notes = payload.notes

    await db.commit()
    await db.refresh(op)
    return op


# -------------------------------------------------------------------
# DEBUG ENDPOINTS – Ollama integration
# -------------------------------------------------------------------


@app.post("/debug/parse-invoice")
async def debug_parse_invoice(file: UploadFile = File(...)) -> dict:
    """
    Debug endpoint to test the Ollama-backed invoice parser.

    - Saves the uploaded PDF under data/debug/
    - Runs the real Ollama parser
    - Returns the parsed lines as JSON

    This does NOT create invoices or invoice lines in the database.
    """
    debug_dir = os.path.join("data", "debug")
    os.makedirs(debug_dir, exist_ok=True)

    filename = file.filename or "debug_invoice.pdf"
    file_path = os.path.join(debug_dir, filename)

    with open(file_path, "wb") as f:
        f.write(await file.read())

    lines = await ollama_parse_invoice_pdf(file_path)

    return {
        "file_path": file_path,
        "line_count": len(lines),
        "lines": lines,
    }


@app.get("/debug/test-ollama")
async def debug_test_ollama() -> dict:
    """
    Simple connectivity test to Ollama.

    Returns either:
    - ok: true and a short response from the model, or
    - HTTP 500 with the error/response from Ollama
    """
    url = f"{OLLAMA_BASE_URL.rstrip('/')}/api/generate"
    payload = {
        "model": OLLAMA_MODEL,
        "prompt": "Reply with just the single word: OK",
        "stream": False,
    }

    try:
        async with httpx.AsyncClient(timeout=10) as client:
            resp = await client.post(url, json=payload)
    except Exception as exc:
        raise HTTPException(
            status_code=500,
            detail=f"Error contacting Ollama at {url}: {exc!r}",
        )

    if resp.status_code != 200:
        # Return the raw text so we can see what Ollama said
        raise HTTPException(
            status_code=resp.status_code,
            detail=f"Ollama returned {resp.status_code}: {resp.text}",
        )

    data = resp.json()
    return {
        "ok": True,
        "model": OLLAMA_MODEL,
        "ollama_response": (data.get("response") or "").strip(),
    }


# -------------------------------------------------------------------
# DEBUG ENDPOINT – Travel-time estimation
# -------------------------------------------------------------------


@app.get("/debug/test-travel", summary="Debug travel-time estimation")
async def debug_test_travel(
    postcode: str,
    claimed: float = 0.0,
    tolerance: float = 1.0,
):
    """
    Quick sanity check for the OpenRouteService travel-time integration.

    Example:
      /debug/test-travel?postcode=BS1+4DJ&claimed=6&tolerance=1

    Returns a JSON object showing:
      - estimated_hours: ORS estimate (one-way, hours)
      - claimed_hours: what you pass in as `claimed`
      - delta_hours: claimed - estimated
      - ok: True if |delta| <= tolerance
      - debug: text with routing details or error info
    """
    result = check_travel_time_claim(
        destination_postcode=postcode,
        claimed_travel_hours=claimed,
        tolerance_hours=tolerance,
    )
    return result


# -------------------------------------------------------------------
# DEBUG ENDPOINTS – Jotform integration
# -------------------------------------------------------------------


@app.get("/debug/jotform/forms")
async def debug_jotform_forms() -> dict:
    """
    List Jotform forms for this account.
    Useful to confirm we can see 'Stock Job Form'.
    """
    try:
        forms = await get_jotform_forms()
    except JotformError as e:
        raise HTTPException(status_code=500, detail=str(e))

    return {
        "count": len(forms),
        "forms": [
            {
                "id": f.get("id"),
                "title": f.get("title"),
                "status": f.get("status"),
                "created_at": f.get("created_at"),
            }
            for f in forms
        ],
    }


@app.get("/debug/jotform/stock-job-submissions")
async def debug_jotform_stock_job_submissions(limit: int = 10, offset: int = 0) -> dict:
    """
    Fetch raw submissions for the 'Stock Job Form'.

    This is a debug endpoint so we can see the exact field structure
    and then design the ShiftRecord mapping.
    """
    try:
        submissions = await get_stock_job_form_submissions(limit=limit, offset=offset)
    except JotformError as e:
        raise HTTPException(status_code=500, detail=str(e))

    return {
        "count": len(submissions),
        "limit": limit,
        "offset": offset,
        "submissions": submissions,
    }
