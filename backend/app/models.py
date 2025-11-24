from sqlalchemy import Column, Integer, String, Float, Date, ForeignKey, Enum, Text, Boolean
from sqlalchemy.orm import relationship
from .database import Base
import enum


class RoleType(str, enum.Enum):
    MAIN_OPERATOR = "main_operator"
    SECOND_OPERATOR = "second_operator"
    YARD = "yard"
    TRAVEL_DRIVER = "travel_driver"
    TRAVEL_PASSENGER = "travel_passenger"


class MatchStatus(str, enum.Enum):
    MATCHED = "MATCHED"
    PARTIAL = "PARTIAL_MATCH"
    REJECTED = "REJECTED"
    NEEDS_REVIEW = "NEEDS_REVIEW"


class Subcontractor(Base):
    """
    This doubles as your operator table.
    You can flag whether they hold an HGV license here.
    """
    __tablename__ = "subcontractors"
    id = Column(Integer, primary_key=True, index=True)
    name = Column(String, unique=True, index=True)
    email = Column(String, unique=True, nullable=True)
    has_hgv_license = Column(Boolean, default=False)

    invoices = relationship("Invoice", back_populates="subcontractor")


class Invoice(Base):
    __tablename__ = "invoices"
    id = Column(Integer, primary_key=True, index=True)
    subcontractor_id = Column(Integer, ForeignKey("subcontractors.id"))
    invoice_number = Column(String, index=True)
    invoice_date = Column(Date)
    file_path = Column(String)
    total_amount = Column(Float, default=0.0)

    subcontractor = relationship("Subcontractor", back_populates="invoices")
    lines = relationship("InvoiceLine", back_populates="invoice", cascade="all, delete-orphan")


class InvoiceLine(Base):
    __tablename__ = "invoice_lines"
    id = Column(Integer, primary_key=True, index=True)
    invoice_id = Column(Integer, ForeignKey("invoices.id"))

    work_date = Column(Date, index=True)
    site_location = Column(String)
    role = Column(Enum(RoleType))
    hours_on_site = Column(Float, default=0.0)
    hours_travel = Column(Float, default=0.0)
    hours_yard = Column(Float, default=0.0)
    rate_per_hour = Column(Float)
    line_total = Column(Float)

    match_status = Column(Enum(MatchStatus), default=MatchStatus.NEEDS_REVIEW)
    match_score = Column(Float, default=0.0)
    match_notes = Column(Text, default="")

    jobsheet_id = Column(String, nullable=True)
    yard_record_id = Column(String, nullable=True)

    invoice = relationship("Invoice", back_populates="lines")
