import React from "react";
import { Invoice } from "../api";

const cardStyle: React.CSSProperties = {
  marginTop: "1.4rem",
  background: "#020617",
  borderRadius: "1rem",
  boxShadow: "0 20px 45px rgba(15,23,42,0.9)",
  padding: "1.5rem 1.75rem",
  border: "1px solid #1f2937",
};

const headerText: React.CSSProperties = {
  fontSize: "1.1rem",
  fontWeight: 600,
  marginBottom: "0.35rem",
  color: "#f9fafb",
};

const subtitle: React.CSSProperties = {
  fontSize: "0.9rem",
  color: "#9ca3af",
  marginBottom: "0.8rem",
};

const MatchSummary: React.FC<{ invoice: Invoice }> = ({ invoice }) => {
  const statusColor: Record<string, string> = {
    MATCHED: "#22c55e",
    PARTIAL_MATCH: "#eab308",
    NEEDS_REVIEW: "#fb923c",
    REJECTED: "#f97373",
  };

  return (
    <div style={cardStyle}>
      <h2 style={headerText}>
        Invoice {invoice.invoice_number} – {invoice.subcontractor_name}
      </h2>
      <p style={subtitle}>
        {invoice.lines.length === 0
          ? "No invoice lines were parsed (parser stub currently returns an empty list)."
          : "Review the automatically checked lines below."}
      </p>
      {invoice.lines.length > 0 && (
        <div style={{ overflowX: "auto" }}>
          <table
            style={{
              borderCollapse: "collapse",
              width: "100%",
              fontSize: "0.9rem",
              color: "#e5e7eb",
            }}
          >
