<?php

require_once 'config.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';
$where = $search ? "WHERE name LIKE '%$search%' OR phone LIKE '%$search%' OR email LIKE '%$search%'" : '';
$customers = $pdo->query("SELECT c.*, 
    (SELECT COUNT(*) FROM sales s WHERE s.customer_id = c.id) as invoice_count,
    (SELECT SUM(s.total) FROM sales s WHERE s.customer_id = c.id) as total_spent
    FROM customers c $where")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $phone, $email, $address]);
    header("Location: index.php?page=customers");
    exit;
}
?>

<!-- Header and Search -->
<div class="flex flex-wrap gap-4 justify-between items-start md:items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Customers</h1>
        <p class="text-gray-600 mt-2">Manage your customer database</p>
    </div>
    <div class="flex items-center flex-wrap gap-2">
        <form method="GET" class="relative flex items-center gap-2 w-full sm:w-auto">
            <input type="hidden" name="page" value="customers">
            <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4"></i>
            <input type="text" id="searchInput" name="search"
                placeholder="Search customers..." value="<?php echo htmlspecialchars($search); ?>"
                class="pl-10 pr-8 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full sm:w-64"
                onkeypress="if(event.key === 'Enter'){ this.form.submit(); }">
            <button type="submit"
                class="text-sm px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                Search
            </button>
            <?php if ($search): ?>
                <button type="button"
                    onclick="document.getElementById('searchInput').value=''; window.location='?page=customers';"
                    class="text-gray-400 hover:text-gray-600 text-lg px-2">✕</button>
            <?php endif; ?>
        </form>
        <button onclick="document.getElementById('customerForm').classList.remove('hidden')"
            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 w-full sm:w-auto">
            <i data-lucide="plus" class="w-5 h-5"></i> Add Customer
        </button>
    </div>
</div>

<!-- Customer List -->
<?php if (empty($customers)): ?>
    <div class="text-center py-12">
        <p class="text-gray-500 text-lg">No customers found</p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($customers as $customer): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($customer['name']); ?></h3>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center gap-3 text-gray-600">
                        <i data-lucide="phone" class="w-4 h-4"></i>
                        <span class="text-sm"><?php echo htmlspecialchars($customer['phone']); ?></span>
                    </div>
                    <?php if ($customer['email']): ?>
                        <div class="flex items-center gap-3 text-gray-600">
                            <i data-lucide="mail" class="w-4 h-4"></i>
                            <span class="text-sm"><?php echo htmlspecialchars($customer['email']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($customer['address']): ?>
                        <div class="flex items-center gap-3 text-gray-600">
                            <i data-lucide="map-pin" class="w-4 h-4"></i>
                            <span class="text-sm"><?php echo htmlspecialchars($customer['address']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="text-sm text-gray-500">
                        <p>Invoices: <?php echo $customer['invoice_count']; ?></p>
                        <p>Total Spent: Rs. <?php echo number_format($customer['total_spent'] ?: 0, 2); ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Add Customer Modal -->
<div id="customerForm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 hidden">
    <div class="bg-white rounded-xl w-full max-w-md sm:max-w-lg p-6">
        <h2 class="text-xl font-semibold mb-6">Add New Customer</h2>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                <input type="text" name="name" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                <input type="tel" name="phone" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea name="address" rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="document.getElementById('customerForm').classList.add('hidden')"
                    class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Add
                    Customer</button>
            </div>
        </form>
    </div>
</div>

<!-- Lucide Icons Script -->
<script>
    if (window.lucide) {
        lucide.createIcons();
    }
</script>
