<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=Please log in to access the dashboard");
    exit;
}
require_once 'config.php';
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>POS System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
            // Toggle sidebar on mobile
            document.getElementById('menu-toggle').addEventListener('click', () => {
                document.getElementById('sidebar').classList.toggle('hidden');
            });
        });
    </script>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="flex flex-col md:flex-row min-h-screen">
        <!-- Mobile Menu Button -->
        <button id="menu-toggle" class="md:hidden p-4 bg-black text-white flex items-center justify-between">
            <span>Menu</span>
            <i data-lucide="menu" class="w-6 h-6"></i>
        </button>
        <!-- Sidebar -->
        <div id="sidebar" class="w-full md:w-64 bg-black text-white md:sticky md:top-0 md:h-screen hidden md:block">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-blue-400">POS System</h1>
                <p class="text-slate-400 text-sm mt-1">Point of Sale</p>
                <p class="text-slate-200 text-sm mt-2">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            </div>
            <nav class="mt-8">
                <a href="?page=dashboard" class="w-full flex items-center px-6 py-3 text-left transition-colors <?php echo $page == 'dashboard' ? 'bg-blue-600 text-white border-r-2 border-blue-400' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?>" aria-label="Dashboard">
                    <i data-lucide="layout-dashboard" class="w-5 h-5 mr-3"></i> Dashboard
                </a>
                <a href="?page=sales" class="w-full flex items-center px-6 py-3 text-left transition-colors <?php echo $page == 'sales' ? 'bg-blue-600 text-white border-r-2 border-blue-400' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?>" aria-label="Sales">
                    <i data-lucide="shopping-cart" class="w-5 h-5 mr-3"></i> Sales
                </a>
                <a href="?page=products" class="w-full flex items-center px-6 py-3 text-left transition-colors <?php echo $page == 'products' ? 'bg-blue-600 text-white border-r-2 border-blue-400' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?>" aria-label="Products">
                    <i data-lucide="package" class="w-5 h-5 mr-3"></i> Products
                </a>
                <a href="?page=invoices" class="w-full flex items-center px-6 py-3 text-left transition-colors <?php echo $page == 'invoices' ? 'bg-blue-600 text-white border-r-2 border-blue-400' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?>" aria-label="Invoices">
                    <i data-lucide="file-text" class="w-5 h-5 mr-3"></i> Invoices
                </a>
                <a href="?page=customers" class="w-full flex items-center px-6 py-3 text-left transition-colors <?php echo $page == 'customers' ? 'bg-blue-600 text-white border-r-2 border-blue-400' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?>" aria-label="Customers">
                    <i data-lucide="users" class="w-5 h-5 mr-3"></i> Customers
                </a>
                <a href="?page=day-end" class="w-full flex items-center px-6 py-3 text-left transition-colors <?php echo $page == 'day-end' ? 'bg-blue-600 text-white border-r-2 border-blue-400' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?>" aria-label="Day End">
                    <i data-lucide="calendar-check" class="w-5 h-5 mr-3"></i> Day End
                </a>
                <a href="logout.php" class="w-full flex items-center px-6 py-3 text-left transition-colors text-slate-300 hover:bg-red-800 hover:text-white" aria-label="Logout">
                    <i data-lucide="log-out" class="w-5 h-5 mr-3"></i> Logout
                </a>
            </nav>
        </div>
        <!-- Main Content -->
        <div class="flex-1 p-4 sm:p-6 md:p-8">
            <?php
            $pages = ['dashboard', 'products', 'sales', 'invoices', 'customers', 'day-end'];
            if (in_array($page, $pages)) {
                include $page . '.php';
            } else {
                echo '<div class="text-red-600 text-center">Page not found!</div>';
            }
            ?>
        </div>
    </div>
</body>

</html>