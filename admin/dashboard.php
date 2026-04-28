<?php
/**
 * dashboard.php
 * Landing page and high-level KPIs.
 */
session_start();
require_once '../db_connect.php';

// Security check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?error=unauthorized");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AUF Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-slate-50 flex">

    <?php require_once 'sidebar.php'; ?>

    <div
        class="flex-1 ml-64 [.collapsed-sidebar_&]:ml-20 transition-all duration-300 min-h-screen relative z-0 overflow-hidden bg-slate-50/80">

        <div
            class="absolute top-0 left-0 w-full h-96 bg-gradient-to-b from-blue-900/5 to-transparent -z-10 pointer-events-none">
        </div>
        <div
            class="absolute -top-24 -right-24 w-96 h-96 bg-blue-400/10 rounded-full blur-3xl -z-10 pointer-events-none">
        </div>
        <div
            class="absolute top-48 -left-24 w-72 h-72 bg-yellow-400/10 rounded-full blur-3xl -z-10 pointer-events-none">
        </div>

        <header
            class="bg-white/60 backdrop-blur-lg shadow-sm border-b border-slate-200/60 h-16 flex items-center px-8 sticky top-0 z-20">
            <h1 class="text-xl font-extrabold text-slate-800 tracking-tight flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                    </path>
                </svg>
                Dashboard Overview
            </h1>
        </header>

        <main class="p-8 space-y-6 max-w-7xl mx-auto w-full">

        </main>
    </div>

</body>

</html>