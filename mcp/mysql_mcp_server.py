#!/usr/bin/env python3

import os
import re
from typing import Any, Dict, List

import pymysql
from fastmcp import FastMCP

mcp = FastMCP("solodesk-mysql")


def _get_env(name: str, default: str | None = None) -> str:
    value = os.getenv(name, default)
    if value is None or value == "":
        raise RuntimeError(f"Missing required env var: {name}")
    return value


def _is_read_only_query(sql: str) -> bool:
    # Allow SELECT or WITH (CTE) only. Block multiple statements.
    cleaned = sql.strip().lower()
    if not (cleaned.startswith("select") or cleaned.startswith("with")):
        return False

    # Allow trailing semicolon only; block multiple statements.
    if ";" in cleaned[:-1]:
        return False

    # Basic guard against obvious write keywords.
    forbidden = [
        r"\binsert\b",
        r"\bupdate\b",
        r"\bdelete\b",
        r"\bdrop\b",
        r"\bcreate\b",
        r"\balter\b",
        r"\btruncate\b",
        r"\breplace\b",
        r"\bgrant\b",
        r"\brevoke\b",
    ]
    for pattern in forbidden:
        if re.search(pattern, cleaned):
            return False

    return True


@mcp.tool()
def query(sql: str) -> Dict[str, Any]:
    """
    Run a read-only SQL query (SELECT/CTE only) against the MySQL database.
    Returns column names and rows.
    """
    if not _is_read_only_query(sql):
        return {
            "error": "Only read-only SELECT/CTE queries are allowed.",
        }

    host = _get_env("MYSQL_HOST")
    port = int(_get_env("MYSQL_PORT", "3306"))
    user = _get_env("MYSQL_USER")
    password = _get_env("MYSQL_PASSWORD")
    database = _get_env("MYSQL_DATABASE")

    connection = pymysql.connect(
        host=host,
        port=port,
        user=user,
        password=password,
        database=database,
        cursorclass=pymysql.cursors.DictCursor,
        charset="utf8mb4",
        autocommit=True,
    )

    try:
        with connection.cursor() as cursor:
            cursor.execute(sql)
            rows: List[Dict[str, Any]] = cursor.fetchall()
            columns = list(rows[0].keys()) if rows else []
            return {
                "columns": columns,
                "rows": rows,
                "row_count": len(rows),
            }
    finally:
        connection.close()


if __name__ == "__main__":
    host = os.getenv("MCP_HOST", "127.0.0.1")
    port = int(os.getenv("MCP_PORT", "7799"))
    transport = os.getenv("MCP_TRANSPORT", "http")
    path = os.getenv("MCP_PATH", "/mcp")

    try:
        mcp.run(transport=transport, host=host, port=port, path=path)
    except TypeError:
        mcp.run(transport=transport, host=host, port=port)
