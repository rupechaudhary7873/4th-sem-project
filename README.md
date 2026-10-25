# Personal Budget Management System (Fiscal Precision)
### 4th Semester Academic Web Engineering Project

[![PHP Version](https://img.shields.io/badge/PHP-7.4%20%7C%208.x-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-4479A1?style=flat&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Web Server](https://img.shields.io/badge/Server-Apache%20with%20mod__rewrite-D22128?style=flat&logo=apache&logoColor=white)](https://httpd.apache.org/)
[![UI Style](https://img.shields.io/badge/Design-Fiscal%20Precision-004AC6?style=flat)](./design.md)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](./README.md)

---

## 📋 Table of Contents
1. [Project Overview](#-project-overview)
2. [Key Features & Modules](#-key-features--modules)
   - [User Portal](#1-user-portal)
   - [Smart Budgeting Engine & Alerts](#2-smart-budgeting-engine--alerts)
   - [Financial Analytics & Visual Reports](#3-financial-analytics--visual-reports)
   - [Administrator Control Panel](#4-administrator-control-panel)
3. [Technology Stack](#-technology-stack)
4. [Database Architecture & Schema](#-database-architecture--schema)
5. [Complete Setup & Installation Guide](#-complete-setup--installation-guide)
   - [Prerequisites](#step-0-prerequisites)
   - [Step 1: Clone / Copy to Web Server Directory](#step-1-clone--copy-to-web-server-directory)
   - [Step 2: Database Creation & Migration](#step-2-database-creation--migration)
   - [Step 3: Database Connection Configuration](#step-3-database-connection-configuration)
   - [Step 4: Apache Server & URL Rewrite Configuration](#step-4-apache-server--url-rewrite-configuration)
   - [Step 5: Permissions Configuration](#step-5-permissions-configuration)
   - [Step 6: Launch & Verify System](#step-6-launch--verify-system)
6. [Default Credentials](#-default-credentials)
7. [Project Directory & File Structure](#-project-directory--file-structure)
8. [Routing & URL Rewrite Map](#-routing--url-rewrite-map)
9. [Security Implementations](#-security-implementations)
10. [Evaluation & Testing Checklist](#-evaluation--testing-checklist)
11. [Troubleshooting & FAQs](#-troubleshooting--faqs)
12. [Academic Declaration & Credits](#-academic-declaration--credits)

---

## 🎯 Project Overview

The **Personal Budget Management System** (engineered under the **Fiscal Precision** design specification) is a full-featured, secure, and modern web application developed in pure **PHP** and **MySQL**. It provides individuals with complete mastery over their personal finances through automated transaction recording, category-level budget thresholds, proactive overspending notifications, and interactive visual reporting.

### Core Objectives
- **Financial Clarity:** Replace error-prone manual spreadsheets with intuitive cash-flow tracking.
- **Proactive Budgeting:** Prevent overspending before it occurs via dynamic progress bars and automated threshold warning alerts (triggered at 80% and 100% of budgeted amounts).
- **Actionable Insights:** Give users visual intelligence (income vs. expense trends, category breakdowns, and net balances) through responsive Chart.js analytics.
- **Administrative Governance:** Equip system administrators with a dedicated management portal to monitor platform metrics, manage users, modify security roles, and standardize default category templates.

---

## ✨ Key Features & Modules

### 1. User Portal
- **Secure Authentication:** User registration, password encryption with PHP's native `PASSWORD_BCRYPT`, secure session persistence, and instant logout.
- **Profile Management:** Update personal credentials, change display names, and upload profile pictures with automated server-side MIME verification, size restrictions (under 2MB), and old file garbage collection.
- **Privacy Mode (Balance Toggle):** Built-in one-click toggle to mask or unmask sensitive account balances with `localStorage` state persistence.
- **Income Tracking:**
  - Record revenue with custom amounts, source categories, dates, and optional remarks.
  - Paginated transaction logs with live category badge styling.
- **Expense Tracking:**
  - Instant logging of expenditures against predefined or custom expense categories.
  - Automated budget recalculation triggered on every insert/update.

### 2. Smart Budgeting Engine & Alerts
- **Monthly Category Limits:** Define explicit spending caps per category for any month and year.
- **Automated Threshold Warnings:**
  - **On Track (< 80%):** Displayed in soft green indicator with exact amount remaining.
  - **Warning (80% – 99%):** Displayed in amber indicator; auto-generates a system notification warning the user of impending budget exhaustion.
  - **Exceeded (≥ 100%):** Displayed in high-contrast expense red; immediately alerts the user with exact deficit calculations.
- **In-App Notification Center:** Unread alerts appear directly on the user dashboard with dismiss/read state persistence.

### 3. Financial Analytics & Visual Reports
- **Dynamic Chart.js Integration:**
  - **6-Month Cash Flow Trend:** Dual-line graph displaying monthly income vs. monthly expenditures along with net cumulative savings.
  - **Category Expense Breakdown:** Donut / Bar charts revealing where funds are distributed.
- **Multi-Period Reporting:**
  - **Daily Report:** Day-specific transaction breakdowns.
  - **Monthly Report:** Full monthly summary, category-wise spending totals, and net balance.
  - **Yearly Report:** Annual financial reconciliation.

### 4. Administrator Control Panel
- **Isolated Admin Authentication:** Dedicated `/admin/login` access separate from standard users.
- **System Metrics Dashboard:** Real-time counters showing total active users, gross platform income, gross expenditures, and total system categories.
- **User Management (`/admin/users`):**
  - Search, filter by role (`user` vs `admin`), and paginated directory.
  - Role promotion/demotion (promote standard users to administrators or revoke admin privileges).
  - Delete user accounts with relational cascade cleanup.
- **Category Template Management (`/admin/categories`):**
  - Add, edit, or remove default template categories (`user_id = 0`) that automatically seed into every newly registered user's account.

---

## 💻 Technology Stack

| Layer | Technology | Details |
|---|---|---|
| **Backend Language** | PHP 7.4 / PHP 8.0+ | Native Object-Oriented PDO database abstraction, native session handlers, BCRYPT hashing |
| **Database** | MySQL 5.7+ / MariaDB 10.3+ | InnoDB engine, utf8mb4 charset, foreign keys with `ON DELETE CASCADE` |
| **Web Server** | Apache HTTP Server | URL rewriting via `mod_rewrite` configured in `.htaccess` |
| **Frontend Markup** | HTML5 Semantic Elements | Accessible structures, modal overlays, clean layouts |
| **Styling & Design** | Pure CSS3 (Fiscal Precision) | Custom CSS design tokens, responsive grid, Inter typography, Material Symbols |
| **Client-Side Scripting** | Vanilla JavaScript (ES6+) | Form validation, AJAX/Modal handlers, dynamic DOM manipulation, LocalStorage |
| **Data Visualization** | Chart.js 4.4.3 (CDN) | Interactive, responsive canvas charts with INR (₹) tooltips |
| **Typography & Icons** | Google Fonts & Icons | Inter font family, Google Material Symbols Outlined, FontAwesome |

---

## 🗄 Database Architecture & Schema

The application uses the `budget_db` relational schema defined in `database.sql`.

```
                    +--------------------+
                    |       admins       |
                    +--------------------+
                    | id (PK)            |
                    | full_name          |
                    | username (UNIQUE)  |
                    | email (UNIQUE)     |
                    | password (BCRYPT)  |
                    | created_at         |
                    +--------------------+

    +-------------------+           +-----------------------+
    |       users       | 1       * |      categories       |
    +-------------------+-----------+-----------------------+
    | id (PK)           |           | id (PK)               |
    | username (UNIQUE) |           | user_id (0=template)  |
    | email (UNIQUE)    |           | name                  |
    | password (BCRYPT) |           | type (income/expense) |
    | role (user/admin) |           | created_at            |
    | profile_pic       |           +-----------------------+
    | created_at        |                       | 1
    | updated_at        |                       |
    +-------------------+                       |
      | 1        | 1             | 1            |
      | *        | *             | *            | *
+-------------+ +-------------+ +------------+ +------------+
|   income    | |  expenses   | |  budgets   | |notificat'ns|
+-------------+ +-------------+ +------------+ +------------+
| id (PK)     | | id (PK)     | | id (PK)    | | id (PK)    |
| user_id(FK) | | user_id(FK) | | user_id(FK)| | user_id(FK)|
| category_id | | category_id | |category_id | | message    |
| amount      | | amount      | | month      | | type       |
| description | | description | | year       | | is_read    |
| date        | | date        | | amount     | | created_at |
+-------------+ +-------------+ +------------+ +------------+
```

### Table Summary:
1. **`users`**: User records with credentials, role flag, and profile avatar filename.
2. **`admins`**: Dedicated administrative credentials table with default seed administrator.
3. **`categories`**: Stores income and expense categories. When `user_id = 0`, it serves as a master template cloned into each user's profile upon registration.
4. **`income`**: Inflow records linked to `users(id)` and `categories(id)`.
5. **`expenses`**: Outflow transactions linked to `users(id)` and `categories(id)`.
6. **`budgets`**: Monthly spending limits per category, enforced with a unique constraint on `(user_id, category_id, month, year)`.
7. **`notifications`**: Automated budget alert messages generated when expenditures exceed 80% or 100% of limits.

---

## 🚀 Complete Setup & Installation Guide

Follow these step-by-step instructions to set up, configure, and run the project from initial download to final execution.

### Step 0: Prerequisites
Ensure you have a local PHP development stack installed. Any of the following works:
- **XAMPP** (Recommended for Windows / macOS / Linux) — includes Apache, PHP 8.x, and MariaDB/MySQL.
- **WampServer** or **Laragon** (Windows).
- **MAMP** (macOS).
- **Native LAMP Stack** (`sudo apt install apache2 php mysql-server libapache2-mod-php php-mysql`).

---

### Step 1: Clone / Copy to Web Server Directory

Place the project directory inside your web server's document root:

- **For XAMPP:** `C:\xampp\htdocs\4th-sem-project` (Windows) or `/opt/lampp/htdocs/4th-sem-project` (Linux).
- **For WampServer:** `C:\wamp64\www\4th-sem-project`.
- **For Linux Apache (Native):** `/var/www/html/4th-sem-project`.

---

### Step 2: Database Creation & Migration

#### Method A: Using phpMyAdmin (GUI)
1. Open your browser and navigate to `http://localhost/phpmyadmin`.
2. Click on the **Databases** tab.
3. In the "Create database" field, enter `budget_db` and choose `utf8mb4_general_ci` as collation, then click **Create**.
4. Select the newly created `budget_db` database in the left sidebar.
5. Click on the **Import** tab at the top.
6. Click **Choose File**, select `database.sql` from your project folder, and click **Import** (or **Go**).
7. You should see a success message indicating all tables and default categories have been imported.

#### Method B: Using MySQL Terminal / Command Line
Open your terminal or command prompt and execute:

```bash
# Log in to MySQL client
mysql -u root -p

# Inside MySQL prompt:
CREATE DATABASE IF NOT EXISTS budget_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# Import the schema file directly:
mysql -u root -p budget_db < /path/to/4th-sem-project/database.sql
```

---

### Step 3: Database Connection Configuration

Open the `config/database.php` file in a text editor.

The default configuration is preset for standard XAMPP/WAMP setups:
```php
define('DB_HOST', getenv('BUDGET_DB_HOST') ?: 'localhost');
define('DB_NAME', 'budget_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', getenv('BUDGET_DB_PORT') ?: 3306);
```

- If your MySQL database has a password (e.g., in MAMP default password is `root`, or custom password), update line 7:
  ```php
  define('DB_PASS', 'your_mysql_password_here');
  ```
- If your MySQL server runs on a non-standard port (e.g., 3307), set `DB_PORT` or configure the environment variable `BUDGET_DB_PORT`.

---

### Step 4: Apache Server & URL Rewrite Configuration

This project relies on clean URL rewriting (e.g., `/dashboard` redirects internally to `user/dashboard.php`).

1. **Verify Apache `mod_rewrite` is enabled:**
   - In **XAMPP Control Panel**: Click **Config** next to Apache → `httpd.conf`.
   - Search for the line:
     ```apache
     #LoadModule rewrite_module modules/mod_rewrite.so
     ```
   - If there is a `#` at the beginning, remove the `#` to uncomment it:
     ```apache
     LoadModule rewrite_module modules/mod_rewrite.so
     ```
2. **Verify `AllowOverride` is enabled:**
   - In `httpd.conf`, find the `<Directory "path/to/htdocs">` section.
   - Ensure `AllowOverride All` is set (not `AllowOverride None`):
     ```apache
     <Directory "C:/xampp/htdocs">
         AllowOverride All
         Require all granted
     </Directory>
     ```
3. **Restart Apache** from the XAMPP / Wamp control panel.

---

### Step 5: Permissions Configuration

The application allows users to upload profile avatars to `/uploads/profiles/`.
Ensure the web server has write permissions to this directory:

- **Linux / macOS:**
  ```bash
  chmod -R 775 uploads/profiles/
  # or ensure Apache/www-data user owns the folder:
  chown -R www-data:www-data uploads/
  ```
- **Windows:** Write permissions are granted by default under XAMPP.

---

### Step 6: Launch & Verify System

1. Open your web browser.
2. Navigate to your local application URL:
   ```
   http://localhost/4th-sem-project/
   ```
3. You will be automatically routed to the login page (`/login`).
4. **Create a Test User:** Click **Create Account** (`/register`) to register a new user account.
5. **Log In to Admin:** Navigate to `http://localhost/4th-sem-project/admin` to access the Admin Control Panel using the default admin credentials.

---

## 🔑 Default Credentials

### Administrator Portal
- **URL:** `http://localhost/4th-sem-project/admin` (or `/admin/login`)
- **Username:** `admin` *(or `admin@example.com`)*
- **Password:** `admin123`
- *Note:* Upon initial access, `admin/login.php` automatically ensures the default administrator account is initialized in the database.

### Standard User Portal
- **URL:** `http://localhost/4th-sem-project/login`
- **Registration:** Users can register new accounts at `http://localhost/4th-sem-project/register`.
- *Note:* New user registrations automatically receive a complete initial set of 14 standard expense & income categories (Salary, Food, Transport, Rent, Healthcare, Utilities, etc.).

---

## 📂 Project Directory & File Structure

```
4th-sem-project/
├── .htaccess                   # Apache URL rewriting & sensitive directory access protection
├── README.md                   # Complete system documentation (this file)
├── database.sql                # Complete relational schema, foreign keys, and seed categories
├── design.md                   # Fiscal Precision design system tokens & specification
│
├── config/
│   └── database.php            # PDO connection, session setup, helper sanitizers & paginators
│
├── includes/
│   ├── auth.php                # User session guard (redirects unauthenticated requests to /login)
│   ├── admin_auth.php          # Admin session guard (restricts access to verified admin IDs)
│   ├── header.php              # Global HTML head, navigation bar, mobile menu, and Chart.js inclusion
│   └── footer.php              # Closing HTML tags, global modal wrappers, and script tags
│
├── admin/
│   ├── login.php               # Isolated admin authentication & automatic seed handler
│   ├── dashboard.php           # High-level platform metrics, recent transactions & users
│   ├── users.php               # User listing, search, role toggling (user <-> admin), account deletion
│   └── categories.php          # Master default category template editor (user_id = 0)
│
├── user/
│   ├── dashboard.php           # User financial dashboard, stats cards, recent activity, trend charts
│   ├── income.php              # Income transaction logger, filters, and paginated logs
│   ├── expenses.php            # Expense logger, auto budget calculation, and warning triggers
│   ├── budget.php              # Monthly category budget limits, visual progress bars, status indicators
│   ├── reports.php             # Financial report generator (Daily, Monthly, Yearly breakdowns)
│   └── profile.php             # User profile editor, password change, and avatar file uploader
│
├── assets/
│   ├── css/
│   │   └── style.css           # Complete Fiscal Precision design system styling & responsive grid
│   └── js/
│       ├── charts.js           # Chart.js initialization logic, tooltip formatters, theme styling
│       └── script.js           # Navigation drawer, modal dialogs, auto-dismiss alerts, balance toggle
│
├── uploads/
│   ├── .htaccess               # Prevents execution of malicious PHP scripts in upload folder
│   └── profiles/               # User uploaded profile pictures (with sample default)
│
├── login.php                   # User login controller & authentication form
├── register.php                # User registration controller & automatic category cloner
└── logout.php                  # Destroys PHP session & redirects to login
```

---

## 🗺 Routing & URL Rewrite Map

Through Apache's `mod_rewrite` configured in `.htaccess`, all query routes are cleanly mapped:

| Public URL Pattern | Target PHP File | Description | Access Level |
|---|---|---|---|
| `/` | `login.php` | Root landing redirect | Public |
| `/login` | `login.php` | User sign-in interface | Public |
| `/register` | `register.php` | New user account creation | Public |
| `/logout` | `logout.php` | Session invalidation | Authenticated |
| `/dashboard` | `user/dashboard.php` | User dashboard & analytics | User |
| `/income` | `user/income.php` | Income transaction management | User |
| `/expenses` | `user/expenses.php` | Expense tracking & budget checks | User |
| `/budget` | `user/budget.php` | Category budget target management | User |
| `/reports` | `user/reports.php` | Multi-interval financial reports | User |
| `/profile` | `user/profile.php` | User profile & avatar settings | User |
| `/admin` or `/admin/login` | `admin/login.php` | Administrator authentication | Public / Admin |
| `/admin/dashboard` | `admin/dashboard.php` | Platform overview & metrics | Administrator |
| `/admin/users` | `admin/users.php` | User accounts & permissions | Administrator |
| `/admin/categories` | `admin/categories.php` | System default category manager | Administrator |

---

## 🛡 Security Implementations

1. **SQL Injection Defense:**
   All queries throughout the application use PHP Data Objects (**PDO**) with parameterized prepared statements (`$pdo->prepare()`) and strict data binding. Emulated prepares are disabled (`PDO::ATTR_EMULATE_PREPARES => false`).
2. **Password Cryptography:**
   User and admin passwords are stored using one-way cryptographic hashes generated via `password_hash($password, PASSWORD_DEFAULT)` utilizing the **BCRYPT** algorithm with automatic salt generation. Password checks are performed using timing-attack-resistant `password_verify()`.
3. **Cross-Site Scripting (XSS) Mitigation:**
   Input is sanitized via custom `sanitize()` helpers, and all user-supplied variables rendered in HTML are escaped using `htmlspecialchars($string, ENT_QUOTES, 'UTF-8')`.
4. **Server Directory Hardening:**
   The root `.htaccess` enforces strict blocking of sensitive directories and file types:
   - Direct requests to `/includes/` and `/config/` return `403 Forbidden`.
   - Direct access to `.sql` and `.md` files is explicitly blocked.
   - The `/uploads/.htaccess` file prevents script execution inside the avatar uploads folder.
5. **Secure File Upload Pipeline:**
   Profile photo uploads enforce:
   - Whitelisted file extensions: `['jpg', 'jpeg', 'png', 'gif']`.
   - File size ceiling: Maximum 2MB.
   - Unique timestamped file naming (`{$user_id}_{time()}.ext`) to prevent path traversal or overwrite attacks.
   - Deletion of orphaned/previous avatars upon new upload.
6. **Session Hijacking & Role Guards:**
   User pages require `includes/auth.php`; admin pages require `includes/admin_auth.php`. Unauthenticated requests are immediately halted and redirected.

---

## 🧪 Evaluation & Testing Checklist

Use this test plan to demonstrate the project for academic grading or QA validation:

| Test Case # | Scenario | Steps to Execute | Expected Result | Status |
|---|---|---|---|---|
| **TC-01** | User Registration | Go to `/register`, submit new username, email, and password. | Account created, 14 default categories auto-seeded, redirect to login with success message. | Pass |
| **TC-02** | User Login | Go to `/login`, enter valid credentials. | Session created, redirected to `/dashboard`. | Pass |
| **TC-03** | Add Income | Go to `/income`, enter ₹50,000 under "Salary". | Total balance increments by ₹50,000, logged in recent transactions table. | Pass |
| **TC-04** | Set Monthly Budget | Go to `/budget`, allocate ₹10,000 for "Food & Dining" for current month. | Budget card appears with 0% spent and green "On Track" status. | Pass |
| **TC-05** | Budget Warning Trigger (80%) | Go to `/expenses`, add ₹8,500 under "Food & Dining". | Progress bar turns Amber (85% used), warning notification auto-injected into dashboard. | Pass |
| **TC-06** | Budget Exceeded Trigger (100%+) | Add another ₹2,000 expense under "Food & Dining" (total ₹10,500). | Progress bar turns Red (105% used), critical overspending notification created. | Pass |
| **TC-07** | Balance Privacy Toggle | Click the eye icon in dashboard header. | All monetary values instantly mask with `••••••`; preference stored in `localStorage`. | Pass |
| **TC-08** | Reports Generation | Go to `/reports`, select "Monthly", choose current month. | Expense breakdown chart renders dynamically; category sums match expense totals. | Pass |
| **TC-09** | Admin Authentication | Go to `/admin/login`, enter `admin` / `admin123`. | Admin session started, routed to platform administration dashboard. | Pass |
| **TC-10** | Admin Role Promotion | In `/admin/users`, click "Make Admin" next to a user. | User's role updates to `admin`, synced into `admins` credentials table. | Pass |
| **TC-11** | Category Template Update | In `/admin/categories`, add "Cryptocurrency" (Income). | New category created with `user_id = 0`, available to future registered users. | Pass |

---

## ❓ Troubleshooting & FAQs

### Q1: I see a "404 Not Found" error when clicking navigation links (e.g., `/dashboard`).
- **Cause:** Apache's `mod_rewrite` is disabled, or `AllowOverride` is not set to `All`.
- **Solution:** Open your Apache `httpd.conf` file, ensure `LoadModule rewrite_module modules/mod_rewrite.so` is uncommented, set `AllowOverride All` within your web root directory block, and restart Apache.

### Q2: "Connection failed: SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost'".
- **Cause:** Incorrect MySQL credentials in `config/database.php`.
- **Solution:** Check whether your MySQL installation has a password set for user `root`. Open `config/database.php` and update `DB_PASS` with your password.

### Q3: Profile picture fails to upload or error says "Permission denied".
- **Cause:** The `uploads/profiles/` folder does not have write permissions.
- **Solution:** Run `chmod -R 775 uploads/profiles/` (on Linux/macOS) or verify folder write permissions in Windows.

### Q4: Charts are not displaying on the Dashboard or Reports page.
- **Cause:** No internet connection to load the Chart.js CDN script, or ad-blocker blocking the CDN.
- **Solution:** Ensure your machine is connected to the internet so `https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js` loads properly, or download `chart.umd.min.js` locally into `assets/js/`.

---

## 📜 Academic Declaration & Credits

This project was developed as a comprehensive **4th Semester Capstone / Course Project** in Web Engineering & Database Management Systems.

- **Project Title:** Personal Budget Management System (*Fiscal Precision*)
- **Core Focus:** Relational Database Engineering, Secure Web Development, Responsive UI Design, and Financial Analytics.
- **Source Repository:** `rupechaudhary7873/4th-sem-project`

---
*Developed with precision for academic demonstration and real-world personal financial tracking.*

