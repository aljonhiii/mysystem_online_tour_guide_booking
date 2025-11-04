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

// Handle AJAX for chart updates
if(isset($_GET['ajax'], $_GET['year'])){
    $year = (int)$_GET['year'];
    $monthly_hire = array_fill(1,12,0);
    $monthly_custom = array_fill(1,12,0);

    $stmt = $conn->prepare("SELECT MONTH(created_at) AS month, SUM(payment) AS total FROM hire_requests WHERE guide_id=? AND status='completed' AND YEAR(created_at)=? GROUP BY MONTH(created_at)");
    $stmt->execute([$guide_id, $year]);
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row){
        $monthly_hire[(int)$row['month']] = (float)$row['total'];
    }

    $stmt = $conn->prepare("SELECT MONTH(created_at) AS month, SUM(payment) AS total FROM custom_requests WHERE guide_id=? AND status='completed' AND YEAR(created_at)=? GROUP BY MONTH(created_at)");
    $stmt->execute([$guide_id, $year]);
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row){
        $monthly_custom[(int)$row['month']] = (float)$row['total'];
    }

    echo json_encode(['hire'=>array_values($monthly_hire),'custom'=>array_values($monthly_custom)]);
    exit;
}

// Available years
$stmt = $conn->prepare("
    SELECT DISTINCT YEAR(created_at) AS year FROM hire_requests WHERE guide_id=?
    UNION
    SELECT DISTINCT YEAR(created_at) AS year FROM custom_requests WHERE guide_id=?
    ORDER BY year DESC
");
$stmt->execute([$guide_id, $guide_id]);
$available_years = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'year');

$selected_year = $available_years[0] ?? date('Y');

// Monthly earnings
$monthly_hire = $monthly_custom = array_fill(1,12,0);
$stmt = $conn->prepare("SELECT MONTH(created_at) AS month, SUM(payment) AS total FROM hire_requests WHERE guide_id=? AND status='completed' AND YEAR(created_at)=? GROUP BY MONTH(created_at)");
$stmt->execute([$guide_id, $selected_year]);
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $monthly_hire[(int)$row['month']] = (float)$row['total'];

$stmt = $conn->prepare("SELECT MONTH(created_at) AS month, SUM(payment) AS total FROM custom_requests WHERE guide_id=? AND status='completed' AND YEAR(created_at)=? GROUP BY MONTH(created_at)");
$stmt->execute([$guide_id, $selected_year]);
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $monthly_custom[(int)$row['month']] = (float)$row['total'];

// Total stats
$total_hire = array_sum($monthly_hire);
$total_custom = array_sum($monthly_custom);
$total_hire_count = $conn->prepare("SELECT COUNT(*) FROM hire_requests WHERE guide_id=? AND status='completed'");
$total_hire_count->execute([$guide_id]);
$total_hire_count = $total_hire_count->fetchColumn();

$total_custom_count = $conn->prepare("SELECT COUNT(*) FROM custom_requests WHERE guide_id=? AND status='completed'");
$total_custom_count->execute([$guide_id]);
$total_custom_count = $total_custom_count->fetchColumn();

$total_booking_count = $total_hire_count + $total_custom_count;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Guide Statistics Dashboard</title>
<link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; margin:0; }

/* Sidebar */
.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  width: 250px;
  height: 100vh;
  background: #fff;
  padding: 25px 20px;
  box-shadow: 2px 0 12px rgba(0,0,0,0.05);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.sidebar h2 { font-weight: 700; color: #212832; text-align: center; margin-bottom: 35px; }
.sidebar ul { list-style: none; padding:0; }
.sidebar ul li { margin:15px 0; }
.sidebar ul li a { color:#212832; text-decoration:none; display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:6px; transition:0.2s; font-weight:500; }
.sidebar ul li a:hover { background-color:#eaecef; }
.logout { margin-top:auto; text-align:center; padding:8px 0; font-weight:600; color:#ef4444; border:1px solid #ef4444; border-radius:6px; text-decoration:none; display:block; }

/* Main content */
.main-content { margin-left:270px; padding:30px; }
.card { border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.1); padding:20px; }
</style>
</head>
<body>

<div class="sidebar">
  <h2>Guide Panel</h2>
  <ul>
    <li><a href="-dashboard.php"><i class="fa-solid fa-list"></i> Requests</a></li>
    <li><a href="guide-request.php"><i class="fa-solid fa-calendar-check"></i> Bookings</a></li>
    <li><a href="profile-guide.php"><i class="fa-solid fa-user-circle"></i> Profile</a></li>
    <li><a href="statistic.php"><i class="fa-solid fa-chart-line"></i> Statistics</a></li>
  </ul>
  <a href="../log-in/login.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main-content">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Dashboard Overview</h1>
    <img src="../log-in/Seal_of_Zamboanga_City.png" alt="Logo" style="height:60px;">
  </div>

  <!-- Summary Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card text-white bg-primary">
        <h5>Total Bookings</h5>
        <h3><?= $total_booking_count ?></h3>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-white bg-success">
        <h5>Total Hire Earnings</h5>
        <h3>₱<?= number_format($total_hire,2) ?></h3>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-white bg-info">
        <h5>Total Custom Earnings</h5>
        <h3>₱<?= number_format($total_custom,2) ?></h3>
      </div>
    </div>
  </div>

  <!-- Chart Card -->
  <div class="card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5>Monthly Earnings</h5>
      <select id="yearSelect" class="form-select w-auto">
        <?php foreach($available_years as $y): ?>
          <option value="<?= $y ?>" <?= $y==$selected_year?'selected':'' ?>><?= $y ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <canvas id="earningsChart" height="120"></canvas>
  </div>
</div>

<script>
let ctx = document.getElementById('earningsChart').getContext('2d');
let chartData = {
    labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
    datasets: [
        { label: 'Hire Bookings (₱)', data: <?= json_encode(array_values($monthly_hire)) ?>, backgroundColor: '#22c55e' },
        { label: 'Custom Bookings (₱)', data: <?= json_encode(array_values($monthly_custom)) ?>, backgroundColor: '#2563eb' }
    ]
};
let earningsChart = new Chart(ctx, {
    type: 'bar',
    data: chartData,
    options: { responsive:true, plugins:{legend:{position:'top'}}, scales:{x:{stacked:true}, y:{stacked:true, beginAtZero:true}} }
});

document.getElementById('yearSelect').addEventListener('change', function(){
    let year = this.value;
    fetch(`statistic.php?ajax=1&year=${year}`)
    .then(res=>res.json())
    .then(data=>{
        earningsChart.data.datasets[0].data = data.hire;
        earningsChart.data.datasets[1].data = data.custom;
        earningsChart.update();
    });
});
</script>
</body>
</html>
