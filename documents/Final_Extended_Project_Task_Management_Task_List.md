# Final Extended Project & Task Management Task List

**Project:** Existing HR/Attendance/Payroll System + AI-Powered Team Project & Task Management  
**Scope:** Phases 20–35  
**Status:** Final planning baseline  
**Goal:** Implement the project/task management extension without duplicating the existing HR system, while preserving strict RBAC, data isolation, auditability, and project-scoped AI/MCP behavior.

---

## Phase 20 — Project Foundation

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T199 | Project Setup | Roles & Migration | Developer | Extend User Roles for Project Module | Add `manager`, `team_lead`, and `client` roles while preserving the existing hierarchy: Super Admin -> HR Admin and Super Admin -> Manager. Manager, Team Lead, and Client are separate project-module account roles, not employee permission flags. | Backend | T010 | High | Done |
| T200 | Project Setup | Client Schema | Developer | Create Clients & Contacts Migrations | Build `clients`, `client_contacts`, and `client_users` with ownership/status/contact relationships and referential integrity. | Database | T199 | High | Done |
| T201 | Project Setup | Team Schema | Developer | Create Teams Migration | Build `teams` with exactly one Manager and one Team Lead per team. Support active/inactive state and enforce one primary team per employee. | Database | T199 | High | Done |
| T202 | Project Setup | Project Schema | Developer | Create Projects & Members Migrations | Build `projects`, `project_members`, and required project-member role/status fields. Link projects to clients and teams without duplicating employees. | Database | T200,T201 | High | Done |
| T203 | Auth | RBAC Policies | Developer | Define Project Authorization Scopes | Create Laravel policies for Super Admin, Manager, Team Lead, Employee, and Client. Managers operate within authorized teams/projects; Team Leads within their own team; Clients only see explicitly shared project data. | Backend | T199,T033 | Critical | Done |
| T204 | Auth | Route Groups | Developer | Define Project Route Groups | Create separate authenticated route groups for manager, team-lead, employee, and client areas with server-side middleware and policy enforcement. | Backend | T203 | High | Done |
| T205 | Audit | Integration | Developer | Extend Audit Logger for Projects | Reuse the existing immutable audit infrastructure for project-module actions. | Backend | T153,T204 | High | Done |
| T206 | Project Foundation | Integrity | Developer | Add Project Referential Integrity Rules | Add foreign keys, unique constraints, indexes, soft-delete strategy where appropriate, and safeguards against orphaned clients, teams, projects, members, and assignments. | Database | T200,T201,T202 | High | Done |

---

## Phase 21 — Client Management

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T207 | Client Management | CRUD | Manager / Super Admin | Build Client Management Pages | Create/edit/view clients including company name, status, contacts, and linked projects. | Frontend | T200,T203 | High | Done |
| T208 | Client Management | Contacts | Manager / Super Admin | Build Client Contacts Management | Allow multiple contacts per client with validation and status. | Backend | T207 | Medium | Done |
| T209 | Client Management | Projects Link | Manager / Super Admin | Associate Clients with Projects | Link a client to a project during project creation/editing and enforce authorization. | Backend | T202,T207 | High | Done |
| T210 | Client Management | Documents | Manager / Super Admin | Manage Client Documents | Reuse existing storage with isolated logical folders. Apply the confirmed project document policy. | Backend | T107,T207 | Medium | Done |
| T211 | Client Management | Communication | Manager / Super Admin | Log Client Communication History | Track client emails, calls, meetings, and notes with actor/date/type metadata. | Backend | T207 | Low | Done |
| T212 | Client Management | Portal Access | Manager / Super Admin | Manage Client Portal Access | Create, activate, deactivate, and revoke client portal users. Enforce read-only permissions. | Backend | T200,T203 | High | Done |
| T213 | Client Management | Audit | Developer | Log Client Actions | Audit client creation, updates, contact changes, portal access changes, and relevant document access. | Backend | T205,T207 | Medium | Done |

---

## Phase 22 — Teams & Project Employee Profiles

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T214 | Team Management | CRUD | Manager / Super Admin | Build Team Management Pages | Create/edit teams and assign exactly one Manager and one Team Lead. | Frontend | T201,T203 | High | Done |
| T215 | Team Management | Membership | Manager / Super Admin | Manage Team Membership | Add/remove existing employees and enforce one primary team per employee. Team Lead cannot modify membership. | Backend | T214 | High | Done |
| T216 | Team Management | Team Scope | Developer | Enforce Team Leadership Scope | Calculate Manager and Team Lead access from active team/project membership. | Backend | T203,T215 | High | Done |
| T217 | Employee Profile | Extension | Developer | Extend Employee Profile for Projects | Add skills and availability through an extension table. Do not duplicate or corrupt HR core employee data. | Backend | T011 | Medium | Done |
| T218 | Employee Profile | View | Manager / Team Lead | View Project Employee Profile | Display allowed project fields: name, role, skills, availability, team membership, and active project tasks. | Frontend | T217 | Medium | Done |
| T219 | Team Management | Audit | Developer | Log Team Changes | Audit team creation, manager/TL assignment, membership, and scope-affecting changes. | Backend | T205,T214 | Medium | Done |

