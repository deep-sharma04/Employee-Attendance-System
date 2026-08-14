# Employee Attendance, Leave & Payroll Management System
## Product Requirements Document (PRD)

**Document Version:** 2.0  
**Status:** Finalized for initial production development  
**Product Type:** Web-based Employee Attendance & HR Management System  
**Primary Roles:** Super Admin, HR Admin, Employee

---

# 1. Product Overview

The system is a web-based employee attendance and HR management platform for managing:

- Employee accounts and employment information
- Employee login
- Punch-in / punch-out attendance
- Office IP validation
- Attendance history and corrections
- Working shifts and holidays
- Casual and Medical leave
- Employee documents and verification
- Monthly payroll
- LOP (Loss of Pay) calculation
- Payslip generation
- Payroll payment status
- HR and management reports
- Role-based access control
- Audit history

The system is intended for a single company and will have three roles:

1. **Super Admin**
2. **HR Admin**
3. **Employee**

The product should remain simple and practical. Unnecessary complexity, such as complicated payroll components, unnecessary workflows, or excessive configuration, is intentionally avoided.

---

# 2. Product Goals

## 2.1 Primary Goals

1. Provide secure username/password login.
2. Allow employees to punch in and punch out from approved office networks.
3. Capture and validate the employee's IP address during attendance.
4. Allow HR to manage employee information.
5. Allow employees to apply for leave.
6. Allow HR to approve or reject leave.
7. Maintain accurate attendance history.
8. Allow HR to correct or add historical attendance.
9. Calculate salary deductions from attendance and LOP rules.
10. Generate monthly payroll.
11. Generate payslips using the approved payslip structure.
12. Allow HR to manage and verify employee documents.
13. Give Super Admin complete administrative control.
14. Maintain auditability of important administrative changes.

## 2.2 Non-Goals for Initial Version

The following are intentionally not required for the initial version:

- Multiple companies/tenants
- Employee self-upload of documents
- Overtime calculation
- PF calculation
- ESI calculation
- Complex allowance management
- Automatic bank salary transfer
- Complicated password rules
- Document expiry tracking
- Leave carry-forward
- Overnight shifts
- Employee attendance from outside approved office networks

---

# 3. User Roles

## 3.1 Super Admin

Super Admin has full system access.

### Responsibilities

- Manage HR Admin accounts
- Create HR Admin
- Edit HR Admin
- Disable/remove HR Admin access
- Manage employees
- View and manage attendance
- Configure shifts
- Configure holidays/calendar
- Configure IP allowlist
- Manage leave settings
- Manage employee leave allocation
- View and manage documents
- Verify documents
- Manage payroll
- Approve/finalize payroll
- View payslips
- Manage payment status
- View reports
- Manage system-level configuration
- View audit logs

---

## 3.2 HR Admin

HR Admin manages employee and HR operations.

### Responsibilities

- Login
- Create employee
- Edit employee
- Update employee status
- Manage employee username/password
- View employee list
- View employee attendance
- Correct attendance
- Add past attendance
- Manage shifts as permitted
- Manage holidays/calendar as permitted
- Allocate employee leave
- View leave requests
- Approve/reject leave
- View employee documents
- Upload employee documents
- Verify/reject documents
- Generate payroll
- Manage payroll payment status
- View payslips
- View reports

HR Admin cannot manage Super Admin accounts.

---

## 3.3 Employee

Employee has access only to their own information and functions.

### Employee capabilities

- Login with username/password
- View own profile
- Punch in
- Punch out
- View own attendance history
- View own attendance status
- Apply for leave
- View leave balance
- View leave requests
- Cancel unapproved leave
- View finalized payslips
- View own payroll information available to employees

Employees cannot:

- Modify attendance
- Add historical attendance
- Approve leave
- Upload documents
- Verify documents
- Modify salary
- Modify employee information controlled by HR
- View another employee's information
- Punch from an unauthorized office IP

---

# 4. Authentication

## 4.1 Login

All three roles use:

```text
Username
Password
```

No OTP-based login is required.

## 4.2 Password

The system should use secure password hashing.

The password policy should remain simple; no unnecessarily complicated password composition is required.

## 4.3 Authorization

Every protected action must verify:

1. User is authenticated.
2. User account is active.
3. User has permission for the requested operation.
4. User is authorized to access the requested employee/resource.

Employees must never be able to access another employee's private data by changing an ID in a URL or request.

---

# 5. Employee Management

HR Admin can create an employee using an employee form.

## 5.1 Employee Personal Information

Required:

- First Name
- Last Name
- Email
- Mobile Number

## 5.2 Employment Information

- Employee ID
- Username
- Designation
- Department
- Employment Type
- Joining Date
- Employee Status
- Assigned Shift

### Department

Department is a manually entered field.

No separate department-management module is required.

Example:

```text
IT
HR
Finance
Sales
```

### Designation

Designation is a manually entered field representing the employee's job title.

Example:

```text
Software Developer
Senior Developer
HR Executive
Accountant
```

No separate designation-management module is required.

### Employee ID

HR manages the Employee ID.

The system must ensure that the Employee ID is unique.

### Username

HR manages the username.

The system must ensure that the username is unique.

## 5.3 Employment Types

The system must support the configured employment types required by the company, including:

- Probation
- Permanent
- Contractual

The exact labels can be managed according to the company's terminology.

## 5.4 Employee Status

Employee status is managed by HR.

