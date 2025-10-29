<?php
/**
 * Authentication Monitoring Dashboard
 * 
 * Provides monitoring capabilities for authentication failures and security events
 */

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth-logger.php';
require_once __DIR__ . '/../includes/security-manager.php';

// Initialize security manager
$security = new SecurityManager();

// Validate admin access
$access_validation = $security->validateAdminAccess('auth_monitoring');
if (!$access_validation['valid']) {
    http_response_code(403);
    die('Access denied: ' . implode(', ', $access_validation['errors']));
}

// Handle AJAX requests for real-time data
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['ajax']) {
        case 'stats':
            $period = $_GET['period'] ?? '24h';
            $stats = getAuthFailureStats($period);
            echo json_encode($stats);
            break;
            
        case 'recent_events':
            $limit = (int)($_GET['limit'] ?? 20);
            try {
                $db = getDB();
                $stmt = $db->prepare("
                    SELECT timestamp, event_type, category, level, user_id, 
                           ip_address, details, created_at
                    FROM auth_logs 
                    ORDER BY created_at DESC 
                    LIMIT ?
                ");
                $stmt->execute([$limit]);
                $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'events' => $events]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;
            
        default:
            echo json_encode(['error' => 'Invalid request']);
    }
    exit;
}

// Get initial statistics
$stats_24h = getAuthFailureStats('24h');
$stats_7d = getAuthFailureStats('7d');

?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2>Authentication Monitoring Dashboard</h2>
            <p class="text-muted">Monitor authentication failures and security events</p>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">24h Events</h5>
                    <h3 id="total-events-24h"><?php echo $stats_24h['total_events']; ?></h3>
                    <small>Total authentication events</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Failed Logins</h5>
                    <h3 id="failed-logins-24h"><?php echo $stats_24h['by_category']['LOGIN_FAILED'] ?? 0; ?></h3>
                    <small>Last 24 hours</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Critical Errors</h5>
                    <h3 id="critical-errors-24h"><?php echo $stats_24h['by_level']['CRITICAL'] ?? 0; ?></h3>
                    <small>Require immediate attention</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Hash Corruptions</h5>
                    <h3 id="corruptions-24h"><?php echo $stats_24h['by_category']['CORRUPTION'] ?? 0; ?></h3>
                    <small>Database integrity issues</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Time Period Selector -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary active" data-period="24h">24 Hours</button>
                <button type="button" class="btn btn-outline-primary" data-period="7d">7 Days</button>
                <button type="button" class="btn btn-outline-primary" data-period="30d">30 Days</button>
            </div>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-success" onclick="refreshData()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>
    
    <!-- Charts and Tables -->
    <div class="row">
        <!-- Event Categories Chart -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Events by Category</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Log Levels Chart -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Events by Severity Level</h5>
                </div>
                <div class="card-body">
                    <canvas id="levelChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Top IPs with Failed Attempts -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Top IPs with Failed Login Attempts</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>IP Address</th>
                                    <th>Failed Attempts</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="top-ips-table">
                                <?php foreach ($stats_24h['top_ips'] as $ip_data): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ip_data['ip_address']); ?></td>
                                    <td><span class="badge bg-warning"><?php echo $ip_data['count']; ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewIPDetails('<?php echo htmlspecialchars($ip_data['ip_address']); ?>')">
                                            View Details
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Events -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Authentication Events</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Event</th>
                                    <th>Level</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody id="recent-events-table">
                                <!-- Will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Event Details Modal -->
    <div class="modal fade" id="eventDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Event Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <pre id="event-details-content"></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let categoryChart, levelChart;
let currentPeriod = '24h';

// Initialize charts
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    loadRecentEvents();
    
    // Set up period selector
    document.querySelectorAll('[data-period]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('[data-period]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentPeriod = this.dataset.period;
            refreshData();
        });
    });
    
    // Auto-refresh every 30 seconds
    setInterval(refreshData, 30000);
});

