<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .fade-in {
            animation: fadeIn 1.2s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .slide-up {
            animation: slideUp 1s cubic-bezier(.4,0,.2,1);
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(60px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .glow {
            box-shadow: 0 0 20px 2px #2563eb44;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex flex-col">
    <header class="w-full py-6 bg-white/80 shadow-md sticky top-0 z-10 fade-in">
        <div class="container mx-auto flex justify-between items-center px-4">
            <div class="flex items-center gap-3">
                <img src="assets/images/logo.png" alt="Library Logo" class="w-10 h-10 rounded-full shadow-md">
                <span class="text-2xl font-bold text-blue-700 tracking-wide">LibraryMS</span>
            </div>
            <nav class="hidden md:flex gap-8 text-lg font-medium">
                <a href="#features" class="hover:text-blue-600 transition">Features</a>
                <a href="#about" class="hover:text-blue-600 transition">About</a>
                <a href="#contact" class="hover:text-blue-600 transition">Contact</a>
            </nav>
            <div class="flex gap-2">
                <a href="auth/login.php" class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 transition font-semibold shadow">Login</a>
                <a href="auth/register.php" class="bg-white border border-blue-600 text-blue-600 px-5 py-2 rounded hover:bg-blue-50 transition font-semibold">Register</a>
            </div>
        </div>
    </header>
    <main class="flex-1 flex flex-col items-center justify-center text-center px-4">
        <section class="mt-12 mb-16 fade-in">
            <h1 class="text-4xl md:text-6xl font-extrabold text-blue-800 mb-6 slide-up">Welcome to <span class="text-indigo-600">LibraryMS</span></h1>
            <p class="text-lg md:text-2xl text-gray-700 mb-8 max-w-2xl mx-auto slide-up" style="animation-delay:0.2s;animation-fill-mode:both;">A modern, responsive, and feature-rich Library Management System for seamless book, user, and fine management. Empower your library with real-time analytics, mobile-first design, and advanced automation.</p>
            <div class="flex flex-col md:flex-row gap-4 justify-center items-center slide-up" style="animation-delay:0.4s;animation-fill-mode:both;">
                <a href="auth/login.php" class="bg-blue-600 text-white px-8 py-3 rounded-lg text-lg font-bold shadow-lg hover:bg-blue-700 transition glow">Get Started</a>
                <a href="#features" class="bg-white border border-blue-600 text-blue-600 px-8 py-3 rounded-lg text-lg font-bold hover:bg-blue-50 transition">Learn More</a>
            </div>
        </section>
        <section id="features" class="w-full max-w-5xl mx-auto mb-20">
            <h2 class="text-3xl font-bold text-indigo-700 mb-8 fade-in">Key Features</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white rounded-xl shadow-lg p-6 fade-in slide-up">
                    <i class="fas fa-user-shield text-3xl text-blue-600 mb-4"></i>
                    <h3 class="font-bold text-lg mb-2">Multi-Role Authentication</h3>
                    <p class="text-gray-600">Secure login, registration, and role-based access for admins and users.</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 fade-in slide-up">
                    <i class="fas fa-book text-3xl text-indigo-600 mb-4"></i>
                    <h3 class="font-bold text-lg mb-2">Book & Inventory Management</h3>
                    <p class="text-gray-600">CRUD for books, categories, ISBN validation, cover uploads, and inventory tracking.</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 fade-in slide-up">
                    <i class="fas fa-chart-line text-3xl text-green-600 mb-4"></i>
                    <h3 class="font-bold text-lg mb-2">Analytics & Reports</h3>
                    <p class="text-gray-600">Real-time statistics, borrowing trends, and exportable reports.</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 fade-in slide-up">
                    <i class="fas fa-mobile-alt text-3xl text-purple-600 mb-4"></i>
                    <h3 class="font-bold text-lg mb-2">Mobile-First & PWA</h3>
                    <p class="text-gray-600">Responsive design, touch-friendly UI, and offline-ready features.</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 fade-in slide-up">
                    <i class="fas fa-search text-3xl text-yellow-600 mb-4"></i>
                    <h3 class="font-bold text-lg mb-2">Advanced Search</h3>
                    <p class="text-gray-600">AJAX-powered real-time search and barcode scanning for quick access.</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 fade-in slide-up">
                    <i class="fas fa-lock text-3xl text-red-600 mb-4"></i>
                    <h3 class="font-bold text-lg mb-2">Security</h3>
                    <p class="text-gray-600">Prepared statements, CSRF/XSS protection, secure uploads, and more.</p>
                </div>
            </div>
        </section>
        <section id="about" class="w-full max-w-3xl mx-auto mb-20 fade-in">
            <h2 class="text-3xl font-bold text-blue-700 mb-4">About LibraryMS</h2>
            <p class="text-gray-700 text-lg mb-4">LibraryMS is designed to streamline library operations for both administrators and users. With a focus on usability, security, and automation, it offers a seamless experience for managing books, users, fines, and analytics—all in a beautiful, mobile-friendly interface.</p>
            <ul class="text-left text-gray-600 list-disc pl-6">
                <li>Built with PHP 8+, MySQL/MariaDB, Tailwind CSS, and Chart.js</li>
                <li>Modular, component-based architecture for easy customization</li>
                <li>Open-source and extensible for any library size</li>
            </ul>
        </section>
        <section id="contact" class="w-full max-w-2xl mx-auto mb-16 fade-in">
            <h2 class="text-3xl font-bold text-indigo-700 mb-4">Contact & Support</h2>
            <p class="text-gray-700 mb-4">For support, feature requests, or contributions, please open an issue on GitHub or contact the project maintainer.</p>
            <div class="flex flex-col md:flex-row gap-4 justify-center items-center">
                <a href="mailto:support@libraryms.local" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition font-semibold"><i class="fas fa-envelope mr-2"></i>Email Support</a>
                <a href="https://github.com/your-repo/libraryms" target="_blank" class="bg-gray-800 text-white px-6 py-2 rounded hover:bg-gray-900 transition font-semibold"><i class="fab fa-github mr-2"></i>GitHub</a>
            </div>
        </section>
    </main>
    <footer class="w-full py-6 bg-white/80 shadow-inner text-center text-gray-500 text-sm fade-in">
        &copy; <?php echo date('Y'); ?> LibraryMS. All rights reserved.
    </footer>
    <script>
        // Animate features on scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fadeIn');
                }
            });
        }, { threshold: 0.1 });
        document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));
    </script>
</body>
</html>
