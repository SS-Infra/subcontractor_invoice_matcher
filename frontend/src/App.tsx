import React, { useState } from "react";
import InvoiceUpload from "./components/InvoiceUpload";
import MatchSummary from "./components/MatchSummary";
import { Invoice } from "./api";

const App: React.FC = () => {
  const [currentInvoice, setCurrentInvoice] = useState<Invoice | null>(null);

  return (
    <div style={{ padding: "2rem", fontFamily: "system-ui, sans-serif" }}>
      <h1>Subcontractor Invoice Matcher</h1>
      <p style={{ maxWidth: 600 }}>
        Upload a subcontractor invoice PDF and this tool will (once parsing is implemented) compare it
        against job sheet and yard data, and flag anything that needs review.
      </p>
      <InvoiceUpload onUploaded={setCurrentInvoice} />
      {currentInvoice && <MatchSummary invoice={currentInvoice} />}
    </div>
  );
};

export default App;
