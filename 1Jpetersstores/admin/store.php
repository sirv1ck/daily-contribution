<?php
session_start();
require_once '../config/database.php';
require_once 'check_admin.php';
checkAdmin();

class AdminStoreManager {
    private $conn;
    private $upload_dir = '../uploads/store/';
    
    public function __construct() {
        $this->conn = connectDB();
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
        }
    }
    
    public function getAllProducts() {
        $sql = "SELECT * FROM store_products ORDER BY created_at DESC";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
        public function addProduct($data, $image) {
            if ($image['error'] === 0) {
                $image_path = $this->uploadImage($image);
            } else {
                $image_path = null;
            }
            
            $sql = "INSERT INTO store_products (name, description, price, 
                    image_url, category, phone_number, whatsapp_number, status, user_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                return false;
            }
            
            // Assuming user_id is stored in session
            $user_id = $_SESSION['user_id'] ?? null;
            
            $stmt->bind_param("ssssssssi", 
                $data['name'],
                $data['description'],
                $data['price'],
                $image_path,
                $data['category'],
                $data['phone_number'],
                $data['whatsapp_number'],
                $data['status'],
                $user_id
            );
            
            return $stmt->execute();
        }
    
    public function updateProduct($id, $data, $image = null) {
        $image_path = null;
        if ($image && $image['error'] === 0) {
            $image_path = $this->uploadImage($image);
        }
        
        $sql = "UPDATE store_products SET 
                name = ?, description = ?, price = ?, 
                category = ?,
                phone_number = ?, whatsapp_number = ?, 
                status = ?";
        
        $params = [
            $data['name'],
            $data['description'],
            $data['price'],
            $data['category'],
            $data['phone_number'],
            $data['whatsapp_number'],
            $data['status']
        ];
        $types = "sssssss";
        
        if ($image_path) {
            $sql .= ", image_url = ?";
            $params[] = $image_path;
            $types .= "s";
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        $types .= "i";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        return $stmt->execute();
    }

    public function deleteProduct($id) {
        $sql = "DELETE FROM store_products WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    private function uploadImage($file) {
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $filepath = $this->upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return 'uploads/store/' . $filename;
        }
        return null;
    }
}

$storeManager = new AdminStoreManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $result = $storeManager->addProduct($_POST, $_FILES['image']);
                $message = $result ? 'Product added successfully' : 'Error adding product';
                break;
            case 'update':
                $result = $storeManager->updateProduct($_POST['id'], $_POST, $_FILES['image'] ?? null);
                $message = $result ? 'Product updated successfully' : 'Error updating product';
                break;
            case 'delete':
                $result = $storeManager->deleteProduct($_POST['id']);
                $message = $result ? 'Product deleted successfully' : 'Error deleting product';
                break;
        }
    }
}

$products = $storeManager->getAllProducts();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Management - Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Store Management</h1>
            <button onclick="document.getElementById('addProductModal').classList.remove('hidden')" 
                    class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                <i class="fas fa-plus mr-2"></i> Add Product
            </button>
        </div>

        <?php if (isset($message)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Products Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">
                        <th class="py-3 px-4">Product</th>
                        <th class="py-3 px-4">Price</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr class="border-t">
                        <td class="py-3 px-4">
                            <div class="flex items-center">
                                <?php if ($product['image_url']): ?>
                                <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="" class="w-12 h-12 rounded object-cover mr-3">
                                <?php endif; ?>
                                <div>
                                    <p class="font-medium"><?php echo htmlspecialchars($product['name']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($product['description'], 0, 50)); ?>...</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4">₦<?php echo number_format($product['price'], 2); ?></td>
                        <td class="py-3 px-4"><?php echo htmlspecialchars($product['category']); ?></td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 text-xs rounded-full <?php 
                                switch($product['status']) {
                                    case 'available':
                                        echo 'bg-green-100 text-green-800';
                                        break;
                                    case 'out_of_stock':
                                        echo 'bg-red-100 text-red-800';
                                        break;
                                    default:
                                        echo 'bg-gray-100 text-gray-800';
                                }
                            ?>">
                                <?php echo ucfirst($product['status']); ?>
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <button onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)" 
                                    class="text-blue-600 hover:text-blue-800 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteProduct(<?php echo $product['id']; ?>)" 
                                    class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div id="addProductModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Product</h3>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Name</label>
                        <input type="text" name="name" required 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                        <textarea name="description" required 
                                  class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Price</label>
                        <input type="number" name="price" required step="0.01" 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Category</label>
                        <input type="text" name="category" required 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Phone Number</label>
                        <input type="tel" name="phone_number" required 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">WhatsApp Number</label>
                        <input type="tel" name="whatsapp_number" required 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                        <select name="status" required 
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="available">Available</option>
                            <option value="out_of_stock">Out of Stock</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Image</label>
                        <input type="file" name="image" accept="image/*" 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="button" onclick="document.getElementById('addProductModal').classList.add('hidden')"
                                class="bg-gray-500 text-white px-4 py-2 rounded-lg mr-2">Cancel</button>
                        <button type="submit" 
                                class="bg-purple-600 text-white px-4 py-2 rounded-lg">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editProductModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Product</h3>
                <form action="" method="POST" enctype="multipart/form-data" id="editForm">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Name</label>
                        <input type="text" name="name" id="edit_name" required 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                        <textarea name="description" id="edit_description" required 
                                  class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Price</label>
                        <input type="number" name="price" id="edit_price" required step="0.01" 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Category</label>
                        <input type="text" name="category" id="edit_category" required 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Phone Number</label>
                        <input type="tel" name="phone_number" id="edit_phone_number" required 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">WhatsApp Number</label>
                        <input type="tel" name="whatsapp_number" id="edit_whatsapp_number" required 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                        <select name="status" id="edit_status" required 
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="available">Available</option>
                            <option value="unavailable">Out of Stock</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">New Image (optional)</label>
                        <input type="file" name="image" accept="image/*" 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="button" onclick="closeEditModal()"
                                class="bg-gray-500 text-white px-4 py-2 rounded-lg mr-2">Cancel</button>
                        <button type="submit" 
                                class="bg-purple-600 text-white px-4 py-2 rounded-lg">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Delete</h3>
                <p class="text-gray-500 mb-4">Are you sure you want to delete this product? This action cannot be undone.</p>
                <form action="" method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <div class="flex justify-center">
                        <button type="button" onclick="closeDeleteModal()"
                                class="bg-gray-500 text-white px-4 py-2 rounded-lg mr-2">Cancel</button>
                        <button type="submit" 
                                class="bg-red-600 text-white px-4 py-2 rounded-lg">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editProduct(product) {
            document.getElementById('edit_id').value = product.id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_description').value = product.description;
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_category').value = product.category;
            document.getElementById('edit_phone_number').value = product.phone_number;
            document.getElementById('edit_whatsapp_number').value = product.whatsapp_number;
            document.getElementById('edit_status').value = product.status;
            document.getElementById('editProductModal').classList.remove('hidden');
        }

        function deleteProduct(productId) {
            document.getElementById('delete_id').value = productId;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editProductModal').classList.add('hidden');
            document.getElementById('editForm').reset();
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = [
                document.getElementById('addProductModal'),
                document.getElementById('editProductModal'),
                document.getElementById('deleteModal')
            ];
            
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.classList.add('hidden');
                }
            });
        }

        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let valid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        valid = false;
                        field.classList.add('border-red-500');
                    } else {
                        field.classList.remove('border-red-500');
                    }
                });
                
                if (!valid) {
                    e.preventDefault();
                    alert('Please fill in all required fields');
                }
            });
        });
    </script>
</body>
</html>