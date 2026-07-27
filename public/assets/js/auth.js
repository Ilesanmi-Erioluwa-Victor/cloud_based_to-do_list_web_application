document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const forgotForm = document.getElementById('forgotForm');
    const resetForm = document.getElementById('resetForm');

    async function apiPost(url, data) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        return res.json();
    }

    function showError(msg) {
        const el = document.getElementById('errorMsg');
        if (el) { el.textContent = msg; el.style.display = 'block'; }
    }

    function showSuccess(msg) {
        const el = document.getElementById('successMsg');
        if (el) { el.textContent = msg; el.style.display = 'block'; }
    }

    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const data = {
                email: document.getElementById('email').value,
                password: document.getElementById('password').value
            };
            const result = await apiPost('/api/auth/login', data);
            if (result.error) {
                showError(result.error);
            } else {
                localStorage.setItem('token', result.token);
                window.location.href = '/';
            }
        });
    }

    if (registerForm) {
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const data = {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                password: document.getElementById('password').value
            };
            const result = await apiPost('/api/auth/register', data);
            if (result.error) {
                showError(result.error);
            } else {
                localStorage.setItem('token', result.token);
                window.location.href = '/';
            }
        });
    }

    if (forgotForm) {
        forgotForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const data = {
                email: document.getElementById('email').value
            };
            const result = await apiPost('/api/auth/forgot-password', data);
            if (result.error) {
                showError(result.error);
            } else {
                showSuccess('If an account exists with that email, a reset link has been sent.');
            }
        });
    }

    if (resetForm) {
        resetForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const data = {
                token: document.getElementById('token').value,
                password: document.getElementById('password').value
            };
            const result = await apiPost('/api/auth/reset-password', data);
            if (result.error) {
                showError(result.error);
            } else {
                showSuccess('Password reset successfully! Redirecting to login...');
                setTimeout(() => window.location.href = '/login', 2000);
            }
        });
    }

    const verifyEmailPage = window.location.pathname === '/verify-email';
    if (verifyEmailPage) {
        const token = new URLSearchParams(window.location.search).get('token');
        if (token) {
            apiPost('/api/auth/verify-email', {token}).then(result => {
                if (result.error) showError(result.error);
                else showSuccess('Email verified! You can now use all features.');
            });
        }
    }
});