The system should support active/inactive employment states and termination/resignation status where required.

Historical attendance, leave, payroll, payslip, document and audit data must remain available after an employee becomes inactive.

Permanent deletion of an employee should not be used for normal offboarding.

---

# 6. Employee Salary Information

Employee salary is monthly.

Initial salary model:

```text
Monthly Salary = Basic/Gross Salary
```

No normal allowance structure is required for V1.

No normal deduction structure is required for V1.

Future-ready fields may exist for payroll items such as:

- HRA
- Bonus
- LTA
- Special Allowance
- Professional Tax
- Income Tax
- Provident Fund

but these are not part of the initial complex calculation model unless configured/used.

---

# 7. Employee Bank Details

Employee records must support bank information required for payroll/payslip purposes.

Fields should include:

- Account Holder Name
- Bank Name
- Bank Account Number
- IFSC

Sensitive bank information must only be accessible to authorized HR/Super Admin users and appropriate employee self-view areas.

---

# 8. Attendance Management

Attendance is one of the core modules.

## 8.1 Punch In

Employee can punch in when:

- The employee is authenticated.
- The employee account is active.
- The current IP is present in the approved IP allowlist.
- The employee has not already completed an incompatible attendance action.

On punch-in, the system records:

- Employee
- Date
- Time
- IP address
- Attendance event/action
- Relevant shift

## 8.2 Punch Out

Employee can punch out when:

- The employee is authenticated.
- The employee is active.
- The IP is authorized.

The system records:

- Employee
- Date
- Time
- IP address
- Attendance event/action

## 8.3 Office IP Validation

Employees are not allowed to punch from outside the office.

The system must validate the request against an administrator-managed IP allowlist.

Conceptually:

```text
Employee
    |
    v
Punch In/Out
    |
    v
Capture IP
    |
    v
Check Approved IP
    |
    +---- Allowed ----> Record Attendance
    |
    +---- Not Allowed -> Reject Punch
```

The IP used for the attendance action must be stored with the attendance record.

## 8.4 IP Allowlist

Authorized administrators must be able to maintain approved office IP addresses.

At minimum:

- IP address
- Description/location
- Active/inactive status

Changes to the allowlist must be audited.

---

# 9. Working Hours and Shifts

Working hours are configurable by the administrator.

Shift timing is also managed by the administrator.

A shift should contain at least:

- Shift name
- Start time
- End time
- Applicable working days
- Late/grace rules
- Half-day threshold
- Active/inactive status

Overnight attendance is not supported.

The system should not interpret a shift as continuing into the next calendar day.

---

# 10. Working Days

Saturday is a working day.

The company's working calendar must support the configured working days.

Holidays are managed separately through the calendar.

---

# 11. Attendance Rules

Attendance classification uses the configured shift timings.

## 11.1 Normal Attendance

An employee is considered on time when the punch-in time falls within the configured allowed/grace period.

## 11.2 Late

The confirmed business rule is:

```text
15 minutes late -> Late
```

The system must apply the configured threshold consistently.

## 11.3 Half Day

An employee who is one hour late is marked as Half Day.

## 11.4 Late Conversion

```text
3 Late Marks = 1 Absent Day
```

The original attendance records remain visible as Late; the payroll calculation applies the resulting absence/LOP consequence.

## 11.5 Half-Day Conversion

```text
2 Half Days = 1 Absent Day
```

The original attendance records remain visible as Half Day; payroll applies the resulting absence/LOP consequence.

---

# 12. Attendance Correction

HR Admin can:

- Correct an employee's attendance.
- Add missing attendance.
- Add past attendance.
- Correct incorrect punch information where authorized.

Super Admin can also perform these operations.

Every manual attendance modification should capture an audit trail containing, where applicable:

- Who made the change
- Employee affected
- Previous value
- New value
- Date/time of change
- Reason/comment when supplied

---

# 13. Attendance History

Employee attendance history should show at least:

- Date
- Shift
- Punch-in
- Punch-out
- Total/working duration where applicable
- Attendance status
- IP address associated with punch events where visible to the user
- Leave/holiday status
- Manual correction indicator where applicable

Employee sees only their own history.

HR/Super Admin can view employee and company attendance according to permissions.

---

# 14. Holiday Calendar

The system must provide an administrator-managed calendar.

Administrators can:

- Add holiday
- Edit holiday
- Remove holiday
- View holidays
- Mark the holiday date
- Provide holiday name/description

Holidays must be considered by attendance and payroll calculations.

---

# 15. Leave Management

Initial leave types:

1. Casual Leave
2. Medical Leave

HR Admin manages employee leave allocation.

## 15.1 Leave Allocation

Leave allocation is set by HR.

Leave allocation cycle is configurable by the system administrator.

## 15.2 Carry Forward

Carry-forward is not supported.

Unused leave expires at the end of the applicable leave cycle.

## 15.3 Leave Balance

An employee cannot normally apply for leave beyond the available balance.

## 15.4 Weekends and Holidays

Weekends and holidays do not consume leave days.

Example:

```text
Friday   = Leave
Saturday = Working/weekly calendar
Sunday   = Holiday/weekly off
Monday   = Leave
```

Only applicable leave days according to the company calendar are deducted.

## 15.5 Half-Day Leave

Half-day leave is supported.

The system should distinguish between:

- Full day
- Half day

The exact first-half/second-half distinction is not required unless introduced later.

## 15.6 Leave Application

Employee can submit:

