<?php
require_once 'config.php';

// Get selected date from GET parameter or default to today
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Validate date format (basic validation to prevent SQL injection)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date)) {
    $selected_date = date('Y-m-d');
}

// Fetch daily sales with user and customer details for the selected date
$stmt = $pdo->prepare("
    SELECT s.id, s.invoice_id, s.total, s.created_at, s.payment_method, 
           c.name as customer_name, u.username as user_name
    FROM sales s
    JOIN customers c ON s.customer_id = c.id
    JOIN users u ON s.user_id = u.id
    WHERE DATE(s.created_at) = ? AND s.status = 'paid'
    ORDER BY s.created_at DESC
");
$stmt->execute([$selected_date]);
$daily_sales = $stmt->fetchAll();

// Fetch sale items
$daily_sale_items = [];
foreach ($daily_sales as $sale) {
    $stmt = $pdo->prepare("
        SELECT si.quantity, p.name as product_name, p.sku
        FROM sale_items si
        JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = ?
    ");
    $stmt->execute([$sale['id']]);
    $daily_sale_items[$sale['id']] = $stmt->fetchAll();
}

// Calculate total daily revenue
$daily_revenue = array_sum(array_column($daily_sales, 'total')) ?: 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Revenue Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media (max-width: 640px) {
            .responsive-text-2xl { font-size: 1.5rem; }
            .responsive-text-lg { font-size: 1rem; }
            .responsive-text-sm { font-size: 0.75rem; }
            .responsive-text-xs { font-size: 0.65rem; }
            .responsive-px-4 { padding-left: 0.5rem; padding-right: 0.5rem; }
            .responsive-py-3 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        }
        @media print {
            .no-print { display: none; }
            .print-container { padding: 0; margin: 0; }
            table { width: 100%; font-size: 12px; }
            th, td { padding: 6px; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="print-container max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-4 sm:mb-6">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 responsive-text-2xl">Daily Revenue Report</h1>
                    <p class="text-sm text-gray-500 responsive-text-sm"><?php echo date('F j, Y', strtotime($selected_date)); ?></p>
                    <p class="text-lg font-semibold text-gray-800 mt-1 responsive-text-lg">Total: Rs. <?php echo number_format($daily_revenue, 2); ?></p>
                </div>
                <div class="no-print flex flex-col sm:flex-row sm:space-x-3 gap-2">
                    <form method="GET" class="flex items-center gap-2">
                        <input type="date" name="date" value="<?php echo htmlspecialchars($selected_date); ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center text-sm">
                            <i data-lucide="search" class="w-4 h-4 mr-2"></i> Search
                        </button>
                    </form>
                    <a href="index.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center text-sm">
                        <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Dashboard
                    </a>
                    <button onclick="window.print()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center text-sm">
                        <i data-lucide="printer" class="w-4 h-4 mr-2"></i> Print
                    </button>
                </div>
            </div>
        </div>
        <?php if (empty($daily_sales)): ?>
            <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 text-center">
                <p class="text-gray-500 text-base sm:text-lg responsive-text-lg">No sales recorded for <?php echo date('F j, Y', strtotime($selected_date)); ?></p>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider responsive-px-4 responsive-py-3">Time</th>
                            <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider responsive-px-4 responsive-py-3">Customer</th>
                            <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider responsive-px-4 responsive-py-3">User</th>
                            <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider responsive-px-4 responsive-py-3">Products Sold</th>
                            <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider responsive-px-4 responsive-py-3">Total</th>
                            <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider responsive-px-4 responsive-py-3">Payment</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($daily_sales as $sale): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-2 sm:px-4 py-2 sm:py-3 text-sm text-gray-900 responsive-px-4 responsive-py-3"><?php echo date('h:i A', strtotime($sale['created_at'])); ?></td>
                                <td class="px-2 sm:px-4 py-2 sm:py-3 text-sm text-gray-900 responsive-px-4 responsive-py-3"><?php echo htmlspecialchars($sale['customer_name']); ?><br><span class="text-xs text-gray-500 responsive-text-xs">Inv: <?php echo htmlspecialchars($sale['invoice_id']); ?></span></td>
                                <td class="px-2 sm:px-4 py-2 sm:py-3 text-sm text-gray-900 responsive-px-4 responsive-py-3"><?php echo htmlspecialchars($sale['user_name']); ?></td>
                                <td class="px-2 sm:px-4 py-2 sm:py-3 text-sm text-gray-600 responsive-px-4 responsive-py-3">
                                    <ul class="list-disc list-inside">
                                        <?php foreach ($daily_sale_items[$sale['id']] as $item): ?>
                                            <li class="text-xs sm:text-sm responsive-text-xs"><?php echo htmlspecialchars($item['product_name']); ?> (SKU: <?php echo htmlspecialchars($item['sku']); ?>) - Qty: <?php echo $item['quantity']; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td class="px-2 sm:px-4 py-2 sm:py-3 text-sm font-semibold text-gray-900 responsive-px-4 responsive-py-3">Rs. <?php echo number_format($sale['total'], 2); ?></td>
                                <td class="px-2 sm:px-4 py-2 sm:py-3 text-sm text-gray-600 responsive-px-4 responsive-py-3"><?php echo ucfirst($sale['payment_method']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>