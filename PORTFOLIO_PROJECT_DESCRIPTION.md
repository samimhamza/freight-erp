# Freight Forwarding & Logistics ERP System

**Solo Project (AI-Assisted with Antigravity & Cursor)**  
·  
**08/2026 – 10/2026**

Architected and developed a full-scale cloud ERP for international freight forwarding and customs clearance logistics. Engineered end-to-end Master/House Bill of Lading workflows, multi-container shipment tracking with VIN-level vehicle manifests, automated tax-compliant PDF generation, dual-ledger accounting (client receivables and carrier remittances), and role-based access control using Laravel 13, Filament v5, Livewire v4, and PostgreSQL.

### Tech Stack
- PHP 8.4
- Laravel 13
- Filament v5
- Livewire v4
- PostgreSQL
- Tailwind CSS v4
- Spatie Laravel PDF
- DomPDF
- Resend API
- PHPUnit 11
- Antigravity
- Cursor

---

## Case Study

### Problem & Users
International ocean freight forwarders and cargo clearing agencies often rely on fragmented spreadsheets, disconnected accounting software, and manual email notifications. This results in error-prone documentation, delayed arrival notices, missed invoice due dates, and poor visibility into shipment-level profit margins. 

The primary users are operations coordinators managing vessel consolidations, clearance agents handling port releases, and finance officers managing carrier payables and customer collections.

### Role & Responsibilities
Sole Architect & Full-Stack Developer (AI-assisted with Google DeepMind Antigravity and Cursor). Designed the data architecture, implemented the business logic and user interface, built automated document rendering engines, and created comprehensive automated test suites.

### Architecture & Components
- **Domain & Data Layer (PostgreSQL & Eloquent):** Normalized relational schema modeling international trade hierarchies: Master Bills of Lading (vessel, voyage, origin/destination ports, carrier), House Bills of Lading (consignor, consignee, notify party), container allocations (TEU/FEU, tare weight, seals), and vehicle manifests (VIN-level tracking).
- **Admin & Operations Panel (Filament v5 & Livewire v4):** High-density, reactive management console featuring nested relation managers, modal workflows, quick actions, and real-time status transitions.
- **Document & Notification Engine:** Server-side PDF generation pipeline (Spatie Laravel PDF / DomPDF) creating pixel-perfect, branded Tax Invoices, Arrival Notices, Remittance Advices, and Bills of Lading. Integrated with Resend for automated transactional emailing with attached PDFs and CLI commands for automated arrival alerts and overdue invoice reminders.
- **Financial & Dual-Ledger Management:** Automated VAT calculation, partial payment tracking, receivables aging, and carrier remittance advice settlement to reconcile operational costs against customer billings.
- **Executive BI & Dashboards:** Permission-aware KPI dashboard with SVG sparklines (YTD billed revenue, outstanding receivables, active container shipments) and interactive multi-timescale charts tracking gross revenue, carrier cost, net margins, and TEU shipment volume.
- **Security & Auditability:** Granular role-based permissions (Filament Shield / Spatie Permission) governing resource and widget access, combined with Spatie Activitylog to record immutable audit trails for critical shipping and financial changes.

### Key Decisions & Trade-offs
- **Filament v5 + Livewire over Headless SPA:** Opted for a unified Laravel/Filament v5 architecture rather than a separate React/Next.js frontend. This eliminated API serialization boilerplate, reduced development time by over 50%, and maintained server-rendered speed and tight Eloquent transaction safety.
- **Server-Side PDF Pipeline with Base64 Assets:** Chosen over client-side HTML-to-canvas rendering or external headless Chrome drivers to guarantee deterministic, fast PDF rendering across both web downloads and queued background email dispatches.
- **Dual-Ledger Margin Visibility:** Modeled client invoices and carrier remittance advices as distinct financial entities linked via the underlying shipping bills, giving management instantaneous shipment-level profitability metrics without requiring separate accounting exports.
- **AI-Accelerated Development:** Leveraged agentic pair-programming (Antigravity and Cursor) for rapid schema drafting, test suite generation, and complex financial aggregate queries.

### Evaluation, Testing & Deployment
- Built a comprehensive PHPUnit 11 feature test suite verifying invoice lifecycle transitions, payment balance calculations, remittance dispatch logic, PDF layout data binding, and permission enforcement.
- Configured database seeding pipelines with realistic demo multi-modal logistics data (carriers, global ports, containers, vessels, customs clearance line items).
- Enforced code quality standards with Laravel Pint (`PSR-12` / Laravel preset).

### Outcome
Delivered a self-contained, enterprise-grade logistics and financial ERP capable of managing end-to-end freight operations, reducing document issuance time from hours to seconds, and providing real-time visibility into operational cash flows.

---

## Structured Data (For Portfolio CMS / MDX Collections)

```json
{
  "title": "Freight & Logistics Cloud ERP",
  "category": "Enterprise Full-Stack & Logistics",
  "projectType": "Independent Project (AI-Assisted)",
  "period": "08/2026 – 10/2026",
  "summary": "Engineered an end-to-end freight forwarding and logistics ERP managing multi-modal ocean shipments, container manifests, and automated billing workflows. Features Master/House B/L tracking, automated tax-compliant PDF generation, dual-ledger accounting, and real-time executive analytics built with Laravel 13, Filament v5, and PostgreSQL.",
  "technologies": [
    "PHP 8.4",
    "Laravel 13",
    "Filament v5",
    "Livewire v4",
    "PostgreSQL",
    "Tailwind CSS v4",
    "Spatie PDF",
    "Resend",
    "PHPUnit",
    "Antigravity",
    "Cursor"
  ],
  "highlights": [
    "Complete maritime logistics workflow: Master B/L, House B/L, container manifests, and VIN tracking",
    "Dual-ledger financial engine: Automated tax invoicing, payment reconciliation, and carrier remittance advice",
    "High-performance document engine for pixel-perfect PDF invoices, arrival notices, and bill of ladings",
    "Executive KPI analytics with sparklines and interactive multi-timescale revenue/volume charts",
    "Granular RBAC (Filament Shield) with full activity audit logging across all entities"
  ]
}
```

