<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Initialize database connection
$conn = connectDB();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UnitedMBS/J Peters - Daily Thrift & Marketplace</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-purple: #6B46C1;
            --primary-purple-dark: #553C9A;
        }
        .btn-purple {
            background-color: var(--primary-purple);
            color: white;
            transition: background-color 0.3s;
        }
        .btn-purple:hover {
            background-color: var(--primary-purple-dark);
        }
        .hero-gradient {
            background: linear-gradient(135deg, #6B46C1 0%, #805AD5 100%);
        }
        .feature-card {
            transition: transform 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-purple-700 text-white px-6 py-4">
    <div class="container mx-auto">
        <!-- Desktop Navigation -->
        <div class="flex justify-between items-center">
            <!-- Left Logo -->
            <div class="flex items-center space-x-4">
                <img src="images/united-logo.jpeg" alt="United MBS Logo" class="h-12 w-auto">
                <h1 class="text-2xl font-bold hidden md:block">UnitedMBS/J Peters</h1>
            </div>

            <!-- Mobile Menu Button -->
            <button id="mobile-menu-button" class="md:hidden flex items-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-6">
                <a href="#" class="hover:text-purple-200">Home</a>
                <a href="#features" class="hover:text-purple-200">Features</a>
                <a href="#about" class="hover:text-purple-200">About</a>
                <a href="#contact" class="hover:text-purple-200">Contact</a>
                <a href="login.php" class="hover:text-purple-200">Login</a>
                <a href="register.php" class="px-4 py-2 rounded-lg bg-white text-purple-700 hover:bg-purple-100">Register</a>
            </div>

            <!-- Right Logo and Profile -->
            <div class="hidden md:flex items-center space-x-4">
                <img src="images/jpeters-logo.jpeg" alt="J Peters Logo" class="h-12 w-auto">
            </div>
        </div>

        <!-- Mobile Menu Dropdown -->
        <div id="mobile-menu" class="hidden md:hidden mt-4">
            <div class="flex flex-col space-y-3">
               <a href="#" class="hover:text-purple-200">Home</a>
                <a href="#features" class="hover:text-purple-200">Features</a>
                <a href="#about" class="hover:text-purple-200">About</a>
                <a href="#contact" class="hover:text-purple-200">Contact</a>
                <a href="login.php" class="hover:text-purple-200">Login</a>
                <a href="register.php" class="px-4 py-2 rounded-lg bg-white text-purple-700 hover:bg-purple-100">Register</a>
            </div>
        </div>
    </div>
</nav>
<script>
document.getElementById('mobile-menu-button').addEventListener('click', function() {
    document.getElementById('mobile-menu').classList.toggle('hidden');
});
</script>

    <!-- Hero Section -->
<div class="hero-gradient min-h-screen pt-16">
    <div class="container mx-auto px-6 py-20 text-white">
        <!-- Main Content -->
        <div class="text-center max-w-4xl mx-auto">
            <h1 class="text-5xl font-bold mb-8">Daily Thrift & Marketplace</h1>
            
            <!-- Two-Column Benefits -->
            <div class="grid md:grid-cols-2 gap-8 mb-12">
                <!-- Thrift Column -->
                <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6">
                    <div class="text-4xl mb-4">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                    <h2 class="text-2xl font-bold mb-4">Daily Thrift Savings</h2>
                    <ul class="text-lg space-y-3 mb-6">
                        <li><i class="fas fa-check-circle mr-2"></i>Annual interest and Rewards</li>
                        <li><i class="fas fa-check-circle mr-2"></i>Daily, weekly & monthly plans</li>
                        <li><i class="fas fa-check-circle mr-2"></i>0% APR micro-loans available</li>
                    </ul>
                </div>
                
                <!-- Store Column -->
                <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6">
                    <div class="text-4xl mb-4">
                        <i class="fas fa-store"></i>
                    </div>
                    <h2 class="text-2xl font-bold mb-4">Digital Marketplace</h2>
                    <ul class="text-lg space-y-3 mb-6">
                        <li><i class="fas fa-check-circle mr-2"></i>Quality Products</li>
                        <li><i class="fas fa-check-circle mr-2"></i>Direct ordering via WhatsApp</li>
                        <li><i class="fas fa-check-circle mr-2"></i>Convenient home delivery</li>
                    </ul>
                </div>
            </div>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-4">
                <a href="register.php" class="px-8 py-3 bg-white text-purple-700 rounded-lg font-semibold hover:bg-purple-100 transition-colors">
                    <i class="fas fa-user-plus mr-2"></i>Create Free Account
                </a>
                <a href="#features" class="px-8 py-3 border-2 border-white rounded-lg font-semibold hover:bg-white hover:text-purple-700 transition-colors">
                    <i class="fas fa-info-circle mr-2"></i>Learn More
                </a>
            </div>
        </div>
    </div>
</div>

    <!-- Features Section 
    <div id="features" class="py-20 bg-white">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold text-center mb-16">Our Features</h2>
            <div class="grid md:grid-cols-3 gap-12">
                //Save Money Card
                <div class="feature-card bg-white rounded-xl shadow-lg p-8">
                    <div class="text-purple-600 text-4xl mb-4">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Save Money</h3>
                    <p class="text-gray-600">
                        With thrift contribution, you can easily save your money on schedule based on your chosen plan. 
                        Automate your savings for consistent growth.
                    </p>
                </div>

                //Earn Interest Card 
                <div class="feature-card bg-white rounded-xl shadow-lg p-8">
                    <div class="text-purple-600 text-4xl mb-4">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Earn Interest</h3>
                    <p class="text-gray-600">
                        Enjoy up to 18% interest p.a on all your savings as it accumulates based on your consistent 
                        contribution to the thrift plan.
                    </p>
                </div>

                //Micro-loan Card 
                <div class="feature-card bg-white rounded-xl shadow-lg p-8">
                    <div class="text-purple-600 text-4xl mb-4">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Micro-loan</h3>
                    <p class="text-gray-600">
                        Access micro-loans at 0% interest to urgently meet up needs that can help your life plans and goals.
                    </p>
                </div>
            </div>
        </div>
    </div> -->
    
    
    <!-- Store Products Section -->
<div class="bg-gray-50 py-16">
    <div class="container mx-auto px-6">
        <h2 class="text-3xl font-bold text-center mb-12">Our Products</h2>
        
        <?php
        $admin_phone = 2348188502964;

        $admin_whatsapp = 8188502964;
        // Initialize StoreManager
        $storeManager = new StoreManager();
        
        // Pagination settings
        $products_per_page = 6;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $products_per_page;
        
        // Get total products count for pagination
        $total_products_query = "SELECT COUNT(*) as total FROM store_products WHERE status = 'available'";
        $total_result = $conn->query($total_products_query);
        $total_products = $total_result->fetch_assoc()['total'];
        $total_pages = ceil($total_products / $products_per_page);
        
        // Get products for current page
        $products_query = "SELECT * FROM store_products 
                          WHERE status = 'available' 
                          ORDER BY created_at DESC 
                          LIMIT $offset, $products_per_page";
        $products_result = $conn->query($products_query);
        ?>

        <!-- Products Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
            <?php while ($product = $products_result->fetch_assoc()): ?>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <?php if ($product['image_url']): ?>
                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                     class="w-full h-48 object-cover">
                <?php else: ?>
                <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                    <span class="text-gray-500">No image available</span>
                </div>
                <?php endif; ?>
                
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($product['description']); ?></p>
                    <p class="text-2xl font-bold text-purple-600 mb-4">₦<?php echo number_format($product['price'], 2); ?></p>
                    
                    <div class="space-y-2">
                        <?php if ($product['phone_number']): ?>
                        <a href="tel:<?php echo htmlspecialchars($admin_phone); ?>" 
                           class="block w-full text-center bg-purple-600 text-white py-2 px-4 rounded-lg hover:bg-purple-700 transition-colors">
                            <i class="fas fa-phone-alt mr-2"></i> Call to Order
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($product['whatsapp_number']): ?>
                        <a href="https://wa.me/<?php echo htmlspecialchars($admin_whatsapp); ?>" 
                           target="_blank"
                           class="block w-full text-center border border-purple-600 text-purple-600 py-2 px-4 rounded-lg hover:bg-purple-50 transition-colors">
                            <i class="fab fa-whatsapp mr-2"></i> WhatsApp Order
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="flex justify-center items-center space-x-4">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>" 
               class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                Previous
            </a>
            <?php endif; ?>
            
            <div class="text-gray-600">
                Page <?php echo $page; ?> of <?php echo $total_pages; ?>
            </div>
            
            <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>" 
               class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                Next
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

    <!-- CTA Section -->
    <div class="bg-purple-700 py-16">
        <div class="container mx-auto px-6 text-center">
            <h2 class="text-3xl font-bold text-white mb-8">
                Affordable loans after completing compulsory online training
            </h2>
            <p class="text-white text-xl mb-8 max-w-2xl mx-auto">
                Are you an upcoming entrepreneur or you are looking for capital to start a trade? 
                Then join us for an humble beginning.
            </p>
            <a href="register.php" class="inline-block px-8 py-3 bg-white text-purple-700 rounded-lg font-semibold hover:bg-purple-100">
                Get Started Now
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="container mx-auto px-6">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">UnitedMBS/JPeters</h3>
                    <p class="text-gray-400">Your trusted partner in digital thrift contributions and micro-loans.</p>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white">Home</a></li>
                        <li><a href="#features" class="text-gray-400 hover:text-white">Features</a></li>
                        <li><a href="#about" class="text-gray-400 hover:text-white">About</a></li>
                        <li><a href="#contact" class="text-gray-400 hover:text-white">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Legal</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white">Privacy Policy</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Terms of Service</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Contact Us</h4>
                    <ul class="space-y-2">
                        <li class="text-gray-400">Email: info@jpetersmbs.com</li>
                        <li class="text-gray-400">Phone: (234) 123-4567</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center">
                <p class="text-gray-400">&copy; 2025 JpetersMBS. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>