<?php
session_start();
require_once "../classes/database.php";

// Redirect if user is not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../log-in/login.php");
    exit;
}

// Fetch user name
$db = new Database();
$conn = $db->connect();
$stmt = $conn->prepare("SELECT fname, lname FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$fname = $user['fname'];
$lname = $user['lname'];

// Sample cards data
$cards = [
    ["img" => "pasonanca.jpeg", "alt" => "Pasonanca Park", "text" => "Pasonanca Park – Green spaces and playgrounds for the family."],
    ["img" => "sta cruz.jpeg", "alt" => "Sta. Cruz Island", "text" => "Sta. Cruz Island – Famous pink sand beaches and clear waters."],
    ["img" => "merloquet.jpeg", "alt" => "Merloquet Falls", "text" => "Merloquet Falls – Hidden waterfall for a refreshing experience."],
    ["img" => "vintattt.jpeg", "alt" => "Vinta Boat Tour", "text" => "Vinta Boat Tour – Experience traditional boats along the coastline."],
    ["img" => "myhome2].jpeg", "alt" => "Zamboanga Cathedral", "text" => "Zamboanga Cathedral – Historical church in the heart of the city."],
    ["img" => "peak view.jpeg", "alt" => "Peak View", "text" => "Peak view – A peaceful retreat with lush greenery."],
    ["img" => "whitesa.jpeg", "alt" => "White Sand Beach", "text" => "White Sand Beach – Perfect for sunbathing and beach activities."],

    // New Zamboanga City Places
    ["img" => "muti.jpeg", "alt" => "Muti Zamboanga City", "text" => "Muti – A quiet coastal barangay known for its mangroves and scenic rural charm."],
    ["img" => "fort_pilar.jpeg", "alt" => "Fort Pilar", "text" => "Fort Pilar – A historical Spanish-era fortress and religious shrine."],
    ["img" => "paseo.jpeg", "alt" => "Paseo del Mar", "text" => "Paseo del Mar – A lively seaside promenade with restaurants and sunset views."],
    ["img" => "once_islas.jpg", "alt" => "Once Islas", "text" => "Once Islas – A stunning cluster of 11 islands for snorkeling and eco-adventures."],
    ["img" => "yakan weaving.webp", "alt" => "Yakan Weaving Village", "text" => "Yakan Weaving Village – Discover traditional Yakan textiles and handwoven fabrics."],
    ["img" => "zamboanga_boulevard.jpeg", "alt" => "Zamboanga City Boulevard", "text" => "Zamboanga Boulevard – A relaxing beachfront avenue ideal for walks and local food."],
    ["img" => "grassland_lantawan.jpeg", "alt" => "Grassland Zamboanga", "text" => "Grassland – A wide open green field perfect for picnics and sunset viewing."]
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Zamboanga Tour Dashboard</title>
<link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
<style>
body { 
    background-color: #f1f3f6; 
    font-family: 'Segoe UI', sans-serif; 
    scroll-behavior: smooth; 
    margin:0; 
    display:flex; 
    flex-direction:column; 
    min-height:100vh; 
}
#main { transition: margin-left 0.4s; padding: 20px; margin-left: 60px; flex:1 0 auto; }
#main.expanded { margin-left: 250px; }

/* Sidebar */
.sidebar { height: 100%; width: 60px; position: fixed; z-index:1040; top:0; left:0; background-color: #1f2937; overflow-x:hidden; transition:0.4s; padding-top:20px; }
.sidebar.expanded { width:250px; padding-top:60px; }
.sidebar a { padding:15px; text-decoration:none; font-size:16px; color:#fff; display:flex; align-items:center; transition:0.3s; }
.sidebar a i { width:25px; text-align:center; }
.sidebar a span { margin-left:10px; display:none; }
.sidebar.expanded a span { display:inline; }
.sidebar a:hover { color:#3b82f6; }
.sidebar-img-container { padding:10px 0; }
.sidebar-img { width:50px; height:50px; border-radius:50%; object-fit:cover; display:block; margin:0 auto 10px auto; border:2px solid #fff; }

/* Welcome Section */
.welcome-section h1 { font-size:2rem; font-weight:600; color:#111827; }
.welcome-section p { font-size:1rem; color:#6b7280; line-height:1.7; }

/* Cards */
.card { border-radius:20px; overflow:hidden; transition: transform 0.3s, box-shadow 0.3s; position: relative; background-color:#fff; }
.card:hover { transform:translateY(-5px); box-shadow:0 20px 35px rgba(0,0,0,0.25); }
.card-img-top { height:180px; object-fit:cover; border-radius:20px 20px 0 0; transition: transform 0.3s; cursor:pointer; }
.card:hover .card-img-top { transform: scale(1.05); }
.card-body { padding:1.2rem; }
.card-text { font-size:0.95rem; color:#4b5563; }
.btn-hire, .btn-view { font-size:0.85rem; border-radius:8px; flex:1; margin:2px 0; }
.btn-view { color:#fff; background-color:#6b7280; border:none; }
.btn-view:hover { background-color:#4b5563; color:#fff; }
.card-img-overlay { position:absolute; top:0; left:0; width:100%; height:100%; border-radius:20px 20px 0 0; background:rgba(0,0,0,0.15); opacity:0; transition:opacity 0.3s; pointer-events:none; }
.card:hover .card-img-overlay { opacity:1; }
.row.g-4 { margin-top:1.5rem; }
.card-buttons { display:flex; gap:0.5rem; flex-wrap:wrap; }

/* Footer */
footer { background:#1f2937; color:#fff; padding:20px 0; margin-top:auto; }
footer a { color:#fff; text-decoration:none; }
footer a:hover { color:#3b82f6; text-decoration:underline; }
footer .border-top { border-color:#4b5563 !important; }
.footer-seal { width:60px; height:auto; transition: transform 0.3s; }
.footer-seal:hover { transform: scale(1.15); } /* Hover scale effect */
@media (max-width:767px){
    footer .d-flex { flex-direction: column !important; text-align: center; }
    footer .footer-seal { margin-top:10px; }
}

/* Modal */
.modal-img { width:100%; }

/* Modern button */
.btn-modern { font-size:1rem; border-radius:8px; padding:10px 25px; background-color:#1f2937; color:white; border:none; cursor:pointer; }
.btn-modern:hover { background-color:#3b82f6; color:white; }
</style>
</head>
<body>

<!-- Sidebar -->
<div id="mySidebar" class="sidebar">
  <div class="sidebar-img-container text-center mb-3">
    <img src="../log-in/Seal_of_Zamboanga_City.png" alt="ZC Seal" class="sidebar-img">
  </div>
  <a href="javascript:void(0)" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i> <span>Menu</span></a>
  <a href="hire-a-guide.php"><i class="fa-solid fa-user-tie"></i> <span>Hire a Guide</span></a>
  <a href="custom-request.php"><i class="fa-solid fa-map-location-dot"></i> <span>Custom Request</span></a>
  <a href="browsing-places.php"><i class="fa-solid fa-compass"></i> <span>Browse Places!</span></a>
  <a href="my-bookings.php"><i class="fa-solid fa-calendar-check"></i> <span>Bookings</span></a>
  <a href="profile-user.php"><i class="fa-solid fa-user-circle"></i> <span>Profile</span></a>
  <a href="../log-in/login.php"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
</div>

<!-- Main Content -->
<div id="main">
  <section class="py-4 text-center container welcome-section">
    <div class="row py-lg-4">
      <div class="col-lg-8 col-md-10 mx-auto">
        <h1 class="fw-bold">Welcome, <?= htmlspecialchars($fname) . " " . htmlspecialchars($lname) ?>!</h1>
        <p class="lead">
          Discover the hidden gems and iconic spots of Zamboanga City with ease. Browse places or hire guides.
        </p>
      </div>
    </div>
  </section>

  <!-- Album Cards -->
  <div class="container py-4" id="guide-album">
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
      <?php foreach($cards as $card): ?>
      <div class="col">
        <div class="card shadow-sm">
          <img src="<?= $card['img'] ?>" class="card-img-top view-img" alt="<?= $card['alt'] ?>">
          <div class="card-img-overlay"></div>
          <div class="card-body">
            <p class="card-text"><?= $card['text'] ?></p>
            <div class="card-buttons">
              <a href="hire-a-guide.php" class="btn btn-sm btn-primary btn-hire">Hire a Guide</a>
              <button class="btn btn-sm btn-view view-img-btn">View</button>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-body p-0">
        <img src="../log-in/Seal_of_Zamboanga_City.png" class="modal-img" alt="Preview">
      </div>
    </div>
  </div>
</div>

<!-- Footer -->
<footer>
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6 mb-3 text-start">
        <h5>Contact Information</h5>
        <p class="mb-1">Phone: 0962622-3168 || 0906909 -0124</p>
        <p>Email: <a href="mailto:ae202403715@wmsu.edu.ph">ae202403715@wmsu.edu.ph</a></p>
      </div>
      <div class="col-md-6 mb-3 text-start">
        <h5>Location</h5>
        <p class="mb-1">City Government of Zamboanga</p>
        <p>Zamboanga City Hall, NS Valderrosa Street, Zone IV</p>
        <p>Zamboanga City</p>
      </div>
    </div>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center pt-3 border-top">
      <small>&copy; <?= date("Y") ?> Zamboanga Tour. All Rights Reserved.</small>
      <img src="../log-in/Seal_of_Zamboanga_City.png" alt="Seal of Zamboanga" class="footer-seal mt-2 mt-md-0">
    </div>
  </div>
</footer>

<script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Enlarge image on view
  document.querySelectorAll('.view-img-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const img = this.closest('.card').querySelector('.card-img-top');
      document.querySelector('.modal-img').src = img.src;
      new bootstrap.Modal(document.getElementById('imageModal')).show();
    });
  });

  // Sidebar toggle
  const sidebar = document.getElementById('mySidebar');
  const main = document.getElementById('main');
  function toggleSidebar() {
    sidebar.classList.toggle('expanded');
    main.classList.toggle('expanded');
  }
</script>
</body>
</html>
