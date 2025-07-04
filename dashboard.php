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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>POS Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-600 mt-2">Welcome to your POS system overview</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">Rs. <?php echo number_format($total_revenue, 2); ?>
                    </p>
                </div>
                <div class="p-3 rounded-lg bg-emerald-500">
                    <i data-lucide="dollar-sign" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Active Products</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">
                        <?php echo $active_products; ?>/<?php echo $total_products; ?></p>
                </div>
                <div class="p-3 rounded-lg bg-blue-500">
                    <i data-lucide="shopping-bag" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Customers</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo $total_customers; ?></p>
                </div>
                <div class="p-3 rounded-lg bg-purple-500">
                    <i data-lucide="users" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Invoices</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo $total_invoices; ?></p>
                </div>
                <div class="p-3 rounded-lg bg-orange-500">
                    <i data-lucide="trending-up" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Recent Transactions</h2>
            <?php if (empty($recent_transactions)): ?>
                <p class="text-gray-500 text-center py-8">No transactions yet</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($recent_transactions as $transaction): ?>
                        <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-b-0">
                            <div>
                                <p class="font-medium text-gray-900">
                                    <?php echo htmlspecialchars($transaction['customer_name']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($transaction['invoice_id']); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900">Rs.
                                    <?php echo number_format($transaction['total'], 2); ?></p>
                                <p class="text-sm text-gray-500"><?php echo ucfirst($transaction['payment_method']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Product Status</h2>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <i data-lucide="check-circle" class="w-5 h-5 text-green-600"></i>
                        <span class="font-medium text-green-900">Active Products</span>
                    </div>
                    <span class="text-2xl font-bold text-green-600"><?php echo $active_products; ?></span>
                </div>
                <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <i data-lucide="x-circle" class="w-5 h-5 text-red-600"></i>
                        <span class="font-medium text-red-900">Inactive Products</span>
                    </div>
                    <span class="text-2xl font-bold text-red-600"><?php echo $inactive_products; ?></span>
                </div>
                <?php if ($inactive_products > 0): ?>
                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex items-center gap-2">
                            <i data-lucide="alert-triangle" class="w-4 h-4 text-yellow-600"></i>
                            <span class="text-sm font-medium text-yellow-800">
                                You have <?php echo $inactive_products; ?> inactive
                                product<?php echo $inactive_products > 1 ? 's' : ''; ?>
                            </span>
                        </div>
                        <p class="text-xs text-yellow-700 mt-1">Inactive products won't appear in sales</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Low Stock Alert</h2>
            <?php if (empty($low_stock_products)): ?>
                <p class="text-gray-500 text-center py-8">All active products are well stocked</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($low_stock_products as $product): ?>
                        <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-b-0">
                            <div>
                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($product['sku']); ?></p>
                            </div>
                            <div class="text-right">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $product['stock'] == 0 ? 'bg-red-100 text-red-800' : ($product['stock'] <= 5 ? 'bg-yellow-100 text-yellow-800' : 'bg-orange-100 text-orange-800'); ?>">
                                    <?php echo $product['stock']; ?> left
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>

</html>