- Leave type
- Start date
- End date
- Full/half day
- Reason/comment

## 15.7 Leave Approval

HR Admin can:

- Approve
- Reject
- View pending requests
- View historical requests

Super Admin can also manage leave requests.

## 15.8 Leave Cancellation

Employee can cancel an unapproved leave request.

Employee cannot cancel an approved leave request.

Approved leave cancellation, if required later, must be handled by HR/Admin.

## 15.9 Supporting Documents

Supporting documents are not required for the initial leave types.

---

# 16. Leave and Attendance Interaction

Approved leave must be represented as leave rather than absence.

Example:

```text
Approved Medical Leave
        |
        v
Attendance Status = LEAVE
```

This prevents an employee with approved leave from being incorrectly marked absent.

---

# 17. Holiday Bridging / Sandwich Rule

The confirmed business rule is that a holiday surrounded by absence can become salary-deductible.

Example:

```text
14 Aug = Absent
15 Aug = Official Holiday
16 Aug = Absent
```

The holiday is treated as an absent/LOP day for salary calculation.

This rule must be implemented carefully so approved leave does not incorrectly create an LOP.

---

# 18. Payroll

Payroll is generated monthly.

HR Admin initiates payroll generation for the required payroll month.

## 18.1 Salary Basis

```text
Monthly Gross/Basic Salary
```

No regular allowance or deduction calculation is required for V1.

## 18.2 Daily Salary

The confirmed salary divisor is 30.

```text
Daily Salary = Monthly Salary / 30
```

## 18.3 LOP

LOP means Loss of Pay.

LOP can result from:

- Unpaid/absent days
- Late conversion
- Half-day conversion
- Applicable holiday-bridging rules
- Other approved payroll rules introduced by the company

## 18.4 LOP Deduction

```text
LOP Deduction = Daily Salary × LOP Days
```

Example:

```text
Monthly Salary = ₹30,000
LOP Days = 2

Daily Salary = ₹30,000 / 30
             = ₹1,000

LOP Deduction = ₹1,000 × 2
             = ₹2,000

Net Salary = ₹28,000
```

## 18.5 Net Salary

Initial calculation:

```text
Net Salary =
Monthly Salary
- LOP Deduction
- Applicable Configured Deductions
```

Currently, regular deductions are not required.

---

# 19. Payroll Approval

Payroll requires an approval process.

Workflow:

```text
HR Admin
    |
    v
Generate Payroll
    |
    v
Review
    |
    v
Super Admin Approval
    |
    v
Finalize
    |
    v
Payslip Available
```

The finalized payroll record should be protected from ordinary editing.

If correction is required after finalization, an authorized revision/adjustment process should be used rather than silently changing historical payroll.

---

# 20. Payroll Payment Status

Payment status is manually managed by HR.

Supported statuses:

```text
Pending
Processing
Cleared
Failed
```

No automatic bank-transfer integration is required in V1.

---

# 21. Payslip

The payslip must follow the structure of the supplied reference payslip.

## 21.1 Header

- Company logo
- Company name
- Company address
- Payslip month

## 21.2 Employee Information

- Name
- Designation
- Department
- Location
- LOP
- Employee ID
- Bank Name
- Bank Account Number
- PAN

## 21.3 Earnings

The initial payslip structure contains:

- Basic
- HRA
- Telephone Reimbursements
- Bonus
- LTA
- Special Allowance
- Total Earnings

Unused fields may show zero values.

## 21.4 Deductions

The initial payslip structure contains:

- Income Tax
- Provident Fund
- Professional Tax
- Total Deductions

These fields are retained for the payslip structure, but complex statutory calculation is not required in V1.

## 21.5 Net Pay

The payslip must clearly display:

```text
Net Pay for the month
```

## 21.6 Footer

The generated payslip should state that it is system generated and does not require a signature, consistent with the provided reference.

## 21.7 Payslip Access

Employees can view their finalized payslips.

HR and Super Admin can view authorized employee payslips.

---

# 22. Professional Tax Provision

Professional Tax is retained as a future/configurable payroll deduction provision.

Automatic statutory Professional Tax calculation is not required in the initial implementation unless later configured.

---

# 23. Employee Documents

HR manages employee documents.

## 23.1 Document Types

Document types are managed by HR.

Examples may include:

- Identity document
- Address proof
- Education document
- Experience document
- Bank proof
- Employment document

The exact document types are not hard-coded and should be configurable.

## 23.2 Upload

Only HR/Admin users can upload employee documents.

Employees cannot upload their own documents.

## 23.3 File Size

Maximum file size:

```text
500 KB
```

## 23.4 Allowed File Types

```text
PNG
JPEG
PDF
```

## 23.5 Document Verification

Documents should support a verification workflow:

```text
Uploaded
    |
    v
Pending Verification
    |
    +---- Verified
    |
    +---- Rejected
```

HR Admin and Super Admin can verify documents.

## 23.6 Document Expiry

Document expiry tracking is not required.

## 23.7 Document Access

Sensitive employee documents are restricted to:

- Super Admin
- HR Admin

Employees cannot access the HR document-management interface unless a future requirement explicitly grants access.

---

# 24. Reports

The system should provide reports around the core modules.

## 24.1 Attendance Reports

Recommended initial reports:

- Daily attendance
- Monthly attendance
- Employee attendance
- Late report
- Absent report
- Half-day report
- Missing punch report
- LOP report
- IP attendance report

