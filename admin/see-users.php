<?php
session_start();
require_once "../classes/database.php";

// Restrict to admin only
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../log-in/login.php");
    exit;
}

$db = new Database();
$conn = $db->connect();

// ✅ Real-time AJAX search
if (isset($_GET['ajax']) && isset($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $stmt = $conn->prepare("
        SELECT id AS user_id, fname, mname, lname, email, user_type, profile_image, created_at
        FROM users
        WHERE user_type = 'user'
        AND (fname LIKE :search OR lname LIKE :search OR email LIKE :search)
        ORDER BY created_at DESC
    ");
    $stmt->bindValue(':search', $search, PDO::PARAM_STR);
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// ✅ Default display
$stmt = $conn->prepare("
    SELECT id AS user_id, fname, mname, lname, email, user_type, profile_image, created_at
    FROM users
    WHERE user_type = 'user' AND is_deleted = 0
    ORDER BY created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Manage Users</title>
<link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
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
}
#sidebar .nav-link:hover {
    background-color: #f0e3e7;
    border-radius: 0.35rem;
}
#sidebar .nav-link.active {
    background-color: #1a1818;
    color: #fff;
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
.top-navbar .form-control::placeholder { color: #aaa; }
.btn-red {
    background-color: #b02e24;
    color: #fff;
    border: none;
}
.btn-red:hover {
    background-color: #8c1f1b;
}
.btn-search {
    background-color: #1b201eff;
    color: #fff;
    border: none;
}
.btn-search:hover {
    background-color: #121514ff;
}
.profile-pic {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
}
.logout-img {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fff;
    margin-left: 12px;
}
.guide-bg {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 10px;
}
.guide-overlay {
    position: absolute;
    bottom: 10px;
    left: 10px;
    color: #fff;
    background: rgba(0,0,0,0.4);
    padding: 5px 10px;
    border-radius: 5px;
}
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
            <li class="nav-item mb-2"><a class="nav-link active" href="see-users.php">Users</a></li>
            <li class="nav-item mb-2"><a class="nav-link" href="see-all-bookings.php">Bookings</a></li>
            <li class="nav-item mb-2"><a class="nav-link" href="profile.php">Profile</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="col-md-10 ms-sm-auto col-lg-10 px-md-4">
        <!-- Top Navbar -->
        <nav class="navbar top-navbar px-3 py-3 mb-4">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <span class="navbar-brand mb-0 h1 text-white">Admin Panel</span>
                <form class="d-flex" onsubmit="return false;">
                    <input id="searchBox" class="form-control me-2" type="search" placeholder="Search users...">
                    <button id="searchBtn" class="btn btn-search">Search</button>
                </form>
                <div class="d-flex align-items-center">
                    <a href="../log-in/login.php" class="btn btn-red">Logout</a>
                    <img src="../admin/uploads/puc.jpeg" alt="Admin" class="logout-img">
                </div>
            </div>
        </nav>

        <h2>All Users</h2>
        <?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-info"><?= htmlspecialchars($_SESSION['message']) ?></div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

        <div id="userTableContainer">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Profile</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>User Type</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                <?php if (empty($users)): ?>
                    <tr><td colspan="7" class="text-center text-muted">No users found.</td></tr>
                <?php else: $count = 1; foreach ($users as $u): ?>
                    <?php $fullPath = "../log-in/uploads/" . ($u['profile_image'] ?: 'default.jpg'); ?>
                    <tr>
                        <td><?= $count++; ?></td>
                        <td><img src="<?= htmlspecialchars($fullPath) ?>" class="profile-pic"></td>
                        <td><?= htmlspecialchars($u['fname'] . ' ' . ($u['mname'] ? $u['mname'] . ' ' : '') . $u['lname']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= ucfirst($u['user_type']) ?></td>
                        <td><?= htmlspecialchars($u['created_at']) ?></td>
                        <td>
                           <a href="delete-u.php?id=<?= $u['user_id'] ?>" class="btn btn-sm btn-red" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</div>

<script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
<script>
const searchBox = document.getElementById('searchBox');
const searchBtn = document.getElementById('searchBtn');
const userTableBody = document.getElementById('userTableBody');

function performSearch() {
    const query = searchBox.value.trim();
    fetch(`?ajax=1&search=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            userTableBody.innerHTML = "";
            if (data.length === 0) {
                userTableBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No users found.</td></tr>';
                return;
            }
            let count = 1;
            data.forEach(u => {
                const imgPath = '../log-in/uploads/' + (u.profile_image || 'default.jpg');
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${count++}</td>
                    <td><img src="${imgPath}" class="profile-pic"></td>
                    <td>${u.fname} ${u.mname ? u.mname + ' ' : ''}${u.lname}</td>
                    <td>${u.email}</td>
                    <td>${u.user_type.charAt(0).toUpperCase() + u.user_type.slice(1)}</td>
                    <td>${u.created_at}</td>
                    <td>
                        <a href="edit-user.php?id=${u.user_id}" class="btn btn-sm btn-primary">Edit</a>
                        <a href="delete-user.php?id=${u.user_id}" class="btn btn-sm btn-red" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                    </td>`;
                userTableBody.appendChild(tr);
            });
        })
        .catch(err => console.error(err));
}

searchBox.addEventListener('input', performSearch);
searchBtn.addEventListener('click', performSearch);
</script>
</body>
</html>
