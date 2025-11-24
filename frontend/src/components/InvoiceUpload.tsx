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
    <form onSubmit={handleSubmit} style={{ display: "flex", flexDirection: "column", gap: "0.5rem", maxWidth: 400 }}>
      <input
        type="text"
        placeholder="Subcontractor name"
        value={subcontractor}
        onChange={e => setSubcontractor(e.target.value)}
        required
      />
      <input
        type="text"
        placeholder="Invoice number"
        value={invoiceNumber}
        onChange={e => setInvoiceNumber(e.target.value)}
        required
      />
      <input
        type="date"
        value={invoiceDate}
        onChange={e => setInvoiceDate(e.target.value)}
        required
      />
      <input
        type="file"
        accept="application/pdf"
        onChange={e => setFile(e.target.files?.[0] ?? null)}
        required
      />
      <button type="submit" disabled={loading}>
        {loading ? "Uploading..." : "Upload & Match"}
      </button>
      {error && <div style={{ color: "red" }}>{error}</div>}
    </form>
  );
};

export default InvoiceUpload;
