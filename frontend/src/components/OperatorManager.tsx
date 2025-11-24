import React, { useEffect, useState } from "react";
import { Operator, listOperators, createOperator, updateOperator } from "../api";

const OperatorManager: React.FC = () => {
  const [operators, setOperators] = useState<Operator[]>([]);
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [hasHgv, setHasHgv] = useState(false);
  const [loading, setLoading] = useState(false);

  const load = async () => {
    const data = await listOperators();
    setOperators(data);
  };

  useEffect(() => {
    load();
  }, []);

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!name.trim()) return;
    setLoading(true);
    try {
      await createOperator({
        name: name.trim(),
        email: email.trim() || undefined,
        has_hgv_license: hasHgv,
      });
      setName("");
      setEmail("");
      setHasHgv(false);
      await load();
    } finally {
      setLoading(false);
    }
  };

  const toggleHgv = async (op: Operator) => {
    const updated = await updateOperator(op.id, { has_hgv_license: !op.has_hgv_license });
    setOperators((prev) => prev.map((o) => (o.id === op.id ? updated : o)));
  };

  return (
    <div className="card">
      <div className="card-header">
        <h2>Operators & HGV status</h2>
        <span>Used when checking travel driver rates</span>
      </div>

      <form onSubmit={handleCreate} className="input-grid-1col" style={{ marginBottom: "1rem" }}>
        <div className="input-grid">
          <label>
            Name
            <input
              type="text"
              value={name}
              placeholder="e.g. Joshua Dunton-Baker"
              onChange={(e) => setName(e.target.value)}
              required
            />
          </label>
          <label>
            Email (optional)
            <input
              type="email"
              value={email}
              placeholder="operator@example.com"
              onChange={(e) => setEmail(e.target.value)}
            />
          </label>
          <label style={{ alignItems: "flex-start", flexDirection: "row", gap: "0.5rem" }}>
            <input
              type="checkbox"
              checked={hasHgv}
              onChange={(e) => setHasHgv(e.target.checked)}
              style={{ width: "16px" }}
            />
            <span>HGV license holder</span>
          </label>
        </div>
        <div style={{ display: "flex", justifyContent: "flex-end" }}>
          <button type="submit" className="primary" disabled={loading}>
            Add operator
          </button>
        </div>
      </form>

      <div className="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>HGV license</th>
            </tr>
          </thead>
          <tbody>
            {operators.map((op) => (
              <tr key={op.id}>
                <td>{op.name}</td>
                <td>{op.email ?? "—"}</td>
                <td>
                  <button
                    type="button"
                    className="primary"
                    style={{
                      padding: "0.15rem 0.7rem",
                      fontSize: "0.75rem",
                      background: op.has_hgv_license
                        ? "rgba(22,163,74,0.25)"
                        : "rgba(148,163,184,0.15)",
                      color: op.has_hgv_license ? "var(--success)" : "var(--text-muted)",
                      boxShadow: "none",
                    }}
                    onClick={() => toggleHgv(op)}
                  >
                    {op.has_hgv_license ? "HGV" : "No HGV"}
                  </button>
                </td>
              </tr>
            ))}
            {operators.length === 0 && (
              <tr>
                <td colSpan={3} style={{ color: "var(--text-muted)" }}>
                  No operators yet. Add your subcontractor operators above so travel rates can be validated correctly.
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default OperatorManager;
