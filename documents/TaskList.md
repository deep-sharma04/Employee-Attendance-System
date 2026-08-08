# Employee Attendance, Leave & Payroll Management System
## Development Task List — Phase-Wise Breakdown

**Based on:** Employee_Attendance_HR_Management_PRD_Final.md (v2.0)
**Tech Stack:** Laravel + Laravel Blade + MySQL (local)
**Total Tasks:** 198
**Status Legend:** Not Started / In Progress / Blocked / In Review / Done

Tasks are sequenced chronologically within each phase, and phases are ordered so that no task depends on something built in a later phase. "Dependent Task ID" lists the task(s) that must be completed first.

---

## Phase 0 — Project Foundation & Environment Setup

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T001 | Project Setup | Environment | Developer | Initialize Laravel Project | Set up a new Laravel project, configure `.env` for local MySQL, initialize Git repository. | DevOps | — | High | Not Started |
| T002 | Project Setup | Package Installation | Developer | Install Core Packages | Install Composer packages (auth base, PDF generation e.g. barryvdh/laravel-dompdf) and NPM packages for frontend build. | DevOps | T001 | High | Not Started |
| T003 | Project Setup | Folder Structure | Developer | Define Application Folder Structure | Organize controllers, models, services, requests, and policies into a role/module-based structure. | Backend | T001 | High | Not Started |
| T004 | Project Setup | Base Layout | Developer | Create Base Blade Layout | Build master Blade layout (header, sidebar, footer) with role-based navigation placeholders. | Frontend | T001 | Medium | Not Started |
| T005 | Project Setup | Styling | Developer | Set Up CSS Framework | Integrate a CSS framework (Bootstrap/Tailwind) for consistent UI styling. | Frontend | T004 | Medium | Not Started |
| T006 | Project Setup | Routing | Developer | Define Base Route Groups | Set up route groups/prefixes and middleware stubs for super-admin, hr-admin, and employee areas. | Backend | T003 | High | Not Started |
| T007 | Project Setup | Error Handling | Developer | Configure Custom Error Pages | Create custom 403/404/500 Blade error pages that avoid exposing internal details. | Frontend | T004 | Low | Not Started |
| T008 | Project Setup | Testing Setup | Developer | Configure PHPUnit Testing Environment | Set up a separate testing database and base test case classes. | DevOps | T001 | Medium | Not Started |

---

## Phase 1 — Database Schema & Core Models

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T009 | Database | ERD | Developer | Finalize Entity Relationship Diagram | Convert the PRD's suggested data model (Section 30) into a finalized ERD covering all entities and relationships. | Database | T001 | High | Not Started |
| T010 | Database | Users & Roles | Developer | Create Users, Roles & Permissions Migrations | Build migrations/models for users and role assignment (Super Admin / HR Admin / Employee). | Database | T009 | High | Not Started |
| T011 | Database | Employees | Developer | Create Employees Migration & Model | Build `employees` table (personal, employment, salary, bank fields) linked to `users`. | Database | T010 | High | Not Started |
| T012 | Database | Shifts | Developer | Create Shifts Migration & Model | Build `shifts` table (name, start/end time, working days, grace period, half-day threshold, status). | Database | T009 | High | Not Started |
| T013 | Database | IP Allowlist | Developer | Create Office IP Allowlist Migration & Model | Build `office_ip_allowlists` table (IP, description, status). | Database | T009 | High | Not Started |
| T014 | Database | Holidays | Developer | Create Holidays Migration & Model | Build `holidays` table (date, name/description). | Database | T009 | Medium | Not Started |
| T015 | Database | Attendance | Developer | Create Attendance Records/Events Migrations & Models | Build `attendance_records`/`attendance_events` tables capturing employee, date, time, IP, action, shift, status. | Database | T011,T012,T013 | High | Not Started |
| T016 | Database | Leave | Developer | Create Leave Types, Balances & Requests Migrations & Models | Build `leave_types`, `employee_leave_balances`, `leave_requests` tables. | Database | T011 | High | Not Started |
| T017 | Database | Documents | Developer | Create Document Types & Documents Migrations & Models | Build `document_types` and `documents` tables with verification status fields. | Database | T011 | Medium | Not Started |
| T018 | Database | Payroll | Developer | Create Payroll, Payroll Items & Payslips Migrations & Models | Build `payrolls`, `payroll_items`, `payslips` tables that preserve historical data. | Database | T011,T015,T016 | High | Not Started |
| T019 | Database | Audit Logs | Developer | Create Audit Logs Migration & Model | Build `audit_logs` table (actor, action, target, before/after, timestamp). | Database | T010 | High | Not Started |
| T020 | Database | Company Settings | Developer | Create Company Settings Migration & Model | Build `company_settings` table for configurable business rules and payslip header info. | Database | T009 | Medium | Not Started |
| T021 | Database | Seeders | Developer | Create Base Seeders | Seed default roles, a Super Admin account, default leave types (Casual, Medical), and default document types. | Database | T010,T016,T017 | High | Not Started |
| T022 | Database | Model Relationships | Developer | Define Eloquent Relationships | Implement Eloquent relationships across employee, attendance, leave, payroll, and document models. | Database | T011,T012,T013,T014,T015,T016,T017,T018,T019,T020 | High | Not Started |
| T023 | Database | Factories | Developer | Create Model Factories for Testing | Build factories for employees, attendance, leave, and payroll for automated testing. | Database | T022 | Low | Not Started |

---

