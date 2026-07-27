<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - CloudTasks</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-page">
<div class="auth-container">
<h1>CloudTasks</h1>
<h2>Create Account</h2>
<form id="registerForm">
<div class="form-group">
<label for="name">Full Name</label>
<input type="text" id="name" required placeholder="Your name">
</div>
<div class="form-group">
<label for="email">Email</label>
<input type="email" id="email" required placeholder="your@email.com">
</div>
<div class="form-group">
<label for="password">Password</label>
<input type="password" id="password" required placeholder="At least 6 characters" minlength="6">
</div>
<div class="form-actions">
<button type="submit" class="btn btn-primary">Create Account</button>
</div>
<p class="auth-link">Already have an account? <a href="/login">Sign in</a></p>
<div id="errorMsg" class="error-message" style="display:none"></div>
</form>
</div>
<script src="/assets/js/auth.js"></script>
</body>
</html>
