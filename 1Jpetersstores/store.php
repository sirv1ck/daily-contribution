<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
        $admin_phone = 2348188502964;

        $admin_whatsapp = 2348188502964;
// Initialize managers
$storeManager = new StoreManager();
$profileManager = new ProfileManager($_SESSION['user_id']);
$userProfile = $profileManager->getUserProfile();

// Get current page and category from URL parameters
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$category = isset($_GET['category']) ? $_GET['category'] : null;

// Get products and total pages
try {
    $products = $storeManager->getAllProducts($current_page, $category);
    $total_pages = $storeManager->getTotalPages($category);
} catch (Exception $e) {
    error_log("Error fetching products: " . $e->getMessage());
    $products = [];
    $total_pages = 1;
}

// Get profile image URL
$profileImage = $userProfile['profile_image'] ? 'uploads/profile_images/' . $userProfile['profile_image'] : '/api/placeholder/96/96';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store - UnitedMBS/JPeters</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">Our Products</h1>
            <button onclick="showAddProductModal()" 
                    class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700">
                <i class="fas fa-plus mr-2"></i> Add Product
            </button>
        </div>

        <!-- Category Filter -->
        <div class="mb-8">
            <select onchange="window.location.href=this.value" class="px-4 py-2 border rounded-lg">
                <option value="store.php" <?php echo !$category ? 'selected' : ''; ?>>All Categories</option>
                <option value="store.php?category=electronics" <?php echo $category === 'electronics' ? 'selected' : ''; ?>>Electronics</option>
                <option value="store.php?category=fashion" <?php echo $category === 'fashion' ? 'selected' : ''; ?>>Fashion</option>
                <option value="store.php?category=home" <?php echo $category === 'home' ? 'selected' : ''; ?>>Home & Living</option>
                <option value="store.php?category=other" <?php echo $category === 'other' ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>

        <!-- Product Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php if (empty($products)): ?>
            <div class="col-span-full text-center py-8 text-gray-500">
                No products available in this category.
            </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <?php if ($product['image_url']): ?>
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         class="w-full h-64 object-cover">
                    <?php endif; ?>
                    
                    <div class="p-6">
                        <h3 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="text-gray-600 mb-2"><?php echo htmlspecialchars($product['description']); ?></p>
                        <p class="text-sm text-gray-500 mb-4">Seller: <?php echo htmlspecialchars($product['seller_name']); ?></p>
                        <p class="text-purple-600 font-bold text-xl mb-4">
                            ₦<?php echo number_format($product['price'], 2); ?>
                        </p>
                        
                        <div class="space-y-3">
                            <?php if ($product['phone_number']): ?>
                            <a href="tel:<?php echo htmlspecialchars($admin_phone); ?>" 
                               class="block w-full text-center bg-purple-600 text-white py-3 rounded-lg hover:bg-purple-700">
                                <i class="fas fa-phone-alt mr-2"></i> Call to Order
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($product['whatsapp_number']): ?>
                            <a href="https://wa.me/<?php echo htmlspecialchars($admin_whatsapp); ?>" 
                               target="_blank"
                               class="block w-full text-center border border-purple-600 text-purple-600 py-3 rounded-lg hover:bg-purple-50">
                                <i class="fab fa-whatsapp mr-2"></i> WhatsApp Order
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="flex justify-center space-x-2 mt-8">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?><?php echo $category ? '&category=' . urlencode($category) : ''; ?>" 
               class="px-4 py-2 rounded-lg <?php echo $i === $current_page ? 'bg-purple-600 text-white' : 'bg-white text-purple-600 hover:bg-purple-50'; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Add Product Modal -->
    <div id="addProductModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-lg w-full">
            <h2 class="text-2xl font-bold mb-6">Add New Product</h2>
            
            <form action="process_product.php" method="POST" enctype="multipart/form-data">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Product Name</label>
                        <input type="text" name="name" required
                               class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">Description</label>
                        <textarea name="description" required
                                  class="w-full px-3 py-2 border rounded-lg"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">Price (₦)</label>
                        <input type="number" name="price" required step="0.01"
                               class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">Category</label>
                        <select name="category" required
                                class="w-full px-3 py-2 border rounded-lg">
                            <option value="">Select Category</option>
                            <option value="electronics">Electronics</option>
                            <option value="fashion">Fashion</option>
                            <option value="home">Home & Living</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">Product Image</label>
                        <input type="file" name="product_image" accept="image/*"
                               class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">Phone Number</label>
                        <input type="tel" name="phone_number" required
                               class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">WhatsApp Number</label>
                        <input type="tel" name="whatsapp_number"
                               class="w-full px-3 py-2 border rounded-lg">
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4 mt-6">
                    <button type="button" onclick="hideAddProductModal()"
                            class="px-6 py-2 border rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" name="action" value="add_product"
                            class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                        Add Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddProductModal() {
            document.getElementById('addProductModal').classList.remove('hidden');
        }
        
        function hideAddProductModal() {
            document.getElementById('addProductModal').classList.add('hidden');
        }
    </script>
</body>
</html>