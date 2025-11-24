import React, { useState } from "react";

interface LoginProps {
  onLoginSuccess: () => void;
}

const cardStyle: React.CSSProperties = {
  background: "#020617",
  borderRadius: "1.1rem",
  padding: "2rem",
  maxWidth: "380px",
  width: "100%",
  boxShadow: "0 18px 40px rgba(15,23,42,0.9)",
  border: "1px solid #1f2937",
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
  color: "#cbd5f5",
};

const buttonStyle: React.CSSProperties = {
  width: "100%",
  marginTop: "0.8rem",
  padding: "0.7rem 1rem",
  borderRadius: "999px",
  border: "none",
  background:
    "linear-gradient(135deg, rgb(59,130,246), rgb(56,189,248))",
  color: "#f9fafb",
  fontWeight: 600,
  fontSize: "0.95rem",
  cursor: "pointer",
};

const Login: React.FC<LoginProps> = ({ onLoginSuccess }) => {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

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
    }, 250);
  };

  return (
    <div