## Phase 2 — Authentication & Authorization (RBAC)

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T024 | Authentication | Login | All | Build Login Page & Controller | Create Blade login form and controller validating username/password against hashed credentials. | Backend | T010 | High | Not Started |
| T025 | Authentication | Session | All | Implement Login Session & Role Redirect | On success, create an authenticated session and redirect to the role-specific dashboard. | Backend | T024 | High | Not Started |
| T026 | Authentication | Logout | All | Implement Logout | Destroy the session on logout and redirect to the login page. | Backend | T024 | Medium | Not Started |
| T027 | Authentication | Failure Handling | All | Implement Login Failure Handling | Return a generic auth error for invalid username/password/inactive/disabled accounts without revealing which condition failed. | Backend | T024 | High | Not Started |
| T028 | Authentication | Rate Limiting | All | Apply Rate Limiting to Login Endpoint | Configure Laravel throttle middleware on the login route to prevent brute-force attempts. | Backend | T024 | Medium | Not Started |
| T029 | Authentication | Password Security | All | Implement Secure Password Hashing | Ensure all passwords use bcrypt hashing; never store plain text. | Backend | T010 | High | Not Started |
| T030 | Authentication | Password Change | All | Build Change Password Feature | Allow a logged-in user to change their password after verifying the current one. | Backend | T029 | Medium | Not Started |
| T031 | Authentication | Password Reset | All | Build Forgot/Reset Password Flow | Implement forgot-password and reset-password endpoints and views. | Backend | T029 | Low | Not Started |
| T032 | Authorization | Middleware | Developer | Build Role Middleware | Create middleware restricting routes by role (Super Admin / HR Admin / Employee). | Backend | T010,T025 | High | Not Started |
| T033 | Authorization | Policies | Developer | Define Laravel Policies/Gates | Implement policies for employee, attendance, leave, document, and payroll resources. | Backend | T032 | High | Not Started |
| T034 | Authorization | Active Account Check | Developer | Enforce Active-Account Check | Add middleware verifying the account is active on every protected request, not just at login. | Backend | T032 | High | Not Started |
| T035 | Authorization | Own-Data Guard | Developer | Enforce Employee Own-Data Access Guard | Ensure employees cannot access another employee's records by manipulating IDs in requests. | Backend | T033 | High | Not Started |

---

## Phase 3 — Employee Management

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T036 | Employee Management | Listing | HR Admin / Super Admin | Build Employee List Page | Display a paginated employee list with search and filter by department/status. | Frontend | T011,T033 | High | Not Started |
| T037 | Employee Management | Create Form | HR Admin / Super Admin | Build Employee Creation Form | Build a multi-section form capturing personal, employment, salary, and bank information. | Frontend | T036 | High | Not Started |
| T038 | Employee Management | Validation | Developer | Build Employee Form Request Validation | Implement Form Request classes validating required fields, email/mobile format, and unique Employee ID/username. | Backend | T037 | High | Not Started |
| T039 | Employee Management | Employee ID | Developer | Implement Employee ID Uniqueness | Enforce a unique Employee ID at DB and application level. | Backend | T038 | High | Not Started |
| T040 | Employee Management | Username | Developer | Implement Username Uniqueness & Generation | Enforce a unique username; allow HR to set or auto-generate it. | Backend | T038 | High | Not Started |
| T041 | Employee Management | Credentials | HR Admin / Super Admin | Implement Temporary Password Generation | Generate a temporary password on employee creation for HR to communicate securely. | Backend | T029,T040 | High | Not Started |
| T042 | Employee Management | Shift Assignment | HR Admin / Super Admin | Add Shift Assignment to Employee Form | Allow HR to assign a configured shift during creation/edit. | Frontend | T012,T037 | Medium | Not Started |
| T043 | Employee Management | Leave Allocation | HR Admin / Super Admin | Add Leave Allocation Step to Employee Creation | Allow HR to set initial leave balances per leave type during employee creation. | Backend | T016,T037 | High | Not Started |
| T044 | Employee Management | Save Employee | Developer | Implement Employee Store Logic | Persist employee, linked user account, salary, and bank details in a single DB transaction. | Backend | T038,T039,T040,T041,T042,T043 | High | Not Started |
| T045 | Employee Management | Edit | HR Admin / Super Admin | Build Employee Edit Page | Allow HR/Super Admin to update personal, employment, salary, and bank information. | Frontend | T044 | High | Not Started |
| T046 | Employee Management | Status Update | HR Admin / Super Admin | Build Employee Status Update Feature | Allow HR to change status (Active/Inactive/Terminated/Resigned) without deleting historical data. | Backend | T044 | High | Not Started |
| T047 | Employee Management | Profile View (HR) | HR Admin / Super Admin | Build Employee Detail/Profile Page | Show full employee profile with attendance/leave/document/payroll summary. | Frontend | T044 | Medium | Not Started |
| T048 | Employee Management | Own Profile | Employee | Build "My Profile" Page | Allow an employee to view (read-only) their own profile information. | Frontend | T035,T044 | Medium | Not Started |
| T049 | Employee Management | Soft Delete Guard | Developer | Prevent Hard Deletion of Employee Records | Disable permanent employee deletion in favor of status-based offboarding. | Backend | T046 | Medium | Not Started |
| T050 | Employee Management | Audit Hooks | Developer | Add Audit Logging to Employee Actions | Log employee create/edit/status-change events with before/after values. | Backend | T019,T044,T046 | High | Not Started |
| T051 | Employee Management | Bank Data Protection | Developer | Restrict Bank Detail Visibility | Ensure bank account fields are visible only to authorized HR/Super Admin and the owning employee. | Backend | T033,T044 | High | Not Started |

