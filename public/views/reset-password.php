<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password - CloudTasks</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-page">
<div class="auth-container">
<h1>CloudTasks</h1>
<h2>Reset Password</h2>
<form id="resetForm">
<input type="hidden" id="token" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">
<div class="form-group">
<label for="password">New Password</label>
<input type="password" id="password" required placeholder="At least 6 characters" minlength="6">
</div>
<div class="form-actions">
<button type="submit" class="btn btn-primary">Reset Password</button>
</div>
<p class="auth-link"><a href="/login">Back to sign in</a></p>
<div id="errorMsg" class="error-message" style="display:none"></div>
</form>
</div>
<script src="/assets/js/auth.js"></script>
</body>
</html>
