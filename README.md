# InvoiSync 🇲🇾

InvoiSync is a modern, full-stack web application designed to help Malaysian businesses seamlessly comply with the **Lembaga Hasil Dalam Negeri (LHDN) E-Invoicing** requirements. Built with a robust Laravel backend and a highly interactive React frontend, InvoiSync simplifies the complex process of creating, validating, and submitting e-invoices directly to the MyInvois portal.

Whether you are a small business transitioning to e-invoicing or a larger enterprise needing bulk upload capabilities, InvoiSync provides an intuitive Business Intelligence dashboard and powerful operational tools to manage your invoicing workflow end-to-end.

## 🚀 Key Features

### 🏢 **LHDN MyInvois Integration**
- **Direct Submission:** Seamlessly connects to the LHDN MyInvois API using the official `Laraditz/MyInvois` SDK.
- **TIN Validation:** Real-time validation of Tax Identification Numbers (TIN) and Registration Numbers (BRN/NRIC) directly from your Business Profile.
- **Status Syncing:** Automatically polls and syncs invoice statuses (Draft, Submitted, Valid, Invalid, Rejected) directly from LHDN's servers.

### 📊 **Business Intelligence Dashboard**
- **Visual Analytics:** Beautiful, interactive charts powered by `Chart.js` to track your revenue trends and estimate tax liabilities.
- **LHDN Rejection Insights:** Analyzes and visualizes exactly *why* your invoices were rejected by LHDN (e.g., date-time errors, invalid TINs) so you can fix them quickly.
- **Top Customers:** Tracks your top-performing clients by valid invoice volume.

### 📁 **Smart Bulk Upload**
- **Excel & CSV Support:** Easily upload hundreds of invoices at once.
- **Intelligent Data Mapping:** Automatically maps your spreadsheet columns (Buyer Name, TIN, Line Items, Unit Price) to the correct LHDN schema.
- **Pre-flight Validation:** Highlights exact rows and columns that have errors (e.g., missing TIN, invalid MSIC code) *before* you submit anything to LHDN.
- **Bulk Submission:** Submit all your validated invoices to the LHDN portal with a single click.

### 🎨 **Modern & Responsive UI**
- Built with **React** and **Inertia.js** for a lightning-fast Single Page Application (SPA) feel.
- Styled with **Tailwind CSS**, featuring full dark mode support, glassmorphism elements, and smooth micro-animations.

## 🛠️ Tech Stack

**Backend:**
- PHP 8.2+
- Laravel 11.x
- `laraditz/my-invois` (LHDN SDK)
- `PhpOffice/PhpSpreadsheet` (Excel parsing)
- MySQL / PostgreSQL

**Frontend:**
- React 18
- Inertia.js
- Tailwind CSS
- `react-chartjs-2` & `Chart.js` (Data visualizations)
- Vite (Bundling)

## ⚙️ Setup & Installation

### Prerequisites
- PHP >= 8.2
- Node.js (v18+) & npm
- Composer
- A Database (MySQL/MariaDB or PostgreSQL)

### Getting Started

1. **Clone the repository:**
   ```bash
   git clone https://github.com/yourusername/invoisync.git
   cd invoisync
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Install Node dependencies:**
   ```bash
   npm install
   ```

4. **Environment Configuration:**
   Copy the example environment file and configure your database settings.
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Run Migrations:**
   ```bash
   php artisan migrate
   ```

6. **Start the Development Servers:**
   You will need two terminal windows running simultaneously.
   
   *Terminal 1 (Laravel Backend):*
   ```bash
   php artisan serve
   ```
   
   *Terminal 2 (React Frontend & Vite):*
   ```bash
   npm run dev
   ```

## 🔒 Configuring LHDN Credentials

To submit real invoices, you must configure your LHDN MyInvois API credentials:
1. Register for a Sandbox or Production account on the LHDN MyInvois Portal.
2. Obtain your **Client ID** and **Client Secret**.
3. Log into InvoiSync and navigate to your **Business Profile**.
4. Enter your credentials at the bottom of the form and click **Validate TIN** to ensure they are working correctly.

*(Note: By default, the application runs in Sandbox mode. Set `MYINVOIS_SANDBOX=false` in your `.env` to switch to Production.)*

## 🤝 Contributing

Contributions are welcome! If you find a bug or have a feature request, please open an issue. If you'd like to contribute code, please submit a Pull Request.

## 📄 License

The InvoiSync project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