---

## Phase 23 — Project Management

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T220 | Project Management | CRUD | Manager / Super Admin | Build Project Management Pages | Create/edit projects with name, client, description, objectives, scope, budget, estimated hours, start date, deadline, status, and priority. | Frontend | T202,T203 | High | Done |
| T221 | Project Management | Milestones | Manager | Manage Project Milestones | Create, update, reorder, complete, and track milestones and deadlines. | Backend | T220 | High | Done |
| T222 | Project Management | Status & Priority | Manager | Manage Project Status & Priority | Support Planning, Active, On Hold, Completed, Cancelled and configured priorities. | Backend | T220 | Medium | Done |
| T223 | Project Management | Health Engine | Developer | Build Deterministic Project Health Engine | Flag project health using deadline/time elapsed versus task completion and overdue-task conditions. Thresholds must be configurable. | Backend | T221,T222 | High | Done |
| T224 | Project Management | Health Configuration | Super Admin | Configure Project Health Thresholds | Add configuration for schedule variance and overdue thresholds without code changes. | Backend | T223 | Medium | Done |
| T225 | Project Management | Audit | Developer | Log Project Actions | Audit project creation, edits, status/priority changes, milestone changes, budget/hour changes, and member changes. | Backend | T205,T220 | High | Done |

---

## Phase 24 — Task Management

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T226 | Task Management | Database Schema | Developer | Create Tasks Migrations | Build `tasks`, `task_dependencies`, `task_checklists`, `task_comments`, `task_history`, and required assignment/status fields. | Database | T202 | High | Done |
| T227 | Task Management | CRUD | Manager / Team Lead | Build Task Creation & Assignment | Create tasks, assign/reassign within authorized team/project scope, set priority/deadline, and validate project membership. | Backend | T226,T203 | High | Done |
| T228 | Task Management | Subtasks | Manager / Team Lead | Support Subtasks & Dependencies | Support child tasks, blocking dependencies, validation, and circular-dependency prevention. | Backend | T227 | Medium | Done |
| T229 | Task Management | Recurring Tasks | Manager / Team Lead | Implement Recurring Task Rules | Support daily/weekly/monthly recurrence with validation and safe scheduling. | Backend | T227 | Low | Done |
| T230 | Task Management | Comments | Employee / Team Lead / Manager | Add Internal Task Comments | Allow internal project comments while maintaining strict separation from client-visible data. | Backend | T227 | Medium | Done |
| T231 | Task Management | Attachments | Employee / Team Lead / Manager | Upload Task Attachments | Reuse existing storage with isolated project/task paths and confirmed upload restrictions. | Backend | T107,T227 | Medium | Done |
| T232 | Task Management | History | Developer | Track Task History | Record assignment, status, deadline, priority, dependency, and relevant changes. | Backend | T227 | High | Done |
| T233 | Task Management | Views | Project Users | Build Kanban & List Views | Provide authorized Kanban/list views with filtering, sorting, search, status, assignee, priority, and deadline. | Frontend | T227 | High | Done |
| T234 | Task Management | Overdue Detection | Developer | Implement Overdue Task Detection | Scheduled job identifies overdue tasks and feeds notifications/health reporting. | Backend | T227 | High | Done |
| T235 | Task Management | Audit | Developer | Log Task Actions | Audit creation, assignment, reassignment, status, deadline, completion, and archive actions. | Backend | T205,T227 | High | Done |

---

## Phase 25 — Manual Timesheets

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T236 | Timesheets | Database Schema | Developer | Create Timesheets Migrations | Build `timesheets` and `timesheet_entries` with project/task linkage, dates, hours, notes, status, submitter, reviewer, and timestamps. | Database | T226 | High | Done |
| T237 | Timesheets | Logging | Employee | Build Manual Timesheet Entry UI | Allow employees to manually log hours against tasks. Keep separate from attendance/punch-in/out. | Frontend | T236 | High | Done |
| T238 | Timesheets | Submission | Employee | Implement Timesheet Submission | Allow daily/weekly submission and prevent unauthorized edits after submission. | Backend | T237 | High | Done |
| T239 | Timesheets | Approval | Developer | Define & Implement Timesheet Approval Workflow | Implement configurable workflow: employee submission -> Team Lead review -> Manager review where required. | Backend | T238,T203 | High | Done |
| T240 | Timesheets | Approval UI | Manager / Team Lead | Build Timesheet Approval Queue | Provide authorized pending/approved/rejected/returned queues. Lock approved records and preserve review history. | Frontend | T239 | High | Done |
| T241 | Timesheets | Cost Calculation | Developer | Calculate Project Labor Cost | Use approved salary data from HR/payroll through an isolated cost service. Do not hardcode `/30/8`; make working-day/hours assumptions configurable. Never modify payroll. | Backend | T240,T018 | High | Done |
| T242 | Timesheets | Audit | Developer | Log Timesheet Actions | Audit creation, editing, submission, approval, rejection, return, and cost calculation without exposing salary values. | Backend | T205,T239 | Medium | Done |

