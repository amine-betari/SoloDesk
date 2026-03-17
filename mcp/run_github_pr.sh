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

export MCP_HOST="${MCP_HOST:-127.0.0.1}"
export MCP_PORT="${MCP_PORT:-7801}"
export MCP_PATH="${MCP_PATH:-/mcp}"

export GITHUB_SOLODESK_TOKEN="${GITHUB_SOLODESK_TOKEN:-}"

python "$ROOT_DIR/mcp/github_pr_mcp_server.py"