## 24.2 Leave Reports

- Leave balance
- Leave utilization
- Approved leave
- Rejected leave
- Pending leave
- Leave by type

## 24.3 Payroll Reports

- Monthly payroll
- Employee salary
- Total payroll expense
- LOP deduction
- Payment status
- Payroll history

Reports must respect role permissions.

---

# 25. Role-Based Access Control

## Super Admin

Full access.

## HR Admin

HR/employee operations, attendance, leave, documents, payroll and reports.

## Employee

Own profile, attendance, leave and finalized payslips.

All APIs and backend operations must enforce authorization server-side.

UI hiding alone is not considered sufficient security.

---

# 26. Audit Logging

Important administrative actions should be audited.

Examples:

- Employee created
- Employee edited
- Employee status changed
- Attendance manually changed
- Past attendance added
- Leave approved/rejected
- Leave allocation changed
- Document uploaded
- Document verified/rejected
- Payroll generated
- Payroll approved
- Payroll finalized
- Payment status changed
- IP allowlist changed
- Shift changed
- Holiday changed
- HR Admin created/disabled

Audit records should capture the actor, action, target/resource, timestamp and relevant before/after information where applicable.

Audit history should not be casually deleted or modified.

---

# 27. Security Requirements

The system must implement:

- Secure password hashing
- Authentication
- Role-based authorization
- Server-side validation
- Request validation
- Protection against unauthorized employee data access
- Rate limiting on authentication-sensitive endpoints
- Secure document upload validation
- File type validation
- File size validation
- Secure storage for uploaded documents
- Audit logging
- Protection against mass assignment
- Protection against common web vulnerabilities
- HTTPS in production
- Secure session/token handling appropriate to the chosen web architecture

Sensitive information such as bank account details and employee documents must be protected.

---

# 28. Data Integrity Rules

The backend must enforce:

- Unique username
- Unique Employee ID
- Valid employee status
- Valid leave type
- Valid shift
- Valid attendance state transitions
- Valid payroll state transitions
- Valid payment status transitions
- Valid document type
- Valid file size/type
- Leave balance rules
- Payroll calculation rules
- IP allowlist validation

The frontend must not be trusted to enforce business rules by itself.

---

# 29. Core Business Workflows

## 29.1 Employee Creation

```text
HR Admin
   ↓
Create Employee
   ↓
Enter personal information
   ↓
Enter employment information
   ↓
Enter salary
   ↓
Enter bank details
   ↓
Allocate leave
   ↓
Assign shift
   ↓
Create username/password
   ↓
Employee account activated
```

---

## 29.2 Daily Attendance

```text
Employee Login
      ↓
Punch In
      ↓
Capture IP
      ↓
Validate IP
      ↓
Record Punch In
      ↓
Work
      ↓
Punch Out
      ↓
Capture IP
      ↓
Validate IP
      ↓
Record Punch Out
```

---

## 29.3 Leave

```text
Employee
   ↓
Apply Leave
   ↓
Pending
   ↓
HR Admin
   ├── Approve
   └── Reject
```

Unapproved leave may be cancelled by the employee.

Approved leave cannot be cancelled by the employee.

---

## 29.4 Document

```text
HR Uploads
     ↓
Pending Verification
     ↓
HR/Super Admin Review
     ├── Verified
     └── Rejected
```

---

## 29.5 Payroll

```text
HR Admin
    ↓
Select Payroll Month
    ↓
Generate Payroll
    ↓
Attendance + Leave + LOP Calculation
    ↓
Payroll Review
    ↓
Super Admin Approval
    ↓
Finalize
    ↓
Generate Payslip
    ↓
Payment Status
```

---

# 30. Suggested Core Data Model

The implementation should be designed around entities similar to:

```text
users
employees
roles
permissions
shifts
office_ip_allowlists
attendance_records
attendance_events
holidays
leave_types
employee_leave_balances
leave_requests
documents
document_types
payrolls
payroll_items
payslips
audit_logs
company_settings
```

The exact schema should be finalized during database design.

The design should preserve historical payroll and attendance information rather than overwriting history.

---

# 31. Important Database Principles

## Attendance

Attendance history should be append/audit friendly.

Do not destroy historical attendance when an employee is corrected.

## Payroll

Finalized payroll should remain historically reproducible.

## Employee

Employee status changes should preserve historical relationships.

## Documents

Document records should preserve verification status and metadata.

## Audit

Audit records should be immutable from normal administrative UI.

---

# 32. Admin Dashboard

Super Admin dashboard should provide a high-level overview of:

- Total employees
- Active employees
- Today's attendance
- Present
- Absent
- Late
- Half Day
- Pending leave requests
- Pending document verification
- Current payroll status
- Recent administrative activity

HR dashboard should focus on:

- Employee management
- Today's attendance
- Pending leaves
- Documents awaiting verification
- Payroll
- Reports

---

# 33. Employee Dashboard

Employee dashboard should show:

- Today's attendance status
- Punch In button
- Punch Out button
- Current shift
- Current leave balance
- Pending leave requests
- Recent attendance
- Recent payslips
- Notifications if enabled

The punch buttons must reflect the employee's current attendance state.

---

# 34. Attendance UX Rules

The employee should not see a generic punch button if the action is invalid.

Examples:

```text
Before Punch In:
[ Punch In ]

After Punch In:
[ Punch Out ]

After Punch Out:
Attendance Completed
```

If the employee's IP is unauthorized:

