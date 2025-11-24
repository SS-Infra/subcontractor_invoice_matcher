from sqlalchemy.ext.asyncio import AsyncSession
from .models import Invoice
from . import jotform_client, rules
from .yard_client import fetch_yard_signins_for_date


async def run_matching_for_invoice(db: AsyncSession, invoice_id: int):
    invoice = await db.get(Invoice, invoice_id)
    if not invoice:
        return

    await db.refresh(invoice)
    subcontractor = invoice.subcontractor

    for line in invoice.lines:
        jobsheets = await jotform_client.fetch_jobsheets_for_date(subcontractor.name, line.work_date)
        yard_records = await fetch_yard_signins_for_date(subcontractor.name, line.work_date)

        has_jobsheet = len(jobsheets) > 0
        has_yard_record = len(yard_records) > 0

        line.jobsheet_id = jobsheets[0].get("id") if jobsheets else None
        line.yard_record_id = yard_records[0].get("id") if yard_records else None

        rules.apply_rules(
            line,
            has_jobsheet=has_jobsheet,
            has_yard_record=has_yard_record,
            has_hgv_license=subcontractor.has_hgv_license if subcontractor else None,
        )

    await db.commit()