---

## Phase 4 — Shift & IP Allowlist Management

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T052 | Shift Management | CRUD | HR Admin / Super Admin | Build Shift Management Page | UI to add/edit/deactivate shifts (name, start/end time, working days, grace period, half-day threshold). | Frontend | T012,T033 | High | Not Started |
| T053 | Shift Management | Validation | Developer | Validate Shift Configuration | Ensure start/end time and threshold values are logically valid on save. | Backend | T052 | Medium | Not Started |
| T054 | Shift Management | Audit | Developer | Log Shift Changes | Record shift create/edit/deactivate events in the audit log. | Backend | T019,T052 | Medium | Not Started |
| T055 | IP Allowlist | CRUD | HR Admin / Super Admin | Build IP Allowlist Management Page | UI to add/edit/deactivate approved office IP addresses with description. | Frontend | T013,T033 | High | Not Started |
| T056 | IP Allowlist | Validation | Developer | Validate IP Address Format | Ensure entered IP addresses are valid before saving. | Backend | T055 | Medium | Not Started |
| T057 | IP Allowlist | Audit | Developer | Log IP Allowlist Changes | Record IP allowlist create/edit/deactivate events in the audit log. | Backend | T019,T055 | Medium | Not Started |
| T058 | Shift Management | Active Toggle | HR Admin / Super Admin | Implement Shift Active/Inactive Toggle | Allow disabling a shift without deleting historical assignment data. | Backend | T052 | Low | Not Started |
| T059 | IP Allowlist | Active Toggle | HR Admin / Super Admin | Implement IP Active/Inactive Toggle | Allow disabling an IP entry without deleting historical attendance records tied to it. | Backend | T055 | Low | Not Started |

---

## Phase 5 — Holiday Calendar

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T060 | Holiday Calendar | CRUD | HR Admin / Super Admin | Build Holiday Calendar Management Page | UI to add/edit/remove holidays with date and name/description. | Frontend | T014,T033 | Medium | Not Started |
| T061 | Holiday Calendar | View | All | Build Holiday Calendar View | Provide a read-only calendar/list view of holidays visible to all roles. | Frontend | T060 | Low | Not Started |
| T062 | Holiday Calendar | Validation | Developer | Prevent Duplicate Holiday Dates | Validate that a holiday date isn't duplicated within the same year. | Backend | T060 | Low | Not Started |
| T063 | Holiday Calendar | Audit | Developer | Log Holiday Changes | Record holiday create/edit/delete events in the audit log. | Backend | T019,T060 | Medium | Not Started |

---

## Phase 6 — Attendance Management

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T064 | Attendance | IP Validation Service | Developer | Build IP Validation Service | Create a service class checking the requesting IP against the active IP allowlist. | Backend | T013 | High | Not Started |
| T065 | Attendance | Punch In | Employee | Build Punch-In Endpoint & Logic | Validate authentication, active status, and IP; prevent duplicate punch-in for the day. | Backend | T035,T064,T015 | High | Not Started |
| T066 | Attendance | Punch Out | Employee | Build Punch-Out Endpoint & Logic | Validate authentication, active status, IP, and prior punch-in state. | Backend | T065 | High | Not Started |
| T067 | Attendance | IP Capture | Developer | Capture & Store IP with Attendance Event | Ensure IP address is recorded with every punch-in/punch-out event. | Backend | T065,T066 | High | Not Started |
| T068 | Attendance | Rejection Message | Employee | Build Unauthorized-Network Message | Show a clear, non-technical error when punching from an unapproved network. | Frontend | T064,T065 | Medium | Not Started |
| T069 | Attendance | Classification Service | Developer | Build Late/On-Time Classification Service | Apply the configured 15-minute late threshold. | Backend | T012,T065 | High | Not Started |
| T070 | Attendance | Half-Day Rule | Developer | Implement Half-Day Classification Rule | Apply the "1 hour late = Half Day" rule within the classification service. | Backend | T069 | High | Not Started |
| T071 | Attendance | Late Conversion | Developer | Implement Late-to-Absent Conversion Logic | Implement "3 Late = 1 Absent" for payroll, preserving original Late records. | Backend | T069 | High | Not Started |
| T072 | Attendance | Half-Day Conversion | Developer | Implement Half-Day-to-Absent Conversion Logic | Implement "2 Half Days = 1 Absent", preserving original Half-Day records. | Backend | T070 | High | Not Started |
| T073 | Attendance | Employee History | Employee | Build "My Attendance" History Page | Show the employee's own attendance history (date, shift, punch times, status, IP). | Frontend | T035,T065,T066 | High | Not Started |
| T074 | Attendance | HR Monitoring | HR Admin / Super Admin | Build Company-Wide Attendance Monitoring Page | Show attendance across all employees with filters by date/employee/department/status. | Frontend | T033,T065,T066 | High | Not Started |
| T075 | Attendance | Manual Correction | HR Admin / Super Admin | Build Manual Attendance Correction Form | Allow correcting an existing attendance record with a mandatory reason. | Backend | T074 | High | Not Started |
| T076 | Attendance | Add Past Attendance | HR Admin / Super Admin | Build Add-Past-Attendance Form | Allow adding a missing historical attendance record. | Backend | T074 | Medium | Not Started |
| T077 | Attendance | Audit Hooks | Developer | Log Manual Attendance Changes | Capture actor, employee, previous/new value, and reason for every manual change. | Backend | T019,T075,T076 | High | Not Started |
| T078 | Attendance | Dashboard Widget | Employee | Build Punch In/Out Dashboard Widget | Show current attendance state (Punch In / Punch Out / Completed) reflecting real status. | Frontend | T065,T066 | High | Not Started |
| T079 | Attendance | Missing Punch Detection | Developer | Implement Missing-Punch Detection Logic | Flag days with a punch-in but no punch-out (or vice versa) for HR review. | Backend | T065,T066 | Medium | Not Started |
| T080 | Attendance | Overnight Restriction | Developer | Prevent Overnight Attendance Continuation | Ensure a shift/attendance session never carries into the next calendar day. | Backend | T065,T066 | Medium | Not Started |
| T081 | Attendance | State Guard | Developer | Prevent Duplicate/Invalid Punch Actions | Reject a second punch-in without a prior punch-out and vice versa. | Backend | T065,T066 | High | Not Started |
| T082 | Attendance | Working Days Config | Developer | Apply Configured Working Days (incl. Saturday) | Ensure attendance/shift logic treats Saturday as a working day per company calendar. | Backend | T012,T069 | Medium | Not Started |
| T083 | Attendance | Report Data Prep | Developer | Prepare Attendance Data Aggregation Layer | Build a reusable service summarizing attendance status per employee/day/month for reports and payroll. | Backend | T069,T070,T071,T072 | High | Not Started |

