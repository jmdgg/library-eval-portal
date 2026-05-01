<?php
/**
 * dashboard.php
 * Landing page and high-level KPIs with Premium UI Polish.
 */
session_start();
require_once '../db_connect.php';

// Security check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?error=unauthorized");
    exit;
}

// 2. Fetch Admin Details to personalize the UI
$username = $_SESSION['username'] ?? 'Admin';
$is_superadmin = $_SESSION['is_superadmin'] ?? 0;
$role_display = $is_superadmin ? 'Super Administrator' : 'Branch Administrator';

// 3. Fetch Dashboard Analytics
try {
    // Total Evaluations
    $stmt = $pdo->query("SELECT COUNT(*) FROM survey_submission");
    $totalEvaluations = $stmt->fetchColumn() ?: 0;

    // Overall Satisfaction
    $stmt = $pdo->query("SELECT AVG(score) FROM response_detail");
    $avgScore = round($stmt->fetchColumn() ?: 0, 1);

    // Most Active College
    $stmt = $pdo->query("SELECT college FROM survey_submission WHERE college IS NOT NULL AND college != '' GROUP BY college ORDER BY COUNT(*) DESC LIMIT 1");
    $mostActiveCollege = $stmt->fetchColumn() ?: 'N/A';

    // Pending Flags/Reviews
    $stmt = $pdo->query("SELECT COUNT(*) FROM survey_submission WHERE overall_rating IN ('Fair', 'Needs Improvement')");
    $pendingFlags = $stmt->fetchColumn() ?: 0;

    // Recent Submissions
    $stmt = $pdo->query("SELECT submission_id, submission_date, role, college, respondent_name, department, overall_rating FROM survey_submission ORDER BY submission_date DESC, submission_id DESC LIMIT 50");
    $recentSubmissions = $stmt->fetchAll();

    // Demographics
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM survey_submission GROUP BY role");
    $rolesData = $stmt->fetchAll();
    $rolesLabels = [];
    $rolesCounts = [];
    foreach ($rolesData as $row) {
        $rolesLabels[] = $row['role'];
        $rolesCounts[] = $row['count'];
    }

    // Trend Data
    $stmt = $pdo->query("SELECT DATE_FORMAT(submission_date, '%Y-%m') as month_str, COUNT(*) as count FROM survey_submission GROUP BY month_str ORDER BY month_str DESC LIMIT 12");
    $trendDataRaw = $stmt->fetchAll();
    $trendDataRaw = array_reverse($trendDataRaw);
    $trendLabels = [];
    $trendCounts = [];
    foreach ($trendDataRaw as $row) {
        $trendLabels[] = date('M Y', strtotime($row['month_str'] . '-01'));
        $trendCounts[] = $row['count'];
    }

} catch (Exception $e) {
    $totalEvaluations = 0;
    $avgScore = '0.0';
    $mostActiveCollege = 'N/A';
    $pendingFlags = 0;
    $recentSubmissions = [];
    $rolesLabels = ['Student', 'Faculty', 'NTP'];
    $rolesCounts = [65, 25, 10];
    $trendLabels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
    $trendCounts = [12, 19, 15, 25];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AUF Library</title>
    <!-- Using Tailwind for layout structure and utilities -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        slate: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                        },
                        apricot: '#F7882F',
                        offwhite: '#FAFAFA',
                        purewhite: '#FFFFFF'
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        body {
            background-color: #e2e8f0;
            color: #334155;
            font-family: 'Inter', sans-serif;
        }

        .btn-apricot {
            background-color: #F7882F;
            color: #FFFFFF;
            transition: all 0.2s ease-in-out;
        }

        .btn-apricot:hover {
            background-color: #e07725;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(247, 136, 47, 0.2);
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>

<body class="min-h-screen bg-slate-200 flex overflow-x-hidden">

    <?php require_once 'sidebar.php'; ?>

    <!-- Main Wrapper for Responsiveness -->
    <div
        class="flex-1 ml-64 [.collapsed-sidebar_&]:ml-20 transition-all duration-300 min-h-screen flex flex-col relative z-0">

        <!-- Consolidated Glassmorphic Header -->
        <header
            class="bg-white/60 backdrop-blur-lg shadow-sm border-b border-slate-200/60 h-20 flex items-center justify-between px-8 sticky top-0 z-30 flex-shrink-0">
            <div class="flex flex-col">
                <h1 class="text-xl font-extrabold text-slate-800 tracking-tight flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                        </path>
                    </svg>
                    Dashboard Overview
                </h1>
                <p class="text-sm text-slate-500 font-medium">Welcome back, <?php echo htmlspecialchars($username); ?>
                    &mdash; <?php echo $role_display; ?></p>
            </div>


        </header>

        <main class="max-w-7xl mx-auto p-8 space-y-8 w-full flex-1">

            <!-- 1. KPI 'Glance Cards' Row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Evaluations -->
                <div
                    class="bg-white rounded-2xl p-6 shadow-[0_2px_10px_rgb(0,0,0,0.02)] border border-slate-100 flex items-center gap-5 hover:shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-shadow">
                    <div
                        class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Total Evaluations
                        </p>
                        <p class="text-2xl font-extrabold text-slate-800">
                            <?php echo htmlspecialchars($totalEvaluations); ?></p>
                    </div>
                </div>

                <!-- Overall Satisfaction -->
                <div
                    class="bg-white rounded-2xl p-6 shadow-[0_2px_10px_rgb(0,0,0,0.02)] border border-slate-100 flex items-center gap-5 hover:shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-shadow">
                    <div
                        class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Overall Satisfaction
                        </p>
                        <p class="text-2xl font-extrabold text-slate-800">
                            <?php echo number_format((float) $avgScore, 1); ?> <span
                                class="text-sm font-medium text-slate-400">/ 5.0</span></p>
                    </div>
                </div>

                <!-- Most Active College -->
                <div id="kpiMostActive"
                    class="bg-white rounded-2xl p-6 shadow-[0_2px_10px_rgb(0,0,0,0.02)] border border-slate-100 flex items-center gap-5 hover:shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-shadow cursor-pointer">
                    <div
                        class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Most Active College
                        </p>
                        <p class="text-lg font-extrabold text-slate-800 truncate w-32"
                            title="<?php echo htmlspecialchars($mostActiveCollege); ?>">
                            <?php echo htmlspecialchars($mostActiveCollege); ?>
                        </p>
                    </div>
                </div>

                <!-- Pending Flags/Reviews -->
                <div
                    class="bg-white rounded-2xl p-6 shadow-[0_2px_10px_rgb(0,0,0,0.02)] border border-slate-100 flex items-center gap-5 hover:shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-shadow">
                    <div
                        class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Pending Flags</p>
                        <p class="text-2xl font-extrabold text-slate-800"><?php echo htmlspecialchars($pendingFlags); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- 2. The Main Analytics Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
                <!-- Left Column (Performance Trend) -->
                <div
                    class="bg-white p-6 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 border-t-4 border-t-blue-500 relative overflow-hidden group lg:col-span-3 flex flex-col">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-extrabold text-slate-800 tracking-tight">Performance Trend</h3>
                        <div
                            class="bg-blue-50 text-blue-600 px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider">
                            Last 12 Months</div>
                    </div>
                    <div class="w-full h-80 relative flex-1">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>

                <!-- Right Column (Demographics) -->
                <div
                    class="bg-white p-6 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 border-t-4 border-t-indigo-500 relative overflow-hidden group lg:col-span-2 flex flex-col">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-extrabold text-slate-800 tracking-tight">Demographics</h3>
                        <div
                            class="bg-indigo-50 text-indigo-600 px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider">
                            User Distribution</div>
                    </div>
                    <div class="w-full h-80 relative flex-1">
                        <canvas id="demoChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- 3. Recent Activity Table -->
            <div
                class="bg-white p-6 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 border-t-4 border-t-emerald-500 relative overflow-hidden group flex flex-col">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-extrabold text-slate-800 tracking-tight">Recent Submissions</h3>
                        <p class="text-xs text-slate-500 mt-0.5">High-level view of latest library evaluations.</p>
                    </div>
                    <a href="respondents.php"
                        class="btn-apricot px-4 py-2 rounded-lg text-xs font-bold shadow-sm">Go To Respondents</a>
                </div>

                <div class="overflow-x-auto custom-scrollbar border border-slate-100 rounded-xl">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Date
                                </th>
                                <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Respondent</th>
                                <th
                                    class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">
                                    Satisfied?</th>
                                <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">College
                                </th>
                                <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">User
                                    Type</th>
                                <th
                                    class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-slate-50 bg-white">
                            <?php if (!empty($recentSubmissions)): ?>
                                    <?php foreach (array_slice($recentSubmissions, 0, 10) as $sub): ?>
                                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                                <td class="py-4 px-6 text-slate-600 font-medium">
                                                    <?php echo date('M d, Y', strtotime($sub['submission_date'])); ?></td>
                                                <td class="py-4 px-6">
                                                    <div class="flex items-center gap-3">
                                                        <div
                                                            class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-xs uppercase">
                                                            <?php echo strtoupper(substr($sub['respondent_name'] ?: 'A', 0, 1)); ?>
                                                        </div>
                                                        <span
                                                            class="font-bold text-slate-800"><?php echo htmlspecialchars($sub['respondent_name'] ?: 'Anonymous'); ?></span>
                                                    </div>
                                                </td>
                                                <td class="py-4 px-6 text-center">
                                                    <?php
                                                    $rating = $sub['overall_rating'] ?? 'Fair';
                                                    $dot_color = 'bg-slate-400';
                                                    if (in_array($rating, ['Excellent', 'Very Satisfactory', 'Satisfactory']))
                                                        $dot_color = 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.4)]';
                                                    if (in_array($rating, ['Fair', 'Needs Improvement']))
                                                        $dot_color = 'bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.4)]';
                                                    ?>
                                                    <div class="flex items-center justify-center gap-2">
                                                        <span class="w-2 h-2 rounded-full <?php echo $dot_color; ?>"></span>
                                                        <span
                                                            class="text-[10px] font-bold uppercase tracking-tight text-slate-500"><?php echo $rating; ?></span>
                                                    </div>
                                                </td>
                                                <td class="py-4 px-6">
                                                    <span
                                                        class="bg-slate-100 text-slate-600 px-2 py-1 rounded-md text-[10px] font-bold"><?php echo htmlspecialchars($sub['college']); ?></span>
                                                </td>
                                                <td class="py-4 px-6 text-slate-500 font-medium">
                                                    <?php echo htmlspecialchars($sub['role']); ?></td>
                                                <td class="py-4 px-6 text-right">
                                                    <button
                                                        class="text-blue-600 hover:text-blue-800 font-bold text-xs uppercase tracking-wider transition-colors">Details</button>
                                                </td>
                                            </tr>
                                    <?php endforeach; ?>
                            <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="py-12 text-center text-slate-400 font-medium italic">No recent
                                            evaluation records found.</td>
                                    </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- Expanded Submissions Modal (Enhanced) -->
    <div id="submissionsModal"
        class="fixed inset-0 z-[60] hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div
            class="bg-white rounded-2xl shadow-2xl w-full max-w-6xl max-h-[90vh] flex flex-col overflow-hidden border border-slate-100">
            <div class="flex justify-between items-center p-6 border-b border-slate-100 bg-slate-50/50">
                <div>
                    <h3 class="text-xl font-extrabold text-slate-800 tracking-tight">Global Submissions Vault</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Explore and filter the complete collection of evaluation
                        data.</p>
                </div>
                <button id="closeSubmissionsModalBtn"
                    class="text-slate-400 hover:text-slate-600 transition-colors p-2 hover:bg-slate-100 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <div class="p-6 flex flex-col gap-6 overflow-hidden">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-grow relative group">
                        <div
                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" id="advancedSearch"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-3 text-sm outline-none focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-medium"
                            placeholder="Try 'college:ccs student' or 'smith'...">
                    </div>
                    <div class="flex gap-2">
                        <select id="filterCollege"
                            class="bg-white border border-slate-200 rounded-xl px-4 py-2 text-xs font-bold text-slate-700 shadow-sm outline-none focus:border-blue-500 transition-colors">
                            <option value="All Colleges">All Colleges</option>
                            <?php
                            $colleges = ["CAMP", "CAS", "CBA", "CCS", "CCJE", "CEA", "CED", "CON", "SOL", "SOM", "GS", "IS", "N/A"];
                            foreach ($colleges as $c)
                                echo "<option value=\"$c\">$c</option>";
                            ?>
                        </select>
                        <select id="filterUserType"
                            class="bg-white border border-slate-200 rounded-xl px-4 py-2 text-xs font-bold text-slate-700 shadow-sm outline-none focus:border-blue-500 transition-colors">
                            <option value="All User Types">All User Types</option>
                            <option value="Student">Student</option>
                            <option value="Faculty">Faculty</option>
                            <option value="Alumni">Alumni</option>
                            <option value="NTP">NTP</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-y-auto flex-1 custom-scrollbar border border-slate-100 rounded-xl shadow-inner">
                    <table class="w-full text-left border-collapse" id="expandedSubmissionsTable">
                        <thead class="sticky top-0 bg-slate-50 z-10 border-b border-slate-200">
                            <tr>
                                <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Date
                                </th>
                                <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Respondent</th>
                                <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">College
                                </th>
                                <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">User
                                    Type</th>
                                <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Dept.
                                </th>
                                <th
                                    class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-slate-50">
                            <?php foreach ($recentSubmissions as $sub): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="py-4 px-6 text-slate-500 font-medium"><?php echo $sub['submission_date']; ?>
                                        </td>
                                        <td class="py-4 px-6 font-bold text-slate-800">
                                            <?php echo htmlspecialchars($sub['respondent_name'] ?: 'Anonymous'); ?></td>
                                        <td class="py-4 px-6 font-bold text-blue-600">
                                            <?php echo htmlspecialchars($sub['college']); ?></td>
                                        <td class="py-4 px-6 font-medium text-slate-500">
                                            <?php echo htmlspecialchars($sub['role']); ?></td>
                                        <td class="py-4 px-6 font-medium text-slate-500">
                                            <?php echo htmlspecialchars($sub['department']); ?></td>
                                        <td class="py-4 px-6 text-right">
                                            <button
                                                class="text-blue-600 hover:text-blue-800 font-bold text-xs uppercase tracking-wider transition-colors">View</button>
                                        </td>
                                    </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaderboard Modal (Enhanced) -->
    <div id="leaderboardModal"
        class="fixed inset-0 z-[60] hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div
            class="bg-white rounded-2xl shadow-2xl w-full max-w-md border border-slate-100 flex flex-col overflow-hidden">
            <div class="flex justify-between items-center p-6 border-b border-slate-100 bg-slate-50/50">
                <h3 class="text-xl font-extrabold text-slate-800 tracking-tight">College Activity</h3>
                <button id="closeLeaderboardBtn" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto max-h-[60vh] space-y-3" id="leaderboardContent">
                <!-- Data injected via JS -->
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const trendCtx = document.getElementById('trendChart').getContext('2d');
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($trendLabels); ?>,
                    datasets: [{
                        label: 'Evaluations',
                        data: <?php echo json_encode($trendCounts); ?>,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.05)',
                        borderWidth: 3,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#3b82f6',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 10 } } },
                        x: { grid: { display: false }, ticks: { font: { size: 10 } } }
                    }
                }
            });

            const demoCtx = document.getElementById('demoChart').getContext('2d');
            new Chart(demoCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($rolesLabels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($rolesCounts); ?>,
                        backgroundColor: ['#6366f1', '#f59e0b', '#10b981', '#ec4899', '#8b5cf6', '#64748b'],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 10, padding: 15, font: { size: 11, weight: 'bold' } } }
                    },
                    cutout: '75%'
                }
            });
        });

        // Search and Modal Handlers
        const advancedSearch = document.getElementById('advancedSearch');
        const filterCollege = document.getElementById('filterCollege');
        const filterUserType = document.getElementById('filterUserType');

        function applyFilters() {
            const searchQuery = advancedSearch.value.toLowerCase();
            const collegeVal = filterCollege.value.toLowerCase();
            const userTypeVal = filterUserType.value.toLowerCase();

            const rows = document.querySelectorAll('#expandedSubmissionsTable tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const college = row.cells[2].textContent.trim().toLowerCase();
                const type = row.cells[3].textContent.trim().toLowerCase();

                let match = text.includes(searchQuery);
                if (collegeVal !== 'all colleges' && college !== collegeVal) match = false;
                if (userTypeVal !== 'all user types' && type !== userTypeVal) match = false;

                row.style.display = match ? '' : 'none';
            });
        }

        advancedSearch.addEventListener('input', applyFilters);
        filterCollege.addEventListener('change', applyFilters);
        filterUserType.addEventListener('change', applyFilters);



        const leadModal = document.getElementById('leaderboardModal');
        const leadContent = document.getElementById('leaderboardContent');
        document.getElementById('kpiMostActive').onclick = () => {
            const tally = {};
            document.querySelectorAll('#expandedSubmissionsTable tbody tr').forEach(row => {
                const college = row.cells[2].textContent.trim();
                if (college) tally[college] = (tally[college] || 0) + 1;
            });
            const sorted = Object.entries(tally).sort((a, b) => b[1] - a[1]);
            leadContent.innerHTML = sorted.map(([c, n]) => `
                <div class="flex justify-between items-center p-4 bg-slate-50 rounded-xl border border-slate-100 shadow-sm hover:shadow-md transition-all">
                    <span class="font-bold text-slate-800">${c}</span>
                    <span class="bg-blue-50 text-blue-600 text-[10px] font-bold px-3 py-1 rounded-full border border-blue-100">${n} Evaluations</span>
                </div>
            `).join('') || '<p class="text-center text-slate-400 py-6">No data available.</p>';
            leadModal.classList.remove('hidden');
        };

        document.getElementById('closeLeaderboardBtn').onclick = () => leadModal.classList.add('hidden');
        window.onclick = (e) => {
            if (e.target === leadModal) leadModal.classList.add('hidden');
        };
    </script>
</body>

</html>