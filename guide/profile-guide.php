<?php
session_start();
require_once "../classes/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'guide') {
    header("Location: ../log-in/login.php");
    exit;
}

$db = new Database();
$conn = $db->connect();
$guide_user_id = $_SESSION['user_id'];

/* 🔹 FUNCTION: Update Guide Profile with TRANSACTION */
function updateGuideProfile($conn, $guide_user_id, $guide)
{
    try {
        $conn->beginTransaction(); // ✅ Start transaction

        $address = $_POST['address'];
        $expertise = $_POST['expertise'];
        $languages = $_POST['languages'];
        $rate = $_POST['rate'];
        $availability = $_POST['availability'];

        $profile_image = $guide['profile_image'];
        if (!empty($_FILES['profile_image']['name'])) {
            if (!is_dir("uploads")) mkdir("uploads", 0777, true);
            $img_name = time() . '_' . $_FILES['profile_image']['name'];
            move_uploaded_file($_FILES['profile_image']['tmp_name'], "uploads/" . $img_name);
            $profile_image = $img_name;
        }

        // ✅ Update users table
        $stmt1 = $conn->prepare("UPDATE users SET address=?, profile_image=? WHERE id=?");
        $stmt1->execute([$address, $profile_image, $guide_user_id]);

        // ✅ Update guides table
        $stmt2 = $conn->prepare("UPDATE guides SET expertise=?, languages=?, rate=?, availability=? WHERE user_id=?");
        $stmt2->execute([$expertise, $languages, $rate, $availability, $guide_user_id]);

        $conn->commit(); // ✅ Save all changes
        header("Location: profile-guide.php");
        exit;
    } catch (Exception $e) {
        $conn->rollBack(); // ❌ Undo changes on error
        die("❌ Update failed: " . $e->getMessage());
    }
}

/* 🔹 Fetch guide info */
$stmt = $conn->prepare("
  SELECT u.*, g.expertise, g.languages, g.rate, g.availability
  FROM users u
  LEFT JOIN guides g ON u.id=g.user_id
  WHERE u.id=?
");
$stmt->execute([$guide_user_id]);
$guide = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$guide) exit("Guide profile not found.");

/* 🔹 Handle form submissions */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        updateGuideProfile($conn, $guide_user_id, $guide);
    }

    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        if (password_verify($current, $guide['password'])) {
            if ($new === $confirm) {
                try {
                    $conn->beginTransaction();
                    $hashed = password_hash($new, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
                    $stmt->execute([$hashed, $guide_user_id]);
                    $conn->commit();
                    $pass_msg = "Password updated successfully!";
                } catch (Exception $e) {
                    $conn->rollBack();
                    $pass_msg = "Password update failed: " . $e->getMessage();
                }
            } else {
                $pass_msg = "New passwords do not match.";
            }
        } else {
            $pass_msg = "Current password is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Guide Profile</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root {
  --primary: #212832;
  --secondary: #aaa;
  --bg: #f1f3f6;
  --card-bg: #fff;
  --accent: #eaecef;
  --success: #22c55e;
  --danger: #ef4444;
}

body { 
    background: var(--bg); 
    font-family:'Poppins',sans-serif; 
    margin:0; 
    color:var(--primary);
}

/* Sidebar */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 220px;
    height: 100%;
    background: #fff;
    box-shadow: 2px 0 12px rgba(0,0,0,0.05);
    padding: 30px 15px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    z-index: 1000;
}
.sidebar h2 { 
    text-align:center; 
    margin-bottom: 15px;
    font-size:22px; 
}
.sidebar ul { 
    list-style:none; 
    padding:0; 
    margin:0; 
}
.sidebar ul li { 
    margin:10px 0;
}
.sidebar ul li a { 
    color:var(--primary); 
    text-decoration:none; 
    display:flex; 
    align-items:center; 
    gap:12px; 
    font-weight:500; 
    padding:10px 15px; 
    border-radius:8px; 
    transition:0.3s; 
}
.sidebar ul li a:hover { 
    background-color: var(--accent); 
}

.logout { 
    margin-top:auto; 
    text-align:center; 
    padding:8px 0; 
    font-weight:600; 
    color:#ef4444; 
    border:1px solid #ef4444; 
    border-radius:6px; 
    text-decoration:none; 
    display:block; 
}