---

## Phase 7 — Leave Management

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T084 | Leave Management | Leave Type CRUD | HR Admin / Super Admin | Build Leave Type Management Page | Allow managing Casual and Medical leave types. | Frontend | T016,T033 | Medium | Not Started |
| T085 | Leave Management | Allocation | HR Admin / Super Admin | Build Leave Allocation Form | Allow HR to set/update an employee's leave balance per type and cycle. | Backend | T084,T044 | High | Not Started |
| T086 | Leave Management | Balance Service | Developer | Build Leave Balance Calculation Service | Centralize logic for computing remaining leave balance per employee/type. | Backend | T085 | High | Not Started |
| T087 | Leave Management | Application Form | Employee | Build Leave Application Form | Allow submitting leave type, start/end date, full/half day, and reason. | Frontend | T035,T086 | High | Not Started |
| T088 | Leave Management | Balance Validation | Developer | Validate Leave Balance on Application | Prevent leave submission beyond the available balance. | Backend | T086,T087 | High | Not Started |
| T089 | Leave Management | Working-Day Calculation | Developer | Exclude Weekends/Holidays from Leave Day Count | Skip non-working days and holidays when counting leave days. | Backend | T014,T082,T087 | High | Not Started |
| T090 | Leave Management | Half-Day Leave | Employee | Support Half-Day Leave Selection | Allow marking a leave day as full or half day. | Frontend | T087 | Medium | Not Started |
| T091 | Leave Management | Approval Queue | HR Admin / Super Admin | Build Leave Approval/Rejection Page | Show pending leave requests with approve/reject actions. | Frontend | T087,T033 | High | Not Started |
| T092 | Leave Management | Request History | Employee / HR Admin | Build Leave Request History Page | Show historical leave requests with status (own for employee, all for HR). | Frontend | T091 | Medium | Not Started |
| T093 | Leave Management | Cancellation | Employee | Build Leave Cancellation Feature | Allow cancelling only unapproved leave requests. | Backend | T087,T091 | Medium | Not Started |
| T094 | Leave Management | Cancellation Guard | Developer | Block Cancellation of Approved Leave | Enforce that approved leave cannot be cancelled by the employee. | Backend | T093 | High | Not Started |
| T095 | Leave Management | Attendance Sync | Developer | Sync Approved Leave to Attendance Status | Set attendance status to "Leave" (not Absent) for approved-leave dates. | Backend | T083,T091 | High | Not Started |
| T096 | Leave Management | Balance Deduction | Developer | Deduct Leave Balance on Approval | Reduce the employee's leave balance when a request is approved. | Backend | T086,T091 | High | Not Started |
| T097 | Leave Management | Carry-Forward Expiry | Developer | Implement Leave Expiry at Cycle End | Expire unused leave balance at the end of the configured cycle (no carry-forward). | Backend | T086 | Medium | Not Started |
| T098 | Leave Management | Audit Hooks | Developer | Log Leave Actions | Record allocation, approval, rejection, and cancellation events in the audit log. | Backend | T019,T091,T093 | High | Not Started |
| T099 | Leave Management | Dashboard Widgets | Employee / HR Admin | Add Leave Balance & Pending Requests to Dashboards | Show current leave balance and pending requests. | Frontend | T086,T091 | Medium | Not Started |

---

## Phase 8 — Holiday Bridging / Sandwich Rule

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T100 | Payroll Rules | Bridging Detection | Developer | Build Holiday-Bridging Detection Service | Detect when a holiday is surrounded by absence on both sides. | Backend | T014,T083 | High | Not Started |
| T101 | Payroll Rules | Bridging LOP Flag | Developer | Flag Bridged Holiday as LOP Day | Mark a bridged holiday as salary-deductible for payroll. | Backend | T100 | High | Not Started |
| T102 | Payroll Rules | Leave Exclusion | Developer | Exclude Approved Leave from Bridging Rule | Ensure the bridging rule never triggers LOP when a surrounding day is approved leave. | Backend | T095,T100 | High | Not Started |
| T103 | Payroll Rules | Unit Tests | Developer | Write Unit Tests for Bridging Logic | Cover bridging scenarios (absent-holiday-absent, leave-holiday-absent, etc.). | Testing | T100,T101,T102 | High | Not Started |

