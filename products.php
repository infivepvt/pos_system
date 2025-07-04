<?php

require_once 'config.php';

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$query = "SELECT * FROM products";
$params = [];
if ($status_filter != 'all') {
    $query .= " WHERE status = ?";
    $params[] = $status_filter;
}
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add' || $_POST['action'] == 'edit') {
        $name = $_POST['name'];
        $category = $_POST['category'];
        $price = $_POST['price'];
        $description = $_POST['description'];
        $sku = $_POST['sku'] ?: null;
        $stock = $category == 'product' ? ($_POST['stock'] ?: 0) : 0;
        $status = $_POST['status'];

        if ($_POST['action'] == 'add') {
            $stmt = $pdo->prepare("INSERT INTO products (name, category, price, description, sku, stock, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $category, $price, $description, $sku, $stock, $status]);
        } else {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("UPDATE products SET name = ?, category = ?, price = ?, description = ?, sku = ?, stock = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$name, $category, $price, $description, $sku, $stock, $status, $id]);
        }
        header("Location: index.php?page=products");
        exit;
    } elseif ($_POST['action'] == 'delete') {
        $id = $_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: index.php?page=products");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $error_message = "Cannot delete this product because it is associated with existing sales records. Please remove related sales items first.";
            } else {
                $error_message = "An error occurred while deleting the product: " . htmlspecialchars($e->getMessage());
            }
        }
    } elseif ($_POST['action'] == 'toggle_status') {
        $id = $_POST['id'];
        $status = $_POST['status'] == 'active' ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE products SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $id]);
        header("Location: index.php?page=products");
        exit;
    }
}

$edit_product = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_product = $stmt->fetch();
}
?>

