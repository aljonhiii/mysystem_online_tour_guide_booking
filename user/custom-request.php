<?php
session_start();
require_once "../classes/database.php";

// Only logged-in users can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../log-in/login.php");
    exit;
}

$db = new Database();
$conn = $db->connect();

// Fetch user info
$stmt = $conn->prepare("SELECT fname, lname FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$fname = $user['fname'];
$lname = $user['lname'];

// Fetch all guides
$guide_stmt = $conn->prepare("
    SELECT g.id, CONCAT(u.fname,' ',IFNULL(u.mname,''),' ',u.lname) AS guide_name
    FROM guides g
    JOIN users u ON g.user_id = u.id
    ORDER BY u.fname ASC
");
$guide_stmt->execute();
$guides = $guide_stmt->fetchAll(PDO::FETCH_ASSOC);

// Old inputs and errors
$errors = $_SESSION['form_error'] ?? [];
$old = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_error'], $_SESSION['form_old']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Custom Request - Zamboanga Tour</title>
<link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
<style>
body { 
    background: linear-gradient(120deg, #f1f3f6, #e0e7ff); 
    font-family: 'Segoe UI', sans-serif; 
    margin: 0; 
    overflow-x: hidden;
}

/* Sidebar */
.sidebar {
  height: 100%;
  width: 60px;
  position: fixed;
  top: 0;
  left: 0;
  background-color: #1f2937;
  overflow-x: hidden;
  transition: width 0.4s;
  padding-top: 20px;
  z-index: 1040;
}
.sidebar.expanded { width: 250px; padding-top: 60px; }
.sidebar img {
  display:block;
  margin:0 auto 15px;
  width:50px;
  border-radius:50%;
  transition: width 0.4s;
}
.sidebar.expanded img { width:70px; }
.sidebar a {
  padding: 15px;
  text-decoration: none;
  font-size: 16px;
  color: #fff;
  display: flex;
  align-items: center;
  transition: all 0.3s;
}
.sidebar a i { width: 25px; text-align: center; }
.sidebar a span { margin-left: 10px; display: none; }
.sidebar.expanded a span { display: inline; }
.sidebar a:hover { color: #3b82f6; }

/* Main content */
#main { transition: margin-left 0.4s; padding: 20px; margin-left: 250px; }
#main.collapsed { margin-left: 60px; }

/* Toggle button */
.toggle-btn {
  position: fixed;
  top: 15px;
  left: 65px;
  z-index: 1050;
  cursor: pointer;
  font-size: 1.5rem;
  color: #1f2937;
  transition: left 0.4s;
}
.toggle-btn.collapsed { left: 15px; }

/* Custom Request Form */
.custom-request {
  max-width: 600px;
  margin: 80px auto;
  background: #fff;
  padding: 35px 30px;
  border-radius: 25px;
  box-shadow: 0 25px 50px rgba(0,0,0,0.2);
  opacity: 0;
  transform: translateY(-50px);
  transition: all 0.7s ease;
}
.custom-request.visible { 
  opacity: 1;
  transform: translateY(0);
}
.custom-request h5 {
  font-weight: 700;
  margin-bottom: 25px;
  text-align: center;
  color: #1e3a8a;
}
.custom-request .form-floating { margin-bottom: 15px; }
.custom-request button {
  border-radius: 12px;
  font-weight: 600;
  background-color: #2563eb;
  color: #fff;
  border: none;
  transition: 0.3s;
}
.custom-request button:hover { background-color: #1e40af; }

/* Alerts */
.alert { border-radius: 12px; margin-bottom: 20px; }

/* Validation */
.is-invalid { border-color: #dc3545 !important; }
.invalid-feedback { color: #dc3545; }

/* Floating labels color */
.form-floating>.form-control:focus~label,
.form-floating>.form-select:focus~label {
    color: #2563eb;
}
</style>
</head>
<body>

<!-- Sidebar -->
<div id="mySidebar" class="sidebar expanded">
  <img src="../log-in/Seal_of_Zamboanga_City.png" alt="Seal">
  <a href="index.php"><i class="fa-solid fa-house"></i> <span>Home</span></a>
  <a href="hire-a-guide.php"><i class="fa-solid fa-user-tie"></i> <span>Hire a Guide</span></a>
  <a href="custom-request.php"><i class="fa-solid fa-map-location-dot"></i> <span>Custom Request</span></a>
  <a href="browsing-places.php"><i class="fa-solid fa-compass"></i> <span>Browse Places</span></a>
  <a href="my-bookings.php"><i class="fa-solid fa-calendar-check"></i> <span>My Bookings</span></a>
  <a href="profile-user.php"><i class="fa-solid fa-user-circle"></i> <span>Profile</span></a>
  <a href="../log-in/login.php"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
</div>

<i id="sidebarToggle" class="fa-solid fa-bars toggle-btn"></i>

<div id="main">
  <section class="py-2 text-center container welcome-section">
    <div class="row py-lg-4">
      <div class="col-lg-8 col-md-10 mx-auto">
        <h1 class="fw-bold">Custom Request</h1>
        <p class="lead text-muted">Hello <?= htmlspecialchars($fname) ?>! Submit your custom tour request below.</p>
      </div>
    </div>
  </section>

  <div class="custom-request" id="requestForm">
    <form action="custom-request-process.php" method="POST">
      <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">

      <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success text-center"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
      <?php elseif (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger text-center"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
      <?php endif; ?>

      <div class="form-floating">
        <input type="text" class="form-control <?= isset($errors['place'])?'is-invalid':'' ?>" name="place" placeholder="Place" value="<?= $old['place'] ?? '' ?>">
        <label>Place</label>
        <?php if(isset($errors['place'])): ?><div class="invalid-feedback"><?= $errors['place'] ?></div><?php endif; ?>
      </div>

      <div class="form-floating">
        <input type="date" class="form-control <?= isset($errors['day'])?'is-invalid':'' ?>" name="day" placeholder="Select Date" value="<?= $old['day'] ?? '' ?>">
        <label>Date</label>
        <?php if(isset($errors['day'])): ?><div class="invalid-feedback"><?= $errors['day'] ?></div><?php endif; ?>
      </div>

      <div class="form-floating">
        <input type="time" class="form-control <?= isset($errors['hour'])?'is-invalid':'' ?>" name="hour" placeholder="Select Time" value="<?= $old['hour'] ?? '' ?>">
        <label>Time</label>
        <?php if(isset($errors['hour'])): ?><div class="invalid-feedback"><?= $errors['hour'] ?></div><?php endif; ?>
      </div>

      <div class="form-floating">
        <textarea class="form-control <?= isset($errors['description'])?'is-invalid':'' ?>" name="description" placeholder="Description" style="height:80px;"><?= $old['description'] ?? '' ?></textarea>
        <label>Description</label>
        <?php if(isset($errors['description'])): ?><div class="invalid-feedback"><?= $errors['description'] ?></div><?php endif; ?>
      </div>

      <div class="form-floating">
        <input type="number" class="form-control <?= isset($errors['payment'])?'is-invalid':'' ?>" name="payment" placeholder="Payment Amount" value="<?= $old['payment'] ?? '' ?>">
        <label>Payment Amount</label>
        <?php if(isset($errors['payment'])): ?><div class="invalid-feedback"><?= $errors['payment'] ?></div><?php endif; ?>
      </div>

      <div class="form-floating">
        <select class="form-select <?= isset($errors['payment_mode'])?'is-invalid':'' ?>" name="payment_mode">
          <option value="">Select Mode of Payment</option>
          <option value="Cash" <?= isset($old['payment_mode']) && $old['payment_mode']=='Cash'?'selected':'' ?>>Cash</option>
          <option value="Gcash" <?= isset($old['payment_mode']) && $old['payment_mode']=='Gcash'?'selected':'' ?>>Gcash</option>
          <option value="Bank Transfer" <?= isset($old['payment_mode']) && $old['payment_mode']=='Bank Transfer'?'selected':'' ?>>Bank Transfer</option>
        </select>
        <label>Payment Mode</label>
        <?php if(isset($errors['payment_mode'])): ?><div class="invalid-feedback"><?= $errors['payment_mode'] ?></div><?php endif; ?>
      </div>

      <div class="form-floating">
        <select class="form-select <?= isset($errors['guide_id'])?'is-invalid':'' ?>" name="guide_id">
          <option value="">Select a Guide</option>
          <?php foreach($guides as $g): ?>
            <option value="<?= $g['id'] ?>" <?= isset($old['guide_id']) && $old['guide_id']==$g['id']?'selected':'' ?>><?= htmlspecialchars($g['guide_name']) ?></option>
          <?php endforeach; ?>
        </select>
        <label>Choose a Guide</label>
        <?php if(isset($errors['guide_id'])): ?><div class="invalid-feedback"><?= $errors['guide_id'] ?></div><?php endif; ?>
      </div>

      <button type="submit">Send Request</button>
    </form>
  </div>
</div>

<script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar = document.getElementById('mySidebar');
const main = document.getElementById('main');
const toggleBtn = document.getElementById('sidebarToggle');
const requestForm = document.getElementById('requestForm');

// Toggle sidebar
toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('expanded');
    main.classList.toggle('collapsed');
    toggleBtn.classList.toggle('collapsed');
});

// Auto expand on hover
sidebar.addEventListener('mouseenter', () => {
    sidebar.classList.add('expanded');
    main.classList.remove('collapsed');
});
sidebar.addEventListener('mouseleave', () => {
    if (!toggleBtn.classList.contains('collapsed')) {
        sidebar.classList.remove('expanded');
        main.classList.add('collapsed');
    }
});

// Animate form entrance
window.addEventListener('DOMContentLoaded', () => {
    requestForm.classList.add('visible');
    requestForm.scrollIntoView({ behavior: 'smooth' });
});
</script>
</body>
</html>