---

## Phase 9 — Document Management

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T104 | Document Management | Document Type CRUD | HR Admin / Super Admin | Build Document Type Management Page | Allow HR to manage the list of employee document types. | Frontend | T017,T033 | Medium | Not Started |
| T105 | Document Management | Upload Form | HR Admin / Super Admin | Build Document Upload Form | Allow HR/Super Admin to upload a document for an employee (employees cannot upload). | Frontend | T104,T044 | High | Not Started |
| T106 | Document Management | File Validation | Developer | Validate File Size & Type | Enforce 500 KB max size and PNG/JPEG/PDF-only file types on upload. | Backend | T105 | High | Not Started |
| T107 | Document Management | Secure Storage | Developer | Configure Secure File Storage | Store uploaded documents outside the public webroot with access-controlled retrieval. | Backend | T106 | High | Not Started |
| T108 | Document Management | Document List | HR Admin / Super Admin | Build Employee Document List Page | Show all documents uploaded for an employee with verification status. | Frontend | T105 | Medium | Not Started |
| T109 | Document Management | Verification | HR Admin / Super Admin | Build Document Verification Action | Allow marking a document Verified or Rejected with a reason. | Backend | T108 | High | Not Started |
| T110 | Document Management | Access Restriction | Developer | Restrict Document Access to HR/Super Admin | Ensure employees cannot access the document management interface or files directly. | Backend | T033,T107 | High | Not Started |
| T111 | Document Management | Audit Hooks | Developer | Log Document Actions | Record upload, verification, and rejection events in the audit log. | Backend | T019,T105,T109 | Medium | Not Started |
| T112 | Document Management | Dashboard Widget | HR Admin / Super Admin | Add "Pending Verification" Widget to HR Dashboard | Show count/list of documents awaiting verification. | Frontend | T109 | Low | Not Started |
| T113 | Document Management | Validation Messaging | HR Admin | Build Clear Upload Error Messages | Show clear messages for unsupported file type and size-exceeded errors. | Frontend | T106 | Low | Not Started |

---

## Phase 10 — Payroll Management

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T114 | Payroll | Calculation Service | Developer | Build Daily Salary Calculation Service | Implement Daily Salary = Monthly Salary / 30. | Backend | T018,T044 | High | Not Started |
| T115 | Payroll | LOP Aggregation | Developer | Build LOP Day Aggregation Service | Aggregate LOP days from absences, late conversions, half-day conversions, and bridged holidays. | Backend | T071,T072,T101,T114 | High | Not Started |
| T116 | Payroll | LOP Deduction | Developer | Implement LOP Deduction Calculation | Calculate LOP Deduction = Daily Salary × LOP Days. | Backend | T115 | High | Not Started |
| T117 | Payroll | Net Salary | Developer | Implement Net Salary Calculation | Calculate Net Salary = Monthly Salary − LOP Deduction − Configured Deductions. | Backend | T116 | High | Not Started |
| T118 | Payroll | Generation UI | HR Admin | Build Payroll Generation Page | Allow HR to select a payroll month and trigger generation for all/selected employees. | Frontend | T117 | High | Not Started |
| T119 | Payroll | Duplicate Guard | Developer | Prevent Duplicate Finalized Payroll | Block a second finalized payroll for the same employee/month without a controlled revision. | Backend | T118 | High | Not Started |
| T120 | Payroll | Review Page | HR Admin / Super Admin | Build Payroll Review Page | Show generated payroll line items per employee before approval. | Frontend | T118 | High | Not Started |
| T121 | Payroll | Approval | Super Admin | Build Payroll Approval Action | Allow Super Admin to approve a reviewed payroll batch. | Backend | T120 | High | Not Started |
| T122 | Payroll | Finalization | Super Admin | Build Payroll Finalization Action | Lock the approved payroll from ordinary edits once finalized. | Backend | T121 | High | Not Started |
| T123 | Payroll | Revision Process | HR Admin / Super Admin | Build Controlled Payroll Revision Workflow | Allow authorized correction of a finalized payroll via a tracked revision rather than a direct edit. | Backend | T122 | Medium | Not Started |
| T124 | Payroll | Payment Status | HR Admin | Build Payment Status Management | Allow HR to update payment status (Pending/Processing/Cleared/Failed) per employee payroll. | Backend | T122 | High | Not Started |
| T125 | Payroll | Audit Hooks | Developer | Log Payroll Actions | Record generation, approval, finalization, and payment-status changes in the audit log. | Backend | T019,T118,T121,T122,T124 | High | Not Started |
| T126 | Payroll | Professional Tax Field | Developer | Add Professional Tax Provision Field | Add a configurable but non-calculated Professional Tax field to payroll items. | Backend | T018 | Low | Not Started |
| T127 | Payroll | Dashboard Widget | HR Admin / Super Admin | Add Current Payroll Status Widget | Show current payroll cycle status on Super Admin/HR dashboards. | Frontend | T120 | Low | Not Started |
| T128 | Payroll | Unit Tests | Developer | Write Unit Tests for Payroll Calculations | Cover daily salary, LOP, and net salary calculations including edge cases. | Testing | T114,T115,T116,T117 | High | Not Started |

---

