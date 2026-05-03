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

// --- DATE FILTER LOGIC ---
$boundsStmt = $pdo->query("SELECT MIN(created_at) as earliest, MAX(created_at) as latest FROM survey_submission");
$bounds = $boundsStmt->fetch();
$dbEarliest = $bounds['earliest'] ? date('Y-m-d', strtotime($bounds['earliest'])) : date('Y-m-01');
$dbLatest = $bounds['latest'] ? date('Y-m-d', strtotime($bounds['latest'])) : date('Y-m-d');

function getSqlDate($monthName, $year, $isEnd = false)
{
    $monthNum = date('m', strtotime($monthName));
    if ($isEnd)
        return date('Y-m-t 23:59:59', strtotime("$year-$monthNum-01"));
    return "$year-$monthNum-01 00:00:00";
}

$startMonth = $_GET['start_month'] ?? date('F', strtotime($dbEarliest));
$startYear = $_GET['start_year'] ?? date('Y', strtotime($dbEarliest));
$endMonth = $_GET['end_month'] ?? date('F', strtotime($dbLatest));
$endYear = $_GET['end_year'] ?? date('Y', strtotime($dbLatest));

$startDate = getSqlDate($startMonth, $startYear);
$endDate = getSqlDate($endMonth, $endYear, true);

