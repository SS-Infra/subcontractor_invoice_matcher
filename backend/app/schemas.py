from __future__ import annotations

from datetime import date
from typing import List, Optional

from pydantic import BaseModel


# ---------- Operator schemas ----------


class OperatorBase(BaseModel):
    name: str
    base_rate: float
    travel_rate: float
    yard_rate: float          # NEW
    has_hgv: bool
    notes: str = ""


class OperatorCreate(OperatorBase):
    pass


class OperatorUpdate(BaseModel):
    name: Optional[str] = None
    base_rate: Optional[float] = None
    travel_rate: Optional[float] = None
    yard_rate: Optional[float] = None  # NEW
    has_hgv: Optional[bool] = None
    notes: Optional[str] = None


class OperatorRead(OperatorBase):
    id: int

    class Config:
        from_attributes = True


# ---------- Invoice & lines ----------


class InvoiceLineBase(BaseModel):
    work_date: Optional[date] = None
    site_location: str
    role: str
    hours_on_site: float
    hours_travel: float
    hours_yard: float
    rate_per_hour: float
    line_total: float
    match_status: str
    match_score: float
    match_notes: str
    jobsheet_id: Optional[str] = None
    yard_record_id: Optional[str] = None


class InvoiceLineRead(InvoiceLineBase):
    id: int

    class Config:
        from_attributes = True


class InvoiceRead(BaseModel):
    id: int
    invoice_number: str
    invoice_date: date
    total_amount: float
    subcontractor_name: str
    lines: List[InvoiceLineRead] = []

    class Config:
        from_attributes = True