## Phase 11 — Payslip Generation

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T129 | Payslip | Template Design | Developer | Build Payslip Blade Template | Create the payslip layout matching the reference structure (header, employee info, earnings, deductions, net pay, footer). | Frontend | T020,T122 | High | Not Started |
| T130 | Payslip | Earnings Section | Developer | Implement Earnings Section Rendering | Render Basic, HRA, Telephone Reimbursement, Bonus, LTA, Special Allowance with zero defaults where unused. | Backend | T129 | Medium | Not Started |
| T131 | Payslip | Deductions Section | Developer | Implement Deductions Section Rendering | Render Income Tax, Provident Fund, Professional Tax, and Total Deductions fields. | Backend | T129 | Medium | Not Started |
| T132 | Payslip | PDF Generation | Developer | Implement PDF Payslip Export | Integrate a PDF library to generate a downloadable payslip PDF. | Backend | T129 | High | Not Started |
| T133 | Payslip | Association | Developer | Link Payslip to Payroll Record | Tie each payslip to the correct employee and payroll month, generated only after finalization. | Backend | T122,T132 | High | Not Started |
| T134 | Payslip | Employee Access | Employee | Build "My Payslips" Page | Allow an employee to view and download their finalized payslips. | Frontend | T035,T133 | High | Not Started |
| T135 | Payslip | HR Access | HR Admin / Super Admin | Build Payslip Access for HR/Super Admin | Allow viewing/downloading payslips for authorized employees. | Frontend | T133 | Medium | Not Started |
| T136 | Payslip | Access Control | Developer | Restrict Payslip Visibility Until Finalized | Ensure unfinalized/draft payroll never produces a viewable payslip. | Backend | T122,T133 | High | Not Started |

---

## Phase 12 — Reports & Dashboards

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T137 | Dashboards | Super Admin Dashboard | Super Admin | Build Super Admin Dashboard | Show total/active employees, today's attendance breakdown, pending leaves/documents, payroll status, recent activity. | Frontend | T083,T091,T109,T120 | High | Not Started |
| T138 | Dashboards | HR Dashboard | HR Admin | Build HR Admin Dashboard | Show employee management shortcuts, today's attendance, pending leaves/documents, payroll, reports. | Frontend | T083,T091,T109 | High | Not Started |
| T139 | Dashboards | Employee Dashboard | Employee | Build Employee Dashboard | Show attendance status, punch buttons, leave balance, pending requests, recent attendance/payslips. | Frontend | T078,T099,T134 | High | Not Started |
| T140 | Reports | Attendance Reports | HR Admin / Super Admin | Build Attendance Report Suite | Implement daily, monthly, employee, late, absent, half-day, missing-punch, LOP, and IP attendance reports. | Backend | T083 | High | Not Started |
| T141 | Reports | Leave Reports | HR Admin / Super Admin | Build Leave Report Suite | Implement leave balance, utilization, approved/rejected/pending, and by-type reports. | Backend | T086,T091 | High | Not Started |
| T142 | Reports | Payroll Reports | HR Admin / Super Admin | Build Payroll Report Suite | Implement monthly payroll, employee salary, total expense, LOP deduction, payment status, and history reports. | Backend | T122,T124 | High | Not Started |
| T143 | Reports | Filters | HR Admin / Super Admin | Add Report Filtering | Add date range, employee, and department filters across report pages. | Frontend | T140,T141,T142 | Medium | Not Started |
| T144 | Reports | Export | HR Admin / Super Admin | Add Report Export (CSV/PDF) | Allow reports to be exported to CSV and/or PDF. | Backend | T140,T141,T142 | Medium | Not Started |
| T145 | Reports | Access Control | Developer | Enforce Role-Based Report Access | Ensure report data respects each role's permitted scope. | Backend | T033,T140,T141,T142 | High | Not Started |
| T146 | Reports | Attendance List View | HR Admin / Super Admin | Build Attendance Report Listing Pages | Build UI pages consuming the attendance report suite. | Frontend | T140 | Medium | Not Started |
| T147 | Reports | Leave List View | HR Admin / Super Admin | Build Leave Report Listing Pages | Build UI pages consuming the leave report suite. | Frontend | T141 | Medium | Not Started |
| T148 | Reports | Payroll List View | HR Admin / Super Admin | Build Payroll Report Listing Pages | Build UI pages consuming the payroll report suite. | Frontend | T142 | Medium | Not Started |
| T149 | Dashboards | Notifications Widget | Employee | Add Notifications Section to Employee Dashboard | Display in-app notifications if the notification feature is enabled. | Frontend | T139 | Low | Not Started |
| T150 | Dashboards | Recent Activity Feed | Super Admin | Add Recent Administrative Activity Feed | Show a feed of recent audit-logged actions on the Super Admin dashboard. | Frontend | T019,T137 | Low | Not Started |
| T151 | Reports | Performance Optimization | Developer | Optimize Report Query Performance | Add indexes/caching so report pages respond quickly under expected load. | Backend | T140,T141,T142 | Medium | Not Started |
| T152 | Reports | UAT Review | HR Admin / Super Admin | Conduct Report Accuracy Review with Stakeholders | Validate report outputs against sample data before sign-off. | Testing | T140,T141,T142,T146,T147,T148 | Medium | Not Started |

---

