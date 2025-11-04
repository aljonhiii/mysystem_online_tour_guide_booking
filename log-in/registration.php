<?php
require_once "../classes/database.php";

$errors = [];
$first_name = $middle_name = $last_name = $email = $password = $confirmPassword = $address = $userType = "";
$expertise = $languages = $rate = $availability = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $first_name = trim($_POST["first_name"]);
    $middle_name = trim($_POST["middle_name"] ?? '');
    $last_name = trim($_POST["last_name"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $confirmPassword = trim($_POST["confirmPassword"]);
    $address = trim($_POST["address"]);
    $userType = $_POST["userType"] ?? '';
    $expertise = trim($_POST["expertise"] ?? '');
    $languages = trim($_POST["languages"] ?? '');
    $rate = trim($_POST["rate"] ?? '');
    $availability = trim($_POST["availability"] ?? '');

    // --- Validation ---
    if (empty($first_name)) $errors['first_name'] = "First Name is required";
    if (!empty($first_name) && !preg_match("/^[a-zA-Z\s]+$/", $first_name)) $errors['first_name'] = "First Name should not contain numbers";

    if (!empty($middle_name) && !preg_match("/^[a-zA-Z\s]+$/", $middle_name)) $errors['middle_name'] = "Middle Name should not contain numbers";

    if (empty($last_name)) $errors['last_name'] = "Last Name is required";
    if (!empty($last_name) && !preg_match("/^[a-zA-Z\s]+$/", $last_name)) $errors['last_name'] = "Last Name should not contain numbers";

    if (empty($email)) $errors['email'] = "Email is required";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Invalid email format";

    if (empty($password)) $errors['password'] = "Password is required";
    elseif (strlen($password) < 6) $errors['password'] = "Password must be at least 6 characters";

    if (empty($confirmPassword)) $errors['confirmPassword'] = "Confirm your password";
    elseif ($password !== $confirmPassword) $errors['confirmPassword'] = "Passwords do not match";

    if (empty($address)) $errors['address'] = "Address is required";

    if (empty($userType)) $errors['userType'] = "Please select a user type";

    if (empty($_FILES['profile_image']['name'])) {
        $errors['profile_image'] = "Profile image is required";
    }

    if ($userType === "guide") {
        if (empty($expertise)) $errors['expertise'] = "Expertise is required for guides";
        if (empty($languages)) $errors['languages'] = "Languages are required for guides";
        if (empty($rate)) $errors['rate'] = "Rate is required for guides";
        if (empty($availability)) $errors['availability'] = "Availability is required for guides";
    }

    // --- Insert into Database ---
    if (empty($errors)) {
        $db = new Database();
        $conn = $db->connect();

        try {
            // Begin transaction
            $conn->beginTransaction();

            // Check for duplicate email
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                $errors['email'] = "Email already exists";
                $conn->rollBack();
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Handle profile image
                $profile_image = null;
                if (!empty($_FILES['profile_image']['name'])) {
                    if (!is_dir("uploads")) mkdir("uploads", 0777, true);
                    $profile_image = time() . '_' . basename($_FILES['profile_image']['name']);
                    move_uploaded_file($_FILES['profile_image']['tmp_name'], "uploads/" . $profile_image);
                }

                // Insert into users table
                $stmt = $conn->prepare("
                    INSERT INTO users 
                    (fname, mname, lname, email, password, address, user_type, profile_image) 
                    VALUES (:fname, :mname, :lname, :email, :password, :address, :user_type, :profile_image)
                ");
                $stmt->bindParam(":fname", $first_name);
                $stmt->bindParam(":mname", $middle_name);
                $stmt->bindParam(":lname", $last_name);
                $stmt->bindParam(":email", $email);
                $stmt->bindParam(":password", $hashedPassword);
                $stmt->bindParam(":address", $address);
                $stmt->bindParam(":user_type", $userType);
                $stmt->bindParam(":profile_image", $profile_image);

                if ($stmt->execute()) {
                    // Get last inserted user ID
                    $userId = $conn->lastInsertId();

                    // If guide, insert guide details
                    if ($userType === "guide") {
                        $stmtGuide = $conn->prepare("
                            INSERT INTO guides (user_id, expertise, languages, rate, availability) 
                            VALUES (:user_id, :expertise, :languages, :rate, :availability)
                        ");
                        $stmtGuide->bindParam(":user_id", $userId);
                        $stmtGuide->bindParam(":expertise", $expertise);
                        $stmtGuide->bindParam(":languages", $languages);
                        $stmtGuide->bindParam(":rate", $rate);
                        $stmtGuide->bindParam(":availability", $availability);
                        $stmtGuide->execute();
                    }

                    // Commit transaction
                    $conn->commit();
                    header("Location: login.php");
                    exit;
                } else {
                    throw new Exception("Registration failed");
                }
            }

        } catch (Exception $e) {
            // Rollback transaction if any error
            if ($conn->inTransaction()) $conn->rollBack();
            $errors['general'] = "Registration failed. Try again. (" . $e->getMessage() . ")";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Registration Form</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
body, html { height:100%; margin:0; font-family:'Segoe UI', sans-serif; font-style:italic; background:#f0f2f5; display:flex; justify-content:center; align-items:center; padding:20px; }
.split-container { display:flex; width:950px; max-width:100%; border-radius:30px; overflow:hidden; box-shadow:0 8px 25px rgba(0,0,0,0.15); background:white; flex-wrap:wrap; }
.left-side { flex:1; padding:40px 35px; background:white; display:flex; flex-direction:column; justify-content:center; border-top-left-radius:30px; border-bottom-left-radius:30px; }
.left-side h4 { text-align:center; margin-bottom:30px; font-weight:600; color:#333; }
.form-control { border-radius:10px; margin-bottom:15px; font-size:0.9rem; }
.form-row { display:flex; gap:15px; margin-bottom:10px; }
.form-row > div { flex:1; }
.btn-submit { margin-top:10px; background-color:#6c63ff; border:none; color:white; font-weight:600; padding:12px; border-radius:10px; width:100%; font-size:1rem; }
.btn-submit:hover { background-color:#574fd6; }
.sign-in-line { margin-top:20px; text-align:center; font-weight:600; }
.sign-in-line a { color:#007BFF; text-decoration:none; font-weight:bold; }
.sign-in-line a:hover { text-decoration:underline; }
.right-side { flex:1; background:linear-gradient(rgba(5,5,5,0.85),rgba(32,32,32,0.85)), url('https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80') no-repeat center center; background-size:cover; color:white; padding:40px 30px; display:flex; flex-direction:column; justify-content:center; text-align:center; border-top-right-radius:30px; border-bottom-right-radius:30px; }
.right-side h2 { font-weight:700; font-size:2.1rem; margin-bottom:20px; }
.right-side p { font-size:1.05rem; line-height:1.6; max-width:320px; margin:0 auto; }
@media(max-width:768px) { .split-container { flex-direction:column; border-radius:20px; } .right-side { border-radius:0 0 20px 20px; padding:25px; } .left-side { border-radius:20px 20px 0 0; padding:30px; } .form-row { flex-direction:column; } }
.error-msg { color:red; font-size:0.85rem; margin-top:-10px; margin-bottom:10px; }
/* Make toggle default white */
.toggle-btn { background-color: white !important;color: #6c63ff !important;border: 2px solid #6c63ff !important; }
/* Keep active toggle purple */
.toggle-btn.active {background-color: #6c63ff !important; color: white !important; border-color: #6c63ff !important;}
</style>
</head>
<body>

<div class="split-container">
  <div class="left-side">
    <h4>Create Account</h4>
    <?php if(isset($errors['general'])) echo "<p class='error-msg'>{$errors['general']}</p>"; ?>
    <form action="" method="POST" enctype="multipart/form-data">

      <div class="form-row">
        <div>
          <input type="text" class="form-control" name="first_name" placeholder="First Name" value="<?= htmlspecialchars($first_name) ?>">
          <?php if(isset($errors['first_name'])) echo "<div class='error-msg'>{$errors['first_name']}</div>"; ?>
        </div>
        <div>
          <input type="text" class="form-control" name="middle_name" placeholder="Middle Name (Optional)" value="<?= htmlspecialchars($middle_name) ?>">
        </div>
        <div>
          <input type="text" class="form-control" name="last_name" placeholder="Last Name" value="<?= htmlspecialchars($last_name) ?>">
          <?php if(isset($errors['last_name'])) echo "<div class='error-msg'>{$errors['last_name']}</div>"; ?>
        </div>
      </div>

      <div class="form-row">
        <div>
          <input type="email" class="form-control" name="email" placeholder="Email" value="<?= htmlspecialchars($email) ?>">
          <?php if(isset($errors['email'])) echo "<div class='error-msg'>{$errors['email']}</div>"; ?>
        </div>
        <div>
          <input type="password" class="form-control" name="password" placeholder="Password">
          <?php if(isset($errors['password'])) echo "<div class='error-msg'>{$errors['password']}</div>"; ?>
        </div>
      </div>

      <div class="form-row">
        <div>
          <input type="password" class="form-control" name="confirmPassword" placeholder="Confirm Password">
          <?php if(isset($errors['confirmPassword'])) echo "<div class='error-msg'>{$errors['confirmPassword']}</div>"; ?>
        </div>
      </div>

      <!-- Profile Image -->
      <div class="form-row">
        <div>
          <label>Profile Image:</label>
          <input type="file" class="form-control" name="profile_image" accept="image/*">
          <?php if(isset($errors['profile_image'])) echo "<div class='error-msg'>{$errors['profile_image']}</div>"; ?>
        </div>
      </div>

      <textarea class="form-control" name="address" rows="2" placeholder="Address"><?= htmlspecialchars($address) ?></textarea>
      <?php if(isset($errors['address'])) echo "<div class='error-msg'>{$errors['address']}</div>"; ?>

      <!-- Toggle Buttons for User Type -->
      <div class="form-row mb-3">
        <button type="button" class="btn btn-outline-primary toggle-btn <?= $userType === 'user' || $userType==='' ? 'active' : '' ?>" data-type="user">User / Tourist</button>
        <button type="button" class="btn btn-outline-primary toggle-btn <?= $userType === 'guide' ? 'active' : '' ?>" data-type="guide">Guide</button>
        <input type="hidden" name="userType" id="userType" value="<?= htmlspecialchars($userType) ?>">
        <?php if(isset($errors['userType'])) echo "<div class='error-msg'>{$errors['userType']}</div>"; ?>
      </div>

      <!-- Guide Fields -->
      <div id="guideFields" style="display: <?= $userType === 'guide' ? 'block' : 'none' ?>;">
        <div class="form-row">
          <div>
            <input type="text" class="form-control" name="expertise" placeholder="Expertise" value="<?= htmlspecialchars($expertise) ?>">
            <?php if(isset($errors['expertise'])) echo "<div class='error-msg'>{$errors['expertise']}</div>"; ?>
          </div>
          <div>
            <input type="text" class="form-control" name="languages" placeholder="Languages" value="<?= htmlspecialchars($languages) ?>">
            <?php if(isset($errors['languages'])) echo "<div class='error-msg'>{$errors['languages']}</div>"; ?>
          </div>
        </div>

        <div class="form-row">
          <div>
            <input type="number" class="form-control" name="rate" placeholder="Rate/day (PHP)" value="<?= htmlspecialchars($rate) ?>">
            <?php if(isset($errors['rate'])) echo "<div class='error-msg'>{$errors['rate']}</div>"; ?>
          </div>
          <div>
            <input type="text" class="form-control" name="availability" placeholder="Availability" value="<?= htmlspecialchars($availability) ?>">
            <?php if(isset($errors['availability'])) echo "<div class='error-msg'>{$errors['availability']}</div>"; ?>
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-submit">Register</button>
    </form>

    <div class="sign-in-line">
      Already have an account? <a href="login.php">Sign in</a>
    </div>
  </div>

  <div class="right-side">
    <h2>Buenvenido na Zamboanga</h2>
    <p>Bene ya explora diamun bunito lugar ei tambien connecta na maga local guides. We’re glad you're here!</p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const toggleButtons = document.querySelectorAll('.toggle-btn');
  const guideFields = document.getElementById('guideFields');
  const userTypeInput = document.getElementById('userType');

  // Hide guide fields by default
  guideFields.style.display = 'none';

  // Ensure all toggles start as white
  toggleButtons.forEach(b => b.classList.remove('active'));

  toggleButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      // Remove active from all
      toggleButtons.forEach(b => b.classList.remove('active'));

      // Add active to the clicked one
      btn.classList.add('active');

      // Update hidden input value
      userTypeInput.value = btn.dataset.type;

      // Show guide fields if guide is selected
      guideFields.style.display = btn.dataset.type === 'guide' ? 'block' : 'none';
    });
  });
});

</script>

</body>
</html>
