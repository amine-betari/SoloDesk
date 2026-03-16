#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VENV_DIR="$ROOT_DIR/.venv-mcp"

if [ ! -d "$VENV_DIR" ]; then
  if ! command -v python3 >/dev/null 2>&1; then
    echo "python3 not found. Please install Python 3." >&2
    exit 1
  fi
  python3 -m venv "$VENV_DIR"
fi

if [ ! -f "$VENV_DIR/bin/activate" ]; then
  echo "Virtualenv creation failed. Please remove $VENV_DIR and rerun." >&2
  exit 1
fi

# shellcheck disable=SC1091
source "$VENV_DIR/bin/activate"

pip install -r "$ROOT_DIR/mcp/requirements.txt"

export MYSQL_HOST="${MYSQL_HOST:-127.0.0.1}"
export MYSQL_PORT="${MYSQL_PORT:-3399}"
export MYSQL_USER="${MYSQL_USER:-mcp_ro}"
export MYSQL_PASSWORD="${MYSQL_PASSWORD:-mcp_ro_password}"
export MYSQL_DATABASE="${MYSQL_DATABASE:-SoloDesk}"

export MCP_TRANSPORT="${MCP_TRANSPORT:-http}"
export MCP_HOST="${MCP_HOST:-127.0.0.1}"
export MCP_PORT="${MCP_PORT:-7799}"
export MCP_PATH="${MCP_PATH:-/mcp}"

python "$ROOT_DIR/mcp/mysql_mcp_server.py"
