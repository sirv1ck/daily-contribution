<?php
session_start();
require_once '../config/database.php';
require_once 'check_admin.php';
checkAdmin();

class ReportGenerator {
    private $conn;
    
    public function __construct() {
        $this->conn = connectDB();
    }
    
    public function getContributionReport($start_date, $end_date) {
        $sql = "SELECT u.fullname, 
                       COUNT(*) as total_contributions,
                       SUM(c.amount) as total_amount,
                       MIN(c.contribution_date) as first_contribution,
                       MAX(c.contribution_date) as last_contribution
                FROM contributions c
                JOIN users u ON c.user_id = u.id
                WHERE c.contribution_date BETWEEN ? AND ?
                GROUP BY u.id, u.fullname
                ORDER BY total_amount DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getEventAttendanceReport($start_date, $end_date) {
        $sql = "SELECT e.title,
                       COUNT(DISTINCT er.user_id) as total_registrations,
                       SUM(CASE WHEN er.attendance_status = 'attended' THEN 1 ELSE 0 END) as attended,
                       e.event_date
                FROM events e
                LEFT JOIN event_registrations er ON e.id = er.event_id
                WHERE e.event_date BETWEEN ? AND ?
                GROUP BY e.id, e.title, e.event_date
                ORDER BY e.event_date DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getFoodstuffDistributionReport($start_date, $end_date) {
        $sql = "SELECT f.name,
                       COUNT(DISTINCT fd.user_id) as total_recipients,
                       SUM(fd.quantity) as total_distributed,
                       f.unit
                FROM foodstuff f
                LEFT JOIN foodstuff_distribution fd ON f.id = fd.foodstuff_id
                WHERE fd.distribution_date BETWEEN ? AND ?
                GROUP BY f.id, f.name, f.unit
                ORDER BY total_distributed DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function exportToCSV($data, $filename) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array_keys($data[0])); // Header row
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
    }
}

$reportGenerator = new ReportGenerator();

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $report_type = $_POST['report_type'];
    
    switch ($report_type) {
        case 'contributions':
            $report_data = $reportGenerator->getContributionReport($start_date, $end_date);
            $report_title = "Contribution Report";
            break;
        case 'events':
            $report_data = $reportGenerator->getEventAttendanceReport($start_date, $end_date);
            $report_title = "Event Attendance Report";
            break;
        case 'foodstuff':
            $report_data = $reportGenerator->getFoodstuffDistributionReport($start_date, $end_date);
            $report_title = "Foodstuff Distribution Report";
            break;
    }
    
    if (isset($_POST['export']) && $_POST['export'] === 'csv') {
        $reportGenerator->exportToCSV($report_data, $report_type . '_report.csv');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Reports - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Generate Reports</h2>
        </div>

        <!-- Report Generation Form -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Report Type</label>
                        <select name="report_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="contributions">Contributions Report</option>
                            <option value="events">Event Attendance Report</option>
                            <option value="foodstuff">Foodstuff Distribution Report</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" name="start_date" required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">End Date</label>
                        <input type="date" name="end_date" required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    
                    <div class="flex items-end space-x-2">
                        <button type="submit" name="generate_report" 
                                class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                            <i class="fas fa-chart-bar mr-2"></i>Generate Report
                        </button>
                        <button type="submit" name="export" value="csv" 
                                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                            <i class="fas fa-download mr-2"></i>Export CSV
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Report Display -->
        <?php if (isset($report_data) && !empty($report_data)): ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6 border-b">
                <h3 class="text-xl font-semibold"><?php echo $report_title; ?></h3>
                <p class="text-sm text-gray-600">
                    Period: <?php echo date('M d, Y', strtotime($start_date)); ?> - 
                    <?php echo date('M d, Y', strtotime($end_date)); ?>
                </p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <?php foreach (array_keys($report_data[0]) as $header): ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <?php echo str_replace('_', ' ', $header); ?>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($report_data as $row): ?>
                        <tr>
                            <?php foreach ($row as $value): ?>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php 
                                if (is_numeric($value) && strpos($value, '.') !== false) {
                                    echo number_format($value, 2);
                                } else {
                                    echo htmlspecialchars($value);
                                }
                                ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>