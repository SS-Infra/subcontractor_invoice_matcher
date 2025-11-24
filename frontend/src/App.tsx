import React, { useEffect, useState } from "react";
import InvoiceUpload from "./components/InvoiceUpload";
import MatchSummary from "./components/MatchSummary";
import OperatorSettings from "./components/OperatorSettings";
import Login from "./components/Login";
import { Invoice } from "./api";

const App: React.FC = () => {
  const [currentInvoice, setCurrentInvoice] = useState<Invoice | null>(null);
  const [activeTab, setActiveTab] = useState<"invoices" | "operators">(
    "invoices"
  );
  const [authed, setAuthed] = useState(false);

  useEffect(() => {
    if (localStorage.getItem("sim_authed") === "1") {
      setAuthed(true);
    }
  }, []);

  const handleLogout = () => {
    localStorage.removeItem("sim_authed");
    setAuthed(false);
  };

  if (!authed) {
    return <Login onLoginSuccess={() => setAuthed(true)} />;
  }

  const wrapperStyle: React.CSSProperties = {
    minHeight: "100vh",
    background:
      "radial-gradient(circle at top, #020617 0, #020617 40%, #020617 100%)",
    padding: "2rem 1rem",
    color: "#e5e7eb",
  };

  const containerStyle: React.CSSProperties = {
    maxWidth: "1150px",
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
          color: isActive ? "#e5e7eb" : "#9ca3af",
          boxShadow: isActive
            ? "0 10px 25px rgba(15,23,42,0.85)"
            : "none",
          transition: "all 0.15s ease",
        }}
      >
        {label}
      </button>
    );
  };

  const cardStyle: React.CSSProperties = {
    background: "#020617",
    borderRadius: "1rem",
    boxShadow: "0 18px 40px rgba(15,23,42,0.85)",
    padding: "1.5rem",
    border: "1px solid rgba(148,163,184,0.3)",
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
                fontSize: "1.6rem",
                fontWeight: 700,
                letterSpacing: "-0.04em",
                color: "#f9fafb",
              }}
            >
              Subcontractor Invoice Matcher
            </h1>
            <p style={{ fontSize: "0.9rem", color: "#9ca3af" }}>
              Upload subcontractor invoices, compare them to job sheets, and
              manage operator pay settings in one place.
            </p>
          </div>
          <div
            style={{
              display: "flex",
              flexDirection: "column",
              alignItems: "flex-end",
              gap: "0.4rem",
            }}
          >
            <div
              style={{
                backgroundColor: "rgba(15,23,42,0.9)",
                borderRadius: "999px",
                padding: "0.25rem",
                boxShadow: "0 12px 30px rgba(15,23,42,0.9)",
                display: "flex",
                gap: "0.25rem",
                border: "1px solid rgba(148,163,184,0.4)",
              }}
            >
              {navButton("invoices", "Invoices")}
              {navButton("operators", "Operators & Rates")}
            </div>
            <button
              type="button"
              onClick={handleLogout}
              style={{
                fontSize: "0.75rem",
                color: "#9ca3af",
                background: "transparent",
                border: "none",
                cursor: "pointer",
              }}
            >
              Logout
            </button>
          </div>
        </header>

        {activeTab === "invoices" && (
          <main style={{ display: "flex", flexDirection: "column", gap: "1rem" }}>
            <div style={cardStyle}>
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
                  color: "#9ca3af",
                  marginBottom: "0.75rem",
                }}
              >
                Upload a PDF invoice from a subcontractor and we&apos;ll run the
                checks based on your internal rules. Parsing is currently
                stubbed until we hook in your real template logic.
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
