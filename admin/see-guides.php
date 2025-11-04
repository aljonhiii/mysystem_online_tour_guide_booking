<?php
session_start();
require_once "../classes/database.php";

// ✅ Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../log-in/login.php");
    exit;
}

$db = new Database();
$conn = $db->connect();

// ✅ Fetch approved guides
$sql = "
    SELECT g.id AS guide_id, g.expertise, g.languages, g.rate, g.availability, g.status,
           u.fname, u.lname, u.email, u.profile_image
    FROM guides g
    JOIN users u ON g.user_id = u.id
    WHERE g.status = 'approved' AND (u.is_deleted = 0 OR u.is_deleted IS NULL)
    ORDER BY g.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute();
$guides = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ AJAX search
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    $search = trim($_GET['search'] ?? '');
    $query = "
        SELECT g.id AS guide_id, g.expertise, g.languages, g.rate, g.availability, g.status,
               u.fname, u.lname, u.email, u.profile_image
        FROM guides g
        JOIN users u ON g.user_id = u.id
        WHERE g.status = 'approved' AND (u.is_deleted = 0 OR u.is_deleted IS NULL)
          AND (u.fname LIKE :search OR u.lname LIKE :search OR u.email LIKE :search)
        ORDER BY g.created_at DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':search', "%$search%");
    $stmt->execute();
    $guides = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($guides as $index => $g) {
        $imgFile = $g['profile_image'];
        $possiblePaths = [
            "../guide/uploads/$imgFile",
            "../user/uploads/$imgFile",
            "../log-in/uploads/$imgFile",
            "../admin/uploads/$imgFile",
            "uploads/$imgFile"
        ];
        $imagePath = "../guide/uploads/default.png";
        foreach ($possiblePaths as $path) {
            if (!empty($imgFile) && file_exists($path)) {
                $imagePath = $path;
                break;
            }
        }
        $statusClass = match($g['status']) {
            'approved' => 'success',
            'pending' => 'warning',
            'rejected' => 'danger',
            default => 'secondary'
        };
        echo "
        <tr>
            <td>".($index+1)."</td>
            <td><img src='$imagePath' class='profile-pic' alt='Profile'></td>
            <td>{$g['fname']} {$g['lname']}</td>
            <td>{$g['email']}</td>
            <td>{$g['expertise']}</td>
            <td>{$g['languages']}</td>
            <td>".number_format($g['rate'], 2)."</td>
            <td>{$g['availability']}</td>
            <td><span class='badge bg-$statusClass'>".ucfirst($g['status'])."</span></td>
            <td>
                <button class='btn btn-sm btn-danger' onclick='deleteGuide({$g['guide_id']})'>Delete</button>
            </td>
        </tr>";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Guides</title>
    <link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        #sidebar { background-color: #fff; border-right: 1px solid #e0e5ec; min-height: 100vh; }
        #sidebar .nav-link { color: #363d47; font-weight: 500; transition: 0.3s; }
        #sidebar .nav-link:hover { color: #222020ff; background-color: #f0e3e7; border-radius: 0.35rem; }
        #sidebar .nav-link.active { color: #fff; background-color: #1a1818ff; border-radius: 0.35rem; }
        .top-navbar { background-color: #212832; border-bottom: 1px solid #363d47; }
        .top-navbar .form-control { background-color: #2c3038; color: #fff; border: 1px solid #363d47; }
        .top-navbar .form-control::placeholder { color: #aaa; }
        .top-navbar .btn-outline-light { border-color: #fff; color: #fff; }
        .top-navbar .btn-outline-light:hover { background-color: #dc392d; border-color: #dc392d; color: #fff; }
        .profile-pic { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; }
        .btn-red { background-color: #b02e24; color: #fff; }
        .btn-red:hover { background-color: #8c1f1b; color: #fff; }
        .table-actions .btn { padding: 0.25rem 0.5rem; font-size: 0.85rem; }
        .logout-section { display: flex; align-items: center; gap: 12px; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-2 d-none d-md-block sidebar py-4">
            <div class="position-sticky">
                <ul class="nav flex-column">
                    <li class="nav-item mb-2"><a class="nav-link" href="admin-dashboard.php">Dashboard</a></li>
                    <li class="nav-item mb-2"><a class="nav-link active" href="see-guides.php">Guides</a></li>
                    <li class="nav-item mb-2"><a class="nav-link" href="see-users.php">Users</a></li>
                    <li class="nav-item mb-2"><a class="nav-link" href="see-all-bookings.php">Bookings</a></li>
                    <li class="nav-item mb-2"><a class="nav-link" href="profile.php">Profile</a></li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-10 ms-sm-auto col-lg-10 px-md-4">
            <!-- Top Navbar -->
            <nav class="navbar top-navbar px-3 py-3 mb-4">
                <div class="container-fluid d-flex justify-content-between align-items-center">
                    <span class="navbar-brand mb-0 h1 text-white">Admin Panel</span>

                    <!-- AJAX Search bar -->
                    <form class="d-flex" onsubmit="return false;">
                        <input class="form-control me-2" id="searchInput" type="search" placeholder="Search Guides...">
                        <button class="btn btn-outline-light" type="button" onclick="performSearch()">Search</button>
                    </form>

                    <!-- Logout -->
                    <div class="logout-section">
                        <a href="../log-in/login.php" class="btn btn-red">Logout</a>
                        <img src="../admin/uploads/puc.jpeg" alt="Admin" class="profile-pic"
                             onerror="this.src='../admin/uploads/default-admin.png'">
                    </div>
                </div>
            </nav>

            <h2>Approved Guides</h2>
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Profile</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Expertise</th>
                        <th>Languages</th>
                        <th>Rate (₱)</th>
                        <th>Availability</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="guideTableBody">
                    <?php if (count($guides) > 0): ?>
                        <?php $count = 1; foreach ($guides as $g): ?>
                            <?php
                            $imgFile = $g['profile_image'];
                            $possiblePaths = [
                                "../guide/uploads/$imgFile",
                                "../user/uploads/$imgFile",
                                "../log-in/uploads/$imgFile",
                                "../admin/uploads/$imgFile",
                                "uploads/$imgFile"
                            ];
                            $imagePath = "../guide/uploads/default.png";
                            foreach ($possiblePaths as $path) {
                                if (!empty($imgFile) && file_exists($path)) {
                                    $imagePath = $path;
                                    break;
                                }
                            }

                            $statusClass = match($g['status']) {
                                'approved' => 'success',
                                'pending' => 'warning',
                                'rejected' => 'danger',
                                default => 'secondary'
                            };
                            ?>
                            <tr>
                                <td><?= $count++; ?></td>
                                <td><img src="<?= htmlspecialchars($imagePath) ?>" class="profile-pic" alt="Profile"></td>
                                <td><?= htmlspecialchars($g['fname'] . ' ' . $g['lname']) ?></td>
                                <td><?= htmlspecialchars($g['email']) ?></td>
                                <td><?= htmlspecialchars($g['expertise']) ?></td>
                                <td><?= htmlspecialchars($g['languages']) ?></td>
                                <td><?= number_format($g['rate'], 2) ?></td>
                                <td><?= htmlspecialchars($g['availability']) ?></td>
                                <td><span class="badge bg-<?= $statusClass ?>"><?= ucfirst($g['status']) ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-danger" onclick="deleteGuide(<?= $g['guide_id'] ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="10" class="text-center text-muted py-4">No approved guides found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
</div>

<script>
function performSearch() {
    const search = document.getElementById('searchInput').value;
    fetch(`see-guides.php?ajax=1&search=${encodeURIComponent(search)}`)
        .then(res => res.text())
        .then(html => document.getElementById('guideTableBody').innerHTML = html);
}
document.getElementById('searchInput').addEventListener('keyup', performSearch);

function deleteGuide(id) {
    if (confirm("Are you sure you want to delete this guide? This action cannot be undone.")) {
        fetch(`delete-.php?id=${id}`)
            .then(res => res.text())
            .then(data => {
                alert(data);
                performSearch();
            });
    }
}
</script>

<script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