/* Main content */
.main-content { 
    margin-left:240px; 
    padding:40px; 
    transition:0.3s; 
}
.header { 
    display:flex; 
    justify-content:space-between; 
    align-items:center; 
    margin-bottom:30px;
}
.header h1 { 
    font-size:28px; 
    font-weight:700; 
}
.header img { 
    width:60px; 
    height:60px; 
    border-radius:50%; 
    object-fit:cover; 
    border:3px solid var(--primary); 
}

/* Cards and forms */
.card { 
    border:none; 
    background:var(--card-bg); 
    border-radius:15px; 
    box-shadow:0 4px 16px rgba(0,0,0,0.05); 
    margin-bottom:30px; 
    padding:25px; 
}
.card h3 { 
    font-size:20px; 
    font-weight:600; 
    margin-bottom:20px; 
}
.card form p { 
    margin:12px 0 5px; 
    font-weight:500; 
}
.card input[type="text"], 
.card input[type="number"], 
.card input[type="file"], 
.card input[type="password"] { 
    width:100%; 
    padding:10px; 
    margin-top:5px; 
    border-radius:8px; 
    border:1px solid #ccc; 
}
.profile-img { 
    width:120px; 
    height:120px; 
    border-radius:50%; 
    object-fit:cover; 
    display:block; 
    margin:0 auto 15px; 
    border:3px solid var(--primary); 
}
button { 
    background:var(--primary); 
    color:#fff; 
    border:none; 
    border-radius:8px; 
    padding:10px 20px; 
    cursor:pointer; 
    margin-top:15px; 
}
button:hover { 
    background:#0056b3; 
}

@media screen and (max-width: 768px) {
    .sidebar { 
        width: 100%; 
        height:auto; 
        position:relative; 
        padding:20px; 
        flex-direction:row; 
        justify-content:space-around;
    }
    .main-content { 
        margin-left:0; 
        margin-top:20px; 
        padding:20px; 
    }
}
</style>
</head>
<body>

<div class="sidebar">
  <h2>GUIDE PANEL</h2>
  <ul>
    <li><a href="-dashboard.php"><i class="fa-solid fa-list"></i> Requests</a></li>
    <li><a href="guide-request.php"><i class="fa-solid fa-calendar-check"></i> Bookings</a></li>
    <li><a href="profile-guide.php"><i class="fa-solid fa-user-circle"></i> Profile</a></li>
    <li><a href="statistic.php"><i class="fa-solid fa-chart-line"></i> Statistics</a></li>
  </ul>
  <a href="../log-in/login.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main-content">
  <div class="header">
    <h1>My Profile</h1>
    <img src="../log-in/Seal_of_Zamboanga_City.png" alt="City Logo">
  </div>

  <div class="card">
    <ul class="nav nav-tabs mb-3" id="profileTab" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">Profile Info</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab">Change Password</button>
      </li>
    </ul>

    <div class="tab-content" id="profileTabContent">
      <!-- Profile Info -->
      <div class="tab-pane fade show active" id="info" role="tabpanel">
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="update_profile">
          <img src="<?= $guide['profile_image'] ? 'uploads/'.$guide['profile_image']:'uploads/default.jpg' ?>" class="profile-img" alt="Profile Image">
          <p>Change Profile Image:</p>
          <input type="file" name="profile_image" accept="image/*">

          <p>Full Name:</p>
          <input type="text" value="<?= htmlspecialchars($guide['fname'].' '.($guide['mname']?$guide['mname'].' ':'').$guide['lname']) ?>" readonly>

          <p>Address:</p>
          <input type="text" name="address" value="<?= htmlspecialchars($guide['address']) ?>">

          <p>Expertise:</p>
          <input type="text" name="expertise" value="<?= htmlspecialchars($guide['expertise']) ?>">

          <p>Languages:</p>
          <input type="text" name="languages" value="<?= htmlspecialchars($guide['languages']) ?>">

          <p>Rate (₱/day):</p>
          <input type="number" name="rate" step="0.01" value="<?= htmlspecialchars($guide['rate']) ?>">

          <p>Availability:</p>
          <input type="text" name="availability" value="<?= htmlspecialchars($guide['availability']) ?>">

          <button type="submit">Save Changes</button>
        </form>
      </div>

      <!-- Change Password -->
      <div class="tab-pane fade" id="password" role="tabpanel">
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
</body>
</html>
