from __future__ import annotations

from sqlalchemy import (
    Column,
    Integer,
    String,
    Float,
    Boolean,
    Date,
    ForeignKey,
    Text,
)
from sqlalchemy.orm import relationship

from .database import Base


class Subcontractor(Base):
    __tablename__ = "subcontractors"

    id = Column(Integer, primary_key=True, index=True)
    name = Column(String(255), unique=True, nullable=False)

    invoices = relationship("Invoice", back_populates="subcontractor")


class Invoice(Base):
    __tablename__ = "invoices"

    id = Column(Integer, primary_key=True, index=True)
    subcontractor_id = Column(
      Integer,
      ForeignKey("subcontractors.id", ondelete="SET NULL"),
      nullable=True,
    )
    invoice_number = Column(String(100), nullable=False)
    invoice_date = Column(Date, nullable=False)
    total_amount = Column(Float, nullable=False, default=0.0)
    file_path = Column(String(500), nullable=False)

    subcontractor = relationship("Subcontractor", back_populates="invoices")
    lines = relationship("InvoiceLine", back_populates="invoice")


class InvoiceLine(Base):
    __tablename__ = "invoice_lines"

    id = Column(Integer, primary_key=True, index=True)
    invoice_id = Column(
      Integer,
      ForeignKey("invoices.id", ondelete="CASCADE"),
      nullable=False,
    )

    work_date = Column(Date, nullable=True)
    site_location = Column(String(255), nullable=False, default="")
    role = Column(String(255), nullable=False, default="")

    hours_on_site = Column(Float, nullable=False, default=0.0)
    hours_travel = Column(Float, nullable=False, default=0.0)
    hours_yard = Column(Float, nullable=False, default=0.0)

    rate_per_hour = Column(Float, nullable=False, default=0.0)
    line_total = Column(Float, nullable=False, default=0.0)

    match_status = Column(String(50), nullable=False, default="NEEDS_REVIEW")
    match_score = Column(Float, nullable=False, default=0.0)
    match_notes = Column(Text, nullable=False, default="")

    jobsheet_id = Column(String(100), nullable=True)
    yard_record_id = Column(String(100), nullable=True)

    invoice = relationship("Invoice", back_populates="lines")


class Operator(Base):
    __tablename__ = "operators"

    id = Column(Integer, primary_key=True, index=True)
    name = Column(String(255), unique=True, nullable=False)

    # Main on-site rate
    base_rate = Column(Float, nullable=False)

    # Travel time rate
    travel_rate = Column(Float, nullable=False)

    # NEW: yard rate when they’re in the yard but not assigned to a job
    yard_rate = Column(Float, nullable=False, default=17.0)

    has_hgv = Column(Boolean, nullable=False, default=False)
    notes = Column(String(255), nullable=False, default="")
