
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password</title>
<style>
    /* Reset default margin and padding */
    * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }

    body {
        height:100vh;
        display:flex;
        justify-content:center;
        align-items:center;
        background:#f0f2f5;
    }

    form {
        background:#fff;
        padding:40px 30px;
        border-radius:12px;
        box-shadow:0 4px 20px rgba(0,0,0,0.1);
        width:100%;
        max-width:400px;
        text-align:center;
    }

    form h2 {
        margin-bottom:25px;
        color:#212832;
        font-weight:600;
    }

    form input[type="email"] {
        width:100%;
        padding:12px 15px;
        margin-bottom:20px;
        border:1px solid #ccc;
        border-radius:8px;
        font-size:14px;
    }

    form button {
        width:100%;
        padding:12px;
        border:none;
        border-radius:8px;
        background:#2563eb;
        color:#fff;
        font-size:16px;
        cursor:pointer;
        transition:background 0.3s;
    }

    form button:hover {
        background:#1e40af;
    }

    /* Optional: small instruction text */
    form p {
        font-size:13px;
        color:#555;
        margin-top:15px;
    }

</style>
</head>
<body>

<form action="send-link-reset.php" method="POST">
    <h2>Forgot Password</h2>
    <input type="email" name="email" placeholder="Enter your email" required>
    <button type="submit">Send Reset Link</button>
    <p>We'll send a link to your email to reset your password.</p>
    <p><a href="login.php">  Already ready to login into your Account? </a><p>
</form>

</body>
</html>

</html>