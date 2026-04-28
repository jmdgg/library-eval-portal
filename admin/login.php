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

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts: Plus Jakarta Sans + Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap"
        rel="stylesheet" />

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        display: ['"Plus Jakarta Sans"', 'sans-serif'],
                        body: ['Inter', 'sans-serif'],
                    },
                },
            },
        };
    </script>

    <style>
        /* ══════════════════════════════════════════════
       RESET & BASE
    ══════════════════════════════════════════════ */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        /*
      FLUID ROOT FONT — scales smoothly across all monitor sizes:
        1024px wide  →  14px
        1440px wide  →  15.5px
        1920px wide  →  17px
        2560px wide  →  18px (capped)
      Everything that uses rem/em scales with this automatically.
    */
        html {
            font-size: clamp(14px, 0.45vw + 9.4px, 18px);
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: #eef3ff;
            display: flex;
        }

        /* ══════════════════════════════════════════════
       LAYOUT — SPLIT SCREEN
    ══════════════════════════════════════════════ */
        .split-wrap {
            display: flex;
            width: 100%;
            min-height: 100vh;
            flex-direction: column;
            /* mobile: stack */
        }

        @media (min-width: 1024px) {
            .split-wrap {
                flex-direction: row;
            }

            /* desktop: side-by-side */
        }

        /* ══════════════════════════════════════════════
       LEFT PANEL — deep navy → royal blue gradient
    ══════════════════════════════════════════════ */
        .left-panel {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;

            /* Mobile: shorter strip */
            min-height: 38vh;
            padding: clamp(2rem, 5vw, 5rem) clamp(1.5rem, 5vw, 5rem);

            background: linear-gradient(148deg,
                    #0a1628 0%,
                    #0f2460 18%,
                    #1740b0 45%,
                    #2563eb 72%,
                    #3b82f6 100%);
        }

        @media (min-width: 1024px) {
            .left-panel {
                /* Fluid panel width: narrows gracefully on ultra-wide */
                width: clamp(42%, 44%, 50%);
                min-height: 100vh;
            }
        }

        /* Subtle dot grid texture — the only decoration on the panel */
        .dot-grid {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.09) 1.2px, transparent 1.2px);
            background-size: clamp(20px, 2.2vw, 32px) clamp(20px, 2.2vw, 32px);
            pointer-events: none;
        }

        /* Minimal illustration wrapper */
        .illus-wrap {
            width: clamp(160px, 20vw, 240px);
            height: auto;
            margin-bottom: clamp(1.2rem, 2vw, 2.2rem);
            display: flex;
            align-items: center;
            justify-content: center;
            filter: drop-shadow(0 20px 40px rgba(0, 0, 0, 0.3));
        }

        .illus-wrap svg {
            width: 100%;
            height: 100%;
            overflow: visible;
        }

        /* Fluid typography — left panel */
        .panel-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            color: #fff;
            line-height: 1.15;
            font-size: clamp(1.75rem, 2.8vw, 3.1rem);
            margin-bottom: 0.45rem;
        }

        .panel-sub {
            color: #bfdbfe;
            font-size: clamp(0.8rem, 1vw, 1.05rem);
            font-weight: 300;
            line-height: 1.65;
            max-width: 24ch;
        }

        .panel-sub strong {
            color: #fff;
            font-weight: 500;
        }

        /* Wordmark */
        .wordmark-wrap {
            display: flex;
            align-items: center;
            gap: clamp(0.5rem, 0.7vw, 0.75rem);
            margin-bottom: clamp(1.4rem, 2.4vw, 2.8rem);
        }

        .wordmark-icon {
            width: clamp(26px, 2.8vw, 40px);
            height: clamp(26px, 2.8vw, 40px);
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
            overflow: hidden;
            padding: 4px;
        }

        .wordmark-text {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            color: #fff;
            font-size: clamp(0.75rem, 1vw, 1rem);
            letter-spacing: 0.02em;
        }

        /* Trust badges */
        .badge-row {
            display: flex;
            align-items: center;
            gap: clamp(0.75rem, 1.4vw, 1.4rem);
            color: #93c5fd;
            font-size: clamp(0.62rem, 0.72vw, 0.76rem);
            font-weight: 500;
            margin-top: clamp(1.4rem, 2.4vw, 2.8rem);
        }

        .badge-divider {
            width: 1px;
            height: 11px;
            background: #1d4ed8;
            opacity: 0.6;
        }

        .badge-item {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .badge-item svg {
            width: clamp(11px, 1vw, 15px);
            height: clamp(11px, 1vw, 15px);
        }

        /* ══════════════════════════════════════════════
       RIGHT PANEL
    ══════════════════════════════════════════════ */
        .right-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eef3ff;
            padding: clamp(2rem, 4vw, 5rem) clamp(1rem, 3.5vw, 3.5rem);
        }

        /* ── Form card ── */
        .form-card {
            width: 100%;
            /* Fluid max-width: comfortable on small laptop (360px) → roomy on 4K (490px) */
            max-width: clamp(340px, 30vw, 490px);
            animation: cardIn 0.65s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        @keyframes cardIn {
            from {
                opacity: 0;
                transform: translateY(22px) scale(0.97);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .card-inner {
            background: #fff;
            border-radius: clamp(16px, 1.8vw, 26px);
            box-shadow:
                0 24px 64px -12px rgba(30, 58, 138, 0.18),
                0 4px 20px -4px rgba(30, 58, 138, 0.08);
            padding: clamp(1.75rem, 2.8vw, 2.9rem) clamp(1.5rem, 2.6vw, 2.7rem);
        }

        /* Card heading */
        .card-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(1.25rem, 1.7vw, 1.7rem);
            font-weight: 700;
            color: #1e3a8a;
            margin-bottom: 0.28rem;
        }

        .card-sub {
            color: #64748b;
            font-size: clamp(0.72rem, 0.85vw, 0.875rem);
        }

        /* Field group */
        .field-wrap {
            margin-bottom: clamp(0.85rem, 1.2vw, 1.25rem);
        }

        .field-label {
            display: block;
            font-size: clamp(0.72rem, 0.82vw, 0.85rem);
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.38rem;
        }

        /* Input */
        .input-wrap {
            position: relative;
        }

        .input-field {
            width: 100%;
            border: 1.5px solid #e2e8f0;
            border-radius: 0.75rem;
            background: #f8fafc;
            color: #1e293b;
            font-family: 'Inter', sans-serif;
            font-size: clamp(0.78rem, 0.88vw, 0.9rem);
            /* Fluid vertical padding: compact on small screens, roomy on large */
            padding: clamp(0.6rem, 0.85vw, 0.85rem) 1rem;
            padding-left: 2.6rem;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }

        .input-field::placeholder {
            color: #94a3b8;
        }

        .input-field:hover {
            border-color: #93c5fd;
            background: #f0f9ff;
        }

        .input-field:focus {
            outline: none;
            border-color: #3b82f6;
            background: #fff;
            box-shadow: 0 0 0 3.5px rgba(59, 130, 246, 0.14);
        }

        .input-pr {
            padding-right: 2.9rem;
        }

        /* password field right padding for eye icon */

        .icon-left,
        .icon-right {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            display: flex;
            align-items: center;
        }

        .icon-left {
            left: 0.82rem;
            pointer-events: none;
        }

        .icon-right {
            right: 0.82rem;
            cursor: pointer;
            transition: color 0.15s;
        }

        .icon-right:hover {
            color: #2563eb;
        }

        .icon-left svg,
        .icon-right svg {
            width: clamp(13px, 1.1vw, 17px);
            height: clamp(13px, 1.1vw, 17px);
        }

        /* Utility row */
        .utility-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: clamp(0.95rem, 1.3vw, 1.45rem);
            margin-top: clamp(0.1rem, 0.3vw, 0.4rem);
        }

        .check-label {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-size: clamp(0.7rem, 0.8vw, 0.82rem);
            color: #475569;
            cursor: pointer;
            user-select: none;
        }

        .check-label input[type="checkbox"] {
            width: clamp(13px, 1vw, 15px);
            height: clamp(13px, 1vw, 15px);
            accent-color: #2563eb;
            cursor: pointer;
            border-radius: 3px;
        }

        .forgot-link {
            font-size: clamp(0.7rem, 0.8vw, 0.82rem);
            font-weight: 600;
            color: #2563eb;
            text-decoration: none;
            transition: color 0.15s;
        }

        .forgot-link:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }

        /* Login button */
        .btn-login {
            position: relative;
            overflow: hidden;
            width: 100%;
            border: none;
            cursor: pointer;
            border-radius: 0.8rem;
            /* Fluid height: comfortable tap target on all sizes */
            padding: clamp(0.7rem, 1vw, 0.95rem) 1.5rem;
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 55%, #3b82f6 100%);
            color: #fff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(0.82rem, 0.95vw, 1rem);
            font-weight: 700;
            letter-spacing: 0.03em;
            box-shadow: 0 4px 22px -4px rgba(37, 99, 235, 0.55);
            transition: transform 0.15s ease, box-shadow 0.2s ease;
        }

        /* Shimmer sweep */
        .btn-login::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -65%;
            width: 38%;
            height: 200%;
            background: rgba(255, 255, 255, 0.18);
            transform: skewX(-22deg);
            transition: left 0.5s ease;
        }

        .btn-login:hover::after {
            left: 125%;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 30px -4px rgba(37, 99, 235, 0.62);
        }

        .btn-login:active {
            transform: translateY(0);
            box-shadow: 0 3px 12px -2px rgba(37, 99, 235, 0.42);
        }

        /* Error alert */
        .error-alert {
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 0.7rem;
            padding: 0.7rem 0.9rem;
            color: #b91c1c;
            font-size: clamp(0.7rem, 0.8vw, 0.82rem);
            margin-bottom: clamp(0.9rem, 1.2vw, 1.2rem);
            animation: shake 0.4s ease;
        }

        .error-alert svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            20% {
                transform: translateX(-5px);
            }

            40% {
                transform: translateX(5px);
            }

            60% {
                transform: translateX(-3px);
            }

            80% {
                transform: translateX(3px);
            }
        }

        /* Card header spacing */
        .card-header {
            margin-bottom: clamp(1.1rem, 1.7vw, 1.9rem);
        }

        /* Footer */
        .card-footer {
            margin-top: clamp(0.85rem, 1.3vw, 1.4rem);
            text-align: center;
            color: #94a3b8;
            font-size: clamp(0.58rem, 0.68vw, 0.7rem);
            line-height: 1.75;
        }

        /* Mobile alignment tweaks */
        @media (max-width: 640px) {

            .panel-title,
            .panel-sub {
                text-align: center;
            }

            .panel-sub {
                margin: 0 auto;
            }

            .badge-row {
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="split-wrap">

        <!-- ══════════════════════════════════════════════════
       LEFT PANEL — Branding & Illustration
  ══════════════════════════════════════════════════ -->
        <div class="left-panel">
            <div class="dot-grid"></div>

            <div
                style="position:relative;z-index:10;display:flex;flex-direction:column;align-items:center;text-align:center;">

                <!-- Wordmark -->


                <!--
        MINIMAL ILLUSTRATION
        A clean geometric composition: two concentric thin rings,
        a bare lock outline centred inside, and four small corner
        dots — all using only stroke, no fills, no gradients.
        Calm, purposeful, and on-brand without any visual noise.
      -->
                <div class="illus-wrap" aria-hidden="true">
                    <img src="../auf_ul_logo.png" alt="AUF University Library" class="w-full h-auto">
                </div>
                <!-- /MINIMAL ILLUSTRATION -->

                <style>
                    @keyframes spinCW {
                        to {
                            transform: rotate(360deg);
                        }
                    }

                    @keyframes spinCCW {
                        to {
                            transform: rotate(-360deg);
                        }
                    }
                </style>

                <!-- Headline -->
                <h1 class="panel-title">Welcome Back!</h1>
                <p class="panel-sub">
                    Sign in to the <strong>AUF Library</strong><br />Evaluation Dashboard
                </p>

                <!-- Trust badges -->
                <div class="badge-row">
                    <div class="badge-item">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.955 11.955 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                        SSL Secured
                    </div>
                    <div class="badge-divider"></div>
                    <div class="badge-item">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                        Authorized Access Only
                    </div>
                </div>

            </div>
        </div><!-- /left-panel -->


        <!-- ══════════════════════════════════════════════════
       RIGHT PANEL — Login Form
  ══════════════════════════════════════════════════ -->
        <div class="right-panel">
            <div class="form-card">
                <div class="card-inner">

                    <div class="card-header">
                        <h2 class="card-title">Admin Sign In</h2>
                        <p class="card-sub">Enter your credentials to access the dashboard.</p>
                    </div>

                    <!-- Error flash message (set in authenticate.php on failure) -->
                    <?php if ($error): ?>
                        <div class="error-alert">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                            <span>
                                <?= htmlspecialchars($error) ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <!-- ════════════════════════════════════
             LOGIN FORM → POST to authenticate.php
        ════════════════════════════════════ -->
                    <form method="POST" action="authenticate.php" novalidate>
                        <!-- CSRF token — validate this in authenticate.php -->
                        <input type="hidden" name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />

                        <!-- Username -->
                        <div class="field-wrap">
                            <label for="username" class="field-label">Username</label>
                            <div class="input-wrap">
                                <span class="icon-left">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
                                        aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                    </svg>
                                </span>
                                <input type="text" id="username" name="username" autocomplete="username" required
                                    spellcheck="false" placeholder="e.g. jdelacruz"
                                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" class="input-field" />
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="field-wrap">
                            <label for="password" class="field-label">Password</label>
                            <div class="input-wrap">
                                <span class="icon-left">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
                                        aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                    </svg>
                                </span>
                                <input type="password" id="password" name="password" autocomplete="current-password"
                                    required placeholder="••••••••" class="input-field input-pr" />
                                <!-- Eye toggle button -->
                                <span class="icon-right" id="eyeToggle" role="button" tabindex="0"
                                    aria-label="Show password" onclick="togglePwd()"
                                    onkeydown="if(event.key==='Enter'||event.key===' ')togglePwd()">
                                    <!-- Eye open (password hidden) -->
                                    <svg id="eyeOpen" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <!-- Eye closed (password visible) -->
                                    <svg id="eyeClosed" style="display:none;" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                </span>
                            </div>
                        </div>

                        <!-- Remember me + Forgot password -->
                        <div class="utility-row">
                            <label class="check-label">
                                <input type="checkbox" name="remember" />
                                Remember me
                            </label>
                            <a href="/admin/forgot-password.php" class="forgot-link">Forgot password?</a>
                        </div>

                        <!-- Submit -->
                        <button type="submit" class="btn-login">Login</button>

                        <!-- ════════════════════════════════════════════════════════
               PLACEHOLDER: Google Login Button
               ─────────────────────────────────────────────────────
               TODO (Future): Drop in the Google OAuth button below
               once credentials are configured in Google Cloud Console
               and the /admin/auth/google-callback.php handler exists.

               Suggested markup:
               <div style="margin-top:0.85rem;">
                 <div style="display:flex;align-items:center;gap:0.55rem;margin:0.9rem 0;">
                   <div style="flex:1;height:1px;background:#e2e8f0;"></div>
                   <span style="font-size:0.7rem;color:#94a3b8;white-space:nowrap;">or continue with</span>
                   <div style="flex:1;height:1px;background:#e2e8f0;"></div>
                 </div>
                 <a href="/admin/auth/google.php"
                    style="display:flex;align-items:center;justify-content:center;gap:0.65rem;
                           width:100%;padding:0.72rem 1rem;border-radius:0.8rem;
                           border:1.5px solid #e2e8f0;background:#fff;
                           font-size:0.85rem;font-weight:500;color:#334155;
                           text-decoration:none;transition:all 0.15s;"
                    onmouseover="this.style.background='#f0f9ff';this.style.borderColor='#93c5fd';"
                    onmouseout="this.style.background='#fff';this.style.borderColor='#e2e8f0';">
                   <img src="/assets/icons/google.svg" alt="" style="width:1.1rem;height:1.1rem;"/>
                   Login with Google
                 </a>
               </div>

               Dependencies:
                 - Google OAuth 2.0 Client ID in config/env.php
                 - league/oauth2-google (Composer) or similar
                 - /admin/auth/google.php         → redirect to Google consent
                 - /admin/auth/google-callback.php → exchange code, create session
          ════════════════════════════════════════════════════════ -->

                    </form>

                </div><!-- /card-inner -->

                <p class="card-footer">
                    Restricted administrative portal. Unauthorized access is prohibited.<br />
                    &copy;
                    <?= date('Y') ?> Angeles University Foundation Library. All rights reserved.
                </p>

            </div><!-- /form-card -->
        </div><!-- /right-panel -->

    </div><!-- /split-wrap -->

    <script>
        function togglePwd() {
            const input = document.getElementById('password');
            const eyeOpen = document.getElementById('eyeOpen');
            const eyeClosed = document.getElementById('eyeClosed');
            const toggle = document.getElementById('eyeToggle');
            const isHidden = input.type === 'password';

            input.type = isHidden ? 'text' : 'password';
            eyeOpen.style.display = isHidden ? 'none' : '';
            eyeClosed.style.display = isHidden ? '' : 'none';
            toggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        }

        // Auto-focus username field on desktop only
        if (window.matchMedia('(min-width: 1024px)').matches) {
            document.getElementById('username').focus();
        }
    </script>
</body>

</html>