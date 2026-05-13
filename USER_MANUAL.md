# Teachers Performance — User Manual

**Version:** 1.0
**Last updated:** May 2026
**Audience:** Super Admins, Administrators, HR, Deans / Department Heads, Faculty, and Students

---

## Table of Contents

1. About This Manual
2. System Overview
3. Roles at a Glance
4. Accessing the System (URLs & Tenants)
5. Account Lifecycle
   - 5.1 Self-Service Registration
   - 5.2 Logging In
   - 5.3 First-Time Password Change
   - 5.4 Forgotten Password
   - 5.5 Updating Your Profile
6. The Workspace
   - 6.1 Sidebar Navigation
   - 6.2 Top Bar (Announcements, Notifications, Profile)
   - 6.3 Dashboard
7. Student Guide
8. Faculty Guide
9. Dean / Department Head Guide
10. Administrator Guide
11. Human Resource (HR) Guide
12. Super Admin Guide (Central / Tenant Management)
13. Announcements
14. Reports & Analytics
15. AI Features (Analytics, Interventions, IDP, Model Training)
16. Settings, Roles & Audit
17. Billing & Plan Upgrades
18. Troubleshooting & FAQ
19. Glossary

---

## 1. About This Manual

This document explains how to use the **Teachers Performance** system — a web-based, multi-tenant faculty performance evaluation platform for academic institutions. It walks every user role through the screens and workflows they will use most often.

The manual is organized so you can read just the section that matches your role, or read it end-to-end as a complete reference.

> **[Screenshot: Cover / Login page hero — replace with actual screen capture]**

---

## 2. System Overview

Teachers Performance helps schools manage the full evaluation cycle for teaching and non-teaching personnel. The system supports:

- Multi-source evaluations (student, peer, self, dean / supervisor)
- Configurable evaluation criteria and rating periods (Likert scale 1–5)
- Departments, courses, subjects, and student–subject matching
- Performance reports at individual, department, and institution level
- AI-assisted analytics, intervention recommendations, and Individual Development Plans (IDP)
- Announcements, audit trail, role and permission management
- Multi-tenant deployment: each institution (tenant) lives on its own subdomain, managed by a central Super Admin portal

The platform is delivered as a SaaS application. Each school gets a private subdomain (for example, `myschool.teachersperformance.app`) and its data is isolated from other tenants.

> **[Screenshot: High-level architecture diagram — central portal + tenant subdomains]**

---

## 3. Roles at a Glance

| Role | Typical User | What They Can Do |
|---|---|---|
| **Super Admin** | Platform operator | Create / suspend / resume tenants, manage plans and activation codes, run billing |
| **Administrator** | School IT / Registrar | Configure the institution: departments, faculty, students, courses, subjects, criteria, periods, roles, settings, audit |
| **Human Resource** | HR officer | Manage faculty records, criteria, view analytics, generate reports, manage announcements, monitor evaluation compliance |
| **Dean / Department Head** | Academic supervisor | Evaluate faculty under their department, view department analytics and reports, approve student registrations, post department announcements |
| **Faculty** | Teaching personnel | Complete self-evaluation, peer evaluation, view own performance dashboard |
| **Student** | Enrolled learner | Evaluate the faculty teaching them in the open evaluation period |
| **School President / VP Acad / VP Admin** | Executive | View institution-wide analytics and reports; some can submit dean-type evaluations |
| **Staff** | Non-teaching staff | Limited HR dashboard view |

Roles are not hard-coded — an Administrator can adjust which permissions each role has under **Settings → Roles & Permissions** and even delegate a permission temporarily to another user.

---

## 4. Accessing the System (URLs & Tenants)

The platform uses three types of URLs:

1. **Central / marketing site** — `https://teachersperformance.app` (or your configured domain). Landing page, About, Contact, Activate, Subscribe.
2. **Super Admin portal** — `https://admin.teachersperformance.app/login`. Used only by platform operators.
3. **Tenant workspace** — `https://<your-school>.teachersperformance.app`. This is where every school user (Admin, Dean, Faculty, Student, HR) signs in.

If you are unsure which subdomain belongs to your institution, ask your school administrator. Bookmark the tenant URL — that is your day-to-day entry point.

> **[Screenshot: Browser address bar showing tenant subdomain URL]**

---

## 5. Account Lifecycle

