from datetime import date
from typing import List, Optional
from pydantic import BaseModel
from .models import RoleType, MatchStatus


# ----- Subcontractor / Operator -----


class SubcontractorBase(BaseModel):
    name: str
    email: Optional[str] = None
    has_hgv_license: bool = False


class SubcontractorCreate(SubcontractorBase):
    pass


class SubcontractorUpdate(BaseModel):
    name: Optional[str] = None
    email: Optional[str] = None
    has_hgv_license: Optional[bool] = None


class SubcontractorRead(SubcontractorBase):
    id: int

    class Config:
        from_attributes = True


# ----- Invoice / InvoiceLine -----


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
