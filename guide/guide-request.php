<?php
session_start();
require_once "../classes/database.php";

if(!isset($_SESSION['user_id']) || $_SESSION['user_type']!=='guide'){
    header("Location: ../log-in/login.php");
    exit;
}

$db = new Database();
$conn = $db->connect();

// Get guide ID
$stmt = $conn->prepare("SELECT id FROM guides WHERE user_id=?");
$stmt->execute([$_SESSION['user_id']]);
$guide = $stmt->fetch(PDO::FETCH_ASSOC);
$guide_id = $guide['id'] ?? exit("Guide not found.");

// Fetch hire requests: accepted, ongoing, completed
$stmt = $conn->prepare("
    SELECT hr.*, u.fname, u.lname
    FROM hire_requests hr
    JOIN users u ON hr.user_id = u.id
    WHERE hr.guide_id = ? AND hr.status IN ('accepted','ongoing','completed')
    ORDER BY hr.created_at DESC
");
$stmt->execute([$guide_id]);
$hire_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch custom requests for guide: accepted, ongoing, completed
$stmt = $conn->prepare("
    SELECT cr.*, CONCAT(u.fname,' ',IFNULL(u.mname,''),' ',u.lname) AS user_name
    FROM custom_requests cr
    JOIN users u ON cr.user_id = u.id
    WHERE cr.guide_id = ? AND cr.status IN ('accepted','ongoing','completed')
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
<title>Guide Bookings</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root {
  --primary: #f5f6fa; --secondary: #333; --highlight: #f1f3f7;
  --success: #22c55e; --info: #3b82f6; --card-bg: #ffffff;
}
body { font-family: 'Poppins', sans-serif; background-color: var(--primary); color: var(--secondary); margin:0; }
.sidebar { position: fixed; top:0; left:0; width:240px; height:100vh; background: var(--card-bg); box-shadow: 2px 0 10px rgba(0,0,0,0.1); padding:25px 20px; display:flex; flex-direction:column; justify-content:space-between; }
.sidebar h2 { text-align:center; font-weight:700; margin-bottom:30px; }
.sidebar ul { list-style:none; padding:0; }
.sidebar ul li { margin:18px 0; }
.sidebar ul li a { color: var(--secondary); text-decoration:none; display:flex; align-items:center; gap:12px; font-weight:500; padding:8px 10px; border-radius:8px; transition:0.3s; }
.sidebar ul li a:hover, .sidebar ul li a.active { background-color: var(--highlight); color:#000; }
.logout { color:#ef4444; text-align:center; text-decoration:none; font-weight:600; }
.main-content { margin-left:260px; padding:40px; }
.header { display:flex; justify-content:space-between; align-items:center; margin-bottom:40px; background-color:#212832; padding:15px 25px; border-radius:12px; }
.header h1 { font-size:26px; font-weight:700; color:#fff; margin:0; display:flex; align-items:center; gap:10px; }
.header img { width:55px; height:55px; border-radius:50%; border:2px solid #aaa; object-fit:cover; background-color:#fff; }
.card { border:none; background:var(--card-bg); border-radius:15px; box-shadow:0 4px 15px rgba(0,0,0,0.05); margin-bottom:30px; color:var(--secondary); }
.card h3 { font-size:20px; font-weight:600; margin-bottom:15px; color:#000; }
.table thead { background-color:#000; color:#fff; }
.table thead th { border:none; font-size:13px; font-weight:700; padding:10px 12px; text-transform:uppercase; text-align:center; }
.table tbody td { background-color: var(--card-bg); color: var(--secondary); border-bottom:1px solid #dcdcdc; font-size:14px; padding:10px; text-align:center; }
.table tbody tr:hover td { background-color:#f9fafb; }
.text-success { color: var(--success)!important; font-weight:600; }
.text-info { color: var(--info)!important; font-weight:600; }
.text-muted { color:#888!important; }
.btn-start { background-color: var(--info); color:#fff; border:none; border-radius:6px; padding:5px 12px; }
.btn-start:hover { background-color:#2563eb; }
.btn-complete { background-color: var(--success); color:#fff; border:none; border-radius:6px; padding:5px 12px; }
.btn-complete:hover { background-color:#16a34a; }
</style>
</head>
<body>

<div class="sidebar">
  <div>
    <h2>Guide Panel</h2>
    <ul>
      <li><a href="-dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
      <li><a href="guide-requested.php" class="active"><i class="fa-solid fa-calendar-check"></i> Bookings</a></li>
      <li><a href="profile-guide.php"><i class="fa-solid fa-user-circle"></i> Profile</a></li>
      <li><a href="statistic.php"><i class="fa-solid fa-chart-line"></i> Statistics </a></li>
    </ul>
  </div>
  <a href="../log-in/login.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main-content">
  <div class="header">
    <h1><i class="fa-solid fa-calendar-check me-2"></i>Bookings</h1>
    <img src="../log-in/Seal_of_Zamboanga_City.png" alt="City Logo">
  </div>

 <!-- Hire Requests -->
<div class="card p-4">
  <h3><i class="fa-solid fa-user-tie me-2"></i>Hire Requests</h3>
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
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php if($hire_requests): ?>
        <?php foreach($hire_requests as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['fname'].' '.$r['lname']) ?></td>
          <td><?= htmlspecialchars($r['message']) ?></td>
          <td><?= htmlspecialchars(date("M d, Y", strtotime($r['tour_date']))) ?></td> <!-- NEW DATA -->
          <td>₱<?= number_format($r['payment'],2) ?></td>
          <td><?= htmlspecialchars($r['payment_mode']) ?></td>
          <td><?= date("M d, Y h:i A", strtotime($r['created_at'])) ?></td>
          <td>
            <?php
            switch($r['status']){
                case 'accepted': echo '<span class="text-info">Waiting</span>'; break;
                case 'ongoing': echo '<span class="text-info">Ongoing</span>'; break;
                case 'completed': echo '<span class="text-success">Completed</span>'; break;
            }
            ?>
          </td>
          <td>
            <?php
            switch($r['status']){
                case 'accepted': echo '<button class="btn-start" data-id="'.$r['id'].'" data-type="hire">Start Tour</button>'; break;
                case 'ongoing': echo '<button class="btn-complete" data-id="'.$r['id'].'" data-type="hire">Complete Tour</button>'; break;
                case 'completed': echo '-'; break;
            }
            ?>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="8" class="text-center text-muted">No hire requests.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

  <!-- Custom Requests -->
  <div class="card p-4">
    <h3><i class="fa-solid fa-map-location-dot me-2"></i>Custom Requests</h3>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>User</th><th>Place</th><th>Date</th><th>Time</th><th>Description</th><th>Payment</th><th>Payment Mode</th><th>Status</th><th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php if($custom_requests): ?>
          <?php foreach($custom_requests as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['user_name']) ?></td>
            <td><?= htmlspecialchars($r['place']) ?></td>
            <td><?= htmlspecialchars($r['day']) ?></td>
            <td><?= htmlspecialchars($r['hour']) ?></td>
            <td><?= htmlspecialchars($r['description']) ?></td>
            <td>₱<?= number_format($r['payment'],2) ?></td>
            <td><?= htmlspecialchars($r['payment_mode']) ?></td>
            <td>
              <?php
              switch($r['status']){
                  case 'accepted': echo '<span class="text-info">Waiting</span>'; break;
                  case 'ongoing': echo '<span class="text-info">Ongoing</span>'; break;
                  case 'completed': echo '<span class="text-success">Completed</span>'; break;
              }
              ?>
            </td>
            <td>
              <?php
              switch($r['status']){
                  case 'accepted': echo '<button class="btn-start" data-id="'.$r['id'].'" data-type="custom">Start Tour</button>'; break;
                  case 'ongoing': echo '<button class="btn-complete" data-id="'.$r['id'].'" data-type="custom">Complete Tour</button>'; break;
                  case 'completed': echo '-'; break;
              }
              ?>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="9" class="text-center text-muted">No custom requests.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
<script>
// Start Tour
document.querySelectorAll('.btn-start').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        const id = btn.dataset.id;
        const type = btn.dataset.type;
        const formData = new FormData();
        formData.append('request_id', id);
        formData.append('action','start');
        formData.append('type', type);

        fetch('update-request-status.php',{method:'POST',body:formData})
        .then(res=>res.text())
        .then(res=>{
            if(res==='success') location.reload();
            else alert('Error starting tour: '+res);
        });
    });
});

// Complete Tour
document.querySelectorAll('.btn-complete').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        const id = btn.dataset.id;
        const type = btn.dataset.type;
        const formData = new FormData();
        formData.append('request_id', id);
        formData.append('action','complete');
        formData.append('type', type);

        fetch('update-request-status.php',{method:'POST',body:formData})
        .then(res=>res.text())
        .then(res=>{
            if(res==='success') location.reload();
            else alert('Error completing tour: '+res);
        });
    });
});
</script>
</body>
</html>
