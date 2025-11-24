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
  base_rate: number;
  travel_rate: number;
  has_hgv: boolean;
  notes: string;
}

/**
 * API base URL
 *
 * - By default it uses the same host the frontend is loaded from, but with port 8000.
 *   e.g. if you open http://192.168.10.50:5173 it will call http://192.168.10.50:8000
 * - You can override it with VITE_API_BASE in the frontend env if needed.
 */
const guessedBase = window.location.origin.replace(/:\d+$/, ":8000");
const API_BASE =
  (import.meta as any).env?.VITE_API_BASE || guessedBase;

// ---------- Invoices ----------

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
    throw new Error(`Failed to fetch invoices: ${res.status}`);
  }
  return res.json();
}

// ---------- Operators ----------

export async function listOperators(): Promise<Operator[]> {
  const res = await fetch(`${API_BASE}/operators`);
  if (!res.ok) {
    throw new Error(`Failed to fetch operators: ${res.status}`);
  }
  return res.json();
}

export async function createOperator(
  payload: Omit<Operator, "id">
): Promise<Operator> {
  const res = await fetch(`${API_BASE}/operators`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
  if (!res.ok) {
    const text = await res.text();
    throw new Error(`Failed to create operator: ${res.status} ${text}`);
  }
  return res.json();
}

export async function updateOperator(
  id: number,
  payload: Partial<Omit<Operator, "id">>
): Promise<Operator> {
  const res = await fetch(`${API_BASE}/operators/${id}`, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
  if (!res.ok) {
    const text = await res.text();
    throw new Error(`Failed to update operator: ${res.status} ${text}`);
  }
  return res.json();
}