// 3. Fetch Dashboard Analytics (Filtered by Date)
try {
    // Total Evaluations
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM survey_submission WHERE created_at BETWEEN ? AND ?");
    $stmt->execute([$startDate, $endDate]);
    $totalEvaluations = $stmt->fetchColumn() ?: 0;

    // Overall Satisfaction
    $stmt = $pdo->prepare("SELECT AVG(score) FROM response_detail rd JOIN survey_submission ss ON rd.submission_id = ss.submission_id WHERE ss.created_at BETWEEN ? AND ?");
    $stmt->execute([$startDate, $endDate]);
    $avgScore = round($stmt->fetchColumn() ?: 0, 1);

    // Most Active College
    $stmt = $pdo->prepare("
        SELECT c.college_name, COUNT(*) as eval_count
        FROM survey_submission ss 
        JOIN college c ON ss.college_id = c.college_id 
        WHERE ss.created_at BETWEEN ? AND ?
        GROUP BY c.college_id 
        ORDER BY eval_count DESC LIMIT 1
    ");
    $stmt->execute([$startDate, $endDate]);
    $collegeRow = $stmt->fetch();
    $mostActiveCollege = $collegeRow['college_name'] ?? 'N/A';
    $mostActiveCollegeCount = $collegeRow['eval_count'] ?? 0;

    // Pending Flags/Reviews
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM survey_submission 
        WHERE is_read = 0 
          AND created_at BETWEEN ? AND ?
          AND (
            (overall_rating IS NOT NULL AND CAST(overall_rating AS DECIMAL(10,2)) < 3.00) 
            OR (comments IS NOT NULL AND comments != '') 
            OR (recommendations IS NOT NULL AND recommendations != '')
          )
    ");
    $stmt->execute([$startDate, $endDate]);
    $pendingFlags = $stmt->fetchColumn() ?: 0;

    // Recent Submissions
    $stmt = $pdo->prepare("
        SELECT 
            ss.submission_id, 
            ss.created_at as submission_date, 
            pt.type_name as role, 
            COALESCE(c.college_name, 'N/A') as college, 
            ss.email as respondent_name, 
            ld.dept_name as department, 
            ss.overall_rating 
        FROM survey_submission ss
        JOIN patron_type pt ON ss.patron_type_id = pt.patron_type_id
        LEFT JOIN college c ON ss.college_id = c.college_id
        JOIN library_department ld ON ss.lib_dept_id = ld.lib_dept_id
        WHERE ss.created_at BETWEEN ? AND ?
        ORDER BY ss.created_at DESC, ss.submission_id DESC LIMIT 50
    ");
    $stmt->execute([$startDate, $endDate]);
    $recentSubmissions = $stmt->fetchAll();

    // Demographics
    $stmt = $pdo->query("
        SELECT pt.type_name as role, COUNT(*) as count 
        FROM survey_submission ss
        JOIN patron_type pt ON ss.patron_type_id = pt.patron_type_id 
        GROUP BY pt.patron_type_id
    ");
    $rolesData = $stmt->fetchAll();
    $rolesLabels = [];
    $rolesCounts = [];
    foreach ($rolesData as $row) {
        $rolesLabels[] = $row['role'];
        $rolesCounts[] = $row['count'];
    }

    // Trend Data
    $stmt = $pdo->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') as month_str, COUNT(*) as count 
        FROM survey_submission 
        GROUP BY month_str 
        ORDER BY month_str DESC LIMIT 12
    ");
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
                    borderRadius: {
                        'none': '0',
                        'sm': '0',
                        'DEFAULT': '0',
                        'md': '0',
                        'lg': '0',
                        'xl': '0',
                        '2xl': '0',
                        '3xl': '0',
                        'full': '0',
                    },
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
                        purewhite: '#FFFFFF',
                        biblue: '#4A47A3',
                        bigrey: '#E0E0E0'
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        * {
            border-radius: 0 !important;
        }

        body {
            background-color: #e2e8f0;
            color: #334155;
            font-family: 'Inter', sans-serif;
        }

        .shadow-sm,
        .shadow,
        .shadow-md,
        .shadow-lg,
        .shadow-xl,
        .shadow-2xl,
        .shadow-inner {
            box-shadow: none !important;
        }

        .btn-apricot {
            background-color: #F7882F;
            color: #FFFFFF;
            transition: all 0.1s ease-in-out;
            border: 1px solid #e07725;
        }

        .btn-apricot:hover {
            background-color: #e07725;
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
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .kpi-card {
            background: #FFFFFF;
            border: 1px solid #E0E0E0;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 120px;
            position: relative;
        }

        .kpi-title {
            color: #4A47A3;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .kpi-value {
            color: #000000;
            font-size: 1.875rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .kpi-subtext {
            color: #6B7280;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }

        .status-accent {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
        }
    </style>
</head>

<body class="min-h-screen bg-slate-200 flex overflow-x-hidden">

    <?php require_once 'sidebar.php'; ?>

    <!-- Main Wrapper for Responsiveness -->
    <div
        class="flex-1 ml-64 [.collapsed-sidebar_&]:ml-20 transition-all duration-300 min-h-screen flex flex-col relative z-0">

        <!-- Consolidated Flat Header -->
        <header
            class="bg-white border-b border-slate-300 h-20 flex items-center justify-between px-8 sticky top-0 z-30 flex-shrink-0">
            <div class="flex flex-col">
                <h1 class="text-lg font-bold text-slate-800 tracking-tight flex items-center gap-2">
                    <div class="p-1 bg-slate-100 border border-slate-300 rounded-none">
                        <svg class="w-4 h-4 text-[#4A47A3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                            </path>
                        </svg>
                    </div>
                    Dashboard Overview
                </h1>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-tight">System Terminal / Operational
                    Intelligence</p>
            </div>

            <!-- 'Control Center' Date Filters -->
            <div class="flex items-center gap-3">
                <div class="flex items-center bg-white border border-slate-300 p-1">
                    <!-- Start Date Pill -->
                    <div class="flex items-center gap-1 px-3 py-1 bg-slate-50 border border-slate-200 group">
                        <span class="text-[9px] font-black text-slate-500 uppercase tracking-tighter mr-1">From</span>
                        <select id="header_start_month" onchange="applyDateFilter()"
                            class="text-xs font-bold text-slate-800 bg-transparent border-none focus:ring-0 cursor-pointer py-0 px-1">
                            <?php $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                            foreach ($months as $m) {
                                $selected = (strtoupper($m) === strtoupper($startMonth)) ? 'selected' : '';
                                echo "<option value='" . strtoupper($m) . "' class='bg-white text-slate-800' $selected>$m</option>";
                            } ?>
                        </select>
                        <select id="header_start_year" onchange="applyDateFilter()"
                            class="text-xs font-bold text-slate-800 bg-transparent border-none focus:ring-0 cursor-pointer py-0 px-1">
                            <?php for ($y = date('Y', strtotime($dbEarliest)); $y <= date('Y'); $y++) {
                                $selected = ($y == $startYear) ? 'selected' : '';
                                echo "<option value='$y' class='bg-white text-slate-800' $selected>$y</option>";
                            } ?>
                        </select>
                    </div>

                    <!-- Separator Icon -->
                    <div class="px-2 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </div>

                    <!-- End Date Pill -->
                    <div class="flex items-center gap-1 px-3 py-1 bg-slate-50 border border-slate-200 group">
                        <span class="text-[9px] font-black text-slate-500 uppercase tracking-tighter mr-1">To</span>
                        <select id="header_end_month" onchange="applyDateFilter()"
                            class="text-xs font-bold text-slate-800 bg-transparent border-none focus:ring-0 cursor-pointer py-0 px-1">
                            <?php foreach ($months as $m) {
                                $selected = (strtoupper($m) === strtoupper($endMonth)) ? 'selected' : '';
                                echo "<option value='" . strtoupper($m) . "' class='bg-white text-slate-800' $selected>$m</option>";
                            } ?>
                        </select>
                        <select id="header_end_year" onchange="applyDateFilter()"
                            class="text-xs font-bold text-slate-800 bg-transparent border-none focus:ring-0 cursor-pointer py-0 px-1">
                            <?php for ($y = date('Y', strtotime($dbEarliest)); $y <= date('Y'); $y++) {
                                $selected = ($y == $endYear) ? 'selected' : '';
                                echo "<option value='$y' class='bg-white text-slate-800' $selected>$y</option>";
                            } ?>
                        </select>
                    </div>
                </div>

                <!-- No Apply button needed (Auto-submit on change) -->
            </div>
        </header>

        <main class="max-w-7xl mx-auto p-8 space-y-8 w-full flex-1">

            <!-- 1. KPI 'Glance Cards' Row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Evaluations -->
                <div class="kpi-card">
                    <div class="kpi-title">Total Evaluations</div>
                    <div class="kpi-value"><?php echo htmlspecialchars($totalEvaluations); ?></div>
                    <div class="kpi-subtext"></div>
                    <div class="status-accent bg-blue-500"></div>
                </div>

                <!-- Overall Satisfaction -->
                <div class="kpi-card">
                    <div class="kpi-title">Overall Satisfaction</div>
                    <div class="kpi-value"><?php echo number_format((float) $avgScore, 1); ?> <span
                            class="text-sm font-normal text-slate-400">/ 5.0</span></div>
                    <div class="kpi-subtext">Average metric score</div>
                    <div class="status-accent bg-emerald-500"></div>
                </div>

                <!-- Most Active College -->
                <div id="kpiMostActive" class="kpi-card cursor-pointer hover:bg-slate-50 transition-colors">
                    <div class="kpi-title">Most Active College</div>
                    <div class="text-base font-bold text-black mt-1 line-clamp-1"
                        title="<?php echo htmlspecialchars($mostActiveCollege); ?>">
                        <?php echo htmlspecialchars($mostActiveCollege); ?>
                    </div>
                    <div class="kpi-subtext"><?php echo $mostActiveCollegeCount; ?> Evaluations</div>
                    <div class="status-accent bg-amber-500"></div>
                </div>

                <!-- Pending Flags/Reviews -->
                <div class="kpi-card cursor-pointer hover:bg-slate-50 transition-colors"
                    onclick="window.location.href='feedback.php';">
                    <div class="kpi-title">Pending Flags</div>
                    <div class="kpi-value"><?php echo htmlspecialchars($pendingFlags); ?></div>
                    <div class="kpi-subtext"><span
                            class="<?php echo $pendingFlags > 0 ? 'text-rose-600' : 'text-emerald-600'; ?> font-bold"><?php echo $pendingFlags > 0 ? 'Action Needed' : 'Clear'; ?></span>
                    </div>
                    <div class="status-accent bg-rose-500"></div>
                </div>
            </div>

            <!-- 2. The Main Analytics Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                <!-- Left Column (Performance Trend) -->
                <div class="bg-white p-4 border border-slate-300 lg:col-span-3 flex flex-col">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xs font-bold text-[#4A47A3] uppercase tracking-wider">Submission Volume</h3>
                        <div class="flex gap-2">
                            <select id="chartView"
                                class="bg-white border border-slate-300 px-2 py-1 text-[10px] font-bold text-slate-700 outline-none focus:border-blue-500 uppercase tracking-wider">
                                <option value="yearly">Yearly Trend</option>
                                <option value="monthly">Monthly Trend</option>
                            </select>
                            <select id="chartYear"
                                class="bg-white border border-slate-300 px-2 py-1 text-[10px] font-bold text-slate-700 outline-none focus:border-blue-500 uppercase tracking-wider">
                                <!-- JS Populated -->
                            </select>
                            <select id="chartMonth"
                                class="bg-white border border-slate-300 px-2 py-1 text-[10px] font-bold text-slate-700 outline-none focus:border-blue-500 uppercase tracking-wider hidden">
                                <option value="1">Jan</option>
                                <option value="2">Feb</option>
                                <option value="3">Mar</option>
                                <option value="4">Apr</option>
                                <option value="5">May</option>
                                <option value="6">Jun</option>
                                <option value="7">Jul</option>
                                <option value="8">Aug</option>
                                <option value="9">Sep</option>
                                <option value="10">Oct</option>
                                <option value="11">Nov</option>
                                <option value="12">Dec</option>
                            </select>
                        </div>
                    </div>
                    <div class="w-full h-64 relative flex-1">
                        <div id="chartEmptyState"
                            style="display:none; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); text-align:center; width: 100%;">
                            <h4 class="text-slate-400 font-bold text-xs">No evaluations as of yet</h4>
                        </div>
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>

                <!-- Right Column (Demographics) -->
                <div class="bg-white p-4 border border-slate-300 lg:col-span-2 flex flex-col">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xs font-bold text-[#4A47A3] uppercase tracking-wider">Demographics</h3>
                        <div class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">
                            User Distribution</div>
                    </div>
                    <div class="w-full h-64 relative flex-1">
                        <canvas id="demoChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- 3. Recent Activity Table -->
            <div class="bg-white p-4 border border-slate-300 flex flex-col">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h3 class="text-xs font-bold text-[#4A47A3] uppercase tracking-wider">Recent Submissions
                        </h3>
                    </div>
                    <a href="respondents.php" class="btn-apricot px-3 py-1.5 text-xs font-bold">Go To
                        Respondents</a>
                </div>

                <div class="overflow-x-auto border border-slate-200">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th
                                    class="py-2 px-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-r border-slate-200">
                                    Date
                                </th>
                                <th
                                    class="py-2 px-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-r border-slate-200">
                                    Respondent</th>
                                <th
                                    class="py-2 px-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center border-r border-slate-200">
                                    Satisfied?</th>
                                <th
                                    class="py-2 px-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-r border-slate-200">
                                    College
                                </th>
                                <th
                                    class="py-2 px-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-r border-slate-200">
                                    User
                                    Type</th>
                                <th
                                    class="py-2 px-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-right">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-slate-50 bg-white">
                            <?php if (!empty($recentSubmissions)): ?>
                                <?php foreach (array_slice($recentSubmissions, 0, 10) as $sub): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors group">
                                        <td class="py-4 px-6 text-slate-600 font-medium">
                                            <?php echo date('M d, Y', strtotime($sub['submission_date'])); ?>
                                        </td>
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
                                            $rating = (float) $sub['overall_rating'];
                                            if ($rating >= 3.00) {
                                                $dot_color = 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.4)]';
                                                $label = 'Satisfactory';
                                            } else {
                                                $dot_color = 'bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.4)]';
                                                $label = 'Needs Impr.';
                                            }
                                            ?>
                                            <div class="flex items-center justify-center gap-2">
                                                <span class="w-2 h-2 rounded-full <?php echo $dot_color; ?>"></span>
                                                <span class="text-[10px] font-bold uppercase tracking-tight text-slate-500"
                                                    title="Score: <?php echo $rating; ?>"><?php echo $label; ?></span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <span
                                                class="bg-slate-100 text-slate-600 px-2 py-1 rounded-md text-[10px] font-bold"><?php echo htmlspecialchars($sub['college']); ?></span>
                                        </td>
                                        <td class="py-4 px-6 text-slate-500 font-medium">
                                            <?php echo htmlspecialchars($sub['role']); ?>
                                        </td>
                                        <td class="py-4 px-6 text-right text-xs">
                                            <a href="respondents.php?view_id=<?php echo $sub['submission_id']; ?>"
                                                class="text-blue-600 hover:text-blue-800 font-bold uppercase tracking-wider transition-colors">Details</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-slate-400 font-medium italic">No
                                        recent
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
                                        <?php echo htmlspecialchars($sub['respondent_name'] ?: 'Anonymous'); ?>
                                    </td>
                                    <td class="py-4 px-6 font-bold text-blue-600">
                                        <?php echo htmlspecialchars($sub['college']); ?>
                                    </td>
                                    <td class="py-4 px-6 font-medium text-slate-500">
                                        <?php echo htmlspecialchars($sub['role']); ?>
                                    </td>
                                    <td class="py-4 px-6 font-medium text-slate-500">
                                        <?php echo htmlspecialchars($sub['department']); ?>
                                    </td>
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
        let trendChart;

        document.addEventListener('DOMContentLoaded', function () {
            const trendCtx = document.getElementById('trendChart').getContext('2d');
            trendChart = new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Evaluations',
                        data: [],
                        borderColor: '#4A47A3',
                        backgroundColor: 'rgba(74, 71, 163, 0.05)',
                        borderWidth: 2,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#4A47A3',
                        pointBorderWidth: 1,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        tension: 0,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#E0E0E0' }, ticks: { font: { size: 9 } } },
                        x: { grid: { display: false }, ticks: { font: { size: 9 } } }
                    }
                }
            });

            // Initialize Chart Controls
            const chartView = document.getElementById('chartView');
            const chartYear = document.getElementById('chartYear');
            const chartMonth = document.getElementById('chartMonth');

            const currentYear = new Date().getFullYear();
            for (let y = 2025; y <= currentYear; y++) {
                const opt = document.createElement('option');
                opt.value = y;
                opt.textContent = y;
                chartYear.appendChild(opt);
            }
            chartYear.value = currentYear;
            chartMonth.value = new Date().getMonth() + 1;

            function fetchChartData() {
                const view = chartView.value;
                const year = chartYear.value;
                const month = chartMonth.value;

                if (view === 'yearly') {
                    chartMonth.classList.add('hidden');
                } else {
                    chartMonth.classList.remove('hidden');
                }

                fetch('api_chart_data.php?view=' + view + '&year=' + year + '&month=' + month)
                    .then(res => res.json())
                    .then(res => {
                        const emptyState = document.getElementById('chartEmptyState');
                        const canvas = document.getElementById('trendChart');

                        const allZeros = res.data.length === 0 || res.data.every(val => val == 0);

                        if (allZeros) {
                            emptyState.style.display = 'block';
                            canvas.style.display = 'none';
                        } else {
                            emptyState.style.display = 'none';
                            canvas.style.display = 'block';
                            trendChart.data.labels = res.labels;
                            trendChart.data.datasets[0].data = res.data;
                            trendChart.update();
                        }
                    })
                    .catch(err => console.error('Error fetching chart data:', err));
            }

            chartView.addEventListener('change', fetchChartData);
            chartYear.addEventListener('change', fetchChartData);
            chartMonth.addEventListener('change', fetchChartData);

            // Initial fetch
            fetchChartData();

            let demographicsChart;
            const demoCtx = document.getElementById('demoChart').getContext('2d');
            demographicsChart = new Chart(demoCtx, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            'rgba(74, 71, 163, 0.7)',  // Faded Blue
                            'rgba(255, 131, 115, 0.7)', // Salmon
                            'rgba(155, 155, 155, 0.7)', // Grey
                            'rgba(74, 144, 226, 0.7)',  // Light Blue
                            'rgba(247, 136, 47, 0.7)',   // Apricot/Salmon
                            'rgba(100, 116, 139, 0.7)'  // Slate
                        ],
                        borderWidth: 1,
                        borderColor: '#E0E0E0',
                        hoverOffset: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right', labels: { boxWidth: 8, padding: 10, font: { size: 10 } } }
                    },
                    cutout: '60%',
                    onClick: (event, elements, chart) => {
                        if (elements[0]) {
                            const index = elements[0].index;
                            const label = chart.data.labels[index];
                            window.location.href = 'respondents.php?filterType=' + encodeURIComponent(label);
                        }
                    },
                    onHover: (event, chartElement) => {
                        event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
                    }
                }
            });

            function fetchDemographicData() {
                fetch('api_demographics.php')
                    .then(res => res.json())
                    .then(res => {
                        demographicsChart.data.labels = res.labels;
                        demographicsChart.data.datasets[0].data = res.data;
                        demographicsChart.update();
                    })
                    .catch(err => console.error('Error fetching demographic data:', err));
            }

            fetchDemographicData();
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
                    <span class="bg-blue-50 text-blue-600 text-[10px] font-bold px-3 py-1 rounded-full border border-blue-100" style="white-space: nowrap;">${n} Evaluations</span>
                </div>
            `).join('') || '<p class="text-center text-slate-400 py-6">No data available.</p>';
            leadModal.classList.remove('hidden');
        };

        document.getElementById('closeLeaderboardBtn').onclick = () => leadModal.classList.add('hidden');
        window.onclick = (e) => {
            if (e.target === leadModal) leadModal.classList.add('hidden');
        };
        function applyDateFilter() {
            const sm = document.getElementById('header_start_month').value;
            const sy = parseInt(document.getElementById('header_start_year').value);
            const em = document.getElementById('header_end_month').value;
            const ey = parseInt(document.getElementById('header_end_year').value);
            const monthNames = ["JANUARY", "FEBRUARY", "MARCH", "APRIL", "MAY", "JUNE", "JULY", "AUGUST", "SEPTEMBER", "OCTOBER", "NOVEMBER", "DECEMBER"];
            const startDate = new Date(sy, monthNames.indexOf(sm), 1);
            const endDate = new Date(ey, monthNames.indexOf(em), 1);
            const now = new Date();
            const currentMonth = new Date(now.getFullYear(), now.getMonth(), 1);

            if (startDate > currentMonth || endDate > currentMonth) {
                alert("Error: Future dates are not allowed. Please select current or past months.");
                return;
            }
            if (startDate > endDate) {
                alert("Error: 'From' date cannot be later than 'To' date.");
                return;
            }
            window.location.href = `?start_month=${sm}&start_year=${sy}&end_month=${em}&end_year=${ey}`;
        }
    </script>
</body>

</html>