### 5.1 Self-Service Registration

New users who do not yet have an account can request access from the tenant login page.

1. Open your school's tenant URL (e.g. `myschool.teachersperformance.app`).
2. Click **Register** below the login form.
3. Choose your account type — **Student** or **Personnel** (faculty, staff, etc.).
4. Fill in the required fields (name, email, department, course / year level for students, etc.).
5. Submit the form. You will see a confirmation screen.

What happens next:

- **Students** are reviewed and approved by the Dean / Department Head of their target department.
- **Personnel** are reviewed by the Administrator or HR.

You will receive an email when your registration is approved or rejected, with a link to set your password.

> **[Screenshot: Register form — account type selection]**

> **[Screenshot: Registration submitted confirmation screen]**

### 5.2 Logging In

1. Go to your tenant URL.
2. Enter your **email** and **password**.
3. Click **Log in**.

If your tenant is suspended for billing or operational reasons, you will see a suspension notice and will be unable to sign in until the issue is resolved (see §17).

> **[Screenshot: Login page]**

### 5.3 First-Time Password Change

When an Administrator or HR creates your account, the system flags it as `must_change_password`. On your first login you will be redirected to a **Change Password** screen before you can use anything else.

1. Enter the temporary password issued to you.
2. Enter your new password (minimum 8 characters; use a mix of letters, numbers, and symbols).
3. Confirm the new password and click **Update**.

You will be redirected to your dashboard.

> **[Screenshot: Change Password screen]**

### 5.4 Forgotten Password

If you forget your password, use the **Forgot Password Request** workflow:

1. From the login page click **Forgot password?**.
2. Enter the email address linked to your account.
3. Submit the request. An Administrator will review and approve it.
4. Once approved, you will receive an email with a link to set a new password.

Approval is required to prevent abuse; expect a short delay during office hours.

> **[Screenshot: Forgot Password Request form]**

### 5.5 Updating Your Profile

Open the user menu in the top bar and click **Profile**. From here you can:

- Update your name and contact details
- Change your password
- (Where applicable) upload a signature image used on generated reports and certificates
- Delete your account (only allowed where your role permits it)

> **[Screenshot: Profile edit page]**

---

## 6. The Workspace

### 6.1 Sidebar Navigation

The left sidebar is grouped into sections. Only sections you have permission to access are visible.

- **Overview** — Dashboard, Analytics (Pro)
- **Evaluation** — Evaluations, Employee Comments, Individual / Department / Low-Performance / Sustained Low Performance reports, Criteria, Evaluation Periods, Interventions
- **Academic** — Courses, Subjects
- **People** — Departments, Faculty, Students
- **Approvals** — Registrations (shows a badge with pending count)
- **Settings** — Settings, Billing, Roles & Permissions, Sentiment Lexicon, Announcements, Manage Announcements, Audit Trail, Password Requests
- **AI** — Model Training
- **Help Center** — Setup, help guide, and FAQ

> **[Screenshot: Full sidebar for an Administrator role]**

### 6.2 Top Bar (Announcements, Notifications, Profile)

The top bar shows:

- **Announcement bell** — red dot indicates unread announcements
- **Banner** — sometimes pinned at the top for urgent/system-wide messages
- **User menu** — links to Account, Profile, Change Password, Log out

> **[Screenshot: Top bar with announcement bell and user menu open]**

### 6.3 Dashboard

The Dashboard route is the same for everyone (`/dashboard`), but the controller renders a different view depending on your role:

- **Admin Dashboard** — KPIs (active periods, faculty/student counts, evaluation completion, low-performance alerts, recent audit events)
- **Dean Dashboard** — Department-scoped KPIs, faculty under your department, pending registrations, evaluation progress
- **HR Dashboard** — Compliance monitoring, certificates, intervention summary
- **Faculty Dashboard** — Your own evaluation summary, performance trend, IDP status, peer/self tasks
- **Student Dashboard** — Subjects to evaluate in the open period, completion status
- **Default Dashboard** — Shown when no role-specific dashboard applies

> **[Screenshot: Admin Dashboard]**

> **[Screenshot: Faculty Dashboard]**

> **[Screenshot: Student Dashboard]**

---

## 7. Student Guide

As a student you will mainly interact with the **Evaluations** module.

**Step 1 — Sign in** with your school email and password.

