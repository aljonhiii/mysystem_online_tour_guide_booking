<?php
session_start();
require_once "../classes/database.php";

// ✅ Only admin can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../log-in/login.php");
    exit;
}

$db = new Database();
$conn = $db->connect();

// Get total counts for dashboard cards
$total_guides = $conn->query("SELECT COUNT(*) FROM guides")->fetchColumn();
$pending_guides = $conn->query("SELECT COUNT(*) FROM guides WHERE status = 'pending'")->fetchColumn();
$total_bookings = $conn->query("SELECT COUNT(*) FROM hire_requests")->fetchColumn();

// ✅ Total Completed Payments (from both hire_requests and custom_requests)
$total_hire_payments = $conn->query("SELECT SUM(payment) FROM hire_requests WHERE status = 'completed'")->fetchColumn();
$total_custom_payments = $conn->query("SELECT SUM(payment) FROM custom_requests WHERE status = 'completed'")->fetchColumn();
$total_completed_payments = ($total_hire_payments ?: 0) + ($total_custom_payments ?: 0);

// Get pending guides for approval table
$stmt = $conn->prepare("
    SELECT g.id AS guide_id, u.fname, u.lname, u.email, g.status
    FROM guides g
    JOIN users u ON g.user_id = u.id
    WHERE g.status = 'pending'
    ORDER BY g.created_at DESC
");
$stmt->execute();
$pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        #sidebar {
            background-color: #fff;
            border-right: 1px solid #e0e5ec;
            min-height: 100vh;
        }
        #sidebar .nav-link {
            color: #363d47;
            font-weight: 500;
            transition: 0.3s;
        }
        #sidebar .nav-link:hover {
            color: #222020ff;
            background-color: #f0e3e7;
            border-radius: 0.35rem;
        }
        #sidebar .nav-link.active {
            color: #fff;
            background-color: #1a1818ff;
            border-radius: 0.35rem;
        }
        .top-navbar {
            background-color: #212832;
            border-bottom: 1px solid #363d47;
        }
        .top-navbar .form-control {
            background-color: #2c3038;
            color: #fff;
            border: 1px solid #363d47;
        }
        .top-navbar .form-control::placeholder {
            color: #aaa;
        }
        .top-navbar .btn-outline-light {
            border-color: #fff;
            color: #fff;
        }
        .top-navbar .btn-outline-light:hover {
            background-color: #dc392d;
            border-color: #dc392d;
            color: #fff;
        }
        .card-stats {
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgb(58 59 69 / 15%);
        }
        .btn-red {
            background-color: #0c0b0bff;
            color: #fff;
            transition: 0.3s;
        }
        .btn-red:hover {
            background-color: #b02e24;
            color: #fff;
        }
        .profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-left: 15px;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-2 d-none d-md-block sidebar py-4">
            <div class="position-sticky">
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a class="nav-link active" href="#">Dashboard</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link" href="see-guides.php">Guides</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link" href="see-users.php">Users</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link" href="see-all-bookings.php">Bookings</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-10 ms-sm-auto col-lg-10 px-md-4">
            <!-- Top Navbar -->
            <nav class="navbar top-navbar px-3 py-3 mb-4">
                <div class="container-fluid d-flex justify-content-between align-items-center">
                    <span class="navbar-brand mb-0 h1 text-white">Admin Panel</span>

                    <!-- 🔍 Search Bar for Pending Requests -->
                    <form class="d-flex" id="searchForm">
                        <input class="form-control me-2" type="search" id="searchInput" placeholder="Search Pending Requests">
                        <button class="btn btn-outline-light" type="button">Search</button>
                    </form>

                    <div class="d-flex align-items-center">
                        <a href="../log-in/login.php" class="btn btn-red ms-2">Logout</a>
                        <img src="../admin/uploads/puc.jpeg" alt="Admin" class="profile-pic">
                    </div>
                </div>
            </nav>

            <!-- Dashboard Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card card-stats p-3">
                        <h5>Total Guides</h5>
                        <h3><?= $total_guides ?></h3>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card card-stats p-3">
                        <h5>Pending Approval</h5>
                        <h3><?= $pending_guides ?></h3>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card card-stats p-3">
                        <h5>Bookings</h5>
                        <h3><?= $total_bookings ?></h3>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card card-stats p-3 bg-success text-white">
                        <h5>Total Completed Payments</h5>
                        <h3>₱<?= number_format($total_completed_payments, 2) ?></h3>
                    </div>
                </div>
            </div>

            <!-- Pending Guides Table -->
            <div class="card mb-4">
                <div class="card-header">Pending Guide Approvals</div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
                    <?php elseif (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                    <?php endif; ?>

                    <?php if (count($pending) > 0): ?>
                        <table class="table table-striped table-hover" id="pendingTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $count = 1; foreach ($pending as $p): ?>
                                    <tr>
                                        <td><?= $count++; ?></td>
                                        <td><?= htmlspecialchars($p['fname'] . ' ' . $p['lname']); ?></td>
                                        <td><?= htmlspecialchars($p['email']); ?></td>
                                        <td><span class="badge bg-warning text-dark"><?= ucfirst($p['status']); ?></span></td>
                                        <td>
                                            <form action="process-guide-approval.php" method="POST" class="d-flex gap-2">
                                                <input type="hidden" name="guide_id" value="<?= $p['guide_id']; ?>">
                                                <button name="action" value="approve" class="btn btn-sm btn-red">Approve</button>
                                                <button name="action" value="reject" class="btn btn-sm btn-secondary">Reject</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info">No pending guide requests right now.</div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
<script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById('searchInput');
    const table = document.querySelector('.card-body table'); // safely targets your table
    if (!table || !searchInput) return; // stop if table not found

    searchInput.addEventListener('keyup', function () {
        const filter = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
});
</script>

</body>
</html>
