import React, { useEffect, useState } from "react";
import {
  Operator,
  listOperators,
  createOperator,
  updateOperator,
} from "../api";

const cardStyle: React.CSSProperties = {
  background: "#020617",
  borderRadius: "1rem",
  boxShadow: "0 20px 45px rgba(15,23,42,0.9)",
  padding: "1.5rem 1.75rem",
  border: "1px solid #1f2937",
};

const inputStyle: React.CSSProperties = {
  padding: "0.4rem 0.65rem",
  borderRadius: "0.6rem",
  border: "1px solid #1f2937",
  backgroundColor: "#020617",
  color: "#f9fafb",
  fontSize: "0.85rem",
  width: "100%",
};

const labelStyle: React.CSSProperties = {
  fontSize: "0.8rem",
  fontWeight: 500,
  color: "#cbd5f5",
};

const buttonPrimary: React.CSSProperties = {
  padding: "0.5rem 0.95rem",
  borderRadius: "0.7rem",
  border: "none",
  background:
    "linear-gradient(135deg, rgb(59,130,246), rgb(56,189,248))",
  color: "white",
  fontWeight: 600,
  fontSize: "0.85rem",
  cursor: "pointer",
};

const checkboxStyle: React.CSSProperties = {
  width: "1rem",
  height: "1rem",
};

const tableHeadCell: React.CSSProperties = {
  textAlign: "left",
  fontSize: "0.75rem",
  textTransform: "uppercase",
  color: "#9ca3af",
  padding: "0.55rem 0.5rem",
};

const tableCell: React.CSSProperties = {
  padding: "0.45rem 0.5rem",
  fontSize: "0.85rem",
  borderTop: "1px solid #111827",
};

