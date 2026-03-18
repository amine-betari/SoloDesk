# SoloDesk

Test update V2

Minor README update.

## MCP Servers (FR)

### Prerequis

- `python3` disponible dans le PATH.
- Acces aux ressources requises selon le serveur (ex: MySQL, exports CSV, token GitHub).

### Lancer un serveur individuellement

- MySQL MCP: `./mcp/run.sh`
- CSV MCP: `./mcp/run_csv.sh`
- GitHub PR MCP: `./mcp/run_github_pr.sh`

### Lancer tous les serveurs d'un coup

- Tous: `./mcp/run_all.sh`
- Liste: `./mcp/run_all.sh --list`
- Un ou plusieurs: `./mcp/run_all.sh --mysql --csv --github`

### Variables d'environnement (par defaut)

MySQL MCP (`mcp/run.sh`) :

```bash
MYSQL_HOST=127.0.0.1
MYSQL_PORT=3399
MYSQL_USER=mcp_ro
MYSQL_PASSWORD=mcp_ro_password
MYSQL_DATABASE=SoloDesk
MCP_TRANSPORT=http
MCP_HOST=127.0.0.1
MCP_PORT=7799
MCP_PATH=/mcp
```

CSV MCP (`mcp/run_csv.sh`) :

```bash
EXPORTS_DIR=/home/amine/exports
MCP_HOST=127.0.0.1
MCP_PORT=7800
MCP_PATH=/mcp
```

GitHub PR MCP (`mcp/run_github_pr.sh`) :

```bash
MCP_HOST=127.0.0.1
MCP_PORT=7801
MCP_PATH=/mcp
GITHUB_SOLODESK_TOKEN=
```

## MCP Servers (EN)

### Prerequisites

- `python3` available in PATH.
- Access to required resources per server (e.g., MySQL, CSV exports, GitHub token).

### Run a single server

- MySQL MCP: `./mcp/run.sh`
- CSV MCP: `./mcp/run_csv.sh`
- GitHub PR MCP: `./mcp/run_github_pr.sh`

### Run all servers at once

- All: `./mcp/run_all.sh`
- List: `./mcp/run_all.sh --list`
- One or more: `./mcp/run_all.sh --mysql --csv --github`

### Environment variables (defaults)

MySQL MCP (`mcp/run.sh`) :

```bash
MYSQL_HOST=127.0.0.1
MYSQL_PORT=3399
MYSQL_USER=mcp_ro
MYSQL_PASSWORD=mcp_ro_password
MYSQL_DATABASE=SoloDesk
MCP_TRANSPORT=http
MCP_HOST=127.0.0.1
MCP_PORT=7799
MCP_PATH=/mcp
```

CSV MCP (`mcp/run_csv.sh`) :

```bash
EXPORTS_DIR=/home/amine/exports
MCP_HOST=127.0.0.1
MCP_PORT=7800
MCP_PATH=/mcp
```

GitHub PR MCP (`mcp/run_github_pr.sh`) :

```bash
MCP_HOST=127.0.0.1
MCP_PORT=7801
MCP_PATH=/mcp
GITHUB_SOLODESK_TOKEN=
```
