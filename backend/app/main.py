from __future__ import annotations

from datetime import datetime
import os
from typing import List

from fastapi import FastAPI, Depends, UploadFile, File, HTTPException, Form
from fastapi.middleware.cors import CORSMiddleware
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import select

from .database import get_db, Base, engine
from . import models, schemas
from .matching import run_matching_for_invoice
from .invoice_parser import parse_invoice_pdf

app = FastAPI(title="Subcontractor Invoice Matcher")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # lock down later if you want
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.on_event("startup")
async def startup():
    async with engine.begin() as conn:
        await conn.run_sync(Base.metadata.create_all)


# ---------- Invoice upload & listing ----------


@app.post("/invoices/upload", response_model=schemas.InvoiceRead)
async def upload_invoice(
    subcontractor_name: str = Form(...),
    invoice_number: str = Form(...),
    invoice_date: str = Form(...),
    file: UploadFile = File(...),
    db: AsyncSession = Depends(get_db),
):
    # Store under ./data/uploads so it lives in the backend_data volume
    upload_dir = os.path.join("data", "uploads")
    os.makedirs(upload_dir, exist_ok=True)
    file_path = os.path.join(upload_dir, file.filename)

    with open(file_path, "wb") as f:
        f.write(await file.read())

    # get or create subcontractor
    result = await db.execute(
        select(models.Subcontractor).where(models.Subcontractor.name == subcontractor_name)
    )
    subcontractor = result.scalar_one_or_none()
    if not subcontractor:
        subcontractor = models.Subcontractor(name=subcontractor_name)
        db.add(subcontractor)
        await db.flush()

    inv_date = datetime.fromisoformat(invoice_date).date()

    invoice = models.Invoice(
        subcontractor_id=subcontractor.id,
        invoice_number=invoice_number,
        invoice_date=inv_date,
        file_path=file_path,
        total_amount=0.0,
    )
    db.add(invoice)
    await db.flush()

    # parse PDF into lines (currently AI/template based)
    lines_data = await parse_invoice_pdf(file_path)
    for l in lines_data:
        line = models.InvoiceLine(invoice_id=invoice.id, **l)
        db.add(line)

    await db.commit()
    await db.refresh(invoice)

    # run matching (stubbed / your own logic)
    await run_matching_for_invoice(db, invoice.id)
    await db.refresh(invoice)

    return schemas.InvoiceRead(
        id=invoice.id,
        invoice_number=invoice.invoice_number,
        invoice_date=invoice.invoice_date,
        total_amount=invoice.total_amount,
        subcontractor_name=subcontractor.name,
        lines=invoice.lines,
    )


@app.get("/invoices", response_model=List[schemas.InvoiceRead])
async def list_invoices(db: AsyncSession = Depends(get_db)):
    result = await db.execute(select(models.Invoice))
    invoices = result.scalars().all()
    out: List[schemas.InvoiceRead] = []
    for inv in invoices:
        out.append(
            schemas.InvoiceRead(
                id=inv.id,
                invoice_number=inv.invoice_number,
                invoice_date=inv.invoice_date,
                total_amount=inv.total_amount,
                subcontractor_name=inv.subcontractor.name if inv.subcontractor else "",
                lines=inv.lines,
            )
        )
    return out


# ---------- Operators CRUD ----------


@app.get("/operators", response_model=List[schemas.OperatorRead])
async def list_operators(db: AsyncSession = Depends(get_db)):
    result = await db.execute(select(models.Operator))
    operators = result.scalars().all()
    return operators


@app.post("/operators", response_model=sche_
