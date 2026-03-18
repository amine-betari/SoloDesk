#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

echo "Starting MCP servers..."

RUN_MYSQL=false
RUN_CSV=false
RUN_GH=false

if [ "$#" -eq 0 ]; then
  RUN_MYSQL=true
  RUN_CSV=true
  RUN_GH=true
else
  for arg in "$@"; do
    case "$arg" in
      -h|--help)
        echo "Usage: $0 [--mysql] [--csv] [--github]"
        echo "If no options are provided, all servers are started."
        exit 0
        ;;
      --list)
        echo "Available MCP servers:"
        echo "- solodesk-mysql (mcp/run.sh)"
        echo "- solodesk-csv (mcp/run_csv.sh)"
        echo "- solodesk-github-pr (mcp/run_github_pr.sh)"
        exit 0
        ;;
      --mysql)
        RUN_MYSQL=true
        ;;
      --csv)
        RUN_CSV=true
        ;;
      --github)
        RUN_GH=true
        ;;
      *)
        echo "Unknown option: $arg" >&2
        echo "Usage: $0 [--mysql] [--csv] [--github]" >&2
        exit 1
        ;;
    esac
  done
fi

if [ "$RUN_MYSQL" = true ]; then
  # MySQL MCP
  "$ROOT_DIR/mcp/run.sh" &
  PID_MYSQL=$!
  echo "solodesk-mysql PID: $PID_MYSQL"
fi

if [ "$RUN_CSV" = true ]; then
  # CSV MCP
  "$ROOT_DIR/mcp/run_csv.sh" &
  PID_CSV=$!
  echo "solodesk-csv PID: $PID_CSV"
fi

if [ "$RUN_GH" = true ]; then
  # GitHub PR MCP
  "$ROOT_DIR/mcp/run_github_pr.sh" &
  PID_GH=$!
  echo "solodesk-github-pr PID: $PID_GH"
fi

echo "All MCP servers started."
echo "Press Ctrl+C to stop them."

wait