## Phase 13 — Audit Logging Infrastructure

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T153 | Audit Logging | Base Service | Developer | Build Core Audit Logging Service/Trait | Create a reusable service/trait recording actor, action, target, timestamp, and before/after values. | Backend | T019 | High | Not Started |
| T154 | Audit Logging | Viewer | Super Admin | Build Audit Log Viewer Page | Provide Super Admin a searchable/filterable audit view; HR Admin gets a limited view. | Frontend | T153 | Medium | Not Started |
| T155 | Audit Logging | Immutability | Developer | Prevent Audit Log Modification/Deletion | Ensure audit records cannot be edited or deleted via any normal admin UI. | Backend | T153 | High | Not Started |
| T156 | Audit Logging | Coverage Verification | Developer | Verify Audit Coverage Across All Modules | Confirm every auditable action (employee, attendance, leave, document, payroll, IP, shift, holiday, HR Admin) is logged. | Testing | T050,T054,T057,T063,T077,T098,T111,T125 | High | Not Started |

---

## Phase 14 — HR Admin Management (Super Admin)

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T157 | Administration | HR Admin CRUD | Super Admin | Build HR Admin Management Page | Allow Super Admin to create, edit, and disable HR Admin accounts. | Frontend | T010,T033 | High | Not Started |
| T158 | Administration | Access Restriction | Developer | Restrict HR Admin Management to Super Admin | Ensure HR Admin accounts cannot manage other HR Admin or Super Admin accounts. | Backend | T032,T157 | High | Not Started |
| T159 | Administration | Audit Hooks | Developer | Log HR Admin Account Changes | Record HR Admin creation and disabling in the audit log. | Backend | T019,T157 | Medium | Not Started |
| T160 | Administration | HR Admin List | Super Admin | Build HR Admin Listing Page | Show all HR Admin accounts with status and quick actions. | Frontend | T157 | Medium | Not Started |

---

## Phase 15 — System Settings

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T161 | System Settings | Company Profile | Super Admin | Build Company Settings Page | Allow configuring company name, logo, address for the payslip header. | Frontend | T020,T033 | Medium | Not Started |
| T162 | System Settings | Business Rule Config | Super Admin | Expose Configurable Business Rules | Allow late threshold, half-day threshold, conversion rules, and salary divisor to be configured rather than hard-coded. | Backend | T020,T069,T070,T071,T072,T114 | Medium | Not Started |
| T163 | System Settings | Settings Cache | Developer | Cache Company/Business Rule Settings | Cache settings values with invalidation on update for performance. | Backend | T161,T162 | Low | Not Started |
| T164 | System Settings | Audit Hooks | Developer | Log System Settings Changes | Record configuration changes in the audit log. | Backend | T019,T161,T162 | Low | Not Started |

---

## Phase 16 — Notifications (Should-Have)

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T165 | Notifications | Infrastructure | Developer | Build In-App Notification Infrastructure | Create a notifications table/model and a service to dispatch in-app notifications. | Backend | T009 | Low | Not Started |
| T166 | Notifications | Leave Events | Employee | Notify Employee on Leave Approval/Rejection | Trigger a notification when an employee's leave request is approved/rejected. | Backend | T091,T165 | Low | Not Started |
| T167 | Notifications | Payroll Events | Employee | Notify Employee on Payslip Availability | Trigger a notification when a new payslip is finalized. | Backend | T122,T165 | Low | Not Started |
| T168 | Notifications | Document Events | HR Admin | Notify Relevant Users on Document Verification | Trigger a notification when a document is verified/rejected. | Backend | T109,T165 | Low | Not Started |

---

## Phase 17 — Security Hardening

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T169 | Security | Mass Assignment | Developer | Protect Against Mass Assignment | Define `$fillable`/`$guarded` on all Eloquent models. | Backend | T022 | High | Not Started |
| T170 | Security | CSRF | Developer | Verify CSRF Protection on All Forms | Confirm CSRF tokens are present and validated on every state-changing form. | Backend | T004 | High | Not Started |
| T171 | Security | Input Validation | Developer | Audit Form Request Validation Coverage | Confirm every write endpoint uses a Form Request/validation rule set. | Backend | T038,T075,T087,T105,T118 | High | Not Started |
| T172 | Security | XSS | Developer | Verify Blade Output Escaping | Confirm all dynamic output uses escaped Blade syntax to prevent XSS. | Frontend | T004 | Medium | Not Started |
| T173 | Security | File Upload Security | Developer | Harden Document Upload Security | Verify MIME-type checks (not just extension) and storage path isolation for uploads. | Backend | T106,T107 | High | Not Started |
| T174 | Security | Rate Limiting | Developer | Extend Rate Limiting to Sensitive Endpoints | Apply throttling to password reset and other authentication-sensitive endpoints. | Backend | T028 | Medium | Not Started |
| T175 | Security | HTTPS Enforcement | Developer | Configure HTTPS Enforcement for Production | Force HTTPS and secure cookies in the production environment config. | DevOps | T001 | High | Not Started |
| T176 | Security | Session/Token Handling | Developer | Review Session/Token Security Configuration | Confirm session lifetime, regeneration on login, and secure cookie flags are configured. | Backend | T025 | Medium | Not Started |
| T177 | Security | Authorization Test Pass | Developer | Run Full Authorization Test Sweep | Attempt cross-role and cross-employee access on every module and confirm rejection. | Testing | T033,T035,T110 | High | Not Started |
| T178 | Security | Vulnerability Review | Developer | Conduct General Web Vulnerability Review | Review the app against common OWASP risks (SQL injection, IDOR, broken auth) before go-live. | Testing | T169,T170,T171,T172,T173,T174,T175,T176,T177 | High | Not Started |

---

