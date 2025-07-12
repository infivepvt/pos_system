<?php
require_once 'config.php';
$user_id = $_SESSION['user_id'];

// Initialize cart in session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Initialize message variable for SMS status
$sms_message = '';

$stmt = $pdo->prepare("SELECT * FROM products WHERE status = ?");
$stmt->execute(['active']);
$active_products = $stmt->fetchAll();

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_to_cart') {
        $product_id = (int)$_POST['product_id'];
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = ?");
        $stmt->execute([$product_id, 'active']);
        $product = $stmt->fetch();
        if ($product) {
            if ($product['category'] == 'product') {
                $current_qty = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id]['quantity'] : 0;
                if ($current_qty + 1 > $product['stock']) {
                    $sms_message = '<div class="bg-red-100 text-red-700 p-4 rounded-lg mb-4">Insufficient stock available!</div>';
                } else {
                    $_SESSION['cart'][$product_id] = [
                        'product_id' => $product_id,
                        'name' => $product['name'],
                        'price' => $product['price'],
                        'quantity' => $current_qty + 1,
                        'category' => $product['category'],
                        'sku' => $product['sku']
                    ];
                }
            } else {
                $_SESSION['cart'][$product_id] = [
                    'product_id' => $product_id,
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'quantity' => isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id]['quantity'] + 1 : 1,
                    'category' => $product['category'],
                    'sku' => $product['sku']
                ];
            }
        }
    } elseif ($_POST['action'] == 'update_quantity') {
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
        } elseif ($product['category'] == 'product' && $quantity > $product['stock']) {
            $sms_message = '<div class="bg-red-100 text-red-700 p-4 rounded-lg mb-4">Quantity exceeds available stock!</div>';
        } else {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
        }
    } elseif ($_POST['action'] == 'clear_cart') {
        $_SESSION['cart'] = [];
    } elseif ($_POST['action'] == 'checkout') {
        $customer = [
            'name' => $_POST['name'],
            'phone' => $_POST['phone'],
            'email' => $_POST['email'],
            'address' => $_POST['address']
        ];
        $tax_rate = (float)$_POST['tax_rate'];
        $discount = (float)$_POST['discount'];
        $payment_method = $_POST['payment_method'];
        $notes = $_POST['notes'];
        $subtotal = 0;

        foreach ($_SESSION['cart'] as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        $tax = $subtotal * ($tax_rate / 100);
        $total = $subtotal + $tax - $discount;

        if ($total < 0) {
            $sms_message = '<div class="bg-red-100 text-red-700 p-4 rounded-lg mb-4">Total cannot be negative. Please adjust discount.</div>';
        } else {
            $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$customer['name'], $customer['phone'], $customer['email'], $customer['address']]);
            $customer_id = $pdo->lastInsertId();

            $invoice_id = generateInvoiceNumber();
            $due_date = date('Y-m-d H:i:s', strtotime('+30 days'));
            $stmt = $pdo->prepare("INSERT INTO sales (user_id, customer_id, subtotal, tax_rate, tax, discount, total, invoice_id, payment_method, status, notes, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)");
            $stmt->execute([$user_id, $customer_id, $subtotal, $tax_rate, $tax, $discount, $total, $invoice_id, $payment_method, 'paid', $notes, $due_date]);
            $sale_id = $pdo->lastInsertId();

            foreach ($_SESSION['cart'] as $item) {
                $item_subtotal = $item['price'] * $item['quantity'];
                $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$sale_id, $item['product_id'], $item['quantity'], $item['price'], $item_subtotal]);

                if ($item['category'] == 'product') {
                    $stmt = $pdo->prepare("UPDATE products SET stock = stock - ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                }
            }

            $invoice_link = "http://localhost/pos_system/invoice_viewer.php?invoice_id=$invoice_id";
            $stmt = $pdo->prepare("INSERT INTO invoices (sale_id, invoice_id, link) VALUES (?, ?, ?)");
            $stmt->execute([$sale_id, $invoice_id, $invoice_link]);

            $MSISDN = preg_replace('/^0/', '+94', $customer['phone']);
            $SRC = 'InfivePrint';
            $MESSAGE = "Hi {$customer['name']}! Your invoice #{$invoice_id} for Rs. " . number_format($total, 2) . " is ready. View: $invoice_link";
            $AUTH = "2001|d904j2TA6FS18E1XsQIyo8vTyqgfegcvfUsFimjZ"; // Replace with valid token

            $msgdata = array("recipient" => $MSISDN, "sender_id" => $SRC, "message" => $MESSAGE);
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://sms.send.lk/api/v3/sms/send",
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($msgdata),
                CURLOPT_HTTPHEADER => array(
                    "accept: application/json",
                    "authorization: Bearer $AUTH",
                    "cache-control: no-cache",
                    "content-type: application/json",
                ),
                CURLOPT_RETURNTRANSFER => true,
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                $sms_message = '<div class="bg-red-100 text-red-700 p-4 rounded-lg mb-4">Sale completed, but SMS sending failed: Network error. Please try again.</div>';
                error_log("SMS cURL Error: $err");
            } else {
                $response_data = json_decode($response, true);
                if ($response_data && isset($response_data['status']) && $response_data['status'] === 'success') {
                    $sms_message = '
                        <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-4">
                            Sale completed successfully! SMS sent to customer.
                        </div>
                        <a href="invoice_viewer.php?invoice_id=' . $invoice_id . '" target="_blank" class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                            View Invoice
                        </a>
                    ';
                } else {
                    $error_msg = isset($response_data['message']) ? $response_data['message'] : 'Unknown error';
                    $sms_message = '<div class="bg-red-100 text-red-700 p-4 rounded-lg mb-4">Sale completed, but SMS sending failed: ' . htmlspecialchars($error_msg) . '</div>';
                    error_log("SMS API Error: $response");
                }
            }

            $_SESSION['cart'] = [];
        }
    }
}