```text
Attendance cannot be recorded from this network.
```

The system must not expose sensitive internal IP configuration details to employees.

---

# 35. Validation Requirements

Employee form validation:

- Required first name
- Required last name
- Valid email
- Valid mobile
- Unique username
- Unique employee ID
- Valid salary
- Valid bank information where required

Attendance validation:

- Valid authenticated employee
- Active account
- Authorized IP
- Valid attendance state

Leave validation:

- Valid leave type
- Valid dates
- Valid leave balance
- Valid working-day calculation
- Valid approval state

Payroll validation:

- Valid employee
- Valid payroll month
- Valid attendance data
- Valid LOP calculation
- No duplicate finalized payroll for the same employee/month unless a controlled revision is created

Document validation:

- Allowed type
- Maximum 500 KB
- Valid employee
- Valid document type

---

# 36. Production Error Handling

The system should provide clear user-facing messages without exposing internal implementation details.

Examples:

```text
Invalid username or password.

You cannot punch in from this network.

You have already punched in.

You have already completed today's attendance.

Insufficient leave balance.

This leave request cannot be cancelled.

This document type is not supported.

File size must not exceed 500 KB.

Payroll has already been finalized for this month.
```

Technical errors must be logged internally.

---

# 37. API Requirements

The backend API should provide separate authenticated operations for:

### Authentication

```text
POST /login
POST /logout
POST /password/forgot
POST /password/reset
```

### Employee

```text
GET    /employees
POST   /employees
GET    /employees/{id}
PUT    /employees/{id}
PATCH  /employees/{id}/status
```

### Attendance

```text
POST /attendance/punch-in
POST /attendance/punch-out
GET  /attendance
GET  /attendance/{employee}
POST /attendance/manual
PUT  /attendance/{id}
```

### Leave

```text
GET  /leave/types
GET  /leave/balance
POST /leave/requests
GET  /leave/requests
POST /leave/requests/{id}/approve
POST /leave/requests/{id}/reject
POST /leave/requests/{id}/cancel
```

### Documents

```text
GET  /employees/{id}/documents
POST /employees/{id}/documents
PUT  /documents/{id}/verify
PUT  /documents/{id}/reject
```

### Payroll

```text
POST /payroll/generate
GET  /payroll
GET  /payroll/{id}
POST /payroll/{id}/approve
POST /payroll/{id}/finalize
PUT  /payroll/{id}/payment-status
```

### Reports

Separate report endpoints should support attendance, leave and payroll reporting.

The exact endpoint naming and response contracts should be finalized during API design.

---

# 38. Non-Functional Requirements

## Performance

Normal administrative pages and API operations should respond quickly under expected company load.

## Availability

The production system should be deployed with appropriate monitoring, backups and recovery procedures.

## Scalability

The initial product is single-company, but the database and service boundaries should avoid unnecessary assumptions that make future expansion impossible.

## Security

Sensitive employee information must be protected through authorization, secure storage and transport encryption.

## Maintainability

Business rules such as:

- late threshold
- half-day threshold
- leave rules
- working calendar
- shifts
- IP allowlist
- payroll configuration

should not be hard-coded when the requirements explicitly call for administrator configuration.

---

# 39. MVP Scope

## Must Have

### Authentication
- Username/password login
- Role-based access

### Super Admin
- HR Admin CRUD
- Full system access

### HR
- Employee CRUD
- Employee status
- Username/password management
- Salary
- Bank details
- Leave allocation
- Attendance management
- Manual attendance
- Shifts
- Holidays
- IP allowlist
- Documents
- Document verification
- Payroll
- Payment status
- Reports

### Employee
- Login
- Punch in/out
- IP validation
- Attendance history
- Leave application
- Leave balance
- Leave history
- Payslip history

### Payroll
- Monthly payroll
- 30-day salary divisor
- LOP
- Late conversion
- Half-day conversion
- Holiday bridging rule
- Payroll approval
- Payslip
- Payment status

---

# 40. Explicitly Out of Scope for V1

- Multi-company architecture/UI
- Employee document self-upload
- Employee attendance outside approved IPs
- Overnight shifts
- Overtime
- Complex salary allowances
- Automatic bank payment
- PF calculation
- ESI calculation
- Complex tax engine
- Leave carry-forward
- Document expiry
- Complicated password requirements
- Mobile application
- Biometric hardware integration
- Face recognition
- GPS attendance
- Payroll bank API integration

---

# 41. Product Decisions Captured From Stakeholder Answers

The following decisions were explicitly provided during requirements clarification and are therefore treated as product requirements:

