export interface InvoiceLine {
  id: number;
  work_date: string;
  site_location: string;
  role: string;
  hours_on_site: number;
  hours_travel: number;
  hours_yard: number;
  rate_per_hour: number;
  line_total: number;
  match_status: string;
  match_score: number;
  match_notes: string;
  jobsheet_id?: string | null;
  yard_record_id?: string | null;
}

export interface Invoice {
  id: number;
  invoice_number: string;
  invoice_date: string;
  total_amount: number;
  subcontractor_name: string;
  lines: InvoiceLine[];
}

const API_BASE = "http://localhost:8000";

export async function uploadInvoice(
  subcontractor_name: string,
  invoice_number: string,
  invoice_date: string,
  file: File
): Promise<Invoice> {
  const formData = new FormData();
  formData.append("subcontractor_name", subcontractor_name);
  formData.append("invoice_number", invoice_number);
  formData.append("invoice_date", invoice_date);
  formData.append("file", file);

  const res = await fetch(`${API_BASE}/invoices/upload`, {
    method: "POST",
    body: formData
  });
  if (!res.ok) {
    throw new Error(`Upload failed: ${res.status}`);
  }
  return res.json();
}
