from datetime import date
from typing import List, Optional
from pydantic import BaseModel
from .models import RoleType, MatchStatus


class InvoiceLineBase(BaseModel):
    work_date: date
    site_location: str
    role: RoleType
    hours_on_site: float = 0
    hours_travel: float = 0
    hours_yard: float = 0
    rate_per_hour: float
    line_total: float


class InvoiceLineCreate(InvoiceLineBase):
    pass


class InvoiceLineRead(InvoiceLineBase):
    id: int
    match_status: MatchStatus
    match_score: float
    match_notes: str
    jobsheet_id: Optional[str] = None
    yard_record_id: Optional[str] = None

    class Config:
        # Pydantic v2 equivalent of orm_mode = True
        from_attributes = True


class InvoiceBase(BaseModel):
    invoice_number: str
    invoice_date: date
    total_amount: float = 0.0


class InvoiceCreate(InvoiceBase):
    subcontractor_name: str


class InvoiceRead(InvoiceBase):
    id: int
    subcontractor_name: str
    lines: List[InvoiceLineRead]

    class Config:
        from_attributes = True
