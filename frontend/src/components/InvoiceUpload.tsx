import React, { useState } from "react";
import { uploadInvoice, Invoice } from "../api";

interface Props {
  onUploaded: (invoice: Invoice) => void;
}

const InvoiceUpload: React.FC<Props> = ({ onUploaded }) => {
  const [file, setFile] = useState<File | null>(null);
  const [subcontractor, setSubcontractor] = useState("");
  const [invoiceNumber, setInvoiceNumber] = useState("");
  const [invoiceDate, setInvoiceDate] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!file) return;

    setLoading(true);
    setError(null);

    try {
      const inv = await uploadInvoice(subcontractor, invoiceNumber, invoiceDate, file);
      onUploaded(inv);
    } catch (err: any) {
      setError(err?.message ?? "Upload failed");
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="input-grid-1col" style={{ gap: "0.9rem" }}>
      <div className="input-grid">
        <label>
          Subcontractor / Operator
          <input
            type="text"
            value={subcontractor}
            onChange={(e) => setSubcontractor(e.target.value)}
            placeholder="e.g. Joshua Dunton-Baker"
            required
          />
        </label>
        <label>
          Invoice number
          <input
            type="text"
            value={invoiceNumber}
            onChange={(e) => setInvoiceNumber(e.target.value)}
            placeholder="INV-00123"
            required
          />
        </label>
        <label>
          Invoice date
          <input
            type="date"
            value={invoiceDate}
            onChange={(e) => setInvoiceDate(e.target.value)}
            required
          />
        </label>
        <label>
          Invoice PDF
          <input
            type="file"
            accept="application/pdf"
            onChange={(e) => setFile(e.target.files?.[0] ?? null)}
            required
          />
        </label>
      </div>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", gap: "1rem" }}>
        <div style={{ fontSize: "0.8rem", color: "var(--text-muted)" }}>
          PDFs that follow the standard layout will be auto-parsed into yard / drive / work lines.
        </div>
        <button type="submit" className="primary" disabled={loading}>
          {loading ? "Uploading…" : "Upload & Match"}
        </button>
      </div>
      {error && <div style={{ color: "var(--danger)", fontSize: "0.8rem" }}>{error}</div>}
    </form>
  );
};

export default InvoiceUpload;
