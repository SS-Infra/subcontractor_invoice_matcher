import React from "react";
import { Invoice } from "../api";

const statusColor: Record<string, string> = {
  MATCHED: "green",
  PARTIAL_MATCH: "orange",
  NEEDS_REVIEW: "red",
  REJECTED: "red"
};

const MatchSummary: React.FC<{ invoice: Invoice }> = ({ invoice }) => {
  return (
    <div style={{ marginTop: "1.5rem" }}>
      <h2>
        Invoice {invoice.invoice_number} – {invoice.subcontractor_name}
      </h2>
      {invoice.lines.length === 0 && <p>No lines parsed yet (parser stub returns empty list).</p>}
      {invoice.lines.length > 0 && (
        <table style={{ borderCollapse: "collapse", width: "100%", marginTop: "0.5rem" }}>
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
            {invoice.lines.map(line => (
              <tr key={line.id}>
                <td>{line.work_date}</td>
                <td>{line.site_location}</td>
                <td>{line.role}</td>
                <td>
                  {line.hours_on_site} / {line.hours_travel} / {line.hours_yard}
                </td>
                <td>£{line.rate_per_hour.toFixed(2)}</td>
                <td>£{line.line_total.toFixed(2)}</td>
                <td style={{ color: statusColor[line.match_status] || "black" }}>{line.match_status}</td>
                <td>{line.match_notes}</td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
};

export default MatchSummary;
