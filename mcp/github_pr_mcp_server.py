#!/usr/bin/env python3

import json
import os
from typing import Any, Dict, Optional
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen

from fastmcp import FastMCP

mcp = FastMCP("solodesk-github-pr")

OWNER = "amine-betari"
REPO = "SoloDesk"
API_BASE = "https://api.github.com"


def _get_env(name: str, default: Optional[str] = None) -> str:
    value = os.getenv(name, default)
    if value is None or value == "":
        raise RuntimeError(f"Missing required env var: {name}")
    return value


def _make_request(url: str, token: str, payload: Dict[str, Any]) -> Dict[str, Any]:
    data = json.dumps(payload).encode("utf-8")
    req = Request(
        url,
        data=data,
        method="POST",
        headers={
            "Authorization": f"Bearer {token}",
            "Accept": "application/vnd.github+json",
            "Content-Type": "application/json",
            "User-Agent": "solodesk-mcp",
        },
    )

    try:
        with urlopen(req, timeout=30) as response:
            body = response.read().decode("utf-8")
            return {"status": response.status, "data": json.loads(body)}
    except HTTPError as exc:
        try:
            body = exc.read().decode("utf-8")
            data = json.loads(body)
        except Exception:
            data = {"message": "Failed to parse GitHub error response."}
        return {"status": exc.code, "error": data}
    except URLError as exc:
        return {"status": 0, "error": {"message": str(exc)}}


@mcp.tool()
def create_pull_request(
    title: str,
    head: str,
    base: str,
    body: Optional[str] = None,
) -> Dict[str, Any]:
    """
    Create a GitHub Pull Request in amine-betari/SoloDesk.

    - title: PR title
    - head: source branch (e.g., "feature-branch")
    - base: target branch (e.g., "main")
    - body: optional PR description
    """
    token = _get_env("GITHUB_SOLODESK_TOKEN")
    url = f"{API_BASE}/repos/{OWNER}/{REPO}/pulls"

    payload: Dict[str, Any] = {
        "title": title,
        "head": head,
        "base": base,
    }
    if body is not None:
        payload["body"] = body

    result = _make_request(url, token, payload)
    if "error" in result:
        return {
            "error": "GitHub API error",
            "status": result["status"],
            "details": result["error"],
        }

    data = result["data"]
    return {
        "status": result["status"],
        "number": data.get("number"),
        "id": data.get("id"),
        "url": data.get("url"),
        "html_url": data.get("html_url"),
        "state": data.get("state"),
        "title": data.get("title"),
        "head": data.get("head", {}).get("ref"),
        "base": data.get("base", {}).get("ref"),
    }


if __name__ == "__main__":
    host = os.getenv("MCP_HOST", "127.0.0.1")
    port = int(os.getenv("MCP_PORT", "7801"))
    path = os.getenv("MCP_PATH", "/mcp")
    mcp.run(transport="http", host=host, port=port, path=path)
