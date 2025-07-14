<?php
require_once 'config.php';

// Get search parameters from POST or default to current month
$search_date = isset($_POST['search_date']) && !empty($_POST['search_date']) ? $_POST['search_date'] : null;
$search_month = isset($_POST['search_month']) && !empty($_POST['search_month']) ? $_POST['search_month'] : date('Y-m');

// Prepare SQL query based on search input
$where_clause = '';
$params = [];
if ($search_date) {
    $where_clause = "WHERE DATE(s.created_at) = ? AND s.status = 'paid'";
    $params[] = $search_date;
} else {
    $month_year = explode('-', $search_month);
    $year = $month_year[0];
    $month = $month_year[1];
    $where_clause = "WHERE YEAR(s.created_at) = ? AND MONTH(s.created_at) = ? AND s.status = 'paid'";
    $params[] = $year;
    $params[] = $month;
}

// Fetch sales with user and customer details
$stmt = $pdo->prepare("
    SELECT DATE(s.created_at) as sale_date, s.id, s.invoice_id, s.total, s.created_at, 
           s.payment_method, c.name as customer_name, u.username as user_name
    FROM sales s
    JOIN customers c ON s.customer_id = c.id
    JOIN users u ON s.user_id = u.id
    $where_clause
    ORDER BY s.created_at DESC
");
$stmt->execute($params);
$sales = $stmt->fetchAll();

// Group sales by date
$sales_by_date = [];
foreach ($sales as $sale) {
    $date = date('F j, Y', strtotime($sale['sale_date']));
    $sales_by_date[$date][] = $sale;
}

// Fetch sale items
$sale_items = [];
foreach ($sales as $sale) {
    $stmt = $pdo->prepare("
        SELECT si.quantity, p.name as product_name, p.sku
        FROM sale_items si
        JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = ?
    ");
    $stmt->execute([$sale['id']]);
    $sale_items[$sale['id']] = $stmt->fetchAll();
}

// Calculate total revenue
$total_revenue = array_sum(array_column($sales, 'total')) ?: 0;

// Set display title
$display_title = $search_date ? date('F j, Y', strtotime($search_date)) : date('F Y', strtotime($search_month . '-01'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Revenue Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media (max-width: 640px) {
            .responsive-text-2xl { font-size: 1.5rem; }
            .responsive-text-lg { font-size: 1rem; }
            .responsive-text-sm { font-size: 0.75rem; }
            .responsive-text-xs { font-size: 0.65rem; }
            .responsive-px-4 { padding-left: 1rem; padding-right: 1rem; }
            .responsive-py-3 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        }
        @media print {
            .no-print { display: none; }
            .print-container { padding: 0; margin: 0; }
            table { width: 100%; font-size: 12px; }
            th, td { padding: 8px; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="print-container max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-4 sm:mb-6">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 responsive-text-2xl">Revenue Report</h1>
                    <p class="text-sm text-gray-500 responsive-text-sm"><?php echo htmlspecialchars($display_title); ?></p>
                    <p class="text-lg font-semibold text-gray-800 mt-1 responsive-text-lg">Total: Rs. <?php echo number_format($total_revenue, 2); ?></p>
                </div>
                <div class="no-print flex flex-col sm:flex-row sm:space-x-3 gap-2">
                    <a href="index.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center text-sm sm:text-base">
                        <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Dashboard
                    </a>
                    <button onclick="window.print()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center text-sm sm:text-base">
                        <i data-lucide="printer" class="w-4 h-4 mr-2"></i> Print
                    </button>
                </div>
            </div>
            <form method="POST" class="no-print mt-4 sm:mt-6 flex flex-col sm:flex-row gap-4">
                <div>
                    <label for="search_date" class="block text-sm font-medium text-gray-700 responsive-text-sm">Search by Date</label>
                    <input type="date" id="search_date" name="search_date" value="<?php echo htmlspecialchars($search_date ?? ''); ?>" class="mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm sm:text-base">
                </div>
                <div>
                    <label for="search_month" class="block text-sm font-medium text-gray-700 responsive-text-sm">Search by Month</label>
                    <input type="month" id="search_month" name="search_month" value="<?php echo htmlspecialchars($search_month ?? date('Y-m')); ?>" class="mt-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm sm:text-base">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 flex items-center text-sm sm:text-base">
                        <i data-lucide="search" class="w-4 h-4 mr-2"></i> Search
                    </button>
                </div>
            </form>
        </div>
        <?php if (empty($sales)): ?>
            <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 text-center">
                <p class="text-gray-500 text-lg responsive-text-lg">No sales recorded for this period</p>
            </div>
        <?php else: ?>
            <?php foreach ($sales_by_date as $date => $sales): ?>
                <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 responsive-text-lg"><?php echo $date; ?> (<?php echo count($sales); ?> sales)</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider responsive-px-4 responsive-py-3">Time</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider responsive-px-4 responsive-py-3">Customer</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider responsive-px-4 responsive-py-3">User</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider responsive-px-4 responsive-py-3">Products Sold</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider responsive-px-4 responsive-py-3">Total</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider responsive-px-4 responsive-py-3">Payment</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($sales as $sale): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-900 responsive-px-4 responsive-py-3 responsive-text-sm"><?php echo date('h:i A', strtotime($sale['created_at'])); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-900 responsive-px-4 responsive-py-3 responsive-text-sm">
                                            <?php echo htmlspecialchars($sale['customer_name']); ?><br>
                                            <span class="text-xs text-gray-500 responsive-text-xs">Inv: <?php echo htmlspecialchars($sale['invoice_id']); ?></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 responsive-px-4 responsive-py-3 responsive-text-sm"><?php echo htmlspecialchars($sale['user_name']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-600 responsive-px-4 responsive-py-3 responsive-text-sm">
                                            <ul class="list-disc list-inside">
                                                <?php foreach ($sale_items[$sale['id']] as $item): ?>
                                                    <li class="responsive-text-xs"><?php echo htmlspecialchars($item['product_name']); ?> (SKU: <?php echo htmlspecialchars($item['sku']); ?>) - Qty: <?php echo $item['quantity']; ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </td>
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-900 responsive-px-4 responsive-py-3 responsive-text-sm">Rs. <?php echo number_format($sale['total'], 2); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-600 responsive-px-4 responsive-py-3 responsive-text-sm"><?php echo ucfirst($sale['payment_method']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>