from __future__ import annotations

import json
import os
from typing import Any, Dict

import httpx

# Base URL to your Ollama instance, e.g. "http://ollama:11434" or "http://192.168.10.50:11434"
OLLAMA_BASE_URL = os.getenv("OLLAMA_BASE_URL", "http://ollama:11434").rstrip("/")
# Default model used for invoice parsing
OLLAMA_MODEL = os.getenv("OLLAMA_MODEL", "llama3.1:8b")


class OllamaError(RuntimeError):
    pass


async def call_ollama_chat(prompt: str, *, temperature: float = 0.0) -> str:
    """
    Call Ollama's /api/chat endpoint and return the assistant's message content.
    """
    url = f"{OLLAMA_BASE_URL}/api/chat"

    payload: Dict[str, Any] = {
        "model": OLLAMA_MODEL,
        "messages": [
            {
                "role": "system",
                "content": (
                    "You are an invoice parsing assistant. "
                    "You strictly follow instructions and always output valid JSON when requested."
                ),
            },
            {
                "role": "user",
                "content": prompt,
            },
        ],
        "stream": False,
        "options": {
            "temperature": temperature,
        },
    }

    async with httpx.AsyncClient(timeout=120) as client:
        resp = await client.post(url, json=payload)

    if resp.status_code != 200:
        raise OllamaError(f"Ollama HTTP {resp.status_code}: {resp.text}")

    data = resp.json()

    # Newer Ollama responses look like: {"message": {"role": "assistant", "content": "..."}}
    message = data.get("message") or {}
    content = message.get("content")
    if not isinstance(content, str):
        raise OllamaError(f"Ollama response missing 'message.content': {data!r}")

    return content


def parse_json_from_model_output(raw: str) -> Dict[str, Any]:
    """
    Try to parse JSON from the model output.

    - First try direct json.loads
    - If that fails, try to extract the first '{' .. last '}' chunk
    """
    raw = raw.strip()

    # direct attempt
    try:
        return json.loads(raw)
    except json.JSONDecodeError:
        pass

    # try to slice between first { and last }
    start = raw.find("{")
    end = raw.rfind("}")
    if start != -1 and end != -1 and end > start:
        snippet = raw[start : end + 1]
        try:
            return json.loads(snippet)
        except json.JSONDecodeError:
            pass

    raise OllamaError(f"Could not parse JSON from model output: {raw[:400]}...")