| Area | Stakeholder Decision |
|---|---|
| Company | Single company |
| Departments | No department management; HR types manually |
| Designation | HR types manually |
| Employee ID | HR manages |
| Username | HR manages |
| Working hours | Configurable |
| Late | 15-minute rule |
| Half day | 1 hour late |
| Late conversion | 3 late = 1 absent |
| Half-day conversion | 2 half-days = 1 absent |
| Saturday | Working |
| Holidays | Admin calendar |
| Shifts | Admin-managed |
| Outside-office punch | Not allowed |
| IP | Validated against allowlist |
| Attendance correction | HR can correct |
| Past attendance | HR can add |
| Overnight attendance | Not allowed |
| Leave types | Casual + Medical |
| Leave allocation | HR managed |
| Carry forward | No |
| Weekend/holiday leave | Does not consume leave |
| Half-day leave | Yes |
| Leave approval | HR Admin |
| Approved leave cancellation | Employee cannot cancel |
| Unapproved leave cancellation | Employee can cancel |
| Leave documents | Not required |
| Salary cycle | Monthly |
| Salary divisor | 30 |
| Salary structure | Basic/Gross salary |
| Allowances | No regular allowance calculation |
| Deductions | No regular deductions |
| Overtime | No |
| Unpaid leave | Standard LOP deduction |
| Professional Tax | Provision/configurable |
| PF/ESI | No |
| Payroll approval | Required |
| Payment statuses | Pending, Processing, Cleared, Failed |
| Payslip | Based on supplied reference |
| Employee fields | Name, email, mobile, bank details plus employment/payroll fields |
| Document types | HR managed |
| Document size | 500 KB |
| Document types allowed | PNG, JPEG, PDF |
| Document expiry | No |
| Sensitive documents | Super Admin + HR Admin |
| Employee document upload | No |

---

# 42. Acceptance Criteria

## Authentication

- Valid users can login.
- Invalid credentials are rejected.
- Users cannot access unauthorized role functions.
- Employee cannot access another employee's data.

## Attendance

- Employee can punch in from an approved IP.
- Employee can punch out from an approved IP.
- Unauthorized IP is rejected.
- IP is stored with attendance event.
- HR can add past attendance.
- HR can correct attendance.
- Late and half-day rules are applied.
- Attendance history remains available.

## Leave

- Employee can submit valid leave.
- Balance is checked.
- Weekends/holidays do not consume leave.
- HR can approve/reject.
- Employee can cancel pending/unapproved leave.
- Employee cannot cancel approved leave.
- Approved leave is not marked as ordinary absence.

## Documents

- HR can upload supported files.
- Files over 500 KB are rejected.
- Unsupported formats are rejected.
- HR/Super Admin can verify/reject.
- Employee cannot upload documents.
- Unauthorized users cannot view sensitive documents.

## Payroll

- Payroll can be generated for a month.
- Salary is divided by 30 for daily rate.
- LOP deduction is calculated correctly.
- Late/half-day conversions are applied.
- Holiday bridging rule is applied according to the configured business rule.
- HR generates payroll.
- Super Admin approves.
- Finalized payroll cannot be casually edited.
- Payslip reflects payroll.
- Payment status can be updated.

## Payslip

- Payslip contains the approved employee information.
- Earnings section is present.
- Deductions section is present.
- LOP is displayed.
- Net salary is correct.
- Payslip is associated with the correct payroll month and employee.

---

# 43. Definition of Done

The product requirement is considered implementation-ready when:

- Database schema is finalized against this PRD.
- API contract is finalized.
- RBAC is implemented server-side.
- Attendance rules are covered by automated tests.
- Leave rules are covered by automated tests.
- Payroll calculations are covered by automated tests.
- Holiday bridging is covered by automated tests.
- IP allowlist validation is tested.
- Manual attendance changes are audited.
- Payroll approval/finalization is tested.
- Payslip generation is tested.
- Document upload restrictions are tested.
- Security authorization tests are present.
- Critical workflows have end-to-end tests.
- Production logging and error handling are configured.
- Backup/recovery strategy is implemented.
- Deployment configuration is documented.

---

# 44. Final Product Flow

The complete intended product flow is:

```text
                    SUPER ADMIN
                         |
          +--------------+--------------+
          |                             |
      HR ADMIN                    System Settings
          |
          +-------------------------------+
          |               |               |
      Employees       Attendance        Leave
          |               |               |
          |          IP Validation       |
          |               |               |
          |               +-------+-------+
          |                       |
          |                    Payroll
          |                       |
          |                LOP Calculation
          |                       |
          |                 Super Admin
          |                   Approval
          |                       |
          |                    Payslip
          |
      Documents
          |
      Verification


                    EMPLOYEE
                       |
              +--------+--------+
              |        |        |
            Login   Attendance  Leave
                       |        |
                    Punch     Request
                    In/Out      |
                       |       HR
                       |     Approval
                       |
                    Payslip
```

---

# 45. Final Status

**PRD status: READY FOR PRODUCTION DESIGN PHASE**

The business requirements supplied by the stakeholder have been incorporated into this version.

The next engineering artifacts should be produced from this PRD in this order:

1. **Database / ERD design**
2. **API contract**
3. **RBAC permission matrix**
4. **UI/UX page and workflow specification**
5. **Backend development task list**
6. **Frontend development task list**
7. **Test case / acceptance test list**
8. **Deployment and production checklist**

This order should be followed so that attendance and payroll business rules are implemented consistently across database, backend, frontend and testing.

---

# 46. Final Extended Module: AI/MCP Architecture (Phases 30–34)

## Core Architectural Principle — MCP-First

The V1 AI integration architecture is strictly **MCP-First**.

The Laravel web application / backend will **NOT** directly integrate or call external LLM APIs (such as OpenAI, Mistral, Gemini, Claude).
No `MISTRAL_API_KEY`, `OPENAI_API_KEY`, `GEMINI_API_KEY`, or embedded LLM chat UI is required in Laravel for V1.

```text
VS Code / Anti-Gravity
        │
        │ GitHub Copilot / IDE AI Agent
        │
        ▼
   Internal MCP Server
        │
        ▼
 Laravel Backend
        │
        ├── RBAC Enforcement
        ├── Policies & Authorization
        ├── Project Scope
        ├── Team Scope
        ├── Client Scope
        ├── Approval Gates
        ├── Audit Logging
        │
        ▼
 Existing Laravel Business Services
        │
        ▼
      MySQL
```

