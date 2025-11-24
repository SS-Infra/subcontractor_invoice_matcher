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

export interface Operator {
  id: number;
  name: string;
  email?: string | null;
  has_hgv_license: boolean;
}

const API_BASE = "http://localhost:8000"; // change if needed


// ----- Invoices -----

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
    body: formData,
  });
  if (!res.ok) {
    throw new Error(`Upload failed: ${res.status}`);
  }
  return res.json();
}

export async function listInvoices(): Promise<Invoice[]> {
  const res = await fetch(`${API_BASE}/invoices`);
  if (!res.ok) {
    throw new Error("Failed to fetch invoices");
  }
  return res.json();
}


// ----- Operators -----

export async function listOperators(): Promise<Operator[]> {
  const res = await fetch(`${API_BASE}/operators`);
  if (!res.ok) {
    throw new Error("Failed to fetch operators");
  }
  return res.json();
}

export async function createOperator(
  data: Omit<Operator, "id">
): Promise<Operator> {
  const res = await fetch(`${API_BASE}/operators`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
  });
  if (!res.ok) {
    throw new Error("Failed to create operator");
  }
  return res.json();
}

export async function updateOperator(
  id: number,
  data: Partial<Omit<Operator, "id">>
): Promise<Operator> {
  const res = await fetch(`${API_BASE}/operators/${id}`, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
  });
  if (!res.ok) {
    throw new Error("Failed to update operator");
  }
  return res.json();
}
