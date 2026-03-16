---
name: sales-analytics
description: Answer business analytics questions using CSV exports (invoices/payments) exposed via MCP. Use when the user asks for totals, trends, counts, or summaries based on CSV exports.
---

# Sales Analytics (CSV via MCP)

## Overview

Use MCP CSV exports to compute business metrics (totals, counts, trends) from invoices and payments without touching production data.

## Workflow

1. Call `list_exports` to discover available CSV files.
2. Use `read_csv` with pagination to load relevant rows.
3. Compute the requested metric (sum, count, grouping).
4. Present results with clear assumptions (date columns, filters).

## Defaults

When the user does not specify:
- Date source: use `invoice_date` for invoices, `date` for payments (if present).
- Currency: group results by currency column if present; otherwise assume a single currency.

## Notes

Prefer asking a single clarifying question only when the column names are ambiguous.
