<?php
/**
 * config.php
 * System-wide configurations and secure environment variables.
 */

// Superadmin Root Identity
define('SUPERADMIN_EMAIL', 'gayola.justinemarion@student.auf.edu.ph');

// PHPMailer Configuration (Mailtrap)
define('MAIL_HOST', 'sandbox.smtp.mailtrap.io');
define('MAIL_PORT', 2525);
define('MAIL_USER', '178116f505008b');
define('MAIL_PASS', '605e8e3f3ddbf6');
define('MAIL_FROM', 'LEAP@auf-library-null.com');
define('MAIL_FROM_NAME', 'AUF L.E.A.P');

// Security Key for Recovery Log
define('ALLOW_LOG', true);
