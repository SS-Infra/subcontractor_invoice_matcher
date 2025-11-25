from __future__ import annotations

import os
from datetime import datetime
from typing import List

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



async def parse_invoice_pdf(file_path: str) -> list[dict]:
    return []


async def run_matching_for_invoice(db: AsyncSession, invoice_id: int) -> None:
    return None


@app.post("/invoices/upload", response_model=schemas.InvoiceRead)
async def upload_invoice(
    subcontractor_name: str = Form(...),
    invoice_number: str = Form(...),
    invoice_date: str = Form(...),
    file: UploadFile = File(...),
    db: AsyncSession = Depends(get_db),
) -> schemas.InvoiceRead:

    upload_dir = os.path.join("data", "uploads")
    os.makedirs(upload_dir, exist_ok=True)
    file_path = os.path.join(upload_dir, file.filename)

    with open(file_path, "wb") as f:
        f.write(await file.read())

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

    try:
        inv_date = datetime.fromisoformat(invoice_date).date()
    except ValueError:
        raise HTTPException(status_code=400, detail="Invalid invoice_date format")

    invoice = models.Invoice(
        subcontractor_id=subcontractor.id,
        invoice_number=invoice_number,
        invoice_date=inv_date,
        total_amount=0.0,
        file_path=file_path,
    )
    db.add(invoice)
    await db.flush()

    parsed_lines = await parse_invoice_pdf(file_path)
    for line_data in parsed_lines:
        line = models.InvoiceLine(invoice_id=invoice.id, **line_data)
        db.add(line)

    await db.commit()
    await db.refresh(invoice)

    await run_matching_for_invoice(db, invoice.id)
    await db.refresh(invoice)

    return schemas.InvoiceRead(
        id=invoice.id,
        invoice_number=invoice.invoice_number,
        invoice_date=invoice.invoice_date,
        total_amount=invoice.total_amount,
        subcontractor_name=subcontractor.name if invoice.subcontractor else "",
        lines=invoice.lines,
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
                lines=inv.lines,
            )
        )
    return output


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
