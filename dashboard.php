<?php
require_once 'config.php';

// Calculate stats
$total_revenue = $pdo->query("SELECT SUM(total) as revenue FROM sales WHERE status = 'paid'")->fetchColumn() ?: 0;
$total_products = $pdo->query("SELECT COUNT(*) as count FROM products")->fetchColumn();
$active_products = $pdo->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'")->fetchColumn();
$inactive_products = $pdo->query("SELECT COUNT(*) as count FROM products WHERE status = 'inactive'")->fetchColumn();
$total_customers = $pdo->query("SELECT COUNT(*) as count FROM customers")->fetchColumn();
$total_invoices = $pdo->query("SELECT COUNT(*) as count FROM invoices")->fetchColumn();
$recent_transactions = $pdo->query("SELECT s.*, c.name as customer_name FROM sales s JOIN customers c ON s.customer_id = c.id ORDER BY s.created_at DESC LIMIT 5")->fetchAll();
$low_stock_products = $pdo->query("SELECT * FROM products WHERE category = 'product' AND status = 'active' AND stock < 10")->fetchAll();

// Get daily revenue
$daily_revenue = $pdo->query("SELECT SUM(total) as revenue FROM sales WHERE DATE(created_at) = CURDATE() AND status = 'paid'")->fetchColumn() ?: 0;
// Get monthly revenue
$monthly_revenue = $pdo->query("SELECT SUM(total) as revenue FROM sales WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status = 'paid'")->fetchColumn() ?: 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>POS Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media (max-width: 640px) {
            .responsive-text-3xl { font-size: 1.5rem; }
            .responsive-text-2xl { font-size: 1.25rem; }
            .responsive-text-xl { font-size: 1rem; }
            .responsive-text-lg { font-size: 0.875rem; }
            .responsive-text-sm { font-size: 0.75rem; }
            .responsive-text-xs { font-size: 0.65rem; }
            .responsive-py-8 { padding-top: 1rem; padding-bottom: 1rem; }
            .responsive-px-6 { padding-left: 1rem; padding-right: 1rem; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        <div class="mb-6 sm:mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 responsive-text-3xl">Dashboard</h1>
                    <p class="text-gray-600 text-sm sm:text-base mt-2 responsive-text-sm">Welcome to your POS system overview</p>
                </div>
                <div class="flex flex-col sm:flex-row sm:space-x-4 gap-2">
                    <a href="daily_revenue.php" class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 flex items-center text-sm sm:text-base">
                        <i data-lucide="calendar-days" class="w-4 h-4 sm:w-5 sm:h-5 mr-2"></i> Daily Report
                    </a>
                    <a href="monthly_revenue.php" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center text-sm sm:text-base">
                        <i data-lucide="calendar-range" class="w-4 h-4 sm:w-5 sm:h-5 mr-2"></i> Monthly Report
                    </a>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 sm:p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm font-medium text-gray-600 responsive-text-sm">Daily Revenue</p>
                        <p class="text-lg sm:text-2xl font-bold text-gray-900 mt-2 responsive-text-2xl">Rs. <?php echo number_format($daily_revenue, 2); ?></p>
                    </div>
                    <div class="p-2 sm:p-3 rounded-lg bg-cyan-500">
                        <i data-lucide="calendar-days" class="w-5 h-5 sm:w-6 sm:h-6 text-white"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 sm:p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm font-medium text-gray-600 responsive-text-sm">Monthly Revenue</p>
                        <p class="text-lg sm:text-2xl font-bold text-gray-900 mt-2 responsive-text-2xl">Rs. <?php echo number_format($monthly_revenue, 2); ?></p>
                    </div>
                    <div class="p-2 sm:p-3 rounded-lg bg-indigo-500">
                        <i data-lucide="calendar-range" class="w-5 h-5 sm:w-6 sm:h-6 text-white"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 sm:p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm font-medium text-gray-600 responsive-text-sm">Total Revenue</p>
                        <p class="text-lg sm:text-2xl font-bold text-gray-900 mt-2 responsive-text-2xl">Rs. <?php echo number_format($total_revenue, 2); ?></p>
                    </div>
                    <div class="p-2 sm:p-3 rounded-lg bg-emerald-500">
                        <i data-lucide="dollar-sign" class="w-5 h-5 sm:w-6 sm:h-6 text-white"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 sm:p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm font-medium text-gray-600 responsive-text-sm">Active Products</p>
                        <p class="text-lg sm:text-2xl font-bold text-gray-900 mt-2 responsive-text-2xl"><?php echo $active_products; ?>/<?php echo $total_products; ?></p>
                    </div>
                    <div class="p-2 sm:p-3 rounded-lg bg-blue-500">
                        <i data-lucide="shopping-bag" class="w-5 h-5 sm:w-6 sm:h-6 text-white"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 sm:p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm font-medium text-gray-600 responsive-text-sm">Customers</p>
                        <p class="text-lg sm:text-2xl font-bold text-gray-900 mt-2 responsive-text-2xl"><?php echo $total_customers; ?></p>
                    </div>
                    <div class="p-2 sm:p-3 rounded-lg bg-purple-500">
                        <i data-lucide="users" class="w-5 h-5 sm:w-6 sm:h-6 text-white"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 sm:p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm font-medium text-gray-600 responsive-text-sm">Invoices</p>
                        <p class="text-lg sm:text-2xl font-bold text-gray-900 mt-2 responsive-text-2xl"><?php echo $total_invoices; ?></p>
                    </div>
                    <div class="p-2 sm:p-3 rounded-lg bg-orange-500">
                        <i data-lucide="trending-up" class="w-5 h-5 sm:w-6 sm:h-6 text-white"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 sm:p-6">
                <h2 class="text-lg sm:text-xl font-semibold text-gray-900 mb-4 responsive-text-xl">Recent Transactions</h2>
                <?php if (empty($recent_transactions)): ?>
                    <p class="text-gray-500 text-center py-4 sm:py-8 responsive-py-8 responsive-text-sm">No transactions yet</p>
                <?php else: ?>
                    <div class="space-y-3 sm:space-y-4">
                        <?php foreach ($recent_transactions as $transaction): ?>
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between py-2 sm:py-3 border-b border-gray-100 last:border-b-0">
                                <div>
                                    <p class="font-medium text-gray-900 text-sm sm:text-base responsive-text-sm"><?php echo htmlspecialchars($transaction['customer_name']); ?></p>
                                    <p class="text-xs sm:text-sm text-gray-500 responsive-text-xs"><?php echo htmlspecialchars($transaction['invoice_id']); ?></p>
                                </div>
                                <div class="mt-2 sm:mt-0 sm:text-right">
                                    <p class="font-semibold text-gray-900 text-sm sm:text-base responsive-text-sm">Rs. <?php echo number_format($transaction['total'], 2); ?></p>
                                    <p class="text-xs sm:text-sm text-gray-500 responsive-text-xs"><?php echo ucfirst($transaction['payment_method']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 sm:p-6">
                <h2 class="text-lg sm:text-xl font-semibold text-gray-900 mb-4 responsive-text-xl">Product Status</h2>
                <div class="space-y-3 sm:space-y-4">
                    <div class="flex items-center justify-between p-2 sm:p-3 bg-green-50 rounded-lg">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <i data-lucide="check-circle" class="w-4 h-4 sm:w-5 sm:h-5 text-green-600"></i>
                            <span class="font-medium text-green-900 text-sm sm:text-base responsive-text-sm">Active Products</span>
                        </div>
                        <span class="text-lg sm:text-2xl font-bold text-green-600 responsive-text-2xl"><?php echo $active_products; ?></span>
                    </div>
                    <div class="flex items-center justify-between p-2 sm:p-3 bg-red-50 rounded-lg">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <i data-lucide="x-circle" class="w-4 h-4 sm:w-5 sm:h-5 text-red-600"></i>
                            <span class="font-medium text-red-900 text-sm sm:text-base responsive-text-sm">Inactive Products</span>
                        </div>
                        <span class="text-lg sm:text-2xl font-bold text-red-600 responsive-text-2xl"><?php echo $inactive_products; ?></span>
                    </div>
                    <?php if ($inactive_products > 0): ?>
                        <div class="mt-3 sm:mt-4 p-2 sm:p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-center gap-2">
                                <i data-lucide="alert-triangle" class="w-3 h-3 sm:w-4 sm:h-4 text-yellow-600"></i>
                                <span class="text-xs sm:text-sm font-medium text-yellow-800 responsive-text-xs">
                                    You have <?php echo $inactive_products; ?> inactive product<?php echo $inactive_products > 1 ? 's' : ''; ?>
                                </span>
                            </div>
                            <p class="text-xs text-yellow-700 mt-1 responsive-text-xs">Inactive products won't appear in sales</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 sm:p-6">
                <h2 class="text-lg sm:text-xl font-semibold text-gray-900 mb-4 responsive-text-xl">Low Stock Alert</h2>
                <?php if (empty($low_stock_products)): ?>
                    <p class="text-gray-500 text-center py-4 sm:py-8 responsive-py-8 responsive-text-sm">All active products are well stocked</p>
                <?php else: ?>
                    <div class="space-y-3 sm:space-y-4 overflow-y-auto max-h-64">
                        <?php foreach ($low_stock_products as $product): ?>
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between py-2 sm:py-3 border-b border-gray-100 last:border-b-0">
                                <div>
                                    <p class="font-medium text-gray-900 text-sm sm:text-base responsive-text-sm"><?php echo htmlspecialchars($product['name']); ?></p>
                                    <p class="text-xs sm:text-sm text-gray-500 responsive-text-xs"><?php echo htmlspecialchars($product['sku']); ?></p>
                                </div>
                                <div class="mt-2 sm:mt-0 sm:text-right">
                                    <span class="inline-flex items-center px-2 py-0.5 sm:px-2.5 sm:py-0.5 rounded-full text-xs sm:text-xs font-medium <?php echo $product['stock'] == 0 ? 'bg-red-100 text-red-800' : ($product['stock'] <= 5 ? 'bg-yellow-100 text-yellow-800' : 'bg-orange-100 text-orange-800'); ?> responsive-text-xs">
                                        <?php echo $product['stock']; ?> left
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>