<?php
require_once 'config.php';
require_once 'mailer/send_mail.php';
$user_id = $_SESSION['user_id'];

// Get Cash total
$stmt = $pdo->prepare("SELECT SUM(total) FROM sales WHERE user_id = ? AND payment_method = ? AND NOT EXISTS (SELECT 1 FROM day_end_sales WHERE day_end_sales.sale_id = sales.id)");
$stmt->execute([$user_id, 'cash']);
$cash_total = $stmt->fetchColumn();

// Get Card total
$stmt = $pdo->prepare("SELECT SUM(total) FROM sales WHERE user_id = ? AND payment_method = ? AND NOT EXISTS (SELECT 1 FROM day_end_sales WHERE day_end_sales.sale_id = sales.id)");
$stmt->execute([$user_id, 'card']);
$card_total = $stmt->fetchColumn();

// Get Bank Transfer total
$stmt = $pdo->prepare("SELECT SUM(total) FROM sales WHERE user_id = ? AND payment_method = ? AND NOT EXISTS (SELECT 1 FROM day_end_sales WHERE day_end_sales.sale_id = sales.id)");
$stmt->execute([$user_id, 'bank_transfer']);
$bank_transfer_total = $stmt->fetchColumn();

// Calculate Subtotal
$subtotal = $cash_total + $card_total + $bank_transfer_total;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $subtotal > 0) {
    // Handle day end submission
    try {
        // Insert day end record
        $stmt = $pdo->prepare("INSERT INTO day_end (user_id, cash_amt, card_amt, bank_transfer_amt, total_amt, `date`) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $cash_total, $card_total, $bank_transfer_total, $subtotal, date('Y-m-d H:i:s')]);
        $day_end_id = $pdo->lastInsertId();

        // Get all sales for the user that are not already processed
        $stmt = $pdo->prepare("SELECT id FROM sales WHERE user_id = ? AND NOT EXISTS (SELECT 1 FROM day_end_sales WHERE day_end_sales.sale_id = sales.id)");
        $stmt->execute([$user_id]);
        $sales = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Insert to day_end_sales
        if ($sales) {
            $stmt = $pdo->prepare("INSERT INTO day_end_sales (sale_id, day_end_id) VALUES (?, ?)");
            foreach ($sales as $sale_id) {
                $stmt->execute([$sale_id, $day_end_id]);
            }
        }

        // get logged user details
        $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $to = $user['email'];
        $user_name = $user['username'];

        // Send email notification
        $mailer = new AppMailer();
        $subject = "Daily Sales Summary for user $user_name - " . date('Y-m-d');
        $html = '
        <div style="font-family: Arial, sans-serif; background: #f8fafc; padding: 32px;">
            <div style="max-width: 480px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(80, 80, 120, 0.08); padding: 32px 24px;">
            <h2 style="color: #7c3aed; font-size: 24px; margin-bottom: 12px; font-weight: bold; letter-spacing: -1px;">
                Daily Sales Summary
            </h2>
            <p style="color: #22223b; font-size: 16px; margin-bottom: 24px;">
                Hello <strong>Infive Admin</strong>,<br>
                User ' . htmlspecialchars($user_name) . ' - sales summary has been recorded successfully.
            </p>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 24px;">
                <tr>
                <td style="padding: 10px 0; color: #6d28d9; font-weight: 500;">Cash Total</td>
                <td style="padding: 10px 0; text-align: right; color: #22223b; font-weight: bold;">
                    Rs. ' . number_format($cash_total, 2) . '
                </td>
                </tr>
                <tr>
                <td style="padding: 10px 0; color: #6d28d9; font-weight: 500;">Card Total</td>
                <td style="padding: 10px 0; text-align: right; color: #22223b; font-weight: bold;">
                    Rs. ' . number_format($card_total, 2) . '
                </td>
                </tr>
                <tr>
                <td style="padding: 10px 0; color: #6d28d9; font-weight: 500;">Bank Transfer Total</td>
                <td style="padding: 10px 0; text-align: right; color: #22223b; font-weight: bold;">
                    Rs. ' . number_format($bank_transfer_total, 2) . '
                </td>
                </tr>
                <tr>
                <td style="padding: 14px 0; color: #374151; font-size: 17px; font-weight: bold; border-top: 1px solid #e5e7eb;">
                    Subtotal
                </td>
                <td style="padding: 14px 0; text-align: right; color: #7c3aed; font-size: 18px; font-weight: bold; border-top: 1px solid #e5e7eb;">
                    Rs. ' . number_format($subtotal, 2) . '
                </td>
                </tr>
            </table>
            <div style="color: #6b7280; font-size: 13px; margin-top: 12px;">
                <em>Date: ' . date('F j, Y, g:i a') . '</em>
            </div>
            </div>
        </div>
        ';
        $status = $mailer->sendMail($subject, $html);

        // if (!$status) {
        //     header("Location: index.php?page=day-end&error=Failed to send email notification");
        //     exit;
        // }

        header("Location: index.php?page=day-end&success=Day end successfully recorded");
        exit;
    } catch (PDOException $e) {
        header("Location: index.php?page=day-end&error=Failed to record day end: ");
        exit;
    }
}

?>

<div class="p-4 sm:p-6 md:p-8">
    <div class="mb-8 flex flex-col sm:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Day End</h1>
            <p class="text-gray-600 mt-2 text-sm sm:text-base">Review and finalize daily sales summary</p>
        </div>
        <a href="index.php?page=sales" class="bg-gray-100 text-gray-700 px-4 sm:px-6 py-2 sm:py-3 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-2 min-h-10 text-sm sm:text-base">
            <i data-lucide="arrow-left" class="w-5 h-5"></i> Back to Sales
        </a>
    </div>

    <!-- Add message -->
    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-4">
            <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-4"></div>
        <?php echo htmlspecialchars($_GET['error']); ?>
</div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
    <div class="flex items-center gap-2 mb-4">
        <i data-lucide="calendar" class="w-5 h-5 text-purple-600"></i>
        <h2 class="text-lg sm:text-xl font-semibold text-gray-900">Daily Sales Summary - July 12, 2025</h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <div class="bg-gray-50 rounded-lg p-4">
            <h3 class="text-sm sm:text-base font-medium text-gray-700">Cash Total</h3>
            <p class="text-lg sm:text-2xl font-bold text-gray-900">Rs. <?php echo number_format($cash_total, 2); ?></p>
        </div>
        <div class="bg-gray-50 rounded-lg p-4">
            <h3 class="text-sm sm:text-base font-medium text-gray-700">Card Total</h3>
            <p class="text-lg sm:text-2xl font-bold text-gray-900">Rs. <?php echo number_format($card_total, 2); ?></p>
        </div>
        <div class="bg-gray-50 rounded-lg p-4">
            <h3 class="text-sm sm:text-base font-medium text-gray-700">Bank Transfer Total</h3>
            <p class="text-lg sm:text-2xl font-bold text-gray-900">Rs. <?php echo number_format($bank_transfer_total, 2); ?></p>
        </div>
        <div class="bg-gray-50 rounded-lg p-4">
            <h3 class="text-sm sm:text-base font-medium text-gray-700">Subtotal</h3>
            <p class="text-lg sm:text-2xl font-bold text-gray-900">Rs. <?php echo number_format($subtotal, 2); ?></p>
        </div>
    </div>

    <div class="border-t border-gray-200 pt-4">
        <form method="POST">
            <input type="hidden" name="action" value="day_end">
            <button
                type="submit"
                class="w-full bg-purple-600 text-white py-2 sm:py-3 rounded-lg hover:bg-purple-700 transition-colors font-medium flex items-center justify-center gap-2 min-h-10 text-sm sm:text-base"
                <?php if ($subtotal == 0): ?>disabled style="opacity:0.5;cursor:not-allowed" <?php endif; ?>>
                <i data-lucide="calendar" class="w-4 h-4"></i> Submit Day End
            </button>
        </form>
    </div>
</div>
</div>