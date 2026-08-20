# Remote MCP Connection & Production Deployment Guide

This guide describes how to connect remote AI clients (such as ChatGPT, Gemini, Claude Desktop, Cursor, or custom MCP proxies) to the Laravel 13 HRM Remote MCP server over HTTPS, and documents all production deployment requirements.

---

## 1. Architecture & Security Overview

```text
AI Client (ChatGPT / Gemini / Claude / Cursor)
   │
   │ HTTP POST /mcp (Basic Auth / Custom Headers / Bearer Token)
   ▼
https://your-hrm.com/mcp
   │
   │ Username + Password Authentication (Hash::check)
   ▼
Authenticated HRM User Context ($user)
   │
   │ Role & Permission Verification (RBAC Policies)
   ▼
Role-Scoped MCP Tools (client.*, project.*, task.*, timesheet.*, employee.search, intelligence.*)
   │
   ▼
HRM Database
```

### Key Security & Design Guarantees:
- **No Extra User Databases**: Uses existing Laravel `users` database table and password hashes.
- **No Anonymous Access**: Every MCP tool call executes in the context of a verified, active HRM user.
- **Role-Based Access Control (RBAC)**: Reuses existing Laravel policies and permissions. A manager can manage projects, an employee can view assigned tasks and submit timesheets, a client remains strictly read-only, and financial/payroll details (salary, bank info, tax IDs) are strictly redacted.
- **Credential Protection**: Passwords are verified via `Hash::check()` and are never logged, stored in plaintext, or returned in tool outputs or error messages.

---

## 2. Client Connection Instructions (Step-by-Step)

### Step 1: Deploy Laravel HRM
Deploy the application to a HTTPS-enabled web server (e.g. `https://your-hrm.com`).

### Step 2: Confirm Endpoint Reachability
Verify that the remote endpoint is accessible:
```bash
curl -i -X POST https://your-hrm.com/mcp
```
*Expected Response*: `HTTP/1.1 401 Unauthorized` with JSON-RPC error code `-32001` and header `WWW-Authenticate: Basic realm="HRM Remote MCP Server"`.

### Step 3: Configure AI Client Authentication
The remote endpoint supports three standard, production-ready authentication mechanisms:

#### Option A: HTTP Basic Authentication (Recommended for AI Connectors)
Pass standard HTTP Basic Auth credentials in the `Authorization` header:
- **Username**: Your HRM account username (e.g. `manager01` or `manager01@hrm.local`)
- **Password**: Your HRM account password

Header:
```http
Authorization: Basic <base64(username:password)>
Content-Type: application/json
```

Example Curl:
```bash
curl -u "manager01:SecretPass123!" \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"tools/list","id":1}' \
  https://your-hrm.com/mcp
```

#### Option B: Custom Headers
If standard HTTP Basic Auth headers are modified by proxies, pass credentials via custom HTTP headers:
- `X-MCP-Username`: `manager01`
- `X-MCP-Password`: `SecretPass123!`

Example Curl:
```bash
curl -H "X-MCP-Username: manager01" \
  -H "X-MCP-Password: SecretPass123!" \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"tools/list","id":1}' \
  https://your-hrm.com/mcp
```

#### Option C: Bearer Token Authentication
Pass a generated secure local MCP token (`php artisan mcp:token <user-email>`):
```http
Authorization: Bearer mcp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

---

## 3. Production Deployment Configuration Requirements

### A. Environment Variables (`.env`)
Ensure the following variables are set in production `.env`:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-hrm.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hrm_production
DB_USERNAME=hrm_db_user
DB_PASSWORD=your_secure_db_password

QUEUE_CONNECTION=database
CACHE_STORE=file
```

### B. HTTPS Requirement & Web Server Configuration
The Remote MCP endpoint **MUST** operate over **HTTPS** in production to prevent credential sniffing.

Example Nginx Server Configuration block:
```nginx
server {
    listen 443 ssl http2;
    server_name your-hrm.com;

    ssl_certificate /etc/letsencrypt/live/your-hrm.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-hrm.com/privkey.pem;

    root /var/www/hrm/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### C. Required PHP Extensions
Ensure the following PHP 8.2+ / 8.5 extensions are enabled:
- `openssl` (for HTTPS & encryption)
- `pdo_mysql` (for database access)
- `mbstring`, `json`, `ctype`, `curl`

### D. Post-Deployment Optimization Commands
Execute the following commands after pulling deployment updates:
```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### E. Queue Worker Requirements
If long-running AI workflow actions or async notifications are used:
```bash
php artisan queue:work --daemon --tries=3
```

---

## 4. Local STDIO Development (Anti-Gravity / IDE Integration)

Local development via STDIO remains 100% active and supported:

Generate local token:
```bash
php artisan mcp:token manager01@hrm.local
```

Config (`mcp.json`):
```json
{
  "mcpServers": {
    "hrm": {
      "command": "php",
      "args": ["artisan", "mcp:serve", "--token=mcp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"]
    }
  }
}
```