const OperatorSettings: React.FC = () => {
  const [operators, setOperators] = useState<Operator[]>([]);
  const [loading, setLoading] = useState(false);
  const [savingId, setSavingId] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);

  const [newName, setNewName] = useState("");
  const [newBaseRate, setNewBaseRate] = useState("25");
  const [newTravelRate, setNewTravelRate] = useState("17");
  const [newYardRate, setNewYardRate] = useState("17"); // NEW
  const [newHasHgv, setNewHasHgv] = useState(false);
  const [newNotes, setNewNotes] = useState("");

  const fetchData = async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await listOperators();
      setOperators(data);
    } catch (err: any) {
      setError(err?.message ?? "Failed to load operators");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, []);

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newName.trim()) return;
    setError(null);
    try {
      const payload: Omit<Operator, "id"> = {
        name: newName.trim(),
        base_rate: parseFloat(newBaseRate),
        travel_rate: parseFloat(newTravelRate),
        yard_rate: parseFloat(newYardRate),
        has_hgv: newHasHgv,
        notes: newNotes,
      };
      const created = await createOperator(payload);
      setOperators((prev) => [...prev, created]);
      setNewName("");
      setNewBaseRate("25");
      setNewTravelRate("17");
      setNewYardRate("17");
      setNewHasHgv(false);
      setNewNotes("");
    } catch (err: any) {
      setError(err?.message ?? "Failed to create operator");
    }
  };

  const handleUpdateField = (
    id: number,
    field: keyof Operator,
    value: any
  ) => {
    setOperators((prev) =>
      prev.map((op) => (op.id === id ? { ...op, [field]: value } : op))
    );
  };

  const handleSaveRow = async (op: Operator) => {
    setSavingId(op.id);
    setError(null);
    try {
      const updated = await updateOperator(op.id, {
        name: op.name,
        base_rate: op.base_rate,
        travel_rate: op.travel_rate,
        yard_rate: op.yard_rate,
        has_hgv: op.has_hgv,
        notes: op.notes,
      });
      setOperators((prev) =>
        prev.map((x) => (x.id === op.id ? updated : x))
      );
    } catch (err: any) {
      setError(err?.message ?? "Failed to save operator");
    } finally {
      setSavingId(null);
    }
  };

  return (
    <div style={cardStyle}>
      <div
        style={{
          display: "flex",
          justifyContent: "space-between",
          gap: "1rem",
          alignItems: "center",
          marginBottom: "1.1rem",
        }}
      >
        <div>
          <h2
            style={{
              fontSize: "1.1rem",
              fontWeight: 600,
              marginBottom: "0.25rem",
              color: "#f9fafb",
            }}
          >
            Operator & Rate Settings
          </h2>
          <p style={{ fontSize: "0.9rem", color: "#9ca3af" }}>
            Manage each operator&apos;s hourly rate, travel rate, yard rate and
            HGV status.
          </p>
        </div>
      </div>

      {error && (
        <div
          style={{
            marginBottom: "0.8rem",
            padding: "0.5rem 0.75rem",
            borderRadius: "0.5rem",
            backgroundColor: "#450a0a",
            color: "#fecaca",
            fontSize: "0.85rem",
          }}
        >
          {error}
        </div>
      )}

      {/* Create new operator */}
      <form
        onSubmit={handleCreate}
        style={{
          display: "grid",
          gridTemplateColumns: "repeat(6, minmax(0, 1fr))",
          gap: "0.8rem",
          alignItems: "end",
          marginBottom: "1.25rem",
        }}
      >
        <div>
          <div style={labelStyle}>Name</div>
          <input
            style={inputStyle}
            type="text"
            value={newName}
            onChange={(e) => setNewName(e.target.value)}
            required
          />
        </div>
        <div>
          <div style={labelStyle}>Base rate (£/hr)</div>
          <input
            style={inputStyle}
            type="number"
            step="0.01"
            value={newBaseRate}
            onChange={(e) => setNewBaseRate(e.target.value)}
            required
          />
        </div>
        <div>
          <div style={labelStyle}>Travel rate (£/hr)</div>
          <input
            style={inputStyle}
            type="number"
            step="0.01"
            value={newTravelRate}
            onChange={(e) => setNewTravelRate(e.target.value)}
            required
          />
        </div>
        <div>
          <div style={labelStyle}>Yard rate (£/hr)</div>
          <input
            style={inputStyle}
            type="number"
            step="0.01"
            value={newYardRate}
            onChange={(e) => setNewYardRate(e.target.value)}
            required
          />
        </div>
        <div>
          <div style={labelStyle}>HGV</div>
          <div style={{ display: "flex", alignItems: "center", gap: "0.4rem" }}>
            <input
              style={checkboxStyle}
              type="checkbox"
              checked={newHasHgv}
              onChange={(e) => setNewHasHgv(e.target.checked)}
            />
            <span style={{ fontSize: "0.8rem", color: "#f9fafb" }}>
              Has HGV licence
            </span>
          </div>
        </div>
        <div>
          <div style={labelStyle}>Notes (optional)</div>
          <input
            style={inputStyle}
            type="text"
            value={newNotes}
            onChange={(e) => setNewNotes(e.target.value)}
          />
        </div>
        <div style={{ gridColumn: "1 / -1", textAlign: "right" }}>
          <button type="submit" style={buttonPrimary} disabled={loading}>
            Add operator
          </button>
        </div>
      </form>

      {/* List / edit operators */}
      {loading && operators.length === 0 ? (
        <p style={{ fontSize: "0.85rem", color: "#9ca3af" }}>Loading…</p>
      ) : operators.length === 0 ? (
        <p style={{ fontSize: "0.85rem", color: "#9ca3af" }}>
          No operators yet. Add your first one above.
        </p>
      ) : (
        <div style={{ overflowX: "auto" }}>
          <table
            style={{
              width: "100%",
              borderCollapse: "collapse",
              marginTop: "0.25rem",
              color: "#e5e7eb",
            }}
          >
            <thead>
              <tr>
                <th style={tableHeadCell}>Name</th>
                <th style={tableHeadCell}>Base (£/hr)</th>
                <th style={tableHeadCell}>Travel (£/hr)</th>
                <th style={tableHeadCell}>Yard (£/hr)</th>
                <th style={tableHeadCell}>HGV</th>
                <th style={tableHeadCell}>Notes</th>
                <th style={tableHeadCell}></th>
              </tr>
            </thead>
            <tbody>
              {operators.map((op) => (
                <tr key={op.id}>
                  <td style={tableCell}>
                    <input
                      style={inputStyle}
                      type="text"
                      value={op.name}
                      onChange={(e) =>
                        handleUpdateField(op.id, "name", e.target.value)
                      }
                    />
                  </td>
                  <td style={tableCell}>
                    <input
                      style={inputStyle}
                      type="number"
                      step="0.01"
                      value={op.base_rate}
                      onChange={(e) =>
                        handleUpdateField(
                          op.id,
                          "base_rate",
                          parseFloat(e.target.value)
                        )
                      }
                    />
                  </td>
                  <td style={tableCell}>
                    <input
                      style={inputStyle}
                      type="number"
                      step="0.01"
                      value={op.travel_rate}
                      onChange={(e) =>
                        handleUpdateField(
                          op.id,
                          "travel_rate",
                          parseFloat(e.target.value)
                        )
                      }
                    />
                  </td>
                  <td style={tableCell}>
                    <input
                      style={inputStyle}
                      type="number"
                      step="0.01"
                      value={op.yard_rate}
                      onChange={(e) =>
                        handleUpdateField(
                          op.id,
                          "yard_rate",
                          parseFloat(e.target.value)
                        )
                      }
                    />
                  </td>
                  <td style={tableCell}>
                    <input
                      style={checkboxStyle}
                      type="checkbox"
                      checked={op.has_hgv}
                      onChange={(e) =>
                        handleUpdateField(op.id, "has_hgv", e.target.checked)
                      }
                    />
                  </td>
                  <td style={tableCell}>
                    <input
                      style={inputStyle}
                      type="text"
                      value={op.notes}
                      onChange={(e) =>
                        handleUpdateField(op.id, "notes", e.target.value)
                      }
                    />
                  </td>
                  <td style={tableCell}>
                    <button
                      type="button"
                      style={{
                        ...buttonPrimary,
                        padding: "0.35rem 0.8rem",
                        fontSize: "0.8rem",
                      }}
                      onClick={() => handleSaveRow(op)}
                      disabled={savingId === op.id}
                    >
                      {savingId === op.id ? "Saving…" : "Save"}
                    </button>
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

export default OperatorSettings;
