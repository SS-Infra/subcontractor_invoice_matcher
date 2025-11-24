import React from "react";
import { Invoice } from "../api";

const cardStyle: React.CSSProperties = {
  marginTop: "1.5rem",
  background: "#020617",
  borderRadius: "1rem",
  boxShadow: "0 18px 40px rgba(15,23,42,0.85)",
  padding: "1.5rem",
  border: "1px solid rgba(148,163,184,0.3)",
};

const headerText: React.CSSProperties = {
  fontSize: "1.05rem",
  fontWeight: 600,
  marginBottom: "0.3rem",
  color: "#e5e7eb",
};

const subtitle: React.CSSProperties = {
  fontSize: "0.85rem",
  color: "#9ca3af",
  marginBottom: "0.75rem",
};

const MatchSummary: React.FC<{ invoice: Invoice }> = ({ invoice }) => {
  const statusColor: Record<string, string> = {
    MATCHED: "#22c55e",
    PARTIAL_MATCH: "#eab308",
    NEEDS_REVIEW: "#f97316",
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
              fontSize: "0.85rem",
              color: "#e5e7eb",
            }}
          >
            <thead>
              <tr>
                <th style={{ textAlign: "left", padding: "0.4rem" }}>Date</th>
                <th style={{ textAlign: "left", padding: "0.4rem" }}>
                  Location
                </th>
                <th style={{ textAlign: "left", padding: "0.4rem" }}>Role</th>
                <th style={{ textAlign: "left", padding: "0.4rem" }}>
                  Hours (site / travel / yard)
                </th>
                <th style={{ textAlign: "left", padding: "0.4rem" }}>Rate</th>
                <th style={{ textAlign: "left", padding: "0.4rem" }}>Total</th>
                <th style={{ textAlign: "left", padding: "0.4rem" }}>Status</th>
                <th style={{ textAlign: "left", padding: "0.4rem" }}>Notes</th>
              </tr>
            </thead>
            <tbody>
              {invoice.lines.map((line) => (
                <tr key={line.id}>
                  <td style={{ padding: "0.35rem 0.4rem" }}>{line.work_date}</td>
                  <td style={{ padding: "0.35rem 0.4rem" }}>
                    {line.site_location}
                  </td>
                  <td style={{ padding: "0.35rem 0.4rem" }}>{line.role}</td>
                  <td style={{ padding: "0.35rem 0.4rem" }}>
                    {line.hours_on_site} / {line.hours_travel} /{" "}
                    {line.hours_yard}
                  </td>
                  <td style={{ padding: "0.35rem 0.4rem" }}>
                    £{line.rate_per_hour.toFixed(2)}
                  </td>
                  <td style={{ padding: "0.35rem 0.4rem" }}>
                    £{line.line_total.toFixed(2)}
                  </td>
                  <td
                    style={{
                      padding: "0.35rem 0.4rem",
                      color: statusColor[line.match_status] || "#e5e7eb",
                      fontWeight: 600,
                    }}
                  >
                    {line.match_status}
                  </td>
                  <td style={{ padding: "0.35rem 0.4rem" }}>
                    {line.match_notes}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
};

export default MatchSummary;
