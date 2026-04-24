# InvoiSync

InvoiSync is a modern e-invoicing web application built with Laravel and React. It features integration with the MyInvois SDK to streamline invoicing processes, including a robust bulk invoice upload capability.

## Features

- **E-Invoicing Integration:** Seamlessly connect and comply with MyInvois SDK requirements.
- **Bulk Invoice Uploads:** Easily upload bulk invoices via Excel or CSV with automatic data mapping, subtotal calculations, and detailed UI validation error highlighting.
- **Modern UI/UX:** Built with React, Vite, and Tailwind CSS, featuring responsive layouts and dark mode support.
- **Robust Backend:** Powered by Laravel, providing solid API endpoints, database schemas, and service classes for invoice management.

## Tech Stack

- **Backend:** PHP, Laravel
- **Frontend:** React, Vite, Tailwind CSS
- **Data Parsing:** PhpOffice/PhpSpreadsheet

## Setup & Installation

### Prerequisites
- PHP >= 8.1
- Node.js & npm
- Composer

### Getting Started

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
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
   Copy `.env.example` to `.env` and configure your database and MyInvois API credentials.
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Run Migrations:**
   ```bash
   php artisan migrate
   ```

6. **Start the Development Servers:**
   - **Laravel Backend:**
     ```bash
     php artisan serve
     ```
   - **React Frontend:**
     ```bash
     npm run dev
     ```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The InvoiSync project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
