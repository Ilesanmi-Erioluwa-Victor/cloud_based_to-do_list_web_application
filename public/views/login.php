<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - CloudTasks</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-page">
<div class="auth-container">
<h1>CloudTasks</h1>
<h2>Sign In</h2>
<form id="loginForm">
<div class="form-group">
<label for="email">Email</label>
<input type="email" id="email" required placeholder="your@email.com">
</div>
<div class="form-group">
<label for="password">Password</label>
<input type="password" id="password" required placeholder="Enter your password">
</div>
<div class="form-actions">
<button type="submit" class="btn btn-primary">Sign In</button>
</div>
<p class="auth-link"><a href="/forgot-password">Forgot password?</a></p>
<p class="auth-link">Don't have an account? <a href="/register">Register</a></p>
<div id="errorMsg" class="error-message" style="display:none"></div>
</form>
</div>
<script src="/assets/js/auth.js"></script>
</body>
</html>