<div class="p-4 sm:p-6 md:p-8">
    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Products & Services</h1>
        <p class="text-gray-600 mt-2 text-sm sm:text-base">Manage your inventory and services</p>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg text-sm sm:text-base">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <div class="flex flex-col sm:flex-row items-center gap-4 w-full sm:w-auto">
            <div class="relative w-full md:w-1/2">
                <input type="text" id="searchInput" placeholder="Search by name, description, or SKU..." class="w-full pl-10 pr-10 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base" oninput="filterProducts()">
                <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-500"></i>
                <button type="button" onclick="document.getElementById('searchInput').value='';filterProducts()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700" aria-label="Clear search">✕</button>
            </div>
            <select onchange="window.location='?page=products&status='+this.value" class="w-full sm:w-auto px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Products</option>
                <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active Only</option>
                <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
            </select>
        </div>
        <button onclick="document.getElementById('productForm').classList.remove('hidden')" class="bg-blue-600 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 min-h-10 text-sm sm:text-base">
            <i data-lucide="plus" class="w-5 h-5"></i> Add Product
        </button>
    </div>

    <?php if (empty($products)): ?>
        <div class="text-center py-12">
            <p class="text-gray-500 text-sm sm:text-base">No products found</p>
        </div>
    <?php else: ?>
        <div id="productGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <?php foreach ($products as $product): ?>
                <div class="product-card bg-white rounded-xl shadow-sm border-2 <?php echo $product['status'] == 'active' ? 'border-gray-200' : 'border-red-200 bg-red-50'; ?> hover:shadow-md transition-all"
                     data-name="<?php echo htmlspecialchars(strtolower($product['name'])); ?>"
                     data-description="<?php echo htmlspecialchars(strtolower($product['description'])); ?>"
                     data-sku="<?php echo htmlspecialchars(strtolower($product['sku'] ?? '')); ?>">
                    <div class="p-4 sm:p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg <?php echo $product['status'] == 'active' ? 'bg-blue-100' : 'bg-gray-100'; ?>">
                                    <i data-lucide="package" class="w-4 h-4 sm:w-5 sm:h-5 <?php echo $product['status'] == 'active' ? 'text-blue-600' : 'text-gray-400'; ?>"></i>
                                </div>
                                <div>
                                    <h3 class="text-sm sm:text-base font-semibold <?php echo $product['status'] == 'active' ? 'text-gray-900' : 'text-gray-500'; ?>"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <?php if ($product['sku']): ?>
                                        <p class="text-xs sm:text-sm text-gray-500">SKU: <?php echo htmlspecialchars($product['sku']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $product['category'] == 'product' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800'; ?>">
                                    <?php echo ucfirst($product['category']); ?>
                                </span>
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $product['status'] == 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </span>
                            </div>
                        </div>
                        <p class="text-xs sm:text-sm mb-4 <?php echo $product['status'] == 'active' ? 'text-gray-600' : 'text-gray-500'; ?>"><?php echo htmlspecialchars($product['description']); ?></p>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-base sm:text-lg font-bold <?php echo $product['status'] == 'active' ? 'text-gray-900' : 'text-gray-500'; ?>">
                                Rs. <?php echo number_format($product['price'], 2); ?>
                            </span>
                            <?php if ($product['category'] == 'product'): ?>
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $product['status'] == 'inactive' ? 'bg-gray-100 text-gray-600' : ($product['stock'] > 10 ? 'bg-green-100 text-green-800' : ($product['stock'] > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')); ?>">
                                    Stock: <?php echo $product['stock']; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <form method="POST">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="status" value="<?php echo $product['status']; ?>">
                                <button type="submit" class="flex-1 px-4 py-2 sm:py-3 rounded-lg transition-colors flex items-center justify-center gap-2 min-h-10 text-xs sm:text-sm <?php echo $product['status'] == 'active' ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200'; ?>" aria-label="<?php echo $product['status'] == 'active' ? 'Deactivate' : 'Activate'; ?> product">
                                    <i data-lucide="<?php echo $product['status'] == 'active' ? 'eye-off' : 'eye'; ?>" class="w-4 h-4"></i>
                                    <?php echo $product['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                            <a href="?page=products&edit=<?php echo $product['id']; ?>" class="flex-1 bg-gray-100 text-gray-700 px-4 py-2 sm:py-3 rounded-lg hover:bg-gray-200 transition-colors flex items-center justify-center gap-2 min-h-10 text-xs sm:text-sm" aria-label="Edit product">
                                <i data-lucide="edit" class="w-4 h-4"></i> Edit
                            </a>
                            <form method="POST">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                <button type="submit" onclick="return confirm('Are you sure you want to delete this product?')" class="px-4 py-2 sm:py-3 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors flex items-center justify-center min-h-10 text-xs sm:text-sm" aria-label="Delete product">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div id="productForm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 <?php echo !$edit_product ? 'hidden' : ''; ?>">
        <div class="bg-white rounded-xl max-w-lg w-full p-4 sm:p-6 max-h-[90vh] overflow-y-auto">
            <h2 class="text-lg sm:text-xl font-semibold mb-6"><?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="<?php echo $edit_product ? 'edit' : 'add'; ?>">
                <?php if ($edit_product): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_product['id']; ?>">
                <?php endif; ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                    <input type="text" name="name" required value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>" class="w-full px-3 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                    <textarea name="description" required class="w-full px-3 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base" rows="3"><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                        <select name="category" onchange="document.getElementById('stock_div').classList.toggle('hidden', this.value != 'product')" class="w-full px-3 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                            <option value="product" <?php echo $edit_product && $edit_product['category'] == 'product' ? 'selected' : ''; ?>>Product</option>
                            <option value="service" <?php echo $edit_product && $edit_product['category'] == 'service' ? 'selected' : ''; ?>>Service</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                        <select name="status" class="w-full px-3 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                            <option value="active" <?php echo $edit_product && $edit_product['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $edit_product && $edit_product['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price (Rs.) *</label>
                    <input type="number" name="price" required min="0" step="0.01" value="<?php echo $edit_product ? $edit_product['price'] : '0'; ?>" class="w-full px-3 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                </div>
                <div id="stock_div" class="<?php echo $edit_product && $edit_product['category'] == 'service' ? 'hidden' : ''; ?>">
                    <label class="block text-sm font-medium text-gray-700 mb-1">SKU (Stock Keeping Unit)</label>
                    <input type="text" name="sku" value="<?php echo $edit_product ? htmlspecialchars($edit_product['sku']) : ''; ?>" class="w-full px-3 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base" placeholder="e.g., PROD-001">
                    <label class="block text-sm font-medium text-gray-700 mb-1 mt-4">Stock Quantity</label>
                    <input type="number" name="stock" min="0" value="<?php echo $edit_product ? $edit_product['stock'] : '0'; ?>" class="w-full px-3 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base">
                </div>
                <div class="flex flex-col sm:flex-row gap-3 pt-4">
                    <button type="button" onclick="document.getElementById('productForm').classList.add('hidden')" class="flex-1 px-4 py-2 sm:py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors min-h-10 text-sm sm:text-base">Cancel</button>
                    <button type="submit" class="flex-1 px-4 py-2 sm:py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors min-h-10 text-sm sm:text-base"><?php echo $edit_product ? 'Update' : 'Add'; ?> Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('productForm').classList.add('hidden');
<?php if ($edit_product): ?>
    document.getElementById('productForm').classList.remove('hidden');
<?php endif; ?>

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