---

## Phase 26 — Client Portal (Strictly Read-Only)

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T243 | Client Portal | Auth | Client | Build Client Login & Dashboard | Dedicated client authentication and dashboard showing only explicitly permitted projects. | Frontend | T200,T203 | High | Done |
| T244 | Client Portal | Project View | Client | View Project Progress & Milestones | Read-only project progress, status, milestones, timelines, and approved information. | Frontend | T220,T243 | High | Done |
| T245 | Client Portal | Documents | Client | View Shared Documents | Allow access only to documents explicitly shared with the client. | Frontend | T210,T243 | Medium | Done |
| T246 | Client Portal | Strict Restrictions | Developer | Enforce Client Read-Only Data Isolation | Backend rejects client writes and prevents access to internal budgets, labor cost, salary, attendance, HR data, internal comments, and internal documents. | Backend | T203,T243 | Critical | Done |
| T247 | Client Portal | Comments | Client | Define Client Comment Capability | V1 remains view-only: no client commenting, approval, task creation, or modification. | Backend | T246 | High | Done |
| T248 | Client Portal | Audit | Developer | Log Client Access | Audit client login, project views, document views, and denied access attempts. | Backend | T205,T243 | Medium | Done |

---

## Phase 27 — Project Documents & Knowledge

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T249 | Project Documents | Storage | Developer | Configure Project Document Folders | Reuse existing storage with logical project folders such as `projects/{project_id}/`, with task/client subfolders as needed. | DevOps | T107 | High | Done |
| T250 | Project Documents | CRUD | Manager / Team Lead | Upload & Manage Project Documents | Upload, list, search, download, version, and attach project documents subject to confirmed restrictions. | Backend | T249,T203 | High | Done |
| T251 | Project Documents | Upload UI | Manager / Team Lead | Build Project Document UI | Provide authorized upload, listing, search, version selection, sharing, and archive controls. | Frontend | T250 | High | Done |
| T252 | Project Documents | Versioning | Developer | Implement Document Versioning | Retain exactly the latest 10 versions per document and purge older versions automatically while retaining audit metadata. | Backend | T250 | Medium | Done |
| T253 | Project Documents | Sharing | Manager / Super Admin | Manage Client Document Sharing | Explicitly mark project documents as client-visible; internal documents remain private by default. | Backend | T250,T203 | High | Done |
| T254 | Project Documents | Access Control | Developer | Enforce Document Access Rules | Enforce project/team/client scope on every read/write/download endpoint; never rely on UI hiding. | Backend | T203,T250 | Critical | Done |
| T255 | Knowledge Base | Search | Manager / Team Lead | Build Project Knowledge Search | Search authorized project documents, task descriptions, and internal comments while respecting visibility boundaries. | Backend | T250 | Medium | Done |

---

## Phase 28 — Productivity & Reporting

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T256 | Reporting | Executive Dashboard | Super Admin / Manager | Build Executive Project Dashboard | Show active/completed/on-hold projects, health, deadlines, workload, and authorized metrics. | Frontend | T223,T234 | High | Done |
| T257 | Reporting | Productivity Metrics | Developer | Calculate Employee Productivity | Calculate on-time %, overdue count, assigned/completed tasks, and logged-versus-estimated hours. | Backend | T240 | Medium | Done |
| T258 | Reporting | Workload View | Manager / Team Lead | Build Team Workload View | Visualize assignments, task counts, deadlines, and approved/manual logged hours within authorized scope. | Frontend | T227,T240 | Medium | Done |
| T259 | Reporting | Budget Utilization | Manager / Super Admin | Build Project Cost Report | Calculate labor cost, budget consumed, budget remaining, and utilization. V1 does not calculate revenue/profitability. | Backend | T241 | High | Done |
| T260 | Reporting | Exports | Manager / Super Admin | Add Report Export (CSV/PDF) | Export authorized project, task, timesheet, workload, productivity, and budget reports. | Backend | T257,T259 | Medium | Done |
| T261 | Reporting | Access Control | Developer | Enforce Report Scopes | Managers see authorized teams/projects; Team Leads see own-team reports; clients only see portal data. | Backend | T203,T256 | Critical | Done |

---

