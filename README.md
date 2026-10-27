# Freight Forwarding & Logistics ERP System

[![PHP Version](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Filament](https://img.shields.io/badge/Filament-v5-F59E0B?style=flat-square&logo=livewire&logoColor=white)](https://filamentphp.com)
[![Livewire](https://img.shields.io/badge/Livewire-v4-4E56A6?style=flat-square&logo=livewire&logoColor=white)](https://livewire.laravel.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16+-336791?style=flat-square&logo=postgresql&logoColor=white)](https://www.postgresql.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v4-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Tests](https://img.shields.io/badge/Tests-PHPUnit_11-3C873A?style=flat-square&logo=php&logoColor=white)](https://phpunit.de)
[![License](https://img.shields.io/badge/License-MIT-blue?style=flat-square)](LICENSE)

An enterprise-grade, cloud-native **Freight Forwarding & Logistics ERP** built with **Laravel 13**, **Filament v5**, **Livewire v4**, and **PostgreSQL**. 

Designed for ocean freight operators, customs brokers, and cargo clearance agencies, this platform coordinates the end-to-end maritime shipment lifecycle: from Master and House Bills of Lading and multi-container manifests down to VIN-level vehicle tracking, automated tax invoicing, carrier remittance settlements, and real-time executive BI analytics.

---

## 🌟 Key Highlights & Domain Modules

### 🚢 1. Maritime Shipment Operations
- **Bill of Lading Hierarchy:** Complete operational modeling of Master Bills of Lading (MBL) and House Bills of Lading (HBL) linked to shipping lines, vessels, voyages, consignors, and notify parties.
- **Port Route Tracking:** Multi-point port logistics tracking port of loading (POL), port of discharge (POD), final destination, estimated departure/arrival (ETD/ETA), and actual arrival dates (ATA).
- **Container Manifests & Allocation:** Full TEU/FEU container management tracking tare weights, container seals, gross/net weights, and commodity descriptions.
- **VIN-Level Vehicle Tracking:** Specialized automotive logistics module to track vehicle manifests by VIN, make, model, year, and customs clearance status.

### 🧾 2. Dual-Ledger Billing & Accounting
- **Tax-Compliant Invoicing:** Supports both Shipping and Customs Clearance invoice workflows with automated VAT calculations, configurable line items, and dynamic payment statuses (*Draft*, *Sent*, *Partially Paid*, *Paid*, *Overdue*, *Cancelled*).
- **Carrier Remittance Advices:** Reconciles carrier payables directly against Master B/L consoles, allowing accounting teams to manage freight disbursements and maintain gross margin visibility per voyage.
- **Payment Reconciliation:** Real-time customer balance updates, transaction references, partial payment tracking, and ledger audits.

### 📄 3. Deterministic PDF Generation Engine
- **Server-Side Rendering:** Powered by `Spatie Laravel PDF` and `DomPDF` with base64 asset embedding to eliminate external browser rendering dependencies.
- **Branded Documentation:** Generates pixel-perfect, printable PDFs for:
  - Tax Invoices (Standard & Clearance)
  - Official Cargo Arrival Notices
  - Carrier Remittance Advices
  - Master Bills & House Bills of Lading

### 📬 4. Automated Notifications & Scheduled Workflows
- **Transactional Email Pipeline:** Direct integration with Resend API delivering branded HTML emails with automatically generated PDF attachments.
- **Console Schedulers:**
  - `php artisan app:send-arrival-notices`: Automated detection and emailing of arrival notices upon vessel port discharge.
  - `php artisan app:send-due-invoice-reminders`: Automated invoice payment reminder dispatch prior to and on payment due dates.

### 📊 5. Executive Analytics & BI Dashboards
- **Executive KPI Cards:** Real-time metrics with SVG trend sparklines for YTD Billed Revenue, Outstanding Receivables, Active Ocean Shipments, and Overdue Accounts.
- **Financial Performance Charts:** Interactive multi-timescale analysis comparing Gross Revenue vs. Carrier Cost vs. Net Profit Margin across monthly, quarterly, and yearly scopes.
- **Shipment Volume Analytics:** Real-time visualization of container throughput and shipment counts across trade lanes.

### 🔐 6. Enterprise Security & Audit Trails
- **Granular RBAC:** Complete role-based access control built with Filament Shield (Spatie Permission), securing resources, navigation items, page actions, and dashboard widgets.
- **Immutable Audit Trails:** Integrated `spatie/laravel-activitylog` tracking field-level changes across models, users, and causers.

---

## 🛠️ Architecture & Tech Stack

| Layer | Technology |
|---|---|
| **Backend Framework** | PHP 8.4, Laravel 13 |
| **Admin Panel & UI** | Filament v5, Livewire v4, Alpine.js |
| **Styling & Design** | Tailwind CSS v4, Heroicons |
| **Database** | PostgreSQL 16+ (SQLite for test suite) |
| **Document Generation** | Spatie Laravel PDF, DomPDF |
| **Email Delivery** | Resend API (`resend/resend-laravel`) |
| **Authorization & RBAC** | Filament Shield, Spatie Laravel Permission |
| **Audit Logging** | Spatie Laravel Activitylog |
| **Testing & QA** | PHPUnit 11, Laravel Pint |

---

## 🚀 Getting Started

Follow these instructions to set up and run the project locally.

### Prerequisites

Ensure you have the following installed on your development machine:
- **PHP 8.2 or higher** (PHP 8.4 recommended) with `pdo_pgsql`, `gd`, `zip`, `bcmath`, `curl`, and `mbstring` extensions
- **Composer** (v2.x)
- **Node.js** (v20+ recommended) & **npm**
- **PostgreSQL** (or SQLite for quick local evaluation)

---

### Step-by-Step Installation

#### 1. Clone the Repository
```bash
git clone https://github.com/samimhamza/<your-repo-name>.git
cd <your-repo-name>
```

#### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install frontend dependencies & compile assets
npm install
npm run build
```

#### 3. Environment Configuration
Copy the sample environment file and generate the application encryption key:
```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and configure your database connection (default is PostgreSQL):
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=acme_logistics
DB_USERNAME=postgres
DB_PASSWORD=your_password
```
*(Tip: If you want to run quickly without PostgreSQL, set `DB_CONNECTION=sqlite` and touch `database/database.sqlite`)*.

#### 4. Run Migrations & Seed Demo Data
The application includes a comprehensive seeder that generates the Super Admin user, sets up RBAC permissions, and populates realistic demo shipping lines, vessels, containers, and invoices:
```bash
php artisan migrate --seed
```

#### 5. Start the Development Server
```bash
php artisan serve
```

Access the admin dashboard at:
👉 **[http://localhost:8000](http://localhost:8000)**

---

## 🔑 Default Credentials

After running `php artisan migrate --seed`, use the following Super Admin credentials to log in:

| Field | Value |
|---|---|
| **URL** | `http://localhost:8000` |
| **Email** | `admin@admin.com` |
| **Username** | `admin` |
| **Password** | `Admin$123` |
| **Role** | `super_admin` |

---

## 🧪 Testing & Code Quality

### Automated Tests
The application includes feature test suites covering invoice lifecycles, PDF rendering accuracy, remittance advices, RBAC policies, and email attachments:
```bash
# Run the complete test suite
php artisan test --compact

# Run a specific test suite
php artisan test tests/Feature/InvoicePdfTest.php
php artisan test tests/Feature/RemittanceAdviceTest.php
```

### Code Formatting
Ensure adherence to code standards using Laravel Pint:
```bash
vendor/bin/pint --format agent
```

---

## 👤 Author

**Ahmad Samim Hamza**  
*AI & Senior Software Engineer*  
- Portfolio: [samimhamza.com](https://samimhamza.com)
- LinkedIn: [linkedin.com/in/samimhamza](https://linkedin.com/in/samimhamza)
- GitHub: [@samimhamza](https://github.com/samimhamza)

---

## 📄 License

This project is open-source software licensed under the [MIT license](LICENSE).
