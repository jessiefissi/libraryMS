<?php
// Application Constants
define('APP_NAME', 'Library Management System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/libraryMS');

// Database Constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'librarydb');
define('DB_USER', 'root');
define('DB_PASS', '');

// Authentication Constants
define('SESSION_NAME', 'library_session');
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('REMEMBER_ME_DURATION', 86400 * 30); // 30 days

// File Upload Constants
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('BOOK_COVERS_PATH', UPLOAD_PATH . 'book-covers/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Pagination Constants
define('RECORDS_PER_PAGE', 10);
define('ADMIN_RECORDS_PER_PAGE', 15);

// Book Management Constants
define('MAX_BORROW_DAYS', 14);
define('MAX_BOOKS_PER_USER', 5);
define('FINE_PER_DAY', 1.00);

// Security Constants
define('CSRF_TOKEN_NAME', '_token');
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Email Constants
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@library.com');
define('FROM_NAME', 'Library Management System');

// API Constants
define('API_RATE_LIMIT', 100); // requests per hour
define('API_VERSION', 'v1');

// System Paths
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes/');
define('COMPONENTS_PATH', ROOT_PATH . '/components/');
define('ASSETS_PATH', ROOT_PATH . '/assets/');

// User Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'user');

// Status Constants
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');
define('STATUS_ISSUED', 'issued');
define('STATUS_RETURNED', 'returned');
define('STATUS_OVERDUE', 'overdue');

// Date Formats
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'd/m/Y');
define('DISPLAY_DATETIME_FORMAT', 'd/m/Y H:i');

// Error Messages
define('ERROR_UNAUTHORIZED', 'Unauthorized access');
define('ERROR_INVALID_REQUEST', 'Invalid request');
define('ERROR_SERVER_ERROR', 'Internal server error');
define('ERROR_NOT_FOUND', 'Resource not found');

// Success Messages
define('SUCCESS_LOGIN', 'Login successful');
define('SUCCESS_LOGOUT', 'Logout successful');
define('SUCCESS_REGISTRATION', 'Registration successful');
define('SUCCESS_UPDATE', 'Update successful');
define('SUCCESS_DELETE', 'Delete successful');
define('SUCCESS_CREATE', 'Created successfully');
?>