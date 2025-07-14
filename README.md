# Library Management System

A modern, full-featured Library Management System (LMS) built with PHP and MySQL, designed for both administrators and users. The system provides robust book, user, and fine management, real-time analytics, and a responsive, mobile-friendly interface.

---

## Features

### 🔐 Authentication & User Roles
- Secure login, registration, and password reset
- Multi-role support: Admin and User
- Session management and access control

### 📚 Book Management
- CRUD operations for books and categories
- ISBN validation and barcode support
- Book cover uploads and image optimization
- Inventory tracking (quantity management)
- Advanced search and filtering (AJAX-powered)

### 👥 User Management
- User profiles with borrowing history
- Fine tracking and payment
- Borrowing limits and restrictions
- User statistics and analytics

### 📊 Dashboard & Reports
- Real-time statistics with Chart.js
- Borrowing trends and popular books
- Revenue tracking from fines
- User activity monitoring
- PDF/Excel report generation (TCPDF, PhpSpreadsheet)

### 💸 Fine Management
- Automated fine calculation for overdue books
- Fine payment and outstanding fine tracking

### 📱 Mobile-First Design
- Responsive UI using Tailwind CSS
- Touch-friendly and PWA-ready
- Offline browsing capability

### 🔍 Advanced Features
- Real-time search with AJAX
- Barcode scanning for ISBN
- Email notifications for due dates (PHPMailer)
- Backup and restore functionality

### 🛡️ Security
- SQL injection prevention (prepared statements)
- XSS and CSRF protection
- Secure password hashing (bcrypt)
- File upload security
- Rate limiting for API endpoints

---

## Project Structure

```
config/           # Database, authentication, and constants
includes/         # Common header, footer, sidebar, and functions
assets/           # CSS, JS, images
admin/            # Admin dashboard, books, categories, users, issued-books, fines, reports
user/             # User dashboard, profile, books, my-books, fines
api/              # RESTful API endpoints
components/       # Reusable UI components (modals, cards, forms)
uploads/          # Book cover images
```

---

## How It Works

### Admin Panel
- **Dashboard:** View analytics, statistics, and quick actions
- **Books/Categories:** Add, edit, delete, and view books and categories
- **Users:** Manage user accounts and view profiles
- **Issued Books:** Issue, return, and track book borrowing
- **Fines:** Manage and process fines
- **Reports:** Generate and export detailed reports (books, users, financial)

### User Panel
- **Dashboard:** View personal stats and notifications
- **Profile:** Manage personal information
- **Browse/Search Books:** Find and view book details
- **My Books:** Track current, past, and renewal requests
- **Fines:** View and pay outstanding fines

### API
- RESTful endpoints for books, users, dashboard stats, and search
- Secured with authentication and rate limiting

---

## Setup Instructions

1. **Clone the repository**
2. **Import the database**
   - Use `librarydb.sql` to create the required tables
3. **Configure database connection**
   - Edit `config/database.php` with your DB credentials
4. **Install dependencies** (if using Composer for PHPMailer, TCPDF, etc.)
   - `composer install`
5. **Set up web server**
   - Place the project in your web root (e.g., `htdocs` for XAMPP)
6. **Access the app**
   - Visit `http://localhost/libraryMS/` in your browser

---

## Technology Stack
- **Backend:** PHP 8+, MySQL/MariaDB, PDO/MySQLi
- **Frontend:** Tailwind CSS, Alpine.js, Chart.js, Vanilla JS
- **Libraries:** PHPMailer, TCPDF, PhpSpreadsheet

---

## Security & Best Practices
- All user input is validated and sanitized
- Passwords are hashed using bcrypt
- CSRF tokens are used in forms
- File uploads are validated for type and size
- Database queries use prepared statements

---

## Contribution
Pull requests and suggestions are welcome! Please open an issue for major changes.

---

## License
This project is open-source and available under the MIT License.