## Phase 29 — Notifications

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T262 | Notifications | Channels | Developer | Extend Notification Channels | Add email and web-push to existing in-app notifications. No WhatsApp in V1. | Backend | T165 | Medium | Done |
| T263 | Notifications | Preferences | Project Users | Build Notification Preferences | Allow supported category/channel preferences without disabling mandatory security notifications. | Frontend | T262 | Medium | Done |
| T264 | Notifications | Triggers | Developer | Implement Project Notification Triggers | Notify on task assignment, deadline approaching, overdue task, timesheet submission, approval, rejection, and relevant project events. | Backend | T227,T238,T262 | High | Done |
| T265 | Notifications | Summaries | Developer | Send Daily Summaries | Send configurable daily summaries of assigned tasks, overdue work, and pending approvals. | Backend | T227,T262 | Low | Done |
| T266 | Notifications | Audit | Developer | Log Notification Dispatches | Record type, channel, recipient, status, and timestamp without unnecessary sensitive data. | Backend | T205,T264 | Medium | Done |

---

Here is the finalized task list and architectural baseline for the AI/MCP module formatted cleanly in Markdown. 

***

## Phase 30 — AI/MCP Foundation

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T267 | AI/MCP | Database Schema | Developer | Create AI Conversation & Action Tables | Build `ai_conversations`, `ai_messages`, and `ai_action_logs` with authenticated user scope, project scope, action metadata, approval state, execution status, and timestamps for MCP/AI activity auditing. | Database | T009 | High | Done |
| T268 | AI/MCP | AI Client Integration | Developer | Support MCP AI Client Workflow | Support AI clients such as VS Code GitHub Copilot Agent and Anti-Gravity through the internal MCP server. No embedded LLM chat UI or direct LLM provider integration is required in V1. | Backend | T267,T276 | High | Done |
| T269 | AI/MCP | MCP Integration Layer | Developer | Build MCP Integration Layer | Build the Laravel-side MCP integration layer that exposes authorized business tools to connected AI clients. Laravel must not directly call an external LLM provider in V1. | Backend | T267 | Critical | Done |
| T270 | AI/MCP | Identity & Scope | Developer | Enforce Strict MCP User & Project Scope | Every MCP request must inherit the authenticated user's RBAC, team, project, and client scope and can never gain permissions beyond the invoking user. | Backend | T203,T269 | Critical | Done |
| T271 | AI/MCP | Data Isolation | Developer | Block HR/Payroll Access from MCP | Explicitly deny MCP/AI access to salary, bank details, attendance IP, payroll mutations, HR mutations, and other restricted HR data. | Backend | T270 | Critical | Done |
| T272 | AI/MCP | Approval | Project Users | Build MCP Action Approval Flow | Support approval-required MCP actions by returning the proposed action, affected records, scope, required approver, and approval state before sensitive mutations are executed. | Backend | T269,T270 | High | Done |
| T273 | AI/MCP | Audit | Developer | Log AI/MCP Actions | Immutably record AI/MCP requests, tool calls, authenticated actor, project/team scope, parameters, approvals/rejections, execution status, and outcome. | Backend | T205,T269 | Critical | Done |
| T274 | AI/MCP | Failure Handling | Developer | Implement MCP Tool Failure & Retry Handling | Handle timeouts, invalid arguments, unavailable tools, duplicate execution attempts, authorization failures, and partial failures safely without corrupting business data. | Backend | T269 | High | Done |
| T275 | AI/MCP | Rate Policy | Developer | Apply V1 AI/MCP Usage Policy | No AI rate limiting is required in V1. Keep configuration hooks for future usage limits without enforcing them in the initial release. | Backend | T269 | Low | Done |

## Phase 31 — Internal MCP Server & Tools

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T276 | MCP | Server Setup | Developer | Build Internal MCP Server | Build an MCP server for authorized AI clients such as VS Code GitHub Copilot Agent and Anti-Gravity. Do not expose a public/external MCP endpoint in V1. | Backend | T269 | Critical | Done |
| T277 | MCP | Authentication | Developer | Secure Internal MCP Transport | Require authenticated, authorized access to MCP and reject unauthenticated, unauthorized, or unsupported external MCP clients. | Backend | T276,T270 | Critical | Done |
| T278 | MCP | Tool Registry | Developer | Create MCP Tool Registry | Create a centralized registry mapping MCP tool names to existing Laravel business services. MCP handlers must not duplicate existing business rules. | Backend | T276 | High | Done |
| T279 | MCP | Client Tools | Developer | Implement Client MCP Tools | Implement `client.create`, `client.update`, and `client.search` using existing Laravel services and policy checks. | Backend | T278,T207 | Medium | Done |
| T280 | MCP | Project Tools | Developer | Implement Project MCP Tools | Implement `project.create`, `project.update`, and `project.search` using existing Laravel project services and project-scope authorization. | Backend | T278,T220 | High | Done |
| T281 | MCP | Task Tools | Developer | Implement Task MCP Tools | Implement `task.create`, `task.update`, `task.assign`, and `task.complete` using existing task services, team/project policies, and authorization rules. | Backend | T278,T227 | Critical | Done |
| T282 | MCP | Timesheet Tools | Developer | Implement Timesheet MCP Tools | Implement `timesheet.create` and `timesheet.search` with employee/project scope, timesheet state validation, and existing approval rules. | Backend | T278,T237 | Medium | Done |
| T283 | MCP | Employee Tools | Developer | Implement Restricted Employee Search | Implement `employee.search` returning only permitted employee information such as name, role, skills, availability, and minimum permitted project/team context. No `employee.create` or `employee.update` MCP tool. | Backend | T278,T217 | High | Done |
| T284 | MCP | Tool Validation | Developer | Validate MCP Tool Schemas & Authorization | Validate parameters, allowed fields, authenticated actor, authorization context, target project/team/client scope, and destructive-operation flags before every tool execution. | Backend | T278,T270 | Critical | Done |

