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
$user_id = $_SESSION['user_id'];

// Fetch hire requests
$stmt = $conn->prepare("
    SELECT hr.*, g.id AS guide_id, u.fname, u.lname
    FROM hire_requests hr
    JOIN guides g ON hr.guide_id = g.id
    JOIN users u ON g.user_id = u.id
    WHERE hr.user_id = ?
    ORDER BY hr.created_at DESC
");
$stmt->execute([$user_id]);
$hire_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch completed custom requests
$stmt = $conn->prepare("
    SELECT *
    FROM custom_requests
    WHERE user_id = ? AND status IN ('approved', 'rejected', 'ongoing', 'completed')
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$custom_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Requests - Zamboanga Tour</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root {
  --primary: #1e3a8a;
  --secondary: #6b7280;
  --bg: #f1f3f6;
  --card-bg: #fff;
}

body { font-family:'Poppins',sans-serif; margin:0; background:var(--bg); color:var(--primary); }

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

/* Cards */
.card {
  border:none;
  background:var(--card-bg);
  border-radius:20px;
  box-shadow:0 15px 35px rgba(0,0,0,0.1);
  margin-bottom:30px;
  padding:25px;
  opacity:0;
  transform: translateY(30px);
  transition: transform 0.6s ease, opacity 0.6s ease;
}
.card.visible { opacity:1; transform: translateY(0); }
.card h3 { font-size:20px; font-weight:600; margin-bottom:20px; }

/* Table */
.table th { background-color:var(--primary); color:#fff; border:none; }
.table td { background:#fff; vertical-align:middle; }
.table tr:hover td { background:#f7f8fa; }

/* Badges */
.badge-status { padding:5px 12px; border-radius:12px; font-weight:500; }
.bg-pending { background-color:#f59e0b; color:#212832; }
.bg-accepted { background-color:#16a34a; color:#fff; }
.bg-ongoing { background-color:#3b82f6; color:#fff; }
.bg-completed { background-color:#22c55e; color:#fff; }
.bg-rejected { background-color:#dc2626; color:#fff; }
.bg-cancelled { background-color:#6b7280; color:#fff; }

.text-center h2 { font-weight:700; margin-bottom:10px; }
.text-center p { color:var(--secondary); }
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

<!-- Sidebar Toggle -->
<i id="sidebarToggle" class="fa-solid fa-bars toggle-btn"></i>

<!-- Main Content -->
<div id="main">
  <div class="text-center mt-4 mb-5">
    <h2>My Requests</h2>
    <p>View and manage your hire and custom requests below.</p>
  </div>

  <!-- Hire Requests -->
  <div class="card">
    <h3><i class="fa-solid fa-user-tie"></i> My Hire Requests</h3>
    <div class="table-responsive">
      <table class="table align-middle text-center">
        <thead>
          <tr>
            <th>Guide</th>
            <th>Message</th>
            <th>Tour Date</th> <!-- NEW COLUMN -->
            <th>Status</th>
            <th>Requested On</th>
          </tr>
        </thead>
        <tbody>
        <?php if($hire_requests): ?>
          <?php foreach($hire_requests as $r):
            $badgeClass = match($r['status']) {
                'pending' => 'bg-pending',
                'accepted' => 'bg-accepted',
                'ongoing' => 'bg-ongoing',
                'completed' => 'bg-completed',
                'rejected' => 'bg-rejected',
                'cancelled' => 'bg-cancelled',
                default => 'bg-secondary'
            };
            $tourDate = date("M d, Y", strtotime($r['tour_date'] ?? $r['created_at']));
          ?>
            <tr>
              <td><?= htmlspecialchars($r['fname'].' '.$r['lname']) ?></td>
              <td><?= htmlspecialchars($r['message']) ?></td>
              <td><?= $tourDate ?></td> <!-- NEW DATA -->
              <td><span class="badge badge-status <?= $badgeClass ?>" data-id="<?= $r['id'] ?>"><?= ucfirst($r['status']) ?></span></td>
              <td><?= date("M d, Y h:i A", strtotime($r['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="5" class="text-muted">No hire requests found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Custom Requests -->
  <div class="card">
    <h3><i class="fa-solid fa-map-location-dot"></i> My Custom Requests</h3>
    <div class="table-responsive">
      <table class="table align-middle text-center">
        <thead>
          <tr>
            <th>Place</th>
            <th>Tour Date & Time</th> <!-- NEW COLUMN -->
            <th>Description</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php if($custom_requests): ?>
          <?php foreach($custom_requests as $cr):
            $statusClass = match($cr['status']) {
                'approved' => 'bg-accepted',
                'ongoing' => 'bg-ongoing',
                'completed' => 'bg-completed',
                'rejected' => 'bg-rejected',
                'cancelled' => 'bg-cancelled',
                default => 'bg-secondary'
            };
            $dateTime = date("M d, Y", strtotime($cr['day'])) . ' ' . date("h:i A", strtotime($cr['hour']));
          ?>
            <tr>
              <td><?= htmlspecialchars($cr['place']) ?></td>
              <td><?= $dateTime ?></td> <!-- NEW DATA -->
              <td><?= htmlspecialchars($cr['description']) ?></td>
              <td><span class="badge badge-status <?= $statusClass ?>" data-id="<?= $cr['id'] ?>"><?= ucfirst($cr['status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="4" class="text-muted">No custom requests found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar toggle
const sidebar = document.getElementById('mySidebar');
const main = document.getElementById('main');
const toggleBtn = document.getElementById('sidebarToggle');

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

// Animate cards
window.addEventListener('DOMContentLoaded', () => {
  const cards = document.querySelectorAll('.card');
  cards.forEach((card, index) => {
    setTimeout(() => card.classList.add('visible'), index * 150);
  });
});

// Real-time status polling
function checkStatus() {
    fetch('get-request-status.php')
        .then(res => res.json())
        .then(data => {
            data.forEach(req => {
                const badge = document.querySelector(`.badge-status[data-id='${req.id}']`);
                if(badge && badge.textContent.toLowerCase() !== req.status.toLowerCase()){
                    badge.textContent = req.status.charAt(0).toUpperCase() + req.status.slice(1);

                    badge.className = 'badge badge-status ' + ({

                        pending: 'bg-pending',
                        accepted: 'bg-accepted',
                        ongoing: 'bg-ongoing',
                        completed: 'bg-completed',
                        rejected: 'bg-rejected',
                        cancelled: 'bg-cancelled'
                    }[req.status] || 'bg-secondary');
                }
            });
        });
}

// Poll every 5 seconds
setInterval(checkStatus, 5000);
</script>
</body>
</html>
