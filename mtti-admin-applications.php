<?php
/**
 * MTTI Applications Admin Dashboard
 * Access: https://masomoteletraining.co.ke/mtti-admin-applications.php
 *
 * NOTE: Add basic authentication for security
 */

// Simple authentication - change this password!
if (!isset($_GET['key']) || $_GET['key'] !== 'MTTI2026Admin123') {
    header('HTTP/1.0 403 Forbidden');
    die('Access Denied. Invalid authentication key.');
}

$appDir = dirname(__FILE__) . '/wp-content/mtti-applications';

// Get all applications
$applications = array();
if (is_dir($appDir)) {
    $files = glob($appDir . '/*.json');
    foreach ($files as $file) {
        $data = json_decode(file_get_contents($file), true);
        if ($data) {
            $applications[] = $data;
        }
    }
}

// Sort by date (newest first)
usort($applications, function($a, $b) {
    return strtotime($b['submittedAt']) - strtotime($a['submittedAt']);
});

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MTTI Applications Admin Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 5px;
            margin-bottom: 30px;
        }

        .header h1 {
            margin-bottom: 10px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card h3 {
            color: #667eea;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .stat-card p {
            color: #666;
            font-size: 14px;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .filters input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-right: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-radius: 5px;
            overflow: hidden;
        }

        th {
            background: #1b5e20;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: bold;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .action-btn {
            background: #667eea;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
            text-decoration: none;
            display: inline-block;
        }

        .action-btn:hover {
            background: #764ba2;
        }

        .empty {
            background: white;
            padding: 40px;
            text-align: center;
            color: #666;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 5px;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-close {
            float: right;
            cursor: pointer;
            font-size: 24px;
            color: #999;
        }

        .modal-close:hover {
            color: #333;
        }

        .info-row {
            margin: 15px 0;
            display: flex;
        }

        .info-label {
            font-weight: bold;
            width: 150px;
            color: #667eea;
        }

        .info-value {
            flex: 1;
        }

        @media (max-width: 768px) {
            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px;
            }

            .action-btn {
                display: block;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>MTTI Applications Dashboard</h1>
            <p>Manage and review course applications from learners</p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3><?php echo count($applications); ?></h3>
                <p>Total Applications</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count(array_filter($applications, fn($a) => $a['status'] === 'Pending Review')); ?></h3>
                <p>Pending Review</p>
            </div>
            <div class="stat-card">
                <h3><?php
                    $courses = array();
                    foreach ($applications as $app) {
                        $courses = array_merge($courses, $app['courses']);
                    }
                    echo count(array_count_values($courses));
                ?></h3>
                <p>Popular Courses</p>
            </div>
        </div>

        <div class="filters">
            <input type="text" id="searchInput" placeholder="Search by name or email..." onkeyup="filterTable()">
        </div>

        <?php if (empty($applications)): ?>
            <div class="empty">
                <p>No applications yet. Applicants will appear here when they submit the online form.</p>
            </div>
        <?php else: ?>
            <table id="applicationsTable">
                <thead>
                    <tr>
                        <th>Admission ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>WhatsApp</th>
                        <th>Courses</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><strong><?php echo $app['applicationId']; ?></strong></td>
                            <td><?php echo $app['firstName'] . ' ' . $app['lastName']; ?></td>
                            <td><?php echo $app['email']; ?></td>
                            <td><a href="https://wa.me/<?php echo preg_replace('/[^\d\+]/', '', $app['phone']); ?>"><?php echo $app['phone']; ?></a></td>
                            <td><?php echo count($app['courses']); ?> course(s)</td>
                            <td><span class="status status-pending"><?php echo $app['status']; ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($app['submittedAt'])); ?></td>
                            <td>
                                <button class="action-btn" onclick="viewDetails('<?php echo htmlspecialchars(json_encode($app)); ?>')">View</button>
                                <a href="/admission/<?php echo $app['applicationId']; ?>/" target="_blank" class="action-btn">Letter</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Modal for viewing details -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle"></h2>
            <div id="modalBody"></div>
        </div>
    </div>

    <script>
        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('applicationsTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let match = false;

                for (let j = 0; j < cells.length; j++) {
                    if (cells[j].textContent.toUpperCase().includes(filter)) {
                        match = true;
                        break;
                    }
                }

                rows[i].style.display = match ? '' : 'none';
            }
        }

        function viewDetails(jsonStr) {
            const app = JSON.parse(jsonStr);
            const modal = document.getElementById('detailsModal');
            const title = document.getElementById('modalTitle');
            const body = document.getElementById('modalBody');

            title.textContent = app.firstName + ' ' + app.lastName;

            let html = `
                <div class="info-row">
                    <div class="info-label">Admission ID:</div>
                    <div class="info-value"><strong>${app.applicationId}</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value"><a href="mailto:${app.email}">${app.email}</a></div>
                </div>
                <div class="info-row">
                    <div class="info-label">WhatsApp:</div>
                    <div class="info-value"><a href="https://wa.me/${app.phone.replace(/[^\d\+]/g, '')}">${app.phone}</a></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Date of Birth:</div>
                    <div class="info-value">${app.dob}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Education:</div>
                    <div class="info-value">${app.qualification}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Selected Courses:</div>
                    <div class="info-value">${app.courses.join(', ')}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Start Date:</div>
                    <div class="info-value">${app.startDate}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Payment Method:</div>
                    <div class="info-value">${app.paymentMethod}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Submitted:</div>
                    <div class="info-value">${app.submittedAt}</div>
                </div>
                ${app.experience ? `
                <div class="info-row">
                    <div class="info-label">Experience:</div>
                    <div class="info-value">${app.experience}</div>
                </div>
                ` : ''}
            `;

            body.innerHTML = html;
            modal.classList.add('active');
        }

        function closeModal() {
            document.getElementById('detailsModal').classList.remove('active');
        }

        window.onclick = function(event) {
            const modal = document.getElementById('detailsModal');
            if (event.target === modal) {
                modal.classList.remove('active');
            }
        }
    </script>
</body>
</html>
