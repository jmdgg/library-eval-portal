<?php
/**
 * forgot_password.php
 * Dual-Flow Password Recovery (Superadmin Root vs Subadmin Self-Serve)
 */
session_start();
require_once '../db_connect.php';
require_once '../config.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = "Please enter your email address.";
    } else {
        // Dual-Flow Logic
        $is_superadmin_flow = (strtolower($email) === strtolower(SUPERADMIN_EMAIL));
        
        // Find user by email (for subadmins) or by hardcoded root (for superadmin)
        if ($is_superadmin_flow) {
            $stmt = $pdo->prepare("SELECT * FROM admin_user WHERE role = 'superadmin' LIMIT 1");
            $stmt->execute();
        } else {
            $stmt = $pdo->prepare("SELECT * FROM admin_user WHERE email = ? AND role = 'subadmin' LIMIT 1");
            $stmt->execute([$email]);
        }
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Generate Secure Token
            $token = bin2hex(random_bytes(32));

            $update = $pdo->prepare("UPDATE admin_user SET reset_token = ?, token_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE admin_id = ?");
            $update->execute([$token, $user['admin_id']]);

            // Prepare Reset Link
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $reset_link = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;

            // Trigger Mailer
            $mail = new PHPMailer(true);
            try {
                // Server settings (Mailtrap Placeholder)
                $mail->isSMTP();
                $mail->Host       = MAIL_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = MAIL_USER;
                $mail->Password   = MAIL_PASS;
                $mail->Port       = MAIL_PORT;

                // Recipients
                $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
                $target_email = $is_superadmin_flow ? SUPERADMIN_EMAIL : $user['email'];
                $mail->addAddress($target_email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request - AUF Library';
                $mail->Body    = "
                    <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; padding: 20px;'>
                        <h2 style='color: #4A47A3;'>Password Reset</h2>
                        <p>We received a request to reset your password for the AUF Library Evaluation Dashboard.</p>
                        <p>Click the button below to set a new password. This link will expire in 1 hour.</p>
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='$reset_link' style='background-color: #4A47A3; color: white; padding: 12px 24px; text-decoration: none; font-weight: bold; border-radius: 4px;'>Reset My Password</a>
                        </div>
                        <p style='color: #64748b; font-size: 12px;'>If you did not request this, please ignore this email.</p>
                    </div>
                ";

                $mail->send();
                $message = "If an account with that email exists, a reset link has been sent to the registered address.";
            } catch (Exception $e) {
                // FALLBACK: Write to recovery_log.php securely
                $log_entry = "[" . date('Y-m-d H:i:s') . "] RESET_LINK for " . ($is_superadmin_flow ? "ROOT" : $user['username']) . ": " . $reset_link . "\n";
                file_put_contents('logs/recovery_log.php', $log_entry, FILE_APPEND);
                
                $message = "SMTP Error. However, the system has logged your recovery link internally. Contact your administrator.";
            }
        } else {
            // Generic message to prevent user enumeration
            $message = "If an account with that email exists, a reset link has been sent to the registered address.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - AUF Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #eef3ff; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white border border-slate-300 p-8 shadow-xl">
        <div class="mb-8 text-center">
            <h1 class="text-xl font-black text-slate-800 uppercase tracking-widest">Recover Access</h1>
            <p class="text-[10px] font-bold text-slate-400 uppercase mt-1">System Security / Password Reset</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold uppercase tracking-widest">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold uppercase tracking-widest">
                <?php echo $message; ?>
            </div>
        <?php else: ?>
            <form action="forgot_password.php" method="POST" class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Registered Email</label>
                    <input type="email" name="email" required placeholder="e.g. j.doe@auf.edu.ph" class="w-full text-xs font-bold border border-slate-300 px-4 py-3 outline-none focus:border-[#4A47A3] bg-slate-50">
                </div>
                <button type="submit" class="w-full bg-[#4A47A3] hover:bg-[#3b3882] text-white py-4 text-xs font-black uppercase tracking-widest transition-all">
                    Request Reset Link
                </button>
            </form>
        <?php endif; ?>

        <div class="mt-8 pt-6 border-t border-slate-100 text-center">
            <a href="login.php" class="text-[10px] font-black text-[#4A47A3] uppercase tracking-widest hover:underline">
                &larr; Back to Login
            </a>
        </div>
    </div>
</body>
</html>
