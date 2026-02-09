<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Initialize database connection
$conn = connectDB();

// Initialize EventManager
$eventManager = new EventManager($_SESSION['user_id']);

// Process event registration
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_event'])) {
    try {
        $event_id = filter_var($_POST['event_id'], FILTER_VALIDATE_INT);
        
        if ($eventManager->registerForEvent($event_id)) {
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                          Successfully registered for the event!</div>';
        } else {
            throw new Exception("You are already registered for this event.");
        }
    } catch (Exception $e) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                      Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Fetch upcoming events
$upcoming_sql = "SELECT e.*, 
                 (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registered_count,
                 (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id AND user_id = ?) as is_registered
                 FROM events e 
                 WHERE e.event_date > NOW() 
                 AND e.status = 'upcoming'
                 AND e.registration_deadline > NOW()
                 ORDER BY e.event_date";
$stmt = $conn->prepare($upcoming_sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$upcoming_events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch user's registered events
$registered_sql = "SELECT e.*, er.registration_date, er.attendance_status
                  FROM event_registrations er
                  JOIN events e ON er.event_id = e.id
                  WHERE er.user_id = ?
                  ORDER BY e.event_date DESC";
$stmt = $conn->prepare($registered_sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$registered_events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - JpetersMBS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include 'includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <?php echo $message; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Upcoming Events -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Upcoming Events</h2>
                    <div class="space-y-6">
                        <?php if (!empty($upcoming_events)): ?>
                            <?php foreach ($upcoming_events as $event): ?>
                                <div class="border rounded-lg p-4 <?php echo strtotime($event['registration_deadline']) < time() ? 'opacity-50' : ''; ?>">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="font-medium text-lg text-gray-900"><?php echo htmlspecialchars($event['title']); ?></h3>
                                            <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($event['description']); ?></p>
                                        </div>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                                   <?php echo $event['event_type'] === 'training' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                            <?php echo ucfirst($event['event_type']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <p class="text-gray-600">Date & Time:</p>
                                            <p class="font-medium"><?php echo date('F j, Y g:i A', strtotime($event['event_date'])); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-600">Location:</p>
                                            <p class="font-medium"><?php echo htmlspecialchars($event['location']); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-600">Registration Deadline:</p>
                                            <p class="font-medium"><?php echo date('F j, Y', strtotime($event['registration_deadline'])); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-600">Capacity:</p>
                                            <p class="font-medium"><?php echo $event['registered_count']; ?>/<?php echo $event['capacity']; ?> registered</p>
                                        </div>
                                    </div>

                                    <?php if (!$event['is_registered'] && $event['registered_count'] < $event['capacity'] && strtotime($event['registration_deadline']) > time()): ?>
                                        <form action="" method="POST" class="mt-4">
                                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                            <button type="submit" name="register_event"
                                                    class="w-full bg-purple-600 text-white py-2 px-4 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                                Register for Event
                                            </button>
                                        </form>
                                    <?php elseif ($event['is_registered']): ?>
                                        <p class="mt-4 text-center text-green-600 font-medium">You are registered for this event</p>
                                    <?php elseif ($event['registered_count'] >= $event['capacity']): ?>
                                        <p class="mt-4 text-center text-red-600 font-medium">Event is full</p>
                                    <?php elseif (strtotime($event['registration_deadline']) <= time()): ?>
                                        <p class="mt-4 text-center text-red-600 font-medium">Registration closed</p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center text-gray-500 py-4">No upcoming events at this time.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Your Registered Events -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Your Registered Events</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registration Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($registered_events as $event): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($event['title']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($event['location']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M d, Y g:i A', strtotime($event['event_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M d, Y', strtotime($event['registration_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                   <?php echo $event['attendance_status'] === 'attended' ? 'bg-green-100 text-green-800' : 
                                                         ($event['attendance_status'] === 'absent' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                            <?php echo ucfirst($event['attendance_status'] ?? 'pending'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (empty($registered_events)): ?>
                        <p class="text-center text-gray-500 py-4">You haven't registered for any events yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add any JavaScript enhancements here
        const registrationForms = document.querySelectorAll('form');
        registrationForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to register for this event?')) {
                    e.preventDefault();
                }
            });
        });
    });
    </script>
</body>
</html>