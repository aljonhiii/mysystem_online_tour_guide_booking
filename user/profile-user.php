<?php
session_start();
require_once "../classes/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../log-in/login.php");
    exit;
}

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) exit("User profile not found.");

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // ✅ Update profile info
        if (isset($_POST['update_profile'])) {
            $address = $_POST['address'];
            $profile_image = $user['profile_image'];

            if (!empty($_FILES['profile_image']['name'])) {
                if (!is_dir("uploads")) mkdir("uploads", 0777, true);
                $img_name = time() . '_' . basename($_FILES['profile_image']['name']);
                move_uploaded_file($_FILES['profile_image']['tmp_name'], "uploads/" . $img_name);
                $profile_image = $img_name;
            }

            $stmt = $conn->prepare("UPDATE users SET address=?, profile_image=? WHERE id=?");
            $stmt->execute([$address, $profile_image, $user_id]);

            $conn->commit();
            header("Location: profile-user.php");
            exit;
        }

        // ✅ Change password
        if (isset($_POST['change_password'])) {
            $current = $_POST['current_password'];
            $new = $_POST['new_password'];
            $confirm = $_POST['confirm_password'];

            if (password_verify($current, $user['password'])) {
                if ($new === $confirm) {
                    $hashed = password_hash($new, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
                    $stmt->execute([$hashed, $user_id]);
                    $conn->commit();
                    $pass_msg = "Password updated successfully!";
                } else {
                    $conn->rollBack();
                    $pass_msg = "New passwords do not match.";
                }
            } else {
                $conn->rollBack();
                $pass_msg = "Current password is incorrect.";
            }
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $pass_msg = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Profile</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family:'Poppins',sans-serif; background:#f1f3f6; margin:0; color:#212832; }

/* Sidebar */
.sidebar { height:100%; width:60px; position:fixed; top:0; left:0; background-color:#1f2937; overflow-x:hidden; transition:width 0.4s; padding-top:20px; z-index:1040; }
.sidebar.expanded { width:250px; padding-top:60px; }
.sidebar img { display:block; margin:0 auto 15px; width:50px; border-radius:50%; transition:width 0.4s; }
.sidebar.expanded img { width:70px; }
.sidebar a { padding:15px; text-decoration:none; font-size:16px; color:#fff; display:flex; align-items:center; transition:all 0.3s; }
.sidebar a i { width:25px; text-align:center; }
.sidebar a span { margin-left:10px; display:none; }
.sidebar.expanded a span { display:inline; }
.sidebar a:hover { color:#3b82f6; }

/* Main content */
#main { transition:margin-left 0.4s; padding:20px; margin-left:250px; }
#main.collapsed { margin-left:60px; }

/* Toggle button */
.toggle-btn { position:fixed; top:15px; left:65px; z-index:1050; cursor:pointer; font-size:1.5rem; color:#1f2937; transition:left 0.4s; }
.toggle-btn.collapsed { left:15px; }

/* Card & Form */
.card { border:none; background:#fff; border-radius:15px; box-shadow:0 4px 16px rgba(0,0,0,0.05); margin:0 auto 30px; padding:20px; max-width:500px; }
.card h3 { font-size:18px; font-weight:600; margin-bottom:15px; }

.card input[type="text"], 
.card input[type="number"], 
.card input[type="file"], 
.card input[type="password"], 
.card select, 
.card textarea { width:100%; padding:8px; margin-top:5px; border-radius:8px; border:1px solid #ccc; font-size:14px; }

textarea { resize:none; }

button, .btn-request { background:#2563eb; color:#fff; border:none; border-radius:8px; padding:10px 20px; cursor:pointer; margin-top:10px; width:100%; }
button:hover, .btn-request:hover { background:#1e40af; }

/* Profile image */
.profile-img, .card-img-top { width:100px; height:100px; border-radius:50%; object-fit:cover; display:block; margin:0 auto 15px; border:3px solid #2563eb; }

/* Tab alerts */
.alert { font-size:14px; padding:8px 12px; }

/* Center text inside cards */
.card-body { text-align:center; }
</style>
</head>
<body>

<!-- Sidebar -->
<div id="mySidebar" class="sidebar expanded">
  <img src="../log-in/Seal_of_Zamboanga_City.png" alt="ZC">
  <a href="index.php"><i class="fa-solid fa-list"></i> <span>Dashboard</span></a>
  <a href="user-profile.php"><i class="fa-solid fa-user-circle"></i> <span>Profile</span></a>
  <a href="../log-in/login.php"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
</div>
<i id="sidebarToggle" class="fa-solid fa-bars toggle-btn"></i>

<!-- Main Content -->
<div id="main">
  <div class="text-center mb-4">
    <h2>My Profile</h2>
  </div>

  <div class="card">
    <ul class="nav nav-tabs mb-3" id="profileTab" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button">Profile Info</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button">Change Password</button>
      </li>
    </ul>

    <div class="tab-content" id="profileTabContent">
      <!-- Profile Info -->
      <div class="tab-pane fade show active" id="info">
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="update_profile">
          <img src="<?= $user['profile_image'] ? 'uploads/'.$user['profile_image']:'uploads/default.jpg' ?>" class="profile-img">
          <p>Change Profile Image:</p>
          <input type="file" name="profile_image" accept="image/*">

          <p>Full Name:</p>
          <input type="text" value="<?= htmlspecialchars($user['fname'].' '.($user['mname']?$user['mname'].' ':'').$user['lname']) ?>" readonly>

          <p>Address:</p>
          <input type="text" name="address" value="<?= htmlspecialchars($user['address']) ?>">

          <button type="submit">Save Changes</button>
        </form>
      </div>

      <!-- Change Password -->
      <div class="tab-pane fade" id="password">
        <?php if(!empty($pass_msg)) echo "<div class='alert alert-info'>$pass_msg</div>"; ?>
        <form method="post">
          <input type="hidden" name="change_password">
          <p>Current Password:</p>
          <input type="password" name="current_password" required>

          <p>New Password:</p>
          <input type="password" name="new_password" required>

          <p>Confirm New Password:</p>
          <input type="password" name="confirm_password" required>

          <button type="submit">Update Password</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar = document.getElementById('mySidebar');
const main = document.getElementById('main');
const toggleBtn = document.getElementById('sidebarToggle');

toggleBtn.addEventListener('click', () => {
  sidebar.classList.toggle('expanded');
  main.classList.toggle('collapsed');
  toggleBtn.classList.toggle('collapsed');
});
</script>
</body>
</html>