function initializeCharts() {
    // Category Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    categoryChart = new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($stats_24h['by_category'])); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($stats_24h['by_category'])); ?>,
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
                    '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    
    // Level Chart
    const levelCtx = document.getElementById('levelChart').getContext('2d');
    levelChart = new Chart(levelCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($stats_24h['by_level'])); ?>,
            datasets: [{
                label: 'Events',
                data: <?php echo json_encode(array_values($stats_24h['by_level'])); ?>,
                backgroundColor: [
                    '#28a745', '#17a2b8', '#ffc107', '#fd7e14', '#dc3545'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function refreshData() {
    fetch(`?ajax=stats&period=${currentPeriod}`)
        .then(response => response.json())
        .then(data => {
            if (data.success !== false) {
                updateStatistics(data);
                updateCharts(data);
                updateTopIPs(data);
            }
        })
        .catch(error => console.error('Error refreshing data:', error));
    
    loadRecentEvents();
}

function updateStatistics(data) {
    document.getElementById('total-events-24h').textContent = data.total_events || 0;
    document.getElementById('failed-logins-24h').textContent = data.by_category['LOGIN_FAILED'] || 0;
    document.getElementById('critical-errors-24h').textContent = data.by_level['CRITICAL'] || 0;
    document.getElementById('corruptions-24h').textContent = data.by_category['CORRUPTION'] || 0;
}

function updateCharts(data) {
    // Update category chart
    categoryChart.data.labels = Object.keys(data.by_category);
    categoryChart.data.datasets[0].data = Object.values(data.by_category);
    categoryChart.update();
    
    // Update level chart
    levelChart.data.labels = Object.keys(data.by_level);
    levelChart.data.datasets[0].data = Object.values(data.by_level);
    levelChart.update();
}

function updateTopIPs(data) {
    const tbody = document.getElementById('top-ips-table');
    tbody.innerHTML = '';
    
    data.top_ips.forEach(ip => {
        const row = tbody.insertRow();
        row.innerHTML = `
            <td>${ip.ip_address}</td>
            <td><span class="badge bg-warning">${ip.count}</span></td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="viewIPDetails('${ip.ip_address}')">
                    View Details
                </button>
            </td>
        `;
    });
}

function loadRecentEvents() {
    fetch('?ajax=recent_events&limit=20')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateRecentEvents(data.events);
            }
        })
        .catch(error => console.error('Error loading recent events:', error));
}

function updateRecentEvents(events) {
    const tbody = document.getElementById('recent-events-table');
    tbody.innerHTML = '';
    
    events.forEach(event => {
        const row = tbody.insertRow();
        const levelClass = getLevelClass(event.level);
        const time = new Date(event.created_at).toLocaleTimeString();
        
        row.innerHTML = `
            <td>${time}</td>
            <td>
                <small>${event.event_type}</small>
                <button class="btn btn-sm btn-link p-0" onclick="showEventDetails(${JSON.stringify(event).replace(/"/g, '&quot;')})">
                    <i class="fas fa-info-circle"></i>
                </button>
            </td>
            <td><span class="badge ${levelClass}">${event.level}</span></td>
            <td>${event.user_id || 'N/A'}</td>
        `;
    });
}

function getLevelClass(level) {
    const classes = {
        'DEBUG': 'bg-secondary',
        'INFO': 'bg-info',
        'WARNING': 'bg-warning',
        'ERROR': 'bg-danger',
        'CRITICAL': 'bg-dark'
    };
    return classes[level] || 'bg-secondary';
}

function showEventDetails(event) {
    const modal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
    document.getElementById('event-details-content').textContent = JSON.stringify(event, null, 2);
    modal.show();
}

function viewIPDetails(ip) {
    // This could be expanded to show detailed IP analysis
    alert(`Detailed analysis for IP ${ip} would be implemented here.`);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>