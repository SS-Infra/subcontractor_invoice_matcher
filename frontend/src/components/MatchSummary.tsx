import React from "react";
import { Invoice } from "../api";

const statusColor: Record<string, string> = {
  MATCHED: "var(--success)",
  PARTIAL_MATCH: "var(--warning)",
  NEEDS_REVIEW: "var(--danger)",
  REJECTED: "var(--danger)",
};

const statusLabel: Record<string, string> = {
  MATCHED: "Matched",
  PARTIAL_MATCH: "Partial",
  NEEDS_REVIEW: "Needs review",
  REJECTED: "Rejected",
};

interface Props {
  invoice: Invoice | null;
}

const MatchSummary: React.FC<Props> = ({ invoice }) => {
  if (!invoice) {
    return (
      <div className="card">
        <div className="card-header">
          <h2>Latest invoice</h2>
          <span>Results will appear here after upload</span>
        </div>
        <p style={{ color: "var(--text-muted)", fontSize: "0.9rem" }}>
          Upload an invoice on the left to see the parsed lines and any issues the system spots.
        </p>
      </div>
    );
  }

  const lines = invoice.lines || [];

  return (
    <div className="card">
      <div className="card-header">
        <h2>Invoice result</h2>
        <span>
          {invoice.subcontractor_name} • {invoice.invoice_number} •{" "}
          {new Date(invoice.invoice_date).toLocaleDateString()}
        </span>
      </div>

      {lines.length === 0 && (
        <p style={{ color: "var(--text-muted)", fontSize: "0.9rem" }}>
          No lines were parsed from this PDF. If this invoice follows a different layout, the parser may need tweaking.
        </p>
      )}

      {lines.length > 0 && (
        <div className="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Location</th>
                <th>Role</th>
                <th>Hours (site / travel / yard)</th>
                <th>Rate</th>
                <th>Line total</th>
                <th>Status</th>
                <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              {lines.map((line) => (
                <tr key={line.id}>
                  <td>{new Date(line.work_date).toLocaleDateString()}</td>
                  <td>{line.site_location}</td>
                  <td>{line.role}</td>
                  <td>
                    {line.hours_on_site} / {line.hours_travel} / {line.hours_yard}
                  </td>
                  <td>£{line.rate_per_hour.toFixed(2)}</td>
                  <td>£{line.line_total.toFixed(2)}</td>
                  <td>
                    <span className="badge" style={{ color: statusColor[line.match_status] }}>
                      <span
                        className="status-dot"
                        style={{ backgroundColor: statusColor[line.match_status] }}
                      />
                      {statusLabel[line.match_status] ?? line.match_status}
                    </span>
                  </td>
                  <td>{line.match_notes}</td>
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
