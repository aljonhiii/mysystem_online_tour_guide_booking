<?php
session_start();
require_once "../classes/database.php";

// Restrict access to logged-in users only
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../log-in/login.php");
    exit;
}

$db = new Database();
$conn = $db->connect();
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hire a Guide - Zamboanga Tour</title>
<link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
<style>
body {
    background: #f1f3f6;
    font-family: 'Segoe UI', sans-serif;
    margin: 0;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
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
.sidebar a { padding: 15px; text-decoration: none; font-size: 16px; color: #fff; display: flex; align-items: center; transition: all 0.3s; }
.sidebar a i { width: 25px; text-align: center; }
.sidebar a span { margin-left: 10px; display: none; }
.sidebar.expanded a span { display: inline; }
.sidebar a:hover { color: #3b82f6; }

/* Main content */
#main { transition: margin-left 0.4s; padding: 20px; margin-left: 250px; flex:1; }
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

/* Top Navbar Search */
#topSearch {
    width: 50%;
    margin: 20px auto;
    display: block;
    border-radius: 30px;
    padding: 10px 15px;
    border: 1px solid #ccc;
}

/* Guide Cards */
.guide-card {
    border-radius: 25px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 12px 25px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s, box-shadow 0.3s;
    background-color: #000;
    height: 420px;
    color: #fff;
}
.guide-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.25);
}
.guide-bg {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    object-fit: cover;
    filter: brightness(0.7);
}
.guide-overlay {
    position: absolute;
    bottom: 0;
    width: 100%;
    padding: 20px;
    background: linear-gradient(transparent, rgba(0, 0, 0, 0.85));
    border-radius: 0 0 25px 25px;
}
.guide-overlay h5 {
    font-weight: 700;
    color: #fff;
    margin-bottom: 8px;
}
.guide-overlay p {
    font-size: 0.9rem;
    color: #ccc;
    margin-bottom: 10px;
}
.guide-details {
    display: flex;
    justify-content: space-between;
    font-size: 0.85rem;
    margin-bottom: 12px;
}
.btn-request {
    background-color: #fff;
    color: #000;
    font-weight: 600;
    width: 100%;
    padding: 10px 0;
    border: none;
    border-radius: 30px;
    transition: 0.3s;
}
.btn-request:hover {
    background-color: #1f2937;
    color: #fff;
}

/* Highlight match */
.highlight { background-color: #fff; color: #000; }

/* Modal */
textarea { resize:none; }
.modal .form-control, .modal .form-select { border-radius:12px; }
.modal button { border-radius:12px; }

/* Cards layout */
.guide-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
}

/* Footer */
footer {
    background:#1f2937;
    color:#fff;
    text-align:center;
    padding:20px 0;
}
footer img {
    width:60px;
    margin-top:10px;
}
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

<!-- Sidebar Toggle -->
<i id="sidebarToggle" class="fa-solid fa-bars toggle-btn"></i>

