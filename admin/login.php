<?php
// /admin/login.php
// AUF Library Evaluation Dashboard — Admin Login
session_start();
$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login — AUF Library Evaluation Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Inter', sans-serif; background: #eef3ff; display: flex; min-height: 100vh; margin: 0; }
        .split-wrap { display: flex; width: 100%; min-height: 100vh; flex-direction: column; }
        @media (min-width: 1024px) { .split-wrap { flex-direction: row; } }
        .left-panel { position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-direction: column; min-height: 38vh; padding: 2rem; background: linear-gradient(148deg, #0a1628 0%, #0f2460 18%, #1740b0 45%, #2563eb 72%, #3b82f6 100%); }
        @media (min-width: 1024px) { .left-panel { width: 45%; min-height: 100vh; } }
        .dot-grid { position: absolute; inset: 0; background-image: radial-gradient(rgba(255, 255, 255, 0.09) 1.2px, transparent 1.2px); background-size: 24px 24px; pointer-events: none; }
        .illus-wrap { width: 180px; margin-bottom: 1.5rem; filter: drop-shadow(0 20px 40px rgba(0,0,0,0.3)); }
        .panel-title { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; color: #fff; font-size: 2.5rem; margin-bottom: 0.5rem; text-align: center; }
        .panel-sub { color: #bfdbfe; font-size: 1rem; text-align: center; max-width: 28ch; line-height: 1.6; }
        .right-panel { flex: 1; display: flex; align-items: center; justify-content: center; background: #eef3ff; padding: 2rem; }
        .form-card { width: 100%; max-width: 420px; animation: cardIn 0.6s ease-out both; }
        @keyframes cardIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .card-inner { background: #fff; border-radius: 20px; box-shadow: 0 20px 50px -10px rgba(30, 58, 138, 0.15); padding: 2.5rem; }
        .card-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.5rem; font-weight: 700; color: #1e3a8a; margin-bottom: 0.25rem; }
        .card-sub { color: #64748b; font-size: 0.875rem; margin-bottom: 2rem; }
        .field-wrap { margin-bottom: 1.25rem; }
        .field-label { display: block; font-size: 0.85rem; font-weight: 600; color: #334155; }
        .input-wrap { position: relative; margin-top: 0.35rem; }
        .input-field { width: 100%; border: 1.5px solid #e2e8f0; border-radius: 10px; background: #f8fafc; font-size: 0.9rem; padding: 0.75rem 1rem 0.75rem 2.5rem; transition: all 0.2s; }
        .input-field:focus { outline: none; border-color: #3b82f6; background: #fff; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        .icon-left { position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .icon-right { position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%); color: #94a3b8; cursor: pointer; }
        .btn-login { width: 100%; border: none; border-radius: 10px; padding: 0.85rem; background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%); color: #fff; font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3); margin-top: 1rem; }
        .btn-login:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4); }
        .error-alert { display: flex; align-items: center; gap: 0.5rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 0.75rem; color: #b91c1c; font-size: 0.8rem; margin-bottom: 1.5rem; }
        .card-footer { text-align: center; margin-top: 1.5rem; color: #94a3b8; font-size: 0.7rem; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="split-wrap">
        <div class="left-panel">
            <div class="dot-grid"></div>
            <div style="position:relative; z-index:10; display:flex; flex-direction:column; align-items:center;">
                <div class="illus-wrap">
                    <img src="../auf_ul_logo.png" alt="AUF Logo" class="w-full">
                </div>
                <h1 class="panel-title">Welcome Back</h1>
                <p class="panel-sub">Access the <strong>AUF Library Evaluation and Analytics Portal</strong></p>
            </div>
        </div>

        <div class="right-panel">
            <div class="form-card">
                <div class="card-inner">
                    <div class="card-header">
                        <h2 class="card-title">Admin Sign In</h2>
                        <p class="card-sub">Secure access for authorized personnel only.</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="error-alert">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 17c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="authenticate.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />

                        <div class="field-wrap">
                            <label for="username" class="field-label">Username</label>
                            <div class="input-wrap">
                                <span class="icon-left">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                </span>
                                <input type="text" id="username" name="username" required placeholder="Enter username" class="input-field" />
                            </div>
                        </div>

                        <div class="field-wrap">
                            <div class="flex justify-between items-center">
                                <label for="password" class="field-label">Password</label>
                                <a href="forgot_password.php" class="text-[10px] font-bold text-blue-600 hover:underline">Forgot Password?</a>
                            </div>
                            <div class="input-wrap">
                                <span class="icon-left">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                </span>
                                <input type="password" id="password" name="password" required placeholder="••••••••" class="input-field" />
                                <span class="icon-right" onclick="togglePwd()">
                                    <svg id="eyeIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </span>
                            </div>
                        </div>

                        <button type="submit" class="btn-login">Login</button>
                    </form>
                </div>

                <p class="card-footer">
                    &copy; <?= date('Y') ?> AUF Library. All rights reserved.<br>
                    Restricted Access Portal.
                </p>
            </div>
        </div>
    </div>

    <script>
        function togglePwd() {
            const input = document.getElementById('password');
            input.type = input.type === 'password' ? 'text' : 'password';
        }
        if (window.matchMedia('(min-width: 1024px)').matches) {
            document.getElementById('username').focus();
        }
    </script>
</body>
</html>