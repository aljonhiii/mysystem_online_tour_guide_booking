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

// Places array
$places = [
    ["Pasonanca Park","Green spaces and playgrounds for the family.","pasonanca.jpeg"],
    ["Sta. Cruz Island","Famous pink sand beaches and clear waters.","sta cruz.jpeg"],
    ["Merloquet Falls","Hidden waterfall for a refreshing experience.","merloquet.jpeg"],
    ["Vinta Boat Tour","Experience traditional boats along the coastline.","vintattt.jpeg"],
    ["Zamboanga Cathedral","Historical church in the heart of the city.","myhome2].jpeg"],
    ["Peak view","A peaceful retreat with lush greenery.","peak view.jpeg"],
    ["White Sand Beach","Perfect for sunbathing and beach activities.","whitesa.jpeg"],
    ["Muti","A quiet coastal barangay known for its mangroves and scenic rural charm.","muti.jpeg"],
    ["Fort Pilar","A historical Spanish-era fortress and religious shrine.","fort_pilar.jpeg"],
    ["Paseo del Mar","A lively seaside promenade with restaurants and sunset views.","paseo.jpeg"],
    ["Once Islas","A stunning cluster of 11 islands for snorkeling and eco-adventures.","once_islas.jpg"],
    ["Yakan Weaving Village","Discover traditional Yakan textiles and handwoven fabrics.","yakan weaving.webp"],
    ["Zamboanga Boulevard","A relaxing beachfront avenue ideal for walks and local food.","zamboanga_boulevard.jpeg"],
    ["Grassland","A wide open green field perfect for picnics and sunset viewing.","grassland_lantawan.jpeg"]
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
body { background:#f1f3f6; font-family:'Segoe UI', sans-serif; margin:0; display:flex; flex-direction:column; min-height:100vh; }

/* Sidebar */
.sidebar { height:100%; width:60px; position:fixed; top:0; left:0; background-color:#1f2937; overflow-x:hidden; transition:width 0.4s; padding-top:20px; z-index:1040; }
.sidebar.expanded { width:250px; padding-top:60px; }
.sidebar a { padding:15px; text-decoration:none; font-size:16px; color:#fff; display:flex; align-items:center; transition:all 0.3s; }
.sidebar a i { width:25px; text-align:center; }
.sidebar a span { margin-left:10px; display:none; }
.sidebar.expanded a span { display:inline; }
.sidebar a:hover { color:#3b82f6; }

/* Main content */
#main { transition:margin-left 0.4s; padding:20px; margin-left:250px; flex:1 0 auto; }
#main.collapsed { margin-left:60px; }

/* Toggle button */
.toggle-btn { position:fixed; top:15px; left:65px; z-index:1050; cursor:pointer; font-size:1.5rem; color:#1f2937; transition:left 0.4s; }
.toggle-btn.collapsed { left:15px; }

/* Search */
#topSearch { width:50%; margin:20px auto; display:block; border-radius:30px; padding:10px 15px; border:1px solid #ccc; }

/* Cards */
.place-card { border-radius:25px; overflow:hidden; position:relative; box-shadow:0 12px 25px rgba(0,0,0,0.2); transition:transform 0.3s,box-shadow 0.3s; background-color:#000; color:#fff; height:300px; cursor:pointer; }
.place-card:hover { transform:translateY(-5px); box-shadow:0 20px 40px rgba(0,0,0,0.25); }
.place-bg { position:absolute; top:0; left:0; width:100%; height:100%; object-fit:cover; filter:brightness(0.6); }
.place-overlay { position:absolute; bottom:0; width:100%; padding:20px; background:linear-gradient(transparent, rgba(0,0,0,0.85)); border-radius:0 0 25px 25px; }
.place-overlay h5 { margin-bottom:8px; }
.place-overlay p { font-size:0.85rem; color:#ccc; margin-bottom:10px; }
.highlight { background-color:yellow; color:#000; }

/* Grid */
.place-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:2rem; }

/* Footer */
footer { background:#1f2937; color:#fff; padding:30px 0; margin-top:auto; }
footer a { color:#fff; text-decoration:none; }
footer a:hover { color:#3b82f6; text-decoration:underline; }
footer .footer-top { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; border-top:1px solid #4b5563; padding-top:20px; margin-top:20px; }
footer .footer-top img { width:60px; transition: transform 0.3s; }
footer .footer-top img:hover { transform:scale(1.15); }
</style>
</head>
<body>

<!-- Sidebar -->
<div id="mySidebar" class="sidebar expanded">
  <a href="index.php"><i class="fa-solid fa-house"></i> <span>Home</span></a>
  <a href="hire-a-guide.php"><i class="fa-solid fa-user-tie"></i> <span>Hire a Guide</span></a>
  <a href="custom-request.php"><i class="fa-solid fa-map-location-dot"></i> <span>Custom Request</span></a>
  <a href="browsing-places.php"><i class="fa-solid fa-compass"></i> <span>Browse Places</span></a>
  <a href="my-bookings.php"><i class="fa-solid fa-calendar-check"></i> <span>My Bookings</span></a>
  <a href="profile-user.php"><i class="fa-solid fa-user-circle"></i> <span>Profile</span></a>
  <a href="../log-in/login.php"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
</div>

<!-- Toggle -->
<i id="sidebarToggle" class="fa-solid fa-bars toggle-btn"></i>

<!-- Main Content -->
<div id="main">
  <h2 class="text-center mb-4">Welcome, <?= htmlspecialchars($fname) . " " . htmlspecialchars($lname) ?>!</h2>
  <input type="text" id="topSearch" placeholder="Search places...">

  <div class="container mt-4 mb-5">
    <div class="place-grid" id="placeGrid">
      <?php foreach($places as $place): ?>
      <div class="place-card" data-name="<?= strtolower($place[0]) ?>" data-desc="<?= strtolower($place[1]) ?>">
        <img src="<?= $place[2] ?>" class="place-bg" alt="<?= $place[0] ?>">
        <div class="place-overlay">
          <h5><?= $place[0] ?></h5>
          <p><?= $place[1] ?></p>
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
        <img src="" class="modal-img" style="width:100%" alt="Preview">
      </div>
    </div>
  </div>
</div>

<!-- Footer -->
<footer>
  <div class="container">
    <div class="row">
      <div class="col-md-6 mb-3">
        <h5>Contact Information</h5>
        <p class="mb-1">Tel: (062) 992-0420 | 991-4525</p>
        <p>Email: <a href="mailto:pio.zamboangacity@superadmin">pio.zamboangacity@superadmin</a></p>
      </div>
      <div class="col-md-6 mb-3">
        <h5>Location</h5>
        <p class="mb-1">City Government of Zamboanga</p>
        <p>Zamboanga City Hall, NS Valderrosa Street, Zone IV</p>
        <p>Zamboanga City</p>
      </div>
    </div>
    <div class="footer-top mt-3 pt-3 border-top">
      <small>&copy; <?= date("Y") ?> Zamboanga Tour. All Rights Reserved.</small>
      <img src="../log-in/Seal_of_Zamboanga_City.png" alt="Seal of Zamboanga">
    </div>
  </div>
</footer>

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
sidebar.addEventListener('mouseenter', () => sidebar.classList.add('expanded'));
sidebar.addEventListener('mouseleave', () => {
    if (!toggleBtn.classList.contains('collapsed')) {
        sidebar.classList.remove('expanded');
        main.classList.add('collapsed');
    }
});

// Image modal
document.querySelectorAll('.place-card').forEach(card => {
  card.addEventListener('click', function() {
    const img = this.querySelector('.place-bg');
    document.querySelector('.modal-img').src = img.src;
    new bootstrap.Modal(document.getElementById('imageModal')).show();
  });
});

// Live search
const searchInput = document.getElementById('topSearch');
const placeCards = document.querySelectorAll('.place-card');

searchInput.addEventListener('input', () => {
    const term = searchInput.value.toLowerCase();
    placeCards.forEach(card => {
        let matched = card.dataset.name.includes(term) || card.dataset.desc.includes(term);
        card.style.display = matched || term==='' ? '' : 'none';

        // Highlight
        const h = card.querySelector('h5');
        const p = card.querySelector('p');
        h.innerHTML = card.dataset.name.replace(new RegExp(`(${term})`, 'gi'), '<span class="highlight">$1</span>');
        p.innerHTML = card.dataset.desc.replace(new RegExp(`(${term})`, 'gi'), '<span class="highlight">$1</span>');
    });
});
</script>
</body>
</html>
