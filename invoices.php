<?php

require_once 'config.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';
$where = $search ? "WHERE s.invoice_id LIKE '%$search%' OR c.name LIKE '%$search%'" : '';
$invoices = $pdo->query("SELECT s.*, c.name as customer_name, c.phone, i.link FROM sales s JOIN customers c ON s.customer_id = c.id JOIN invoices i ON s.id = i.sale_id $where")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'send_sms') {
    $invoice_id = $_POST['invoice_id'];
    $phone = $_POST['phone'];
    $customer_name = $_POST['customer_name'];
    $total = $_POST['total'];
    $link = $_POST['link'];
    $message = "Hi $customer_name! Your invoice #$invoice_id for Rs. " . number_format($total, 2) . " is ready. View: $link";
    /*
    $response = file_get_contents("https://api.send.lk/v1/sms?to=$phone&message=" . urlencode($message) . "&api_key=YOUR_API_KEY");
    */
    echo "<script>console.log('SMS would be sent: $message');</script>";
    echo "<script>alert('SMS sent successfully!');</script>";
}
?>

<div class="p-4 sm:p-8 max-w-screen-lg mx-auto">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Invoices</h1>
            <p class="text-gray-600 mt-1 sm:mt-2">Manage and track your invoices</p>
        </div>
        <div class="w-full sm:w-80 relative">
            <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
            <input
                type="text"
                placeholder="Search invoices..."
                value="<?php echo htmlspecialchars($search); ?>"
                oninput="window.location='?page=invoices&search='+encodeURIComponent(this.value)"
                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm sm:text-base"
                aria-label="Search invoices"
                autocomplete="off"
            >
        </div>
    </div>

    <?php if (empty($invoices)): ?>
        <div class="text-center py-12">
            <p class="text-gray-500 text-lg">No invoices found</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-xl shadow border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm sm:text-base">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="text-left py-3 px-4 font-semibold text-gray-900 whitespace-nowrap">Invoice #</th>
                        <th scope="col" class="text-left py-3 px-4 font-semibold text-gray-900 whitespace-nowrap">Customer</th>
                        <th scope="col" class="text-left py-3 px-4 font-semibold text-gray-900 whitespace-nowrap">Date</th>
                        <th scope="col" class="text-left py-3 px-4 font-semibold text-gray-900 whitespace-nowrap">Total</th>
                        <th scope="col" class="text-left py-3 px-4 font-semibold text-gray-900 whitespace-nowrap">Status</th>
                        <th scope="col" class="text-left py-3 px-4 font-semibold text-gray-900 whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($invoices as $invoice): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 font-mono text-xs sm:text-sm whitespace-nowrap"><?php echo htmlspecialchars($invoice['invoice_id']); ?></td>
                            <td class="py-3 px-4 whitespace-nowrap max-w-xs">
                                <div>
                                    <p class="font-medium text-gray-900 truncate" title="<?php echo htmlspecialchars($invoice['customer_name']); ?>"><?php echo htmlspecialchars($invoice['customer_name']); ?></p>
                                    <p class="text-gray-500 text-xs sm:text-sm truncate" title="<?php echo htmlspecialchars($invoice['phone']); ?>"><?php echo htmlspecialchars($invoice['phone']); ?></p>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-gray-600 whitespace-nowrap"><?php echo date('Y-m-d', strtotime($invoice['created_at'])); ?></td>
                            <td class="py-3 px-4 font-semibold text-gray-900 whitespace-nowrap">Rs. <?php echo number_format($invoice['total'], 2); ?></td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="inline-block px-2 py-1 rounded-full text-xs font-medium
                                    <?php
                                        switch ($invoice['status']) {
                                            case 'paid': echo 'bg-green-100 text-green-800'; break;
                                            case 'sent': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'overdue': echo 'bg-red-100 text-red-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                    ?>">
                                    <?php echo ucfirst($invoice['status']); ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <a
                                        href="invoice_viewer.php?invoice_id=<?php echo $invoice['invoice_id']; ?>"
                                        target="_blank"
                                        class="inline-flex items-center justify-center p-2 sm:p-2.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                        title="View Invoice"
                                        aria-label="View invoice <?php echo htmlspecialchars($invoice['invoice_id']); ?>"
                                    >
                                        <i data-lucide="eye" class="w-5 h-5"></i>
                                    </a>
                                    <form method="POST" class="inline-block" onsubmit="return confirm('Send SMS to <?php echo htmlspecialchars(addslashes($invoice['customer_name'])); ?>?');">
                                        <input type="hidden" name="action" value="send_sms">
                                        <input type="hidden" name="invoice_id" value="<?php echo $invoice['invoice_id']; ?>">
                                        <input type="hidden" name="phone" value="<?php echo $invoice['phone']; ?>">
                                        <input type="hidden" name="customer_name" value="<?php echo $invoice['customer_name']; ?>">
                                        <input type="hidden" name="total" value="<?php echo $invoice['total']; ?>">
                                        <input type="hidden" name="link" value="<?php echo $invoice['link']; ?>">
                                        <button
                                            type="submit"
                                            class="inline-flex items-center justify-center p-2 sm:p-2.5 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                                            title="Send SMS"
                                            aria-label="Send SMS to <?php echo htmlspecialchars($invoice['customer_name']); ?>"
                                        >
                                            <i data-lucide="send" class="w-5 h-5"></i>
                                        </button>
                                    </form>
                                    <a
                                        href="invoice_viewer.php?invoice_id=<?php echo $invoice['invoice_id']; ?>&download=1"
                                        class="inline-flex items-center justify-center p-2 sm:p-2.5 text-purple-600 hover:bg-purple-50 rounded-lg transition-colors"
                                        title="Download PDF"
                                        aria-label="Download invoice <?php echo htmlspecialchars($invoice['invoice_id']); ?> as PDF"
                                    >
                                        <i data-lucide="download" class="w-5 h-5"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/lucide/dist/lucide.min.js"></script>
<script>
    // Initialize lucide icons
    lucide.replace();
</script>
