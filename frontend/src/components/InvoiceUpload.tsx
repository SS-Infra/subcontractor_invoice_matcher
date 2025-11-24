import React, { useState } from "react";
import { uploadInvoice, Invoice } from "../api";

interface Props {
  onUploaded: (invoice: Invoice) => void;
}

const inputStyle: React.CSSProperties = {
  padding: "0.4rem 0.7rem",
  borderRadius: "0.6rem",
  border: "1px solid #e5e7eb",
  fontSize: "0.9rem",
};

const labelStyle: React.CSSProperties = {
  fontSize: "0.8rem",
  fontWeight: 500,
  color: "#4b5563",
};

const buttonStyle: React.CSSProperties = {
  padding: "0.6rem 1rem",
  borderRadius: "0.8rem",
  border: "none",
  background:
    "linear-gradient(135deg, rgb(59,130,246), rgb(56,189,248))",
  color: "white",
  fontWeight: 600,
  fontSize: "0.9rem",
  cursor: "pointer",
};

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
      const inv = await uploadInvoice(
        subcontractor,
        invoiceNumber,
        invoiceDate,
        file
      );
      onUploaded(inv);
    } catch (err: any) {
      setError(err?.message ?? "Upload failed");
    } finally {
      setLoading(false);
    }
  };

  return (
    <form
      onSubmit={handleSubmit}
      style={{
        display: "grid",
        gridTemplateColumns: "repeat(2, minmax(0, 1fr))",
        gap: "0.75rem",
      }}
    >
      <div>
        <div style={labelStyle}>Subcontractor</div>
        <input
          type="text"
          style={inputStyle}
          placeholder="e.g. ACME Plant Hire"
          value={subcontractor}
          onChange={(e) => setSubcontractor(e.target.value)}
          required
        />
      </div>
      <div>
        <div style={labelStyle}>Invoice number</div>
        <input
          type="text"
          style={inputStyle}
          placeholder="e.g. INV-1234"
          value={invoiceNumber}
          onChange={(e) => setInvoiceNumber(e.target.value)}
          required
        />
      </div>
      <div>
        <div style={labelStyle}>Invoice date</div>
        <input
          type="date"
          style={inputStyle}
          value={invoiceDate}
          onChange={(e) => setInvoiceDate(e.target.value)}
          required
        />
      </div>
      <div>
        <div style={labelStyle}>PDF file</div>
        <input
          type="file"
          style={inputStyle}
          accept="application/pdf"
          onChange={(e) => setFile(e.target.files?.[0] ?? null)}
          required
        />
      </div>
      {error && (
        <div
          style={{
            gridColumn: "1 / -1",
            fontSize: "0.85rem",
            color: "#b91c1c",
          }}
        >
          {error}
        </div>
      )}
      <div
        style={{
          gridColumn: "1 / -1",
          textAlign: "right",
          marginTop: "0.25rem",
        }}
      >
        <button type="submit" style={buttonStyle} disabled={loading}>
          {loading ? "Uploading…" : "Upload & run checks"}
        </button>
      </div>
    </form>
  );
};

export default InvoiceUpload;
