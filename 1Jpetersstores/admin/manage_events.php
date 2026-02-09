<?php
session_start();
require_once '../config/database.php';
require_once 'check_admin.php';
checkAdmin();



// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $eventManager = new AdminEventManager(); // Updated class name here
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    switch ($_POST['action']) {
        case 'add':
            $response = $eventManager->addEvent($_POST);
            break;
            
        case 'update':
            if (isset($_POST['id'])) {
                $response = $eventManager->updateEvent($_POST['id'], $_POST);
            }
            break;
            
        case 'delete':
            if (isset($_POST['id'])) {
                $response = $eventManager->deleteEvent($_POST['id']);
            }
            break;
            
        case 'get':
            if (isset($_POST['id'])) {
                $event = $eventManager->getEvent($_POST['id']);
                if ($event) {
                    $response = ['success' => true, 'data' => $event];
                }
            }
            break;
            
        case 'get_registrations':
            if (isset($_POST['event_id'])) {
                $registrations = $eventManager->getRegistrations($_POST['event_id']);
                if ($registrations) {
                    $response = ['success' => true, 'data' => $registrations];
                }
            }
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle page load
$eventManager = new AdminEventManager();
$search = $_GET['search'] ?? '';
$events = $eventManager->getEvents($search);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include 'header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Search and Add Event Button -->
        <div class="flex justify-between items-center mb-6">
            <form class="flex gap-4">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                       class="border rounded-lg px-4 py-2 w-80" 
                       placeholder="Search events...">
                <button type="submit" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    Search
                </button>
            </form>
            <button onclick="openEventModal()" 
                    class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                Add New Event
            </button>
        </div>

        <!-- Events Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Registrations</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($events as $event): ?>
                    <tr>
                        <td class="px-6 py-4">
                            <?php echo htmlspecialchars($event['title']); ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php echo date('M d, Y g:i A', strtotime($event['event_date'])); ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php echo htmlspecialchars($event['location']); ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo $event['event_type'] === 'training' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                <?php echo ucfirst($event['event_type']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo $event['status'] === 'upcoming' ? 'bg-green-100 text-green-800' : 
                                    ($event['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'); ?>">
                                <?php echo ucfirst($event['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <button onclick="viewRegistrations(<?php echo $event['id']; ?>)"
                                    class="text-blue-600 hover:text-blue-900">
                                <?php echo $event['registrations']; ?> Registered
                            </button>
                        </td>
                        <td class="px-6 py-4 space-x-2">
                            <button onclick="editEvent(<?php echo $event['id']; ?>)"
                                    class="text-blue-600 hover:text-blue-900">Edit</button>
                            <button onclick="deleteEvent(<?php echo $event['id']; ?>)"
                                    class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Event Modal -->
    <div id="eventModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-lg bg-white">
            <div class="flex justify-between items-center pb-3">
                <h3 class="text-lg font-semibold" id="modalTitle">Add New Event</h3>
                <button onclick="closeEventModal()">&times;</button>
            </div>
            <form id="eventForm" class="space-y-4">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="id" id="eventId">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" name="title" id="eventTitle" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="eventDescription" rows="3" required
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date</label>
                        <input type="datetime-local" name="event_date" id="eventDate" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Location</label>
                        <input type="text" name="location" id="eventLocation" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type</label>
                        <select name="event_type" id="eventType" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="meeting">Meeting</option>
                            <option value="training">Training</option>
                            <option value="social">Social</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="eventStatus" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="upcoming">Upcoming</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Maximum Participants</label>
                    <input type="number" name="max_participants" id="eventMaxParticipants" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>

                <div class="flex justify-end pt-4">
                    <button type="button" onclick="closeEventModal()"
                            class="bg-gray-500 text-white px-4 py-2 rounded-lg mr-2">Cancel</button>
                    <button type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg">Save Event</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Registrations Modal -->
    <div id="registrationsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-lg bg-white">
            <div class="flex justify-between items-center pb-3">
                <h3 class="text-lg font-semibold">Event Registrations</h3>
                <button onclick="closeRegistrationsModal()">&times;</button>
            </div>
            <div id="registrationsList" class="mt-4">
                <!-- Registrations will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        // Form handling
        document.getElementById('eventForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            try {
                const response = await fetch('manage_events.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Error processing request');
            }
        });

        // Modal functions
        function openEventModal() {
            document.getElementById('eventModal').classList.remove('hidden');
            document.getElementById('modalTitle').textContent = 'Add New Event';
            document.getElementById('eventForm').reset();
            document.querySelector('input[name="action"]').value = 'add';
        }

        function closeEventModal() {
            document.getElementById('eventModal').classList.add('hidden');
        }

        async function editEvent(id) {
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', id);
            
            try {
                const response = await fetch('manage_events.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    const event = result.data;
                    document.getElementById('eventId').value = event.id;
                    document.getElementById('eventTitle').value = event.title;
                    document.getElementById('eventDescription').value = event.description;
                    document.getElementById('eventDate').value = event.event_date.slice(0, 16);
                    document.getElementById('eventLocation').value = event.location;
                    document.getElementById('eventType').value = event.event_type;
                    document.getElementById('eventStatus').value = event.status;
                    document.getElementById('eventMaxParticipants').value = event.max_participants;
                    
                    document.getElementById('modalTitle').textContent = 'Edit Event';
                    document.querySelector('input[name="action"]').value = 'update';
                    document.getElementById('eventModal').classList.remove('hidden');
                }
            } catch (error) {
                alert('Error loading event data');
            }
        }

        async function deleteEvent(id) {
            if (!confirm('Are you sure you want to delete this event?')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            
            try {
                const response = await fetch('manage_events.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Error processing request');
            }
        }

        async function viewRegistrations(eventId) {
            const formData = new FormData();
            formData.append('action', 'get_registrations');
            formData.append('event_id', eventId);
            
            try {
                const response = await fetch('manage_events.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    const registrations = result.data;
                    let html = `
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Registration Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                    `;
                    
                    registrations.forEach(reg => {
                        html += `
                            <tr>
                                <td class="px-6 py-4">${reg.fullname}</td>
                                <td class="px-6 py-4">${reg.email}</td>
                                <td class="px-6 py-4">${reg.phone}</td>
                                <td class="px-6 py-4">${new Date(reg.registration_date).toLocaleString()}</td>
                                <td class="px-6 py-4">${reg.attendance_status || 'Pending'}</td>
                            </tr>
                        `;
                    });
                    
                    html += `
                            </tbody>
                        </table>
                    `;
                    
                    document.getElementById('registrationsList').innerHTML = html;
                    document.getElementById('registrationsModal').classList.remove('hidden');
                }
            } catch (error) {
                alert('Error loading registrations');
            }
        }

        function closeRegistrationsModal() {
            document.getElementById('registrationsModal').classList.add('hidden');
        }
    </script>
</body>
</html>