**Step 2 — Open the Evaluations page** from the sidebar (`Evaluation → Evaluations`).

You will see a list of subjects and the faculty teaching each subject during the **open evaluation period**. Any subject already evaluated is marked **Completed**.

**Step 3 — Click a faculty/subject row** to open the evaluation form.

The form lists each criterion question. Rate every question on the **1–5 Likert scale** and add comments where prompted. Comments are optional but valuable — the system analyses sentiment to help administrators spot trends.

**Step 4 — Submit**. Once submitted, an evaluation cannot be re-opened. Make sure your ratings reflect your honest assessment.

> **[Screenshot: Student Evaluations index]**

> **[Screenshot: Student evaluation form — criteria questions]**

**Important rules:**

- Only the **open evaluation period** is available. Past periods are read-only.
- You can only evaluate faculty for subjects that match your **course + year level + section** assignment.
- After the period closes, students may be auto-promoted to the next year level.

---

## 8. Faculty Guide

Faculty members complete two kinds of evaluations and can monitor their own performance.

### 8.1 Self-Evaluation

1. Open **Evaluations** from the sidebar.
2. The **Self-Evaluation** tab lists the open period's self-evaluation form. Click it to begin.
3. Rate yourself against the criteria and add reflective comments. Submit when finished.

> **[Screenshot: Faculty Evaluations index showing Self / Peer tabs]**

### 8.2 Peer Evaluation

1. On the Evaluations page, switch to the **Peer Evaluation** tab.
2. You will see the list of colleagues assigned to you (typically within the same department).
3. Open each row and complete the peer rubric. Comments help identify strengths and growth areas.

### 8.3 Viewing Your Performance

The **Faculty Dashboard** shows:

