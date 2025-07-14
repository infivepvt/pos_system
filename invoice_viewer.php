<?php
// Start the session
session_start();
require_once 'config.php';

if (!isset($_GET['invoice_id'])) {
    die("Invoice ID not provided.");
}

$invoice_id = filter_var($_GET['invoice_id'], FILTER_SANITIZE_STRING);
$stmt = $pdo->prepare("SELECT s.*, c.name as customer_name, c.phone, c.email, c.address FROM sales s JOIN customers c ON s.customer_id = c.id WHERE s.invoice_id = ?");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    die("Invoice not found.");
}

$stmt = $pdo->prepare("SELECT si.*, p.name, p.description FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?");
$stmt->execute([$invoice['id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['download']) && $_GET['download'] == 1) {
    echo "<script>alert('Invoice download started!');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo htmlspecialchars($invoice['invoice_id']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
        });
        function handleShare() {
            if (navigator.share) {
                navigator.share({
                    title: 'Invoice <?php echo htmlspecialchars($invoice['invoice_id']); ?>',
                    url: window.location.href
                });
            } else {
                navigator.clipboard.writeText(window.location.href);
                alert('Invoice link copied to clipboard!');
            }
        }
        function goBack() {
            window.location.href = 'https://infiveprint.com/simplepos/index.php?page=sales';
        }
    </script>
</head>
<body class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="flex justify-between items-center mb-6 print:hidden">
            <h1 class="text-2xl font-bold text-gray-900">Invoice Details</h1>
            <div class="flex gap-3">
                <?php if (isset($_SESSION['user_id'])): // Check if user is logged in ?>
                    <button onclick="goBack()" class="flex items-center gap-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
                    </button>
                <?php endif; ?>
                <button onclick="handleShare()" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i data-lucide="share-2" class="w-4 h-4"></i> Share
                </button>
                <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i data-lucide="download" class="w-4 h-4"></i> Download
                </button>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-8">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">INVOICE</h1>
                        <p class="text-blue-100">Professional Point of Sale System</p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold"><?php echo htmlspecialchars($invoice['invoice_id']); ?></p>
                        <p class="text-blue-200 mt-1">Invoice Number</p>
                    </div>
                </div>
            </div>
            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">From:</h3>
                        <div class="text-gray-600">
                            <p class="font-medium text-gray-900">Your Business Name</p>
                            <p>123 Business Street</p>
                            <p>City, State 12345</p>
                            <p>Phone: +94 11 234 5678</p>
                            <p>Email: info@yourbusiness.com</p>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Bill To:</h3>
                        <div class="text-gray-600">
                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($invoice['customer_name']); ?></p>
                            <?php if ($invoice['address']): ?>
                                <p><?php echo htmlspecialchars($invoice['address']); ?></p>
                            <?php endif; ?>
                            <div class="flex items-center gap-2 mt-1">
                                <i data-lucide="phone" class="w-4 h-4"></i>
                                <span><?php echo htmlspecialchars($invoice['phone']); ?></span>
                            </div>
                            <?php if ($invoice['email']): ?>
                                <div class="flex items-center gap-2">
                                    <i data-lucide="mail" class="w-4 h-4"></i>
                                    <span><?php echo htmlspecialchars($invoice['email']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div>
                        <h4 class="font-medium text-gray-900 mb-1">Invoice Date</h4>
                        <div class="flex items-center gap-2 text-gray-600">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                            <span><?php echo date('Y-m-d', strtotime($invoice['created_at'])); ?></span>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900 mb-1">Due Date</h4>
                        <div class="flex items-center gap-2 text-gray-600">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                            <span><?php echo date('Y-m-d', strtotime($invoice['due_date'])); ?></span>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900 mb-1">Status</h4>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php
                            switch ($invoice['status']) {
                                case 'paid': echo 'bg-green-100 text-green-800'; break;
                                case 'sent': echo 'bg-blue-100 text-blue-800'; break;
                                case 'overdue': echo 'bg-red-100 text-red-800'; break;
                                default: echo 'bg-gray-100 text-gray-800';
                            }
                        ?>">
                            <?php echo ucfirst($invoice['status']); ?>
                        </span>
                    </div>
                </div>
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Items</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="border-b-2 border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-900">Item</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-900">Qty</th>
                                    <th class="text-right py-3 px-4 font-semibold text-gray-900">Unit Price</th>
                                    <th class="text-right py-3 px-4 font-semibold text-gray-900">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr class="border-b border-gray-100">
                                        <td class="py-4 px-4">
                                            <div>
                                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></p>
                                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($item['description']); ?></p>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4 text-center"><?php echo $item['quantity']; ?></td>
                                        <td class="py-4 px-4 text-right">Rs. <?php echo number_format($item['price'], 2); ?></td>
                                        <td class="py-4 px-4 text-right font-medium">Rs. <?php echo number_format($item['subtotal'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="flex justify-end">
                    <div class="w-full max-w-sm">
                        <div class="space-y-2">
                            <div class="flex justify-between py-2">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-medium">Rs. <?php echo number_format($invoice['subtotal'], 2); ?></span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span class="text-gray-600">Tax (<?php echo number_format($invoice['tax_rate'], 2); ?>%):</span>
                                <span class="font-medium">Rs. <?php echo number_format($invoice['tax'], 2); ?></span>
                            </div>
                            <?php if ($invoice['discount'] > 0): ?>
                                <div class="flex justify-between py-2">
                                    <span class="text-gray-600">Discount:</span>
                                    <span class="font-medium text-green-600">-Rs. <?php echo number_format($invoice['discount'], 2); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="border-t-2 border-gray-200 pt-2">
                                <div class="flex justify-between py-2">
                                    <span class="text-lg font-semibold text-gray-900">Total:</span>
                                    <span class="text-2xl font-bold text-gray-900">Rs. <?php echo number_format($invoice['total'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if ($invoice['notes']): ?>
                    <div class="mt-8 pt-8 border-t border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Notes</h3>
                        <p class="text-gray-600"><?php echo htmlspecialchars($invoice['notes']); ?></p>
                    </div>
                <?php endif; ?>
                <div class="mt-8 pt-8 border-t border-gray-200 text-center text-gray-500">
                    <p>Thank you for your business!</p>
                    <p class="mt-2">For questions about this invoice, please contact us at +94 11 234 5678</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>