## Phase 32 — AI-Assisted Project Intelligence

*Important: The AI reasoning is performed by the connected AI client (Copilot/Anti-Gravity). Laravel provides only authorized project data and tools through MCP.*

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T285 | AI Intelligence | Natural Language Search | Project Users | Support Natural-Language Project Search | Enable connected AI clients to answer natural-language questions such as “Show all overdue tasks” by retrieving only authorized project data through MCP tools. | MCP/AI | T269,T281 | Medium | Done |
| T286 | AI Intelligence | Risk Analysis | Manager / Super Admin | Explain Deterministic Project Health | Allow connected AI clients to explain existing project-health results using authorized deadline, milestone, completion, and overdue-task evidence without replacing the deterministic health engine. | MCP/AI | T223,T269 | Medium | Done |
| T287 | AI Intelligence | Allocation | Manager / Team Lead | Provide Task Allocation Recommendations | Allow connected AI clients to recommend employees based on permitted skills, availability, workload, and team/project scope. Recommendations must not bypass authorization. | MCP/AI | T217,T258,T269 | Low | Done |
| T288 | AI Intelligence | Reports | Manager / Super Admin | Generate Management Reports via MCP Data | Allow connected AI clients to generate summaries of authorized productivity, workload, deadlines, project progress, and budget utilization using MCP-retrieved data. | MCP/AI | T257,T269 | Low | Done |
| T289 | AI Intelligence | Grounding | Developer | Ground AI Responses in Authorized Project Data | Ensure AI-generated answers are based on retrieved authorized records, distinguish confirmed data from assumptions or estimates, and clearly identify missing or uncertain information. | MCP/AI | T285,T286 | High | Done |

## Phase 33 — AI-Assisted Workflow Execution

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T290 | AI Workflow | Creation | Manager / Super Admin | AI-Assisted Project & Task Creation | Allow connected AI clients to request authorized `project.create` and `task.create` MCP operations after validation and any required approval. | MCP/AI | T280,T281,T272 | High | Done |
| T291 | AI Workflow | Assignment | Manager / Team Lead | AI-Assisted Task Assignment | Allow connected AI clients to execute `task.assign` only within the invoking user's authorized team/project scope. | MCP/AI | T281,T270 | High | Done |
| T292 | AI Workflow | Approval Gates | Developer | Implement Server-Side MCP Approval Gates | Enforce approval rules on the Laravel server. Super Admin can approve within global authority; Manager can approve within scope; Team Lead can propose but cannot approve sensitive MCP actions. | Backend | T272,T284 | Critical | Done |
| T293 | AI Workflow | Destructive Actions | Manager / Super Admin | Execute Approved Destructive MCP Actions | Support approved sensitive actions such as bulk task reassignment through MCP. No automatic undo window is required; all executions remain auditable. | Backend | T272,T292 | High | Done |
| T294 | AI Workflow | Idempotency | Developer | Prevent Duplicate MCP Mutations | Prevent retries or repeated AI/MCP requests from creating duplicate projects, tasks, assignments, or timesheet entries. | Backend | T290,T291,T293 | Critical | Done |
| T295 | AI Workflow | Transaction Safety | Developer | Make MCP Mutations Transactional | Use safe database transactions for multi-step MCP mutations and return an explicit failure state when consistency cannot be guaranteed. | Backend | T290,T293 | Critical | Done |