- Overall performance score across the most recent period
- Trends across past periods
- Breakdown by evaluator type (student / dean / peer / self)
- Suggested improvement areas (when AI features are enabled on your tenant's plan)

If your performance is flagged for intervention, an HR officer or Dean will reach out — you can also see your **Individual Development Plan (IDP)** if one has been generated.

> **[Screenshot: Faculty Dashboard with score trend]**

> **[Screenshot: Individual Development Plan view]**

---

## 9. Dean / Department Head Guide

Deans and Heads supervise a department. Their sidebar is more compact than the Administrator's but contains the powers needed to run an academic unit.

### 9.1 Submitting Dean Evaluations

1. Open **Evaluations**.
2. The **Dean Evaluation** tab lists every faculty member under your department.
3. Open each row and fill the dean's rubric — you can add managerial comments that only HR / Admin will see.
4. Submit.

> **[Screenshot: Dean Evaluations index]**

> **[Screenshot: Dean evaluation form]**

### 9.2 Approving Student Registrations

When students register, you receive them in **Approvals → Registrations**. The sidebar shows a numbered badge for pending items.

1. Open **Registrations**.
2. Review the request — name, email, course / year level / section.
3. Click **Approve** or **Reject**. Rejected applicants are notified by email.

Personnel registrations are handled by Admin/HR; Deans see only **student** registrations for their own department.

### 9.3 Department Analytics & Reports

From the sidebar:

- **Analytics → Department Analytics** (where the AI Predictions feature is enabled)
- **Reports → Department Report** — overall standing of your department
- **Reports → Low Performance Personnel** — current low-performers in your unit
- **Reports → Sustained Low Performance** — those flagged across consecutive periods

> **[Screenshot: Department Analytics page]**

### 9.4 Department Announcements

You can post announcements scoped to your department under **Settings → Manage Announcements**. See §13.

---

## 10. Administrator Guide

The Administrator role is the configuration owner of the tenant. This section walks through every module you will set up before users can work.

### 10.1 Departments

`People → Departments`. Create teaching and non-teaching departments. Departments can be reactivated if soft-deleted.

> **[Screenshot: Departments list]**

### 10.2 Courses

`Academic → Courses`. Add programs like BSIT, BSEd, etc. These are referenced by students for subject matching.

### 10.3 Subjects

`Academic → Subjects`. Each subject has a code, title, units, and is offered for a specific course / year level / section. Assign one or more faculty to teach each subject. Bulk upload via CSV is supported (`Upload CSV` and `Download Template` buttons).

> **[Screenshot: Subject create form]**

### 10.4 Faculty

`People → Faculty`. Add faculty individually or by CSV bulk upload. Each faculty record stores name, contact, department, position (Faculty / Dean / Head / Program Chair / Staff), and personnel type (teaching / non-teaching / academic admin).

Use **Reactivate** to restore a previously archived faculty record.

> **[Screenshot: Faculty list with bulk upload button]**

### 10.5 Students

`People → Students`. Manage student rosters. Students are matched to subjects automatically based on **course + year level + section**. Bulk upload supported.

### 10.6 Evaluation Criteria

`Evaluation → Criteria`. Build the rubrics used by every evaluation type. Each criterion:

- Belongs to one **evaluation type**: student, dean, self, peer.
- Targets one or more **personnel types**: teaching, non-teaching, academic admin.
- Contains a set of **questions**, each rated 1–5 by the evaluator.

> **[Screenshot: Criteria management screen]**

### 10.7 Evaluation Periods

`Evaluation → Evaluation Periods`. Define the semester, school year, and date range.

- Only **one period** can be open at a time. The system rejects attempts to open a second.
- Closing a period locks all data for that period and (optionally) triggers student promotion.
- Closed periods are preserved for historical reporting.

> **[Screenshot: Evaluation Period form]**

### 10.8 Approving Registrations

Same screen as Deans, but Admins see both **student** and **personnel** requests across the institution.

### 10.9 Roles & Permissions

`Settings → Roles & Permissions`. The matrix lists every role on one axis and every permission on the other. Check / uncheck to grant or revoke. Click **Reset to Defaults** to restore the original mapping.

You can also delegate a single permission to a specific user for a limited time under **Roles & Delegations**.

> **[Screenshot: Roles & Permissions matrix]**

### 10.10 Settings

`Settings → Settings`. General institution settings:

- Institution name, logo, address
- Signature images for officers who sign generated certificates and reports
- Email and notification preferences

### 10.11 Audit Trail

`Settings → Audit Trail`. Read-only log of changes made by every user — creates, updates, deletes, logins, role changes. Filter by user, model, or date range. Useful for compliance reviews.

> **[Screenshot: Audit Trail with filters]**

### 10.12 Password Reset Requests

`Settings → Password Requests`. Approve or decline forgotten-password requests submitted from the login screen. Approved users receive an email with a one-time reset link.

### 10.13 Sentiment Lexicon

`Settings → Sentiment Lexicon`. The dictionary that drives comment sentiment analysis. Add positive / negative keywords specific to your institution's language so the system can score comments more accurately.

---

## 11. Human Resource (HR) Guide

HR shares many screens with Administrator but is focused on **people development** rather than configuration.

Common HR tasks:

1. **Monitor evaluation compliance** — `Dashboard` and `Evaluations → Monitor Not Evaluated` show who has and has not completed the current period's evaluations across the institution.
2. **Approve personnel registrations** — same as Admin (`Approvals → Registrations`).
3. **Generate reports** — see §14.
4. **Issue performance certificates** — `Reports → Individual Evaluation` lets you open a faculty profile and print the **Performance Excellent** certificate when criteria are met.
5. **Run intervention recommendations** — see §15.
6. **Post system-wide announcements** — see §13.

> **[Screenshot: HR Dashboard]**

> **[Screenshot: Performance Excellent certificate preview]**

---

## 12. Super Admin Guide (Central / Tenant Management)

The Super Admin portal lives on a separate subdomain (e.g. `admin.teachersperformance.app`) and is reserved for the platform operator. School users will never see this portal.

### 12.1 Logging In

Open `admin.<your-domain>/login` and sign in with your Super Admin credentials.

### 12.2 Tenants

`Tenants` is the home screen. For each tenant you can:

- **Create** a new tenant — enter institution name, subdomain, owner email, plan. The system spins up the tenant database in the background (visible as a Provisioning Job).
- **View** the tenant — shows status (provisioning / active / suspended / failed), current plan, billing summary, recent activation codes.
- **Suspend** — temporarily disables the tenant. Users will see a "Suspended" page.
- **Resume** — re-enables a suspended tenant.
- **Retry** — re-runs a failed provisioning job.
- **Delete** — permanently removes the tenant (use with care).

> **[Screenshot: Tenants list]**

> **[Screenshot: Tenant detail page]**

### 12.3 Activation Codes

From a tenant detail page you can **Regenerate** a one-time activation code (used when an institution self-registers from the central marketing site) or **Revoke** an existing code.

### 12.4 Plans

`Plans` lists every plan available to tenants — name, price, included features (e.g. `ai_predictions`), seat limits, billing interval. Use the Plans page to introduce new plans or adjust existing ones.

### 12.5 Billing

From a tenant page you can **Charge Now** (trigger an immediate billing run) or **Cancel Subscription**. The full self-service billing flow is exposed inside the tenant under `Settings → Billing` (see §17).

---

## 13. Announcements

Every authenticated user can see announcements. Permissions decide who may **post**.

### 13.1 Reading Announcements

- The bell icon in the top bar shows unread count.
- Click **Announcements** in the sidebar to see the full feed.
- Click an announcement to read details. You can **Mark as Read** or **Acknowledge** (acknowledgement is required for important announcements where Admin/HR want a confirmation).

> **[Screenshot: Announcements list with unread badge]**

> **[Screenshot: Announcement detail with Acknowledge button]**

### 13.2 Posting Announcements

`Settings → Manage Announcements`. Permissions:

- `manage-announcements-system` — Admin, HR (institution-wide announcements)
- `manage-announcements-department` — Dean, Head (department-scoped only)

When creating an announcement you set:

- Title and body (rich text)
- Audience — everyone / specific roles / specific departments
- Priority — normal / important / urgent (urgent shows as a banner)
- Requires acknowledgement — yes/no
- Schedule — publish now or at a future date; optional expiry

> **[Screenshot: Manage Announcements — create form]**

---

## 14. Reports & Analytics

The Reports section centralizes every printable view in the system.

| Report | Path | Description |
|---|---|---|
| Employee Comments | `Reports → Employee Comments` | Comment-level export per faculty across periods. Useful for performance reviews. |
| Individual Evaluation | `Reports → Individual Evaluation` | Per-faculty profile with score breakdown, evaluator-type contribution, and the Performance Excellent certificate button. |
| Department Report | `Reports → Department Report` | Department-wide standing, ranking, and KPIs. |
| Low Performance Personnel | `Reports → Low Performance Personnel` | Current-period below-threshold list. |
| Sustained Low Performance | `Reports → Sustained Low Performance` | Personnel flagged across multiple consecutive periods (chronic). |

Every report supports print and CSV/PDF export where appropriate.

> **[Screenshot: Individual Evaluation Report]**

> **[Screenshot: Department Report header]**

### 14.1 Analytics

`Overview → Analytics`. Permission-gated and plan-gated — tenants without the `ai_predictions` plan feature see a **Pro** badge and are redirected to **Plan Upgrade**.

Analytics dashboards include institution-wide KPIs, department comparisons, predicted faculty performance, and trend lines. Filters: school year, semester, department.

> **[Screenshot: Analytics dashboard with trend chart]**

---

## 15. AI Features (Analytics, Interventions, IDP, Model Training)

AI features depend on the tenant's plan. Where a feature requires a paid capability, the menu item is marked **Pro** and clicking it routes to **Plan Upgrade**.

### 15.1 Intervention Recommendations

`Overview → Interventions`. Lists every faculty member with their current performance band and the suggested HR intervention (e.g. mentoring, training course, refresher seminar). Click a row to open the full **Intervention Suggestion** for that faculty member, showing the contributing criteria and recommended actions.

> **[Screenshot: Intervention Recommendations list]**

> **[Screenshot: Individual faculty intervention page]**

### 15.2 AI Intervention Plan

From a faculty profile, an Admin / HR can click **Generate AI Intervention Plan**. The system uses the ML service to produce a structured plan with target areas, suggested activities, timelines, and review cadence. You can update the plan's status (Draft / Active / Completed) as it progresses.

> **[Screenshot: AI Intervention Plan view]**

### 15.3 Individual Development Plan (IDP)

`Faculty profile → Individual Development Plan`. This is the standard HR development workflow available without a paid AI plan. It uses similar suggestions to the AI Intervention Plan but is broader and applies to every faculty member, not only low performers.

### 15.4 AI Improvement Suggestions on Comments

When viewing comments on a faculty member's evaluation, each comment shows an **Analyze** button. The system summarises the comment, flags sentiment, and suggests action items. You can regenerate the suggestion if you want a different angle.

### 15.5 Model Training

`AI → Model Training`. Admins and Deans can re-train the prediction model with the latest evaluation data. Click **Train Now**, wait for the job to finish, and review the new model metrics (accuracy, feature importances) before promoting it.

> **[Screenshot: Model Training page with metrics]**

---

## 16. Settings, Roles & Audit

See §10 for the detailed Administrator coverage. In summary:

- **Settings** — institution branding and signatures
- **Roles & Permissions** — toggle what each role can do, and optionally delegate specific permissions to a user temporarily
- **Sentiment Lexicon** — institution-specific positive/negative word list
- **Audit Trail** — every meaningful change is logged

> **[Screenshot: Settings index]**

---

## 17. Billing & Plan Upgrades

Inside the tenant under `Settings → Billing` you can:

- See the current plan, renewal date, and active features
- Open the in-app **Checkout** to upgrade to a paid plan
- View invoices
- **Cancel** your subscription

When a feature you click is gated and your plan does not include it, you are redirected to **Plan Upgrade** which explains the missing capability and links to checkout.

If your tenant is **suspended** (for non-payment or by the platform operator), all users see a suspension page until the Super Admin resumes the tenant.

> **[Screenshot: Billing page with current plan card]**

> **[Screenshot: Plan Upgrade page]**

> **[Screenshot: Tenant Suspended page]**

---

## 18. Troubleshooting & FAQ

**Q: I can't log in — "Suspended" message.**
A: The platform operator has paused your tenant. Contact your institution's primary contact or the platform support team.

**Q: I forgot my password.**
A: Use **Forgot password?** on the login page. An Administrator must approve the request — expect a delay during office hours.

**Q: I'm a student but I don't see any subject to evaluate.**
A: Check that (1) an evaluation period is currently open, (2) your course / year level / section match the subject offerings, and (3) you have not already completed every evaluation.

**Q: A peer evaluation is missing from my list.**
A: Peer pairings are generated when the period opens. If you joined the system afterwards, ask your Administrator to refresh peer assignments.

**Q: The Analytics link shows a "Pro" badge.**
A: Your plan does not include AI predictions. An Administrator can upgrade under `Settings → Billing`.

**Q: I made a mistake on an evaluation — can I edit it?**
A: No. Submitted evaluations are final to preserve integrity. Comments can be discussed offline with HR.

**Q: My account is locked out / I keep getting redirected to Change Password.**
A: Your account has `must_change_password` flagged. Complete the change-password flow once and you will be redirected to the dashboard.

**Q: Bulk upload of faculty / students keeps failing.**
A: Download the **Template** first and use it as-is. Common errors: missing required columns, invalid course codes, duplicate emails.

**Q: How is the sentiment score calculated?**
A: The system tokenises each comment and looks up words in the **Sentiment Lexicon**. You can add domain-specific words to improve accuracy.

**Q: Who can see my comments?**
A: Student comments are visible to HR and Admin. Dean comments are scoped to the dean and HR. The faculty member sees aggregated themes, not individual comment authors.

---

## 19. Glossary

- **Tenant** — A single institution's isolated workspace on the platform, accessed via a unique subdomain.
- **Central Domain** — The marketing / activation domain shared by all tenants (e.g. `teachersperformance.app`).
- **Super Admin** — Platform operator role; manages tenants from the `admin.` subdomain.
- **Evaluation Period** — A time window during which evaluations can be submitted. Only one can be open at a time.
- **Criterion** — A group of rating questions for a specific evaluation type and personnel type.
- **Personnel Type** — Teaching, non-teaching, or academic administrator. Determines which criteria apply.
- **Likert Scale** — The 1–5 rating scale used for every question.
- **IDP (Individual Development Plan)** — A structured growth plan generated for every faculty member.
- **Intervention** — A targeted HR action recommended for under-performing faculty.
- **Sentiment Lexicon** — The dictionary used to score the sentiment of free-text comments.
- **Plan Feature** — A capability flag on a billing plan (e.g. `ai_predictions`) that toggles paid functionality.
- **Activation Code** — One-time code issued by Super Admin so a new institution can activate its tenant from the central site.
- **Audit Log** — Append-only record of every meaningful change made by any user.

---

*End of User Manual*

> Need help? Open the **Help Center** from the sidebar inside the app, or contact your institution's Administrator. For platform-level issues (tenant suspension, billing, plan changes), the platform operator is your point of contact.