- **AI Client**: Provides the reasoning / LLM capability (e.g., VS Code GitHub Copilot Agent, Anti-Gravity).
- **Internal MCP Server**: The controlled, secure tool interface between the AI client and the Laravel application.
- **Laravel Backend**: Remains the single source of truth for authorization, business rules, validation, scope, mutations, audit, approvals, transactions, and data isolation.

---

# Phase 30 — AI/MCP Foundation

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T267 | AI/MCP | Database Schema | Developer | Create AI Conversation & Action Tables | Build `ai_conversations`, `ai_messages`, and `ai_action_logs` with authenticated user scope, project scope, action metadata, approval state, execution status, and timestamps for MCP/AI activity auditing. | Database | T009 | High | Not Started |
| T268 | AI/MCP | AI Client Integration | Developer | Support MCP AI Client Workflow | Support AI clients such as VS Code GitHub Copilot Agent and Anti-Gravity through the internal MCP server. No embedded LLM chat UI or direct LLM provider integration is required in V1. | Backend | T267,T276 | High | Not Started |
| T269 | AI/MCP | MCP Integration Layer | Developer | Build MCP Integration Layer | Build the Laravel-side MCP integration layer that exposes authorized business tools to connected AI clients. Laravel must not directly call an external LLM provider in V1. | Backend | T267 | Critical | Not Started |
| T270 | AI/MCP | Identity & Scope | Developer | Enforce Strict MCP User & Project Scope | Every MCP request must inherit the authenticated user's RBAC, team, project, and client scope and can never gain permissions beyond the invoking user. | Backend | T203,T269 | Critical | Not Started |
| T271 | AI/MCP | Data Isolation | Developer | Block HR/Payroll Access from MCP | Explicitly deny MCP/AI access to salary, bank details, attendance IP, payroll mutations, HR mutations, and other restricted HR data. | Backend | T270 | Critical | Not Started |
| T272 | AI/MCP | Approval | Project Users | Build MCP Action Approval Flow | Support approval-required MCP actions by returning the proposed action, affected records, scope, required approver, and approval state before sensitive mutations are executed. | Backend | T269,T270 | High | Not Started |
| T273 | AI/MCP | Audit | Developer | Log AI/MCP Actions | Immutably record AI/MCP requests, tool calls, authenticated actor, project/team scope, parameters, approvals/rejections, execution status, and outcome. | Backend | T205,T269 | Critical | Not Started |
| T274 | AI/MCP | Failure Handling | Developer | Implement MCP Tool Failure & Retry Handling | Handle timeouts, invalid arguments, unavailable tools, duplicate execution attempts, authorization failures, and partial failures safely without corrupting business data. | Backend | T269 | High | Not Started |
| T275 | AI/MCP | Rate Policy | Developer | Apply V1 AI/MCP Usage Policy | No AI rate limiting is required in V1. Keep configuration hooks for future usage limits without enforcing them in the initial release. | Backend | T269 | Low | Not Started |

# Phase 31 — Internal MCP Server & Tools

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T276 | MCP | Server Setup | Developer | Build Internal MCP Server | Build an MCP server for authorized AI clients such as VS Code GitHub Copilot Agent and Anti-Gravity. Do not expose a public/external MCP endpoint in V1. | Backend | T269 | Critical | Not Started |
| T277 | MCP | Authentication | Developer | Secure Internal MCP Transport | Require authenticated, authorized access to MCP and reject unauthenticated, unauthorized, or unsupported external MCP clients. | Backend | T276,T270 | Critical | Not Started |
| T278 | MCP | Tool Registry | Developer | Create MCP Tool Registry | Create a centralized registry mapping MCP tool names to existing Laravel business services. MCP handlers must not duplicate existing business rules. | Backend | T276 | High | Not Started |
| T279 | MCP | Client Tools | Developer | Implement Client MCP Tools | Implement `client.create`, `client.update`, and `client.search` using existing Laravel services and policy checks. | Backend | T278,T207 | Medium | Not Started |
| T280 | MCP | Project Tools | Developer | Implement Project MCP Tools | Implement `project.create`, `project.update`, and `project.search` using existing Laravel project services and project-scope authorization. | Backend | T278,T220 | High | Not Started |
| T281 | MCP | Task Tools | Developer | Implement Task MCP Tools | Implement `task.create`, `task.update`, `task.assign`, and `task.complete` using existing task services, team/project policies, and authorization rules. | Backend | T278,T227 | Critical | Not Started |
| T282 | MCP | Timesheet Tools | Developer | Implement Timesheet MCP Tools | Implement `timesheet.create` and `timesheet.search` with employee/project scope, timesheet state validation, and existing approval rules. | Backend | T278,T237 | Medium | Not Started |
| T283 | MCP | Employee Tools | Developer | Implement Restricted Employee Search | Implement `employee.search` returning only permitted employee information such as name, role, skills, availability, and minimum permitted project/team context. No `employee.create` or `employee.update` MCP tool. | Backend | T278,T217 | High | Not Started |
| T284 | MCP | Tool Validation | Developer | Validate MCP Tool Schemas & Authorization | Validate parameters, allowed fields, authenticated actor, authorization context, target project/team/client scope, and destructive-operation flags before every tool execution. | Backend | T278,T270 | Critical | Not Started |