$cart_total = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_total += $item['price'] * $item['quantity'];
}
?>

<div class="p-4 sm:p-6 md:p-8">
    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Sales</h1>
        <p class="text-gray-600 mt-2 text-sm sm:text-base">Process sales transactions</p>
    </div>

    <?php if (!empty($sms_message)): ?>
        <div class="mb-4">
            <?php echo $sms_message; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Products -->
        <div class="lg:col-span-2">
            <div class="flex flex-col sm:flex-row items-center justify-between mb-4 gap-4">
                <h2 class="text-lg sm:text-xl font-semibold text-gray-900">Active Products & Services</h2>
                <div class="relative w-full md:w-1/2">
                    <input type="text" id="searchInput" placeholder="Search by name, description, or SKU..." class="w-full pl-10 pr-10 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base" oninput="filterProducts()">
                    <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-500"></i>
                    <button type="button" onclick="document.getElementById('searchInput').value='';filterProducts()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700" aria-label="Clear search">✕</button>
                </div>
            </div>
            <?php if (empty($active_products)): ?>
                <div class="text-center py-12 bg-white rounded-lg border border-gray-200">
                    <p class="text-gray-500 text-base sm:text-lg">No active products available</p>
                    <p class="text-gray-400 text-sm mt-2">Add products or activate existing ones to start selling</p>
                </div>
            <?php else: ?>
                <div id="productGrid" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <?php foreach ($active_products as $product): ?>
                        <div class="product-card bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow"
                            data-name="<?php echo htmlspecialchars(strtolower($product['name'])); ?>"
                            data-description="<?php echo htmlspecialchars(strtolower($product['description'])); ?>"
                            data-sku="<?php echo htmlspecialchars(strtolower($product['sku'] ?? '')); ?>">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-medium text-gray-900 text-sm sm:text-base"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $product['category'] == 'product' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800'; ?>">
                                    <?php echo ucfirst($product['category']); ?>
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars($product['description']); ?></p>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-base sm:text-lg font-semibold text-gray-900">Rs. <?php echo number_format($product['price'], 2); ?></span>
                                <form method="POST">
                                    <input type="hidden" name="action" value="add_to_cart">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" <?php echo $product['category'] == 'product' && $product['stock'] <= 0 ? 'disabled' : ''; ?> class="bg-blue-600 text-white px-4 py-2 sm:py-3 rounded-lg hover:bg-blue-700 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed flex items-center gap-2 min-h-10 text-sm sm:text-base">
                                        <i data-lucide="plus" class="w-4 h-4"></i> Add
                                    </button>
                                </form>
                            </div>
                            <?php if ($product['category'] == 'product'): ?>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-500">Stock: <?php echo $product['stock']; ?></span>
                                    <?php if ($product['stock'] <= 5): ?>
                                        <span class="text-red-600 font-medium">Low Stock!</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Cart -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6 h-fit">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="shopping-cart" class="w-5 h-5 text-blue-600"></i>
                <h2 class="text-lg sm:text-xl font-semibold text-gray-900">Cart</h2>
                <?php if (!empty($_SESSION['cart'])): ?>
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded-full"><?php echo count($_SESSION['cart']); ?></span>
                <?php endif; ?>
            </div>

            <?php if (empty($_SESSION['cart'])): ?>
                <div class="text-center py-8">
                    <i data-lucide="shopping-cart" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                    <p class="text-gray-500 text-base sm:text-lg">Cart is empty</p>
                    <p class="text-gray-400 text-sm mt-1">Add products to start a sale</p>
                </div>
            <?php else: ?>
                <div class="space-y-4 mb-6 max-h-64 overflow-y-auto">
                    <?php foreach ($_SESSION['cart'] as $item): ?>
                        <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-b-0">
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900 text-sm sm:text-base"><?php echo htmlspecialchars($item['name']); ?></h4>
                                <p class="text-sm text-gray-500">Rs. <?php echo number_format($item['price'], 2); ?> each</p>
                                <p class="text-sm font-medium text-gray-700">Total: Rs. <?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                            </div>
                            <div class="flex items-center gap-2">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_quantity">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <input type="hidden" name="quantity" value="<?php echo $item['quantity'] - 1; ?>">
                                    <button type="submit" class="p-2 rounded-full hover:bg-gray-100 transition-colors" aria-label="Decrease quantity">
                                        <i data-lucide="minus" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                <span class="w-8 text-center font-medium text-sm sm:text-base"><?php echo $item['quantity']; ?></span>
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_quantity">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <input type="hidden" name="quantity" value="<?php echo $item['quantity'] + 1; ?>">
                                    <button type="submit" class="p-2 rounded-full hover:bg-gray-100 transition-colors" aria-label="Increase quantity">
                                        <i data-lucide="plus" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="border-t border-gray-200 pt-4">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-base sm:text-lg font-semibold text-gray-900">Subtotal:</span>
                        <span class="text-lg sm:text-2xl font-bold text-gray-900">Rs. <?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    <div class="space-y-2">
                        <button onclick="document.getElementById('checkoutModal').classList.remove('hidden')" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium flex items-center justify-center gap-2 min-h-10 text-sm sm:text-base">
                            <i data-lucide="calculator" class="w-4 h-4"></i> Checkout
                        </button>
                        <form method="POST">
                            <input type="hidden" name="action" value="clear_cart">
                            <button type="submit" class="w-full bg-gray-100 text-gray-700 py-2 sm:py-3 rounded-lg hover:bg-gray-200 transition-colors min-h-10 text-sm sm:text-base">Clear Cart</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div id="checkoutModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-start justify-center p-4 z-50 hidden overflow-y-auto">
        <div class="bg-white rounded-xl max-w-2xl w-full p-4 sm:p-6 my-4 min-h-[50vh] max-h-[90vh] overflow-y-auto">
            <h2 class="text-lg sm:text-2xl font-semibold mb-6">Checkout</h2>
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="checkout">
                <div>
                    <h3 class="text-base sm:text-lg font-medium mb-4">Customer Information</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                            <input type="text" name="name" required class="w-full px-3 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                            <input type="tel" name="phone" required class="w-full px-3 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" class="w-full px-3 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                            <input type="text" name="address" class="w-full px-3 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-medium mb-4">Order Summary</h3>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-sm sm:text-base"><?php echo htmlspecialchars($item['name']); ?> x<?php echo $item['quantity']; ?></span>
                                <span class="text-sm sm:text-base">Rs. <?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="border-t border-gray-200 pt-2 mt-2 space-y-1">
                            <div class="flex justify-between">
                                <span class="text-sm sm:text-base">Subtotal:</span>
                                <span class="text-sm sm:text-base">Rs. <?php echo number_format($cart_total, 2); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm sm:text-base">Tax (<span id="tax_rate_display">0</span>%):</span>
                                <span id="tax_amount" class="text-sm sm:text-base">Rs. 0.00</span>
                            </div>
                            <div class="flex justify-between text-green-600">
                                <span class="text-sm sm:text-base">Discount:</span>
                                <span id="discount_amount" class="text-sm sm:text-base">-Rs. 0.00</span>
                            </div>
                            <div class="flex justify-between font-bold text-base sm:text-lg border-t border-gray-300 pt-2">
                                <span>Total:</span>
                                <span id="total_amount">Rs. <?php echo number_format($cart_total, 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                        <select name="payment_method" class="w-full px-3 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tax Rate (%)</label>
                        <input type="number" name="tax_rate" min="0" max="100" step="0.1" value="0" oninput="updateTotals()" class="w-full px-3 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount (Rs.)</label>
                        <input type="number" name="discount" min="0" value="0" oninput="updateTotals()" class="w-full px-3 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" class="w-full px-3 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base" rows="3" placeholder="Add any special notes or instructions..."></textarea>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 pt-4">
                    <button type="button" onclick="document.getElementById('checkoutModal').classList.add('hidden')" class="flex-1 px-4 py-2 sm:py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors min-h-10 text-sm sm:text-base">Cancel</button>
                    <button type="submit" class="flex-1 px-4 py-2 sm:py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center gap-2 min-h-10 text-sm sm:text-base">
                        <i data-lucide="send" class="w-4 h-4"></i> Complete Sale & Send SMS
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function updateTotals() {
        const taxRate = parseFloat(document.querySelector('input[name="tax_rate"]').value) || 0;
        const discount = parseFloat(document.querySelector('input[name="discount"]').value) || 0;
        const subtotal = <?php echo $cart_total; ?>;
        const tax = subtotal * (taxRate / 100);
        const total = subtotal + tax - discount;

        document.getElementById('tax_rate_display').textContent = taxRate;
        document.getElementById('tax_amount').textContent = 'Rs. ' + tax.toFixed(2);
        document.getElementById('discount_amount').textContent = '-Rs. ' + discount.toFixed(2);
        document.getElementById('total_amount').textContent = 'Rs. ' + (total < 0 ? 0 : total).toFixed(2);
        document.querySelector('button[type="submit"]').disabled = total < 0;
    }

    function filterProducts() {
        const searchInput = document.getElementById('searchInput').value.toLowerCase();
        const productCards = document.querySelectorAll('.product-card');

        productCards.forEach(card => {
            const name = card.getAttribute('data-name') || '';
            const description = card.getAttribute('data-description') || '';
            const sku = card.getAttribute('data-sku') || '';
            const matches = name.includes(searchInput) || description.includes(searchInput) || sku.includes(searchInput);
            card.style.display = matches ? '' : 'none';
        });
    }
</script>