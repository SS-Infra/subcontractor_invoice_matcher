import React, { useState } from "react";

interface LoginProps {
  onLoginSuccess: () => void;
}

const cardStyle: React.CSSProperties = {
  background: "#020617",
  borderRadius: "1.2rem",
  padding: "2rem",
  maxWidth: "380px",
  width: "100%",
  boxShadow: "0 24px 80px rgba(15,23,42,0.8)",
  border: "1px solid rgba(148,163,184,0.15)",
};

const inputStyle: React.CSSProperties = {
  width: "100%",
  padding: "0.6rem 0.8rem",
  borderRadius: "0.8rem",
  border: "1px solid #1f2937",
  backgroundColor: "#020617",
  color: "#e5e7eb",
  fontSize: "0.9rem",
};

const labelStyle: React.CSSProperties = {
  fontSize: "0.8rem",
  fontWeight: 500,
  color: "#9ca3af",
};

const buttonStyle: React.CSSProperties = {
  width: "100%",
  marginTop: "0.75rem",
  padding: "0.7rem 1rem",
  borderRadius: "999px",
  border: "none",
  background:
    "linear-gradient(135deg, rgb(59,130,246), rgb(56,189,248))",
  color: "#f9fafb",
  fontWeight: 600,
  fontSize: "0.9rem",
  cursor: "pointer",
};

const Login: React.FC<LoginProps> = ({ onLoginSuccess }) => {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  // Very simple front-end only auth.
  const VALID_USER = "admin";
  const VALID_PASS = "admin123";

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setLoading(true);
    setTimeout(() => {
      if (username === VALID_USER && password === VALID_PASS) {
        localStorage.setItem("sim_authed", "1");
        onLoginSuccess();
      } else {
        setError("Invalid username or password.");
      }
      setLoading(false);
    }, 300);
  };

  return (
    <div
      style={{
        minHeight: "100vh",
        background:
          "radial-gradient(circle at top, #020617 0, #020617 40%, #020617 100%)",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        padding: "1.5rem",
      }}
    >
      <div style={cardStyle}>
        <h1
          style={{
            fontSize: "1.4rem",
            fontWeight: 700,
            letterSpacing: "-0.04em",
            color: "#e5e7eb",
            marginBottom: "0.25rem",
          }}
        >
          Subcontractor Invoice Matcher
        </h1>
        <p
          style={{
            fontSize: "0.85rem",
            color: "#9ca3af",
            marginBottom: "1.4rem",
          }}
        >
          Internal tool. Sign in to upload invoices and manage operator
          rates.
        </p>

        <form onSubmit={handleSubmit} style={{ display: "flex", flexDirection: "column", gap: "0.8rem" }}>
          <div>
            <div style={labelStyle}>Username</div>
            <input
              style={inputStyle}
              type="text"
              autoComplete="username"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              required
            />
          </div>
          <div>
            <div style={labelStyle}>Password</div>
            <input
              style={inputStyle}
              type="password"
              autoComplete="current-password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
          </div>

          {error && (
            <div
              style={{
                fontSize: "0.8rem",
                color: "#fca5a5",
                backgroundColor: "#450a0a",
                borderRadius: "0.6rem",
                padding: "0.4rem 0.6rem",
                marginTop: "0.3rem",
              }}
            >
              {error}
            </div>
          )}

          <button
            type="submit"
            style={buttonStyle}
            disabled={loading}
          >
            {loading ? "Signing in…" : "Sign in"}
          </button>

          <p
            style={{
              marginTop: "0.6rem",
              fontSize: "0.75rem",
              color: "#6b7280",
            }}
          >
            Default credentials: <strong>admin / admin123</strong>  
            (change these in <code>Login.tsx</code> for your environment).
          </p>
        </form>
      </div>
    </div>
  );
};

export default Login;
