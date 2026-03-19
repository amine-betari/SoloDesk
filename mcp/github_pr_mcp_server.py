#!/usr/bin/env python3

import json
import os
import subprocess
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

def _run_git(args: list[str]) -> Optional[str]:
    try:
        result = subprocess.run(
            ["git", *args],
            check=False,
            capture_output=True,
            text=True,
            timeout=10,
        )
    except (OSError, subprocess.SubprocessError):
        return None

    if result.returncode != 0:
        return None

    return result.stdout.strip()

def _build_body_from_git(base: str, head: str) -> Optional[str]:
    diff_range = f"{base}...{head}"
    diff_stat = _run_git(["diff", "--stat", diff_range])
    diff_names = _run_git(["diff", "--name-only", diff_range])
    diff_numstat = _run_git(["diff", "--numstat", diff_range])

    if diff_stat is None or diff_names is None:
        return None

    files = [line.strip() for line in diff_names.splitlines() if line.strip() != ""]
    has_tests = any(path.startswith("tests/") for path in files)
    has_config = any(path.startswith("config/") for path in files)
    has_migrations = any(path.startswith("migrations/") for path in files)
    has_src = any(path.startswith("src/") for path in files)
    has_templates = any(path.startswith("templates/") for path in files)
    has_public = any(path.startswith("public/") for path in files)
    has_assets = any(path.startswith("assets/") for path in files)
    has_translations = any(path.startswith("translations/") for path in files)

    zones = []
    if has_src:
        zones.append("src")
    if has_config:
        zones.append("config")
    if has_migrations:
        zones.append("migrations")
    if has_templates:
        zones.append("templates")
    if has_public:
        zones.append("public")
    if has_assets:
        zones.append("assets")
    if has_translations:
        zones.append("translations")

    risques = "Zones touchees: " + ", ".join(zones) if zones else "Aucune zone critique detectee."
    tests = "Tests modifies: oui" if has_tests else "Tests modifies: non"

    top_files_summary = ""
    if diff_numstat:
        entries = []
        for line in diff_numstat.splitlines():
            parts = line.split("\t")
            if len(parts) < 3:
                continue
            added_str, deleted_str, path = parts[0], parts[1], parts[2]
            try:
                added = int(added_str)
                deleted = int(deleted_str)
            except ValueError:
                continue
            entries.append((added + deleted, added, deleted, path))

        entries.sort(key=lambda item: item[0], reverse=True)
        if entries:
            top = entries[:5]
            lines = [f"- {path}: +{added} / -{deleted}" for _, added, deleted, path in top]
            top_files_summary = "\n".join(lines)

    changements = diff_stat
    if top_files_summary != "":
        changements = f"{diff_stat}\n\nTop fichiers modifies:\n{top_files_summary}"

    migrations_config = (
        "## Migrations/Config\n"
        f"- [{'x' if has_migrations else ' '}] Oui (decrire)\n"
        f"- [{' ' if has_migrations else 'x'}] Non\n"
        f"- [{'x' if has_config else ' '}] Config modifiee\n"
        f"- [{' ' if has_config else 'x'}] Pas de changement de config\n"
    )

    checklist = (
        "## Checklist\n"
        "- [ ] Tests executes\n"
        f"- [{'x' if not has_config else ' '}] Aucun changement de config\n"
        f"- [{'x' if not has_migrations else ' '}] Pas de migration\n"
    )

    return (
        "## Contexte\n"
        f"PR generee depuis {head}\n\n"
        "## Changements\n"
        f"{changements}\n\n"
        "## Tests\n"
        f"{tests}\n\n"
        "## Risques/Impacts\n"
        f"{risques}\n\n"
        f"{migrations_config}\n\n"
        f"{checklist}\n"
    )


@mcp.tool()
def create_pull_request(
    head: str,
    base: str,
    title: Optional[str] = None,
    body: Optional[str] = None,
) -> Dict[str, Any]:
    """
    Create a GitHub Pull Request in amine-betari/SoloDesk.

    - head: source branch (e.g., "feature-branch")
    - base: target branch (e.g., "main")
    - title: PR title (optional, auto-generated from head if omitted)
    - body: optional PR description
    """
    token = _get_env("GITHUB_SOLODESK_TOKEN")
    url = f"{API_BASE}/repos/{OWNER}/{REPO}/pulls"

    if title is None or title.strip() == "":
        title = head.replace("/", " ").replace("-", " ").replace("_", " ").title()

    if body is None or body.strip() == "":
        body = _build_body_from_git(base, head)
        if body is None:
            body = (
                "## Contexte\n\n"
                "## Changements\n\n"
                "## Tests\n\n"
                "## Risques/Impacts\n\n"
                "## Migrations/Config\n"
                "- [ ] Oui (decrire)\n"
                "- [ ] Non\n\n"
                "## Checklist\n"
                "- [ ] Tests executes\n"
                "- [ ] Aucun changement de config\n"
                "- [ ] Pas de migration\n"
            )

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
