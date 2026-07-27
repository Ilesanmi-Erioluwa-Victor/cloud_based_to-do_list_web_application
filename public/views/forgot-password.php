<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - CloudTasks</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-page">
<div class="auth-container">
<h1>CloudTasks</h1>
<h2>Forgot Password</h2>
<form id="forgotForm">
<div class="form-group">
<label for="email">Email</label>
<input type="email" id="email" required placeholder="your@email.com">
</div>
<div class="form-actions">
<button type="submit" class="btn btn-primary">Send Reset Link</button>
</div>
<p class="auth-link"><a href="/login">Back to sign in</a></p>
<div id="errorMsg" class="error-message" style="display:none"></div>
<div id="successMsg" class="success-message" style="display:none"></div>
</form>
</div>
<script src="/assets/js/auth.js"></script>
</body>
</html>
