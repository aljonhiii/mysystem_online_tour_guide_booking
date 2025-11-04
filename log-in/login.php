<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zamboanga Tour Guide Login</title>
<link rel="stylesheet" href="../bootstrap-5.3.8-dist/css/bootstrap.min.css">
<style>
    body {
        margin: 0;
        height: 100vh;
        font-family: 'Segoe UI', sans-serif;
        font-style: italic;
        display: flex;
        justify-content: center;
        align-items: center;
        background: url('zc.png') no-repeat center center;
        background-size: cover;
        background-attachment: fixed;
    }
    .login-card {
        width: 320px;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(0,0,0,0.4);
        background-color: #fff;
        backdrop-filter: blur(5px);
    }
    .login-header {
        position: relative;
        padding: 2rem 1rem 1rem 1rem;
        text-align: center;
        color: #fff;
        background: url('png.jpeg') no-repeat center center;
        background-size: cover;
    }
    .login-header::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.4);
    }
    .login-header img, .login-header h3 {
        position: relative; z-index: 1;
    }
    .login-header img {
        width: 70px; border-radius: 50%;
        margin-bottom: 0.5rem; border: 2px solid #fff;
    }
    .login-header h3 { margin: 0; font-weight: bold; font-size: 1.3rem; }
    .login-body { padding: 1.5rem; }
    #error-message {
        font-size: 0.9rem; text-align: center; margin-bottom: 10px;
        color: #dc3545; opacity: 0; transition: opacity 0.4s ease;
    }
    #error-message.show { opacity: 1; }
</style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <img src="Seal_of_Zamboanga_City.png" alt="Zamboanga City Logo">
        <h3>Welcome to Zamboanga City!</h3>
    </div>
    <div class="login-body">
        <form id="loginForm">
            <div id="error-message"></div>

            <div class="form-floating mb-2">
                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                <label for="email">Email address</label>
            </div>

            <div class="form-floating mb-2">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                <label for="password">Password</label>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-2">Login</button>

            <p class="mt-2 text-center text-muted">
                Don't have an account? <a href="registration.php">Register</a>
            </p>
             <p class="text-center mt-2">
             <a href="forgot-password.php">Forgot Password?</a>
         </p>
        </form>
    </div>
</div>

<script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById("loginForm").addEventListener("submit", async function(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const errorDiv = document.getElementById("error-message");
    errorDiv.textContent = "";
    errorDiv.classList.remove("show");

    try {
        const response = await fetch("login-process.php", {
            method: "POST",
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            // Redirect based on user type
            if (result.user_type === "guide") {
                window.location.href = "../guide/-dashboard.php";
            } else if (result.user_type === "admin") {
                window.location.href = "../admin/admin-dashboard.php";
            } else {
                window.location.href = "../user/index.php";
            }
        } else {
            errorDiv.textContent = result.message;
            errorDiv.classList.add("show");
        }
    } catch (err) {
        errorDiv.textContent = "Something went wrong. Please try again.";
        errorDiv.classList.add("show");
    }
});
</script>
</body>
</html>
