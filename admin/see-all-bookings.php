<?php
session_start();
require_once "../classes/database.php";

// --- Admin Access Only ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../log-in/login.php");
    exit;
}

$db = new Database();
$conn = $db->connect();

// --- AJAX Search ---
if (isset($_GET['ajax']) && isset($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $stmt = $conn->prepare("
        SELECT 'hire' AS type, hr.payment, hr.payment_mode, hr.created_at,
               u.fname AS user_fname, u.lname AS user_lname, u.email AS user_email,
               g_user.fname AS guide_fname, g_user.lname AS guide_lname
        FROM hire_requests hr
        JOIN users u ON hr.user_id = u.id
        LEFT JOIN guides g ON hr.guide_id = g.id
        LEFT JOIN users g_user ON g.user_id = g_user.id
        WHERE hr.status = 'completed'
          AND (u.fname LIKE :search OR u.lname LIKE :search OR u.email LIKE :search
               OR g_user.fname LIKE :search OR g_user.lname LIKE :search)

        UNION ALL

        SELECT 'custom' AS type, cr.payment, cr.payment_mode, cr.created_at,
               u.fname AS user_fname, u.lname AS user_lname, u.email AS user_email,
               g_user.fname AS guide_fname, g_user.lname AS guide_lname
        FROM custom_requests cr
        JOIN users u ON cr.user_id = u.id
        LEFT JOIN guides g ON cr.guide_id = g.id
        LEFT JOIN users g_user ON g.user_id = g_user.id
        WHERE cr.status = 'completed'
          AND (u.fname LIKE :search OR u.lname LIKE :search OR u.email LIKE :search
               OR g_user.fname LIKE :search OR g_user.lname LIKE :search)
        ORDER BY created_at DESC
    ");
    $stmt->bindValue(':search', $search, PDO::PARAM_STR);
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// --- Fetch Completed Bookings ---
$stmt = $conn->prepare("
    SELECT 'hire' AS type, hr.payment, hr.payment_mode, hr.created_at,
           u.fname AS user_fname, u.lname AS user_lname, u.email AS user_email,
           g_user.fname AS guide_fname, g_user.lname AS guide_lname
    FROM hire_requests hr
    JOIN users u ON hr.user_id = u.id
    LEFT JOIN guides g ON hr.guide_id = g.id
    LEFT JOIN users g_user ON g.user_id = g_user.id
    WHERE hr.status = 'completed'

    UNION ALL

    SELECT 'custom' AS type, cr.payment, cr.payment_mode, cr.created_at,
           u.fname AS user_fname, u.lname AS user_lname, u.email AS user_email,
           g_user.fname AS guide_fname, g_user.lname AS guide_lname
    FROM custom_requests cr
    JOIN users u ON cr.user_id = u.id
    LEFT JOIN guides g ON cr.guide_id = g.id
    LEFT JOIN users g_user ON g.user_id = g_user.id
    WHERE cr.status = 'completed'
    ORDER BY created_at DESC
");
$stmt->execute();
$completed = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Total Completed Payments ---
$totalStmt = $conn->query("
    SELECT 
        (SELECT IFNULL(SUM(payment),0) FROM hire_requests WHERE status='completed')
        +
        (SELECT IFNULL(SUM(payment),0) FROM custom_requests WHERE status='completed')
        AS total_completed_payments
");
$total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total_completed_payments'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Completed Bookings - Admin</title>
<link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
#sidebar { background-color: #fff; border-right: 1px solid #e0e5ec; min-height: 100vh; }
#sidebar .nav-link { color: #363d47; font-weight: 500; transition: 0.3s; }
#sidebar .nav-link.active { background-color: #1a1818; color: #fff; border-radius: 0.35rem; }
.top-navbar { background-color: #212832; border-bottom: 1px solid #363d47; }
.top-navbar .form-control { background-color: #2c3038; color: #fff; border: 1px solid #363d47; }
.top-navbar .btn-red { background-color: #b02e24; color: #fff; border: none; }
.top-navbar .btn-red:hover { background-color: #8c1f1b; }
.profile-pic { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; margin-left: 10px; }
.card-summary { background-color: #fff; border-left: 5px solid #198754; padding: 15px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.table th, .table td { vertical-align: middle; }
</style>
</head>
<body>
<div class="container-fluid">
<div class="row">
    <!-- Sidebar -->
    <nav id="sidebar" class="col-md-2 d-none d-md-block sidebar py-4">
        <ul class="nav flex-column">
            <li class="nav-item mb-2"><a class="nav-link" href="admin-dashboard.php">Dashboard</a></li>
            <li class="nav-item mb-2"><a class="nav-link" href="see-guides.php">Guides</a></li>
            <li class="nav-item mb-2"><a class="nav-link" href="see-users.php">Users</a></li>
            <li class="nav-item mb-2"><a class="nav-link active" href="see-all-bookings.php">Bookings</a></li>
            <li class="nav-item mb-2"><a class="nav-link" href="profile.php">Profile</a></li>
        </ul>
    </nav>

    <!-- Main -->
    <main class="col-md-10 ms-sm-auto col-lg-10 px-md-4 py-4">
        <!-- Navbar -->
        <nav class="navbar top-navbar px-3 py-3 mb-4">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <span class="navbar-brand mb-0 h1 text-white">Admin Panel</span>
                <form class="d-flex" onsubmit="return false;">
                    <input id="searchBox" class="form-control me-2" type="search" placeholder="Search completed bookings...">
                    <button id="searchBtn" class="btn btn-outline-light" type="button">Search</button>
                </form>
                <div class="d-flex align-items-center">
                    <a href="../log-in/login.php" class="btn btn-red">Logout</a>
                    <img src="../admin/uploads/puc.jpeg" class="profile-pic" alt="Admin">
                </div>
            </div>
        </nav>

        <!-- Total Completed Payments -->
        <div class="card-summary mb-4">
            <h5>Total Completed Payments:</h5>
            <h3 class="text-success fw-bold">₱<?= number_format($total, 2) ?></h3>
        </div>

        <!-- Completed Bookings Table -->
        <h2>Completed Bookings</h2>
        <table class="table table-striped table-hover align-middle text-center">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Guide</th>
                    <th>Payment</th>
                    <th>Payment Mode</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="completedTableBody">
                <?php if(empty($completed)): ?>
                    <tr><td colspan="7" class="text-center text-muted">No completed bookings found.</td></tr>
                <?php else: $count=1; foreach($completed as $b): ?>
                <tr>
                    <td><?= $count++ ?></td>
                    <td><?= htmlspecialchars($b['user_fname'].' '.$b['user_lname']) ?><br><small><?= htmlspecialchars($b['user_email']) ?></small></td>
                    <td><?= $b['guide_fname'] ? htmlspecialchars($b['guide_fname'].' '.$b['guide_lname']) : '-' ?></td>
                    <td>₱<?= number_format($b['payment'], 2) ?></td>
                    <td><?= htmlspecialchars($b['payment_mode']) ?></td>
                    <td><?= htmlspecialchars($b['created_at']) ?></td>
                    <td><span class="badge bg-success">Completed</span></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </main>
</div>
</div>

<script>
const searchBox = document.getElementById('searchBox');
const searchBtn = document.getElementById('searchBtn');
const tableBody = document.getElementById('completedTableBody');

function searchCompleted() {
    const q = searchBox.value.trim();
    fetch(`?ajax=1&search=${encodeURIComponent(q)}`)
        .then(res => res.json())
        .then(data => {
            tableBody.innerHTML = "";
            if (data.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No results found.</td></tr>';
                return;
            }
            let count = 1;
            data.forEach(b => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${count++}</td>
                    <td>${b.user_fname} ${b.user_lname}<br><small>${b.user_email}</small></td>
                    <td>${b.guide_fname ? b.guide_fname + ' ' + b.guide_lname : '-'}</td>
                    <td>₱${parseFloat(b.payment).toLocaleString()}</td>
                    <td>${b.payment_mode}</td>
                    <td>${b.created_at}</td>
                    <td><span class="badge bg-success">Completed</span></td>
                `;
                tableBody.appendChild(tr);
            });
        })
        .catch(err => console.error(err));
}

searchBox.addEventListener('input', searchCompleted);
searchBtn.addEventListener('click', searchCompleted);
</script>
</body>
</html>