## Phase 18 — Testing & QA

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T179 | Testing | Attendance Unit Tests | Developer | Write Unit Tests for Attendance Rules | Cover late, half-day, conversion, and IP validation logic. | Testing | T069,T070,T071,T072,T081 | High | Not Started |
| T180 | Testing | Leave Unit Tests | Developer | Write Unit Tests for Leave Rules | Cover balance validation, weekend/holiday exclusion, and cancellation guards. | Testing | T088,T089,T094 | High | Not Started |
| T181 | Testing | Payroll Unit Tests | Developer | Extend Unit Tests for Full Payroll Pipeline | Extend payroll tests to cover the full pipeline including holiday bridging. | Testing | T128,T103 | High | Not Started |
| T182 | Testing | Auth Feature Tests | Developer | Write Feature Tests for Authentication Flows | Cover login, logout, password change/reset, and account-inactive scenarios. | Testing | T024,T025,T026,T027,T028,T029,T030,T031 | High | Not Started |
| T183 | Testing | RBAC Feature Tests | Developer | Write Feature Tests for Role-Based Access | Confirm each role can only access permitted routes/actions. | Testing | T032,T033 | High | Not Started |
| T184 | Testing | Employee CRUD Tests | Developer | Write Feature Tests for Employee Management | Cover creation, editing, status change, and validation errors. | Testing | T044,T045,T046 | High | Not Started |
| T185 | Testing | Attendance Flow Tests | Developer | Write Feature Tests for Punch In/Out Flow | Cover valid/invalid IP, duplicate punch attempts, and correction workflow. | Testing | T065,T066,T075 | High | Not Started |
| T186 | Testing | Leave Flow Tests | Developer | Write Feature Tests for Leave Workflow | Cover application, approval, rejection, and cancellation end-to-end. | Testing | T087,T091,T093 | High | Not Started |
| T187 | Testing | Document Flow Tests | Developer | Write Feature Tests for Document Upload/Verification | Cover file validation, verification, and access restriction. | Testing | T105,T106,T109,T110 | Medium | Not Started |
| T188 | Testing | Payroll Flow Tests | Developer | Write Feature Tests for Payroll Generate/Approve/Finalize | Cover the full payroll lifecycle including payment status updates. | Testing | T118,T121,T122,T124 | High | Not Started |
| T189 | Testing | Payslip Tests | Developer | Write Feature Tests for Payslip Generation & Access | Confirm payslip correctness and access restrictions. | Testing | T132,T133,T136 | Medium | Not Started |
| T190 | Testing | Regression Pass | Developer | Run Full Regression Test Suite | Execute the complete automated test suite before each release candidate. | Testing | T179,T180,T181,T182,T183,T184,T185,T186,T187,T188,T189 | High | Not Started |

---

## Phase 19 — Deployment & Production Readiness

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T191 | Deployment | Production Config | Developer | Prepare Production `.env` Configuration | Configure production database, mail, storage, and app URL settings. | DevOps | T001 | High | Not Started |
| T192 | Deployment | Migrations & Seeders | Developer | Run Production Migrations & Base Seeders | Deploy schema and seed roles/Super Admin/default reference data to production. | DevOps | T021,T191 | High | Not Started |
| T193 | Deployment | Asset Build | Developer | Build & Deploy Frontend Assets | Compile and deploy production CSS/JS assets via the build tool. | DevOps | T005,T191 | Medium | Not Started |
| T194 | Deployment | Logging & Monitoring | Developer | Configure Production Logging & Error Monitoring | Set up log channels and an error-tracking/monitoring solution. | DevOps | T191 | High | Not Started |
| T195 | Deployment | Backup Strategy | Developer | Implement Database & File Backup Strategy | Configure scheduled backups for MySQL data and uploaded documents. | DevOps | T191 | High | Not Started |
| T196 | Deployment | Deployment Docs | Developer | Write Deployment & Runbook Documentation | Document deployment steps, environment variables, and rollback procedure. | Documentation | T191,T192,T193,T194,T195 | Medium | Not Started |
| T197 | Deployment | UAT Sign-off | Super Admin / HR Admin | Conduct Final User Acceptance Testing | Walk stakeholders through core workflows on staging for sign-off. | Testing | T190 | High | Not Started |
| T198 | Deployment | Go-Live Checklist | Developer | Complete Go-Live Checklist & Launch | Verify all Definition-of-Done items from the PRD (Section 43) are satisfied before production launch. | DevOps | T196,T197,T178 | High | Not Started |

---

## Summary

| Phase | Module | Task Count |
|---|---|---|
| 0 | Project Foundation & Environment | 8 |
| 1 | Database Schema & Core Models | 15 |
| 2 | Authentication & RBAC | 12 |
| 3 | Employee Management | 16 |
| 4 | Shift & IP Allowlist Management | 8 |
| 5 | Holiday Calendar | 4 |
| 6 | Attendance Management | 20 |
| 7 | Leave Management | 16 |
| 8 | Holiday Bridging / Sandwich Rule | 4 |
| 9 | Document Management | 10 |
| 10 | Payroll Management | 15 |
| 11 | Payslip Generation | 8 |
| 12 | Reports & Dashboards | 16 |
| 13 | Audit Logging Infrastructure | 4 |
| 14 | HR Admin Management | 4 |
| 15 | System Settings | 4 |
| 16 | Notifications | 4 |
| 17 | Security Hardening | 10 |
| 18 | Testing & QA | 12 |
| 19 | Deployment & Production Readiness | 8 |
| **Total** | | **198** |