## Phase 34 — AI/MCP Testing & Security

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T296 | Testing | MCP Authorization | Developer | Test MCP Authorization & Scope | Verify MCP cannot act outside the authenticated user's RBAC, team, project, or client scope regardless of the AI client's request. | Testing | T270,T283 | Critical | Done |
| T297 | Testing | Sensitive Data | Developer | Test Sensitive HR Data Isolation | Verify AI/MCP clients cannot query salary, bank details, attendance IP, payroll, or restricted HR data. | Testing | T246,T271 | Critical | Done |
| T298 | Testing | MCP Tools | Developer | Test MCP Tool Execution | Verify every MCP tool validates inputs, calls the correct Laravel business service, respects policies, and handles errors safely. | Testing | T278,T284 | Critical | Done |
| T299 | Testing | Client Isolation | Developer | Test Client Read-Only Isolation | Verify clients cannot use MCP to write or access internal comments, costs, budgets, HR records, or non-shared documents. | Testing | T246 | Critical | Done |
| T300 | Testing | AI Audit | Developer | Verify AI/MCP Audit Immutability | Verify AI/MCP action records cannot be modified or deleted through normal application paths and retain sufficient actor, scope, action, approval, and outcome evidence. | Testing | T273 | High | Done |
| T301 | Testing | Prompt/Tool Safety | Developer | Test AI Prompt & Tool Boundary Safety | Test prompt injection, unauthorized tool requests, malicious parameters, cross-project references, privilege escalation, and policy-bypass attempts against MCP tools. | Testing | T270,T284 | Critical | Done |
| T302 | Testing | Mutation Safety | Developer | Test MCP Idempotency & Transactions | Verify retries and partial failures do not create duplicate or inconsistent projects, tasks, assignments, or timesheet entries. | Testing | T294,T295 | Critical | Done |

---

## The Important Architectural Change

Your final V1 architecture is now:

```text
VS Code / Anti-Gravity
        │
        │ GitHub Copilot / IDE AI
        │
        ▼
   Internal MCP
        │
        ▼
 Laravel Backend
        │
        ├── RBAC
        ├── Policies
        ├── Project Scope
        ├── Team Scope
        ├── Client Scope
        ├── Approval
        ├── Audit
        │
        ▼
 Existing Laravel Services
        │
        ▼
      MySQL
```

Therefore, V1 does **NOT** contain:
- ❌ Mistral API key in Laravel
- ❌ OpenAI API key in Laravel
- ❌ Gemini API key in Laravel
- ❌ Laravel → LLM direct API calls
- ❌ Separate AI chat page required in the web application
- ❌ Public MCP endpoint

V1 **DOES** contain:
- ✅ Internal MCP server
- ✅ MCP authentication
- ✅ MCP tool registry
- ✅ Project/task/client/timesheet tools
- ✅ Employee search with restricted fields
- ✅ Existing Laravel business services reused
- ✅ Existing RBAC/policies enforced
- ✅ AI action audit
- ✅ Approval gates
- ✅ Idempotency
- ✅ Transactions
- ✅ Security testing
- ✅ AI clients such as Copilot/Anti-Gravity using those tools

### And one particularly important point

Your MCP is not the AI.

It is the controlled bridge between the IDE's AI agent and your Laravel application.

So when you tell Copilot:
> "Create a project called Website Redesign for ABC Client and assign Rahul to it."

The conceptual flow is:

```text
Copilot/AI
   ↓
project.create
   ↓
Laravel MCP
   ↓
Authorization
   ↓
ProjectService
   ↓
Database
```

then

```text
task.assign
   ↓
Laravel MCP
   ↓
Authorization
   ↓
TaskService
   ↓
Database
```

## Phase 35 — Final Integration & Production

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T303 | Testing | Regression | Developer | Run Full HR + Project Regression | Verify project/task/AI changes do not break HR, attendance, leave, payroll, authentication, or notifications. | Testing | T190,T302 | Critical | Not Started |
| T304 | Testing | End-to-End | Developer | Verify End-to-End Project Workflows | Test client -> team -> project -> task -> timesheet -> approval -> cost -> reporting -> notification -> AI workflows. | Testing | T303 | Critical | Not Started |
| T305 | Deployment | Performance | Developer | Optimize Project Queries | Add indexes, eager loading, pagination, caching where appropriate, and eliminate N+1 queries. | Backend | T256,T258,T259 | High | Not Started |
| T306 | Deployment | Security | Developer | Conduct OWASP Review for Project Module | Review IDOR/BOLA, authorization bypass, XSS, SQL injection, file upload abuse, path traversal, CSRF/session behavior, and sensitive-data exposure. | Testing | T246,T254,T299 | Critical | Not Started |
| T307 | Deployment | Storage Security | Developer | Verify File Storage Isolation | Verify logical folder isolation, authorization before download, MIME/extension validation, safe filenames, and protected storage. | Testing | T249,T252,T254 | High | Not Started |
| T308 | Deployment | Audit Verification | Developer | Verify Project Audit Completeness | Verify project, team, task, document, timesheet, client, AI, MCP, and security-sensitive actions are audited. | Testing | T205,T235,T242,T266,T273 | High | Not Started |
| T309 | Deployment | UAT | Super Admin / Manager | Conduct Final UAT for Project Module | Validate workflows, permissions, reports, notifications, client portal, AI behavior, and exception cases. | Testing | T304,T306,T308 | Critical | Not Started |
| T310 | Deployment | Documentation | Developer | Publish Project Module Documentation | Document roles, permissions, workflows, database relationships, APIs, storage, AI/MCP tools, configuration, and operations. | Documentation | T309 | High | Not Started |
| T311 | Deployment | Backup & Recovery | Developer | Verify Backup and Recovery | Confirm project/task/document/timesheet/AI data is included in backups and recovery procedures are tested. | DevOps | T303,T307 | High | Not Started |
| T312 | Deployment | Go-Live | Developer | Merge and Deploy to Production | Complete migrations, environment configuration, queue/scheduler, monitoring, backup, security, rollback, and deployment checklist. | DevOps | T309,T310,T311 | Critical | Not Started |

