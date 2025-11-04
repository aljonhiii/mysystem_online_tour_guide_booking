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

// Fetch guide ID
$stmt = $conn->prepare("SELECT id FROM guides WHERE user_id=?");
$stmt->execute([$guide_user_id]);
$guide = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$guide) exit("Guide profile not found.");
$guide_id = $guide['id'];

// Fetch pending hire requests
$stmt = $conn->prepare("
    SELECT hr.*, u.fname, u.lname
    FROM hire_requests hr
    JOIN users u ON hr.user_id=u.id
    WHERE hr.guide_id=? AND hr.status='pending'
    ORDER BY hr.created_at DESC
");
$stmt->execute([$guide_id]);
$hire_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch pending custom requests
$stmt = $conn->prepare("
    SELECT cr.*, CONCAT(u.fname,' ',IFNULL(u.mname,''),' ',u.lname) AS user_name
    FROM custom_requests cr
    JOIN users u ON cr.user_id=u.id
    WHERE (cr.guide_id IS NULL OR cr.guide_id=?) AND cr.status='pending'
    ORDER BY cr.created_at DESC
");
$stmt->execute([$guide_id]);
$custom_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Guide Dashboard</title>
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
  background-color: var(--bg);
  font-family: 'Poppins', sans-serif;
  color: var(--primary);
  margin: 0;
}

/* Sidebar */
.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  width: 240px;
  height: 100vh;
  background: #fff;
  box-shadow: 2px 0 12px rgba(0,0,0,0.05);
  padding: 25px 20px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.sidebar h2 {
  font-weight: 700;
  color: var(--primary);
  text-align: center;
  margin-bottom: 35px;
}

.sidebar ul {
  list-style: none;
  padding: 0;
}

.sidebar ul li {
  margin: 18px 0;
}

.sidebar ul li a {
  color: var(--primary);
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 500;
  transition: 0.3s;
  padding: 8px 10px;
  border-radius: 8px;
}

.sidebar ul li a:hover {
  background-color: var(--accent);
  color: var(--primary);
}

.logout {
  color: var(--danger);
  text-align: center;
  text-decoration: none;
  font-weight: 600;
}

/* Main content */
.main-content {
  margin-left: 260px;
  padding: 40px;
  transition: 0.3s;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.header h1 {
  font-size: 28px;
  font-weight: 700;
  color: var(--primary);
}

.header img {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid var(--primary);
  background-color: white;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

/* Card styling */
.card {
  border: none;
  background: var(--card-bg);
  border-radius: 15px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.05);
  margin-bottom: 30px;
}

.card h3 {
  font-size: 20px;
  font-weight: 600;
  color: var(--primary);
  margin-bottom: 15px;
}

/* Table styling */
.table {
  border-collapse: separate;
  border-spacing: 0 8px;
  width: 100%;
}

.table td, .table th {
  padding: 14px 18px !important;
  text-align: center;
  white-space: nowrap;
}

.table tr td:last-child {
  min-width: 160px; /* Enough space for buttons */
}

/* Buttons */
.btn-accept {
  background-color: var(--success);
  color: #fff;
  border: none;
  border-radius: 6px;
  padding: 6px 12px;
  margin: 2px;
  transition: 0.2s;
}

.btn-accept:hover { background-color: #16a34a; }

.btn-reject {
  background-color: var(--danger);
  color: #fff;
  border: none;
  border-radius: 6px;
  padding: 6px 12px;
  margin: 2px;
  transition: 0.2s;
}

.btn-reject:hover { background-color: #b91c1c; }

.text-muted { color: var(--secondary)!important; }
</style>
</head>
<body>

<div class="sidebar">
  <div>
    <h2>Guide Panel</h2>
    <ul>
      <li><a href="#"><i class="fa-solid fa-list"></i> Requests</a></li>
      <li><a href="guide-request.php"><i class="fa-solid fa-calendar-check"></i> Completed</a></li>
      <li><a href="profile-guide.php"><i class="fa-solid fa-user-circle"></i> Profile</a></li>
      <li><a href="statistic.php"><i class="fa-solid fa-chart-line"></i> Statistics </a></li>
    </ul>
  </div>
  <a href="../log-in/login.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main-content">
  <div class="header">
    <h1>Dashboard Overview</h1>
    <img src="../log-in/Seal_of_Zamboanga_City.png" alt="City Logo">
  </div>

  <!-- Hire Requests -->
  <div class="card p-4">
    <h3><i class="fa-solid fa-user-tie"></i> Pending Hire Requests</h3>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>User</th>
            <th>Message</th>
            <th>Tour Date</th> <!-- NEW COLUMN -->
            <th>Payment</th>
            <th>Payment Mode</th>
            <th>Requested On</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php if($hire_requests): ?>
          <?php foreach($hire_requests as $r): ?>
          <tr data-id="<?= $r['id'] ?>" data-type="hire">
            <td><?= htmlspecialchars($r['fname'].' '.$r['lname']) ?></td>
            <td><?= htmlspecialchars($r['message']) ?></td>
            <td><?= htmlspecialchars(date("M d, Y", strtotime($r['tour_date'] ?? $r['created_at']))) ?></td> <!-- NEW DATA -->
            <td>₱<?= number_format($r['payment'],2) ?></td>
            <td><?= htmlspecialchars($r['payment_mode']) ?></td>
            <td><?= date("M d, Y h:i A", strtotime($r['created_at'])) ?></td>
            <td>
              <button class="btn-accept action-btn">Accept</button>
              <button class="btn-reject action-btn">Reject</button>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7" class="text-center text-muted">No pending hire requests.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Custom Requests -->
  <div class="card p-4">
    <h3><i class="fa-solid fa-map-location-dot"></i> Pending Custom Requests</h3>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>User</th>
            <th>Place</th>
            <th>Tour Date & Time</th> <!-- NEW COLUMN -->
            <th>Description</th>
            <th>Payment</th>
            <th>Payment Mode</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php if($custom_requests): ?>
          <?php foreach($custom_requests as $r): ?>
          <tr data-id="<?= $r['id'] ?>" data-type="custom">
            <td><?= htmlspecialchars($r['user_name']) ?></td>
            <td><?= htmlspecialchars($r['place']) ?></td>
            <td><?= htmlspecialchars($r['day'].' '.$r['hour']) ?></td> <!-- NEW DATA -->
            <td><?= htmlspecialchars($r['description']) ?></td>
            <td>₱<?= number_format($r['payment'],2) ?></td>
            <td><?= htmlspecialchars($r['payment_mode']) ?></td>
            <td>
              <button class="btn-accept action-btn">Accept</button>
              <button class="btn-reject action-btn">Reject</button>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7" class="text-center text-muted">No pending custom requests.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.action-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const tr = btn.closest('tr');
        const request_id = tr.dataset.id;
        const type = tr.dataset.type || 'hire';
        const action = btn.classList.contains('btn-accept') ? 'accept' : 'reject';
        const url = 'update-request-status.php';

        // Only check for accept action
        if(action === 'accept'){
            try {
                // Fetch already accepted dates for this guide
                const bookedRes = await fetch('get-guide.php');
                const bookedDates = await bookedRes.json();
                
                const requestDate = tr.dataset.date; // <tr data-date="YYYY-MM-DD">
                if(bookedDates.includes(requestDate)){
                    alert("You can only accept one booking per day.");
                    return;
                }
            } catch(err) {
                console.error('Error fetching guide bookings:', err);
                alert('Cannot verify booking conflicts. Try again later.');
                return;
            }
        }

        // Proceed with accept/reject
        const formData = new FormData();
        formData.append('request_id', request_id);
        formData.append('action', action);
        formData.append('type', type);

        fetch(url, { method: 'POST', body: formData })
            .then(res => res.text())
            .then(res => {
                if(res === 'success'){
                    tr.remove();
                } else {
                    alert('Could not update request. Debug info:\n' + res);
                    console.error('Request update error:', res);
                }
            })
            .catch(err => {
                alert('Network or server error. See console for details.');
                console.error(err);
            });
    });
});

</script>
</body>
</html>
