<?php
/**
 * reset_password.php
 * Final stage of password recovery.
 */
session_start();
require_once '../db_connect.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = false;

if (empty($token)) {
    die("Invalid request. Token missing.");
}

// Verify token
$stmt = $pdo->prepare("SELECT admin_id, username FROM admin_user WHERE reset_token = ? AND token_expiry > NOW() LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("This link is invalid or has expired. Please request a new one.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pass = $_POST['password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (strlen($new_pass) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "Passwords do not match.";
    } else {
        // Success
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE admin_user SET password_hash = ?, reset_token = NULL, token_expiry = NULL WHERE admin_id = ?");
        $update->execute([$hashed, $user['admin_id']]);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - AUF Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #eef3ff; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white border border-slate-300 p-8 shadow-xl">
        <div class="mb-8 text-center">
            <h1 class="text-xl font-black text-slate-800 uppercase tracking-widest">Set New Password</h1>
            <p class="text-[10px] font-bold text-slate-400 uppercase mt-1">Establishing Identity for: <?php echo htmlspecialchars($user['username']); ?></p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold uppercase tracking-widest">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold uppercase tracking-widest">
                Password updated successfully. You can now login.
            </div>
            <div class="mt-8 text-center">
                <a href="login.php" class="bg-[#4A47A3] hover:bg-[#3b3882] text-white px-8 py-3 text-xs font-black uppercase tracking-widest">
                    Go to Login
                </a>
            </div>
        <?php else: ?>
            <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST" class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">New Password</label>
                    <input type="password" name="password" required minlength="8" class="w-full text-xs font-bold border border-slate-300 px-4 py-3 outline-none focus:border-[#4A47A3] bg-slate-50">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Confirm Password</label>
                    <input type="password" name="confirm_password" required minlength="8" class="w-full text-xs font-bold border border-slate-300 px-4 py-3 outline-none focus:border-[#4A47A3] bg-slate-50">
                </div>
                <button type="submit" class="w-full bg-[#4A47A3] hover:bg-[#3b3882] text-white py-4 text-xs font-black uppercase tracking-widest transition-all">
                    Update Password
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
