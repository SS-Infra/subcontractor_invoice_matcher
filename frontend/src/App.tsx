import React, { useState } from "react";
import InvoiceUpload from "./components/InvoiceUpload";
import MatchSummary from "./components/MatchSummary";
import OperatorSettings from "./components/OperatorSettings";
import { Invoice } from "./api";

const App: React.FC = () => {
  const [currentInvoice, setCurrentInvoice] = useState<Invoice | null>(null);
  const [activeTab, setActiveTab] = useState<"invoices" | "operators">(
    "invoices"
  );

  const wrapperStyle: React.CSSProperties = {
    minHeight: "100vh",
    background:
      "radial-gradient(circle at top, #dbeafe 0, #eff6ff 30%, #f9fafb 70%)",
    padding: "2rem 1rem",
  };

  const containerStyle: React.CSSProperties = {
    maxWidth: "1100px",
    margin: "0 auto",
  };

  const navButton = (tab: "invoices" | "operators", label: string) => {
    const isActive = activeTab === tab;
    return (
      <button
        type="button"
        onClick={() => setActiveTab(tab)}
        style={{
          padding: "0.5rem 0.9rem",
          borderRadius: "999px",
          border: "none",
          cursor: "pointer",
          fontSize: "0.9rem",
          fontWeight: 500,
          background: isActive ? "#0f172a" : "transparent",
          color: isActive ? "#f9fafb" : "#1f2937",
          boxShadow: isActive
            ? "0 10px 25px rgba(15, 23, 42, 0.4)"
            : "none",
          transition: "all 0.15s ease",
        }}
      >
        {label}
      </button>
    );
  };

  return (
    <div style={wrapperStyle}>
      <div style={containerStyle}>
        <header
          style={{
            display: "flex",
            justifyContent: "space-between",
            alignItems: "center",
            marginBottom: "1.75rem",
            gap: "1rem",
          }}
        >
          <div>
            <h1
              style={{
                fontSize: "1.5rem",
                fontWeight: 700,
                letterSpacing: "-0.03em",
                color: "#0f172a",
              }}
            >
              Subcontractor Invoice Matcher
            </h1>
            <p style={{ fontSize: "0.9rem", color: "#6b7280" }}>
              Upload subcontractor invoices, compare them to job sheets, and
              manage operator pay settings in one place.
            </p>
          </div>
          <div
            style={{
              backgroundColor: "rgba(255,255,255,0.8)",
              borderRadius: "999px",
              padding: "0.25rem",
              boxShadow: "0 8px 20px rgba(15, 23, 42, 0.15)",
              display: "flex",
              gap: "0.25rem",
            }}
          >
            {navButton("invoices", "Invoices")}
            {navButton("operators", "Operators & Rates")}
          </div>
        </header>

        {activeTab === "invoices" && (
          <main style={{ display: "flex", flexDirection: "column", gap: "1rem" }}>
            <div
              style={{
                background: "#ffffff",
                borderRadius: "1rem",
                boxShadow: "0 10px 30px rgba(15, 23, 42, 0.08)",
                padding: "1.5rem",
              }}
            >
              <h2
                style={{
                  fontSize: "1.05rem",
                  fontWeight: 600,
                  marginBottom: "0.4rem",
                }}
              >
                Upload invoice
              </h2>
              <p
                style={{
                  fontSize: "0.85rem",
                  color: "#6b7280",
                  marginBottom: "0.75rem",
                }}
              >
                Upload a PDF invoice from a subcontractor and we&apos;ll run the
                checks based on your internal rules. Parsing is currently stubbed
                until we hook in your real template logic.
              </p>
              <InvoiceUpload onUploaded={setCurrentInvoice} />
            </div>

            {currentInvoice && <MatchSummary invoice={currentInvoice} />}
          </main>
        )}

        {activeTab === "operators" && (
          <main>
            <OperatorSettings />
          </main>
        )}
      </div>
    </div>
  );
};

export default App;