<!-- Main Content -->
<div id="main">
  <section class="py-4 text-center container">
    <div class="row py-lg-4">
      <div class="col-lg-8 col-md-10 mx-auto">
        <h1 class="fw-bold">Hire a Tour Guide</h1>
        <p class="lead text-muted">Browse our list of approved and available guides below.</p>
      </div>
    </div>
  </section>

  <!-- Flash Messages -->
  <?php if (isset($_SESSION['success'])): ?>
  <div class="alert alert-success w-75 mx-auto mt-3">
      <?= $_SESSION['success']; unset($_SESSION['success']); ?>
  </div>
  <?php elseif (isset($_SESSION['error'])): ?>
  <div class="alert alert-danger w-75 mx-auto mt-3">
      <?= $_SESSION['error']; unset($_SESSION['error']); ?>
  </div>
  <?php endif; ?>

  <!-- Top Navbar Search -->
  <input type="text" id="topSearch" placeholder="Search guides by name, expertise, languages, or rate...">

  <!-- Guide Cards -->
  <div class="container mt-4 mb-5">
    <div class="guide-grid" id="guideGrid">
      <?php
      $stmt = $conn->prepare("
          SELECT g.id AS guide_id, u.fname, u.lname, u.profile_image, g.expertise, g.languages, g.rate, g.availability
          FROM guides g
          JOIN users u ON g.user_id = u.id
          WHERE g.status = 'approved'
      ");
      $stmt->execute();
      $guides = $stmt->fetchAll(PDO::FETCH_ASSOC);

      if ($guides):
          foreach ($guides as $guide):
      ?>
      <div class="guide-card" 
           data-fname="<?= strtolower($guide['fname']) ?>" 
           data-lname="<?= strtolower($guide['lname']) ?>" 
           data-expertise="<?= strtolower($guide['expertise']) ?>" 
           data-languages="<?= strtolower($guide['languages']) ?>"
           data-rate="<?= $guide['rate'] ?>">
          <img src="../log-in/uploads/<?= $guide['profile_image'] ?: 'default.jpg' ?>" class="guide-bg" alt="">
          <div class="guide-overlay">
              <h5><?= $guide['fname'] ?> <?= $guide['lname'] ?></h5>
              <p><?= $guide['expertise'] ?></p>
              <div class="guide-details">
                  <span>₱<?= $guide['rate'] ?>/hour</span>
                  <span><?= $guide['availability'] ?></span>
              </div>
              <button class="btn-request" data-bs-toggle="modal" data-bs-target="#requestModal<?= $guide['guide_id'] ?>">Request Guide</button>
          </div>
      </div>

      <!-- Modal -->
      <div class="modal fade" id="requestModal<?= $guide['guide_id'] ?>" tabindex="-1">
          <div class="modal-dialog">
              <div class="modal-content">
                  <div class="modal-header">
                      <h5 class="modal-title">Request <?= $guide['fname'] ?> <?= $guide['lname'] ?></h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <form action="process-hire-a-guide.php" method="POST">
                      <div class="modal-body">
                          <input type="hidden" name="guide_id" value="<?= $guide['guide_id'] ?>">

                          <div class="mb-2">
                              <textarea name="message" class="form-control" rows="3" placeholder="Enter your message..." required></textarea>
                          </div>

                          <!-- 🆕 Date to Tour -->
                          <div class="mb-2">
                              <label>Date to Tour</label>
                              <input type="date" name="tour_date" class="form-control" required>
                          </div>

                          <div class="mb-2">
                              <label>Payment Amount (₱)</label>
                              <input type="number" name="payment" class="form-control" min="1" required>
                          </div>

                          <div class="mb-2">
                              <label>Payment Method</label>
                              <select name="payment_mode" class="form-control" required>
                                  <option value="cash">Cash</option>
                                  <option value="card">Card</option>
                                  <option value="gcash">GCash</option>
                              </select>
                          </div>
                      </div>
                      <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-primary">Send Request</button>
                      </div>
                  </form>
              </div>
          </div>
      </div>

      <?php
          endforeach;
      else:
          echo "<p class='text-center text-muted'>No guides are currently available.</p>";
      endif;
      ?>
    </div>
  </div>
</div>

<!-- Footer -->
<footer>
    <div>
        <p>&copy; <?= date("Y") ?> Zamboanga Tour. All Rights Reserved.</p>
        <img src="../log-in/Seal_of_Zamboanga_City.png" alt="Seal of Zamboanga">
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

// Live Search Filter with Highlight
const searchInput = document.getElementById('topSearch');
const guideCards = document.querySelectorAll('.guide-card');

searchInput.addEventListener('input', () => {
    const term = searchInput.value.toLowerCase();

    guideCards.forEach(card => {
        let matched = false;

        const fields = [
            {attr: 'fname', el: card.querySelector('.guide-overlay h5')},
            {attr: 'lname', el: card.querySelector('.guide-overlay h5')},
            {attr: 'expertise', el: card.querySelector('.guide-overlay p')},
            {attr: 'languages', el: card.querySelector('.guide-overlay p')},
            {attr: 'rate', el: card.querySelector('.guide-details span:first-child')}
        ];

        fields.forEach(field => {
            const value = card.getAttribute(`data-${field.attr}`);
            if(value.includes(term) && term !== '') {
                matched = true;
                const regex = new RegExp(`(${term})`, 'gi');
                field.el.innerHTML = field.el.textContent.replace(regex, '<span class="highlight">$1</span>');
            } else {
                if(field.attr==='fname' || field.attr==='lname')
                    field.el.innerHTML = card.querySelector('.guide-overlay h5').textContent;
                else if(field.attr==='expertise' || field.attr==='languages')
                    field.el.innerHTML = card.querySelector('.guide-overlay p').textContent;
                else if(field.attr==='rate')
                    field.el.innerHTML = `₱${card.getAttribute('data-rate')}/hour`;
            }
        });

        card.style.display = matched || term === '' ? '' : 'none';
    });
});
</script>
</body>
</html>
