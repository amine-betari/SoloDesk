#!/usr/bin/env python3

import csv
import os
from pathlib import Path
from typing import Any, Dict, List

from fastmcp import FastMCP

mcp = FastMCP("solodesk-csv")


def _get_exports_dir() -> Path:
    exports_dir = os.getenv("EXPORTS_DIR", "/home/amine/exports")
    path = Path(exports_dir).expanduser().resolve()
    if not path.exists() or not path.is_dir():
        raise RuntimeError(f"EXPORTS_DIR does not exist or is not a directory: {path}")
    return path


def _safe_path(file_name: str) -> Path:
    base = _get_exports_dir()
    candidate = (base / file_name).resolve()
    if base not in candidate.parents and candidate != base:
        raise RuntimeError("Invalid file path.")
    if candidate.suffix.lower() != ".csv":
        raise RuntimeError("Only .csv files are allowed.")
    if not candidate.exists() or not candidate.is_file():
        raise RuntimeError(f"File not found: {candidate}")
    return candidate


@mcp.tool()
def list_exports() -> Dict[str, Any]:
    """List available CSV export files in EXPORTS_DIR."""
    base = _get_exports_dir()
    files = sorted([p.name for p in base.glob("*.csv")])
    return {"files": files}


@mcp.tool()
def read_csv(file: str, limit: int = 50, offset: int = 0) -> Dict[str, Any]:
    """Read rows from a CSV export file with pagination."""
    if limit < 1 or limit > 500:
        return {"error": "limit must be between 1 and 500"}
    if offset < 0:
        return {"error": "offset must be >= 0"}

    path = _safe_path(file)

    with path.open(newline="", encoding="utf-8") as f:
        reader = csv.DictReader(f)
        if reader.fieldnames is None:
            return {"error": "CSV has no header row"}

        rows: List[Dict[str, Any]] = []
        skipped = 0
        for row in reader:
            if skipped < offset:
                skipped += 1
                continue
            rows.append(row)
            if len(rows) >= limit:
                break

    return {
        "columns": reader.fieldnames,
        "rows": rows,
        "row_count": len(rows),
        "offset": offset,
        "limit": limit,
    }


if __name__ == "__main__":
    host = os.getenv("MCP_HOST", "127.0.0.1")
    port = int(os.getenv("MCP_PORT", "7800"))
    path = os.getenv("MCP_PATH", "/mcp")
    mcp.run(transport="http", host=host, port=port, path=path)
