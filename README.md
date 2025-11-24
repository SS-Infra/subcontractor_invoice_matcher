# Subcontractor Invoice Matcher

This is a starter project for matching subcontractor invoices against job sheet data
captured via Jotform, plus yard sign-in data.

## Structure

- `backend/` – FastAPI backend (Python)
- `frontend/` – React + Vite frontend (TypeScript)

## Quick start

### Backend

```bash
cd backend
python -m venv .venv
source .venv/bin/activate  # Windows: .venv\Scripts\activate
pip install -r requirements.txt
uvicorn app.main:app --reload --host 0.0.0.0 --port 8000
```

### Frontend

```bash
cd frontend
npm install
npm run dev -- --host 0.0.0.0 --port 5173
```

Adjust the API base URL in `frontend/src/api.ts` to point at your backend.
