<?php
session_start();
require_once "../classes/database.php";

// Only admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../log-in/login.php");
    exit;
}

$db = new Database();
$conn = $db->connect();

$admin_id = $_SESSION['user_id'];
$errors = [];
$success = "";

// Fetch admin data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id AND user_type='admin'");
$stmt->bindValue(':id', $admin_id, PDO::PARAM_INT);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validation
    if (empty($fname)) $errors[] = "First name is required.";
    if (empty($lname)) $errors[] = "Last name is required.";
    if (empty($email)) $errors[] = "Email is required.";

    // Handle image upload
    $imagePath = $admin['image'] ?? 'puc.jpeg';
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $filename = 'admin_' . $admin_id . '.' . $ext;
        $target = "../admin/uploads/" . $filename;
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target)) {
            $imagePath = $filename;
        } else {
            $errors[] = "Failed to upload image.";
        }
    }

    // Update if no errors
    if (empty($errors)) {
        $updateQuery = "UPDATE users SET fname=:fname, lname=:lname, email=:email, image=:image";
        $params = [
            ':fname' => $fname,
            ':lname' => $lname,
            ':email' => $email,
            ':image' => $imagePath
        ];

        // Update password if provided
        if (!empty($password)) {
            $updateQuery .= ", password=:password";
            $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        $updateQuery .= " WHERE id=:id";
        $params[':id'] = $admin_id;

        $stmt = $conn->prepare($updateQuery);
        if ($stmt->execute($params)) {
            $success = "Profile updated successfully!";
            // Refresh admin data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id AND user_type='admin'");
            $stmt->bindValue(':id', $admin_id, PDO::PARAM_INT);
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $errors[] = "Failed to update profile.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Profile</title>
<link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
#sidebar { background-color: #fff; border-right: 1px solid #e0e5ec; min-height: 100vh; }
#sidebar .nav-link.active { background-color: #1a1818; color: #fff; border-radius: 0.35rem; }
.profile-pic { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; }
.card { border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
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
            <li class="nav-item mb-2"><a class="nav-link" href="see-all-bookings.php">Bookings</a></li>
            <li class="nav-item mb-2"><a class="nav-link active" href="profile.php">Profile</a></li>
        </ul>
    </nav>

    <!-- Main -->
    <main class="col-md-10 ms-sm-auto col-lg-10 px-md-4 py-4">
        <h2>Admin Profile</h2>

        <!-- Alerts -->
        <?php if($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if($errors): ?>
            <div class="alert alert-danger">
                <ul><?php foreach($errors as $e) echo "<li>$e</li>"; ?></ul>
            </div>
        <?php endif; ?>

        <div class="card p-4">
            <form method="POST" enctype="multipart/form-data">
                <div class="d-flex mb-4">
                    <div>
                        <img id="preview" src="../admin/uploads/<?= htmlspecialchars($admin['image'] ?? 'puc.jpeg') ?>" class="profile-pic">
                        <input type="file" name="profile_image" id="profile_image" class="form-control mt-2">
                    </div>
                    <div class="ms-4 flex-grow-1">
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="fname" class="form-control" value="<?= htmlspecialchars($admin['fname']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="lname" class="form-control" value="<?= htmlspecialchars($admin['lname']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                        <button class="btn btn-success">Update Profile</button>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>
</div>

<script>
const inputFile = document.getElementById('profile_image');
const preview = document.getElementById('preview');
inputFile.addEventListener('change', e => {
    const file = e.target.files[0];
    if(file){
        const reader = new FileReader();
        reader.onload = e => preview.src = e.target.result;
        reader.readAsDataURL(file);
    }
});
</script>

</body>
</html>