---

# Cross-Phase Dependency Summary

1. Foundation: T199 -> T200/T201 -> T202 -> T203 -> T204.
2. Authorization: T203 is the central policy dependency for project, client, team, task, document, reporting, AI, and MCP operations.
3. Audit: T205 is reused by client, team, project, task, timesheet, notification, AI, MCP, and security-sensitive operations.
4. Client flow: T200 -> T207 -> T208/T209/T210/T212 -> T243+.
5. Team flow: T201 -> T214 -> T215/T216 -> project/task/AI scope.
6. Project flow: T202 -> T220 -> T221/T222/T223/T224 -> reporting and AI intelligence.
7. Task flow: T226 -> T227 -> T228-T235 -> timesheets, workload, health, notifications, and AI.
8. Timesheet flow: T236 -> T237 -> T238 -> T239/T240 -> T241 -> T259.
9. Document flow: T249 -> T250 -> T251/T252/T253/T254 -> client portal and knowledge search.
10. Reporting flow: T241 -> T257/T258/T259 -> T260/T261.
11. Notification flow: T262 -> T263/T264/T265/T266.
12. AI flow: T267 -> T269 -> T270/T271 -> T273 -> T276 -> T278 -> T279-T284 -> T285-T289 -> T290-T295.
13. AI security: AI permissions are never broader than the invoking user's Laravel permissions.
14. MCP security: MCP is internal-only and contains no independent business logic.
15. Production flow: T296-T302 -> T303-T304 -> T305-T308 -> T309 -> T310/T311 -> T312.

---

# Role & Permission Summary

| Capability | Super Admin | HR Admin | Manager | Team Lead | Employee | Client |
|---|---|---|---|---|---|---|
| Manage HR/Payroll | Yes | Yes | No | No | No | No |
| Manage Clients | Yes | No | Yes | No | No | No |
| Manage Teams | Yes | No | Yes | No | No | No |
| Manage Projects | Yes | No | Yes | No | No | No |
| Assign Tasks | Yes | No | Yes | Yes, Own Team | No | No |
| Log Timesheets | Yes | No | Yes | Yes | Yes | No |
| Approve Timesheets | Yes | No | Yes | Configurable Team Scope | No | No |
| View Project Reports | Yes | No | Yes | Own Team Scope | Limited if explicitly allowed | No |
| Approve AI Actions | Yes | No | Yes, Own Scope | No, Propose Only | No | No |
| Client Portal | Yes | No | No | No | No | Yes, Read-Only |

---

# AI/MCP Permission Summary

| Action | Super Admin | Manager | Team Lead | Employee | Client |
|---|---|---|---|---|---|
| `client.create/update` | Yes | Yes, Scope | No | No | No |
| `project.create/update` | Yes | Yes, Scope | No | No | No |
| `task.create/update` | Yes | Yes, Scope | Yes, Own Team | No | No |
| `task.assign` | Yes | Yes, Scope | Yes, Own Team | No | No |
| `timesheet.create` | Yes | Yes | Yes | Yes | No |
| `employee.search` | Yes | Yes, Scope | Yes, Own Team | Yes, Own Team | No |
| Execute Destructive Actions | Yes | Yes, Own Scope | No | No | No |
| Approve AI Actions | Yes | Yes, Own Scope | No | No | No |

**Restricted `employee.search`:** returns only name, role, skills, availability, and the minimum permitted project/team context.

---

# Confirmed Business Rules

