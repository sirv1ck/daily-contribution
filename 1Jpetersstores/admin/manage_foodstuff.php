<?php
// admin/manage_foodstuff.php
session_start();
require_once '../config/database.php';
require_once 'check_admin.php';
checkAdmin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Foodstuff - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Manage Foodstuff Inventory</h2>
            <button onclick="showAddFoodstuffModal()" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                Add Foodstuff
            </button>
        </div>

        <!-- Foodstuff Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php 
                    $foodstuffManager = new AdminFoodstuffManager();
                    $foodstuffs = $foodstuffManager->getAllFoodstuff();
                    foreach ($foodstuffs as $foodstuff):
                    ?>
                    <tr>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($foodstuff['name']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($foodstuff['description']); ?></td>
                        <td class="px-6 py-4"><?php echo $foodstuff['quantity']; ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($foodstuff['unit']); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                       <?php echo $foodstuff['status'] === 'available' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo ucfirst($foodstuff['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <button onclick="editFoodstuff(<?php echo $foodstuff['id']; ?>)" 
                                    class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                            <button onclick="deleteFoodstuff(<?php echo $foodstuff['id']; ?>)" 
                                    class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<!-- Add/Edit Foodstuff Modal -->
<div id="foodstuffModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">Add New Foodstuff</h3>
            <form id="foodstuffForm" class="mt-4">
                <input type="hidden" id="foodstuffId" name="foodstuff_id">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                        Name
                    </label>
                    <input type="text" id="name" name="name" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                        Description
                    </label>
                    <textarea id="description" name="description" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            rows="3"></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="quantity">
                        Quantity
                    </label>
                    <input type="number" id="quantity" name="quantity" required min="0"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="unit">
                        Unit
                    </label>
                    <input type="text" id="unit" name="unit" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="status">
                        Status
                    </label>
                    <select id="status" name="status" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="available">Available</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                </div>

                <div class="flex items-center justify-between mt-6">
                    <button type="button" onclick="closeFoodstuffModal()"
                            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="submit"
                            class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                        Save Foodstuff
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showAddFoodstuffModal() {
    document.getElementById('modalTitle').textContent = 'Add New Foodstuff';
    document.getElementById('foodstuffForm').reset();
    document.getElementById('foodstuffId').value = '';
    document.getElementById('foodstuffModal').classList.remove('hidden');
}

function editFoodstuff(id) {
    document.getElementById('modalTitle').textContent = 'Edit Foodstuff';
    document.getElementById('foodstuffId').value = id;
    
    // Fetch foodstuff details
    fetch(`get_foodstuff.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('name').value = data.name;
            document.getElementById('description').value = data.description;
            document.getElementById('quantity').value = data.quantity;
            document.getElementById('unit').value = data.unit;
            document.getElementById('status').value = data.status;
            document.getElementById('foodstuffModal').classList.remove('hidden');
        })
        .catch(error => console.error('Error:', error));
}

function closeFoodstuffModal() {
    document.getElementById('foodstuffModal').classList.add('hidden');
    document.getElementById('foodstuffForm').reset();
}

function deleteFoodstuff(id) {
    if (confirm('Are you sure you want to delete this foodstuff?')) {
        fetch('delete_foodstuff.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete foodstuff');
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

// Form submission handler
document.getElementById('foodstuffForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const id = document.getElementById('foodstuffId').value;
    formData.append('action', id ? 'edit' : 'add');
    
    fetch('process_foodstuff.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to save foodstuff');
        }
    })
    .catch(error => console.error('Error:', error));
});
</script>
</body>
</html>