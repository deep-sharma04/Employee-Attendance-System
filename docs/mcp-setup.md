# Internal Model Context Protocol (MCP) Server Setup Guide

## Architecture Overview

This project uses an **MCP-First** architecture. The Laravel backend functions as a secure Model Context Protocol server exposing authorized business tools (`project.*`, `client.*`, `task.*`, `timesheet.*`, `employee.search`) to connected AI clients (e.g. VS Code GitHub Copilot Agent, Anti-Gravity).

Laravel does **not** call any external LLM providers directly and does **not** store any third-party AI API keys.

---

## 1. Authentication & Security (T277)

MCP requests require authentication and adhere strictly to the authenticated user's RBAC role and project/team/client scope.

To generate a secure local MCP access token for your user account:

```bash
php artisan mcp:token <your-email@hrm.local>
```

Output:
```text
Generated secure MCP token for Manager Alpha (manager):
mcp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

Set this in your local MCP client configuration as an environment variable:
  MCP_AUTH_TOKEN=mcp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

---

## 2. Connecting AI Clients via STDIO Transport (T276)

### Option A: Anti-Gravity / VS Code MCP Configuration

In your project root or IDE configuration file (`mcp.json`):

```json
{
  "mcpServers": {
    "hrm": {
      "command": "php",
      "args": ["artisan", "mcp:serve", "--token=YOUR_MCP_TOKEN_HERE"],
      "env": {
        "APP_ENV": "local"
      }
    }
  }
}
```

Or using an environment variable without placing the token in `args`:

```json
{
  "mcpServers": {
    "hrm": {
      "command": "php",
      "args": ["artisan", "mcp:serve"],
      "env": {
        "MCP_AUTH_TOKEN": "YOUR_MCP_TOKEN_HERE"
      }
    }
  }
}
```

---

## 3. Connecting AI Clients via HTTP Transport (T276)

You can also connect HTTP-capable MCP clients to the internal web endpoint:

- **Endpoint**: `POST /mcp`
- **Headers**:
  ```http
  Authorization: Bearer YOUR_MCP_TOKEN_HERE
  Content-Type: application/json
  ```

---

## 4. Available Phase 31 MCP Tools (T278–T284)

| Tool Name | Type | Description |
|---|:---:|---|
| `client.search` | Read | Search clients by keyword or status within authorized scope. |
| `client.create` | Mutation | Create a new client (Super Admin / Manager). |
| `client.update` | Mutation | Update client parameters (Super Admin / Manager). |
| `project.search` | Read | Search projects scoped by role (Super Admin, Manager, Team Lead, Employee, Client). |
| `project.create` | Mutation | Create a project under authorized client/team scope. |
| `project.update` | Mutation | Update project status, priority, health, or dates. |
| `task.create` | Mutation | Create a task within an authorized project. |
| `task.update` | Mutation | Update task details or status. |
| `task.assign` | Mutation | Assign a task to an active, eligible team member. |
| `task.complete` | Mutation | Mark a task as Done (verifying unresolved blockers). |
| `timesheet.search` | Read | Search timesheets within user scope. |
| `timesheet.create` | Mutation | Create a draft timesheet with logged project hours. |
| `employee.search` | Read | Read-only staff search (strictly strips salary, bank details, tax numbers, payroll, and IP allowlist data). |