# Phase 32 — AI-Assisted Project Intelligence

IMPORTANT:
The AI reasoning is performed by the connected AI client (Copilot/Anti-Gravity).
Laravel provides only authorized project data and tools through MCP.

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T285 | AI Intelligence | Natural Language Search | Project Users | Support Natural-Language Project Search | Enable connected AI clients to answer natural-language questions such as “Show all overdue tasks” by retrieving only authorized project data through MCP tools. | MCP/AI | T269,T281 | Medium | Not Started |
| T286 | AI Intelligence | Risk Analysis | Manager / Super Admin | Explain Deterministic Project Health | Allow connected AI clients to explain existing project-health results using authorized deadline, milestone, completion, and overdue-task evidence without replacing the deterministic health engine. | MCP/AI | T223,T269 | Medium | Not Started |
| T287 | AI Intelligence | Allocation | Manager / Team Lead | Provide Task Allocation Recommendations | Allow connected AI clients to recommend employees based on permitted skills, availability, workload, and team/project scope. Recommendations must not bypass authorization. | MCP/AI | T217,T258,T269 | Low | Not Started |
| T288 | AI Intelligence | Reports | Manager / Super Admin | Generate Management Reports via MCP Data | Allow connected AI clients to generate summaries of authorized productivity, workload, deadlines, project progress, and budget utilization using MCP-retrieved data. | MCP/AI | T257,T269 | Low | Not Started |
| T289 | AI Intelligence | Grounding | Developer | Ground AI Responses in Authorized Project Data | Ensure AI-generated answers are based on retrieved authorized records, distinguish confirmed data from assumptions or estimates, and clearly identify missing or uncertain information. | MCP/AI | T285,T286 | High | Not Started |

# Phase 33 — AI-Assisted Workflow Execution

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T290 | AI Workflow | Creation | Manager / Super Admin | AI-Assisted Project & Task Creation | Allow connected AI clients to request authorized `project.create` and `task.create` MCP operations after validation and any required approval. | MCP/AI | T280,T281,T272 | High | Not Started |
| T291 | AI Workflow | Assignment | Manager / Team Lead | AI-Assisted Task Assignment | Allow connected AI clients to execute `task.assign` only within the invoking user's authorized team/project scope. | MCP/AI | T281,T270 | High | Not Started |
| T292 | AI Workflow | Approval Gates | Developer | Implement Server-Side MCP Approval Gates | Enforce approval rules on the Laravel server. Super Admin can approve within global authority; Manager can approve within scope; Team Lead can propose but cannot approve sensitive MCP actions. | Backend | T272,T284 | Critical | Not Started |
| T293 | AI Workflow | Destructive Actions | Manager / Super Admin | Execute Approved Destructive MCP Actions | Support approved sensitive actions such as bulk task reassignment through MCP. No automatic undo window is required; all executions remain auditable. | Backend | T272,T292 | High | Not Started |
| T294 | AI Workflow | Idempotency | Developer | Prevent Duplicate MCP Mutations | Prevent retries or repeated AI/MCP requests from creating duplicate projects, tasks, assignments, or timesheet entries. | Backend | T290,T291,T293 | Critical | Not Started |
| T295 | AI Workflow | Transaction Safety | Developer | Make MCP Mutations Transactional | Use safe database transactions for multi-step MCP mutations and return an explicit failure state when consistency cannot be guaranteed. | Backend | T290,T293 | Critical | Not Started |

# Phase 34 — AI/MCP Testing & Security

| Task ID | Main Module | Sub-Module | User Group | Task Title | Task Description | Task Type | Dependent Task ID | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| T296 | Testing | MCP Authorization | Developer | Test MCP Authorization & Scope | Verify MCP cannot act outside the authenticated user's RBAC, team, project, or client scope regardless of the AI client's request. | Testing | T270,T283 | Critical | Not Started |
| T297 | Testing | Sensitive Data | Developer | Test Sensitive HR Data Isolation | Verify AI/MCP clients cannot query salary, bank details, attendance IP, payroll, or restricted HR data. | Testing | T246,T271 | Critical | Not Started |
| T298 | Testing | MCP Tools | Developer | Test MCP Tool Execution | Verify every MCP tool validates inputs, calls the correct Laravel business service, respects policies, and handles errors safely. | Testing | T278,T284 | Critical | Not Started |
| T299 | Testing | Client Isolation | Developer | Test Client Read-Only Isolation | Verify clients cannot use MCP to write or access internal comments, costs, budgets, HR records, or non-shared documents. | Testing | T246 | Critical | Not Started |
| T300 | Testing | AI Audit | Developer | Verify AI/MCP Audit Immutability | Verify AI/MCP action records cannot be modified or deleted through normal application paths and retain sufficient actor, scope, action, approval, and outcome evidence. | Testing | T273 | High | Not Started |
| T301 | Testing | Prompt/Tool Safety | Developer | Test AI Prompt & Tool Boundary Safety | Test prompt injection, unauthorized tool requests, malicious parameters, cross-project references, privilege escalation, and policy-bypass attempts against MCP tools. | Testing | T270,T284 | Critical | Not Started |
| T302 | Testing | Mutation Safety | Developer | Test MCP Idempotency & Transactions | Verify retries and partial failures do not create duplicate or inconsistent projects, tasks, assignments, or timesheet records. | Testing | T294,T295 | Critical | Not Started |
