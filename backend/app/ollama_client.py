import os
import httpx


OLLAMA_BASE_URL = os.getenv("OLLAMA_BASE_URL", "http://ollama:11434")
OLLAMA_MODEL = os.getenv("OLLAMA_MODEL", "llama3")


async def call_ollama(prompt: str) -> str:
  """
  Call Ollama's /api/generate endpoint and return the 'response' string.
  Uses non-streaming mode for simplicity.
  """
  url = f"{OLLAMA_BASE_URL.rstrip('/')}/api/generate"
  payload = {
      "model": OLLAMA_MODEL,
      "prompt": prompt,
      "stream": False,
  }

  async with httpx.AsyncClient(timeout=90) as client:
      resp = await client.post(url, json=payload)
      resp.raise_for_status()
      data = resp.json()
      # Ollama returns {"model": "...", "response": "...", ...}
      return data.get("response", "")