1. Project hierarchy is parallel to HR: **Super Admin -> HR Admin** and **Super Admin -> Manager**.
2. Manager, Team Lead, and Client are separate project-module account roles, not employee permission flags.
3. HR Admin remains focused on HR/payroll/attendance and has no project-management authority unless explicitly added later.
4. Existing employee records are reused; employees are not duplicated.
5. Project timesheets are separate manual task-hour logs, not attendance records.
6. Existing attendance/leave data may be read for assignment warnings; task assignment does not modify attendance.
7. Client portal is strictly read-only in V1.
8. Client users cannot access internal budgets, labor costs, salaries, HR data, attendance data, internal comments, or non-shared documents.
9. Project documents reuse the existing storage mechanism with separate logical folders per project.
10. Document versioning retains the latest 10 versions.
11. MCP is internal-only in V1.
12. AI inherits the invoking user's permissions and cannot bypass Laravel policies.
13. Super Admin may approve any AI action.
14. Manager may approve AI actions only within authorized scope.
15. Team Lead can propose sensitive AI actions but cannot approve them.
16. V1 requires no AI rate limiting.
17. V1 requires no automatic undo/reversal window for AI actions.
18. Project health is deterministic and configurable, not an opaque ML score.
19. Project cost is based on approved salary-derived labor cost; salary values must not be exposed to project users.
20. V1 reports use budget utilization/cost, not profitability because revenue is not defined.
21. WhatsApp is not included in V1; in-app, email, and web push are supported.
22. AI/MCP must never directly implement duplicate business rules; Laravel services and policies remain authoritative.

---

# Business Rules Requiring Final Client Confirmation

### 1. Timesheet Approval Routing
**Question:** Is the exact workflow Employee -> Team Lead -> Manager, Employee -> Manager, or another route?

**Recommended default:** Employee -> Team Lead -> Manager for escalated/manager-required approval.

**Impact:** T239-T240, notifications, reporting, and approval permissions.

### 2. Project Document & Attachment Limits
**Question:** Confirm maximum file size and allowed MIME/extensions.

**Recommended default:** 2 MB; PDF, DOC, DOCX, XLS, XLSX, PNG, JPEG.

**Impact:** T210, T231, T250-T252.

### 3. Project Health Thresholds
**Question:** Confirm exact schedule variance and overdue thresholds.

**Recommended default:** configurable Super Admin settings, not hardcoded.

**Impact:** T223-T224.

### 4. Salary-to-Hour Costing Assumptions
**Question:** Confirm whether hourly cost uses calendar days, working days, contractual monthly hours, or another HR-approved formula.

**Recommended default:** configurable costing policy using HR/payroll salary data; never hardcode `/30/8`.

**Impact:** T241 and T259.

---

# Non-Goals for V1

- No WhatsApp notifications.
- No native mobile application.
- No external/public MCP client access.
- No client task creation.
- No client commenting/approval.
- No AI access to HR/payroll operations.
- No AI employee create/update tools.
- No employee salary visibility to project users.
- No automatic AI undo window.
- No AI rate limiting requirement.
- No project revenue/profitability calculation.
- No replacement of attendance/punch-in/punch-out functionality.
- No duplicate employee/user records.

---

# Production Readiness Checklist

- [ ] T199-T312 completed, tested, and reviewed.
- [ ] RBAC tested server-side for every role and scope.
- [ ] Client portal verified strictly read-only.
- [ ] HR/Payroll/Attendance data isolation verified.
- [ ] Project/team membership constraints verified.
- [ ] Task assignment scope verified.
- [ ] Timesheet approval workflow verified.
- [ ] Salary-derived costing isolated from payroll mutation.
- [ ] Document MIME/size/path/download security verified.
- [ ] Ten-version document retention verified.
- [ ] Project health rules tested against boundary conditions.
- [ ] Notification channels and preferences verified.
- [ ] AI policy inheritance verified.
- [ ] AI prompt/tool boundary security tested.
- [ ] MCP internal-only transport verified.
- [ ] MCP tool schemas and authorization tested.
- [ ] AI approval gates enforced server-side.
- [ ] AI mutations are idempotent and transaction-safe.
- [ ] AI/MCP audit trail verified immutable through application paths.
- [ ] N+1 queries and expensive reports optimized.
- [ ] OWASP/security review completed.
- [ ] Full HR + Attendance + Leave + Payroll regression passed.
- [ ] End-to-end project workflow passed.
- [ ] Backup and recovery tested.
- [ ] UAT signed off.
- [ ] Documentation published.
- [ ] Production deployment and rollback plan verified.

---

# Final Completion Definition

The Project & Task Management extension is complete only when:

1. Every task from **T199 through T312** is implemented or explicitly accepted as out of scope.
2. Every dependency has been satisfied.
3. All server-side authorization policies are tested.
4. Client, HR, payroll, attendance, project, and AI data boundaries are verified.
5. All business-rule confirmation items are resolved and documented.
6. AI can never bypass the application's authorization model.
7. MCP remains internal-only and delegates to authoritative Laravel services.
8. All sensitive mutations are audited.
9. End-to-end UAT passes.
10. Production backup, deployment, rollback, and monitoring procedures are verified.

**This is the final extended task-list baseline for the project-management + AI/MCP integration.**
