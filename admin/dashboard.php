<?php
/**
 * dashboard.php
 * The Secure Container for the Analytics UI
 */

session_start();

// 1. The Bouncer: Kick out anyone without a valid session token
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
    require_once '../db_connect.php';

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
    $stmt = $pdo->query("SELECT submission_id, submission_date, role, college FROM survey_submission ORDER BY submission_date DESC, submission_id DESC LIMIT 50");
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
    <title>AUF Library Evaluation Dashboard</title>
    <!-- Using Tailwind for layout structure and utilities -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        slate: '#6B7A8F',
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
        body {
            background-color: #FAFAFA;
            color: #6B7A8F;
            font-family: 'Inter', sans-serif;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            color: #6B7A8F;
        }

        .card {
            background-color: #FFFFFF;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            padding: 1.5rem;
        }

        .btn-apricot {
            background-color: #F7882F;
            color: #FFFFFF;
            transition: background-color 0.2s ease-in-out;
        }

        .btn-apricot:hover {
            background-color: #e07725;
        }
    </style>
</head>

<body class="min-h-screen">

    <nav
        class="bg-purewhite shadow-sm border-b border-gray-200 px-6 py-4 flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-xl font-bold">Library Evaluation Analytics</h1>
            <p class="text-sm">Welcome back, <?php echo htmlspecialchars($username); ?> &mdash;
                <?php echo $role_display; ?>
            </p>
        </div>

        <div class="flex items-center gap-4">
            <form id="exportForm" action="generate_excel.php" method="GET" onsubmit="return validateDateRange()"
                class="flex items-center gap-2 bg-offwhite p-2 rounded-lg border border-gray-200 flex-wrap justify-center">
                <select name="start_month" id="start_month"
                    class="text-sm border-gray-300 rounded-md shadow-sm p-1.5 text-slate">
                    <?php
                    $months = ["JANUARY", "FEBRUARY", "MARCH", "APRIL", "MAY", "JUNE", "JULY", "AUGUST", "SEPTEMBER", "OCTOBER", "NOVEMBER", "DECEMBER"];
                    $currentMonth = strtoupper(date('F'));
                    foreach ($months as $m) {
                        $selected = ($m == $currentMonth) ? 'selected' : '';
                        echo "<option value=\"$m\" $selected>" . ucfirst(strtolower($m)) . "</option>";
                    }
                    ?>
                </select>
                <input type="number" name="start_year" id="start_year" value="<?php echo date('Y'); ?>"
                    class="text-sm w-20 border-gray-300 rounded-md shadow-sm p-1.5 text-slate" required>

                <span class="text-sm font-bold text-slate px-1">TO</span>

                <select name="end_month" id="end_month"
                    class="text-sm border-gray-300 rounded-md shadow-sm p-1.5 text-slate">
                    <?php
                    foreach ($months as $m) {
                        $selected = ($m == $currentMonth) ? 'selected' : '';
                        echo "<option value=\"$m\" $selected>" . ucfirst(strtolower($m)) . "</option>";
                    }
                    ?>
                </select>
                <input type="number" name="end_year" id="end_year" value="<?php echo date('Y'); ?>"
                    class="text-sm w-20 border-gray-300 rounded-md shadow-sm p-1.5 text-slate" required>

                <button type="submit" class="btn-apricot px-4 py-2 rounded-md text-sm font-semibold shadow-sm ml-2">
                    Export to Master Template
                </button>
            </form>

            <a href="logout.php" class="text-red-500 hover:text-red-700 text-sm font-semibold px-2">Logout</a>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto p-6 space-y-6">

        <h2 class="text-2xl font-bold mb-2">Performance Overview - <?php echo date('F Y'); ?></h2>

        <!-- 1. KPI 'Glance Cards' Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="card flex flex-col justify-center">
                <p class="text-sm font-semibold uppercase opacity-80 mb-1">Total Evaluations</p>
                <p class="text-3xl font-bold"><?php echo htmlspecialchars($totalEvaluations); ?></p>
            </div>
            <div class="card flex flex-col justify-center">
                <p class="text-sm font-semibold uppercase opacity-80 mb-1">Overall Satisfaction</p>
                <p class="text-3xl font-bold"><?php echo number_format((float) $avgScore, 1); ?> / 5.0</p>
            </div>
            <div id="kpiMostActive"
                class="card flex flex-col justify-center cursor-pointer hover:bg-gray-50 transition-colors">
                <p class="text-sm font-semibold uppercase opacity-80 mb-1">Most Active College</p>
                <p class="text-3xl font-bold truncate" title="<?php echo htmlspecialchars($mostActiveCollege); ?>">
                    <?php echo htmlspecialchars($mostActiveCollege); ?>
                </p>
            </div>
            <div class="card flex flex-col justify-center">
                <p class="text-sm font-semibold uppercase opacity-80 mb-1">Pending Flags/Reviews</p>
                <p class="text-3xl font-bold"><?php echo htmlspecialchars($pendingFlags); ?></p>
            </div>
        </div>

        <!-- 2. The Main Analytics Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            <!-- Left Column (approx 60% -> 3/5 cols) -->
            <div class="card lg:col-span-3">
                <h3 class="text-lg font-bold mb-4">30-Day Trend</h3>
                <div class="w-full h-64 md:h-80 relative">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
            <!-- Right Column (approx 40% -> 2/5 cols) -->
            <div class="card lg:col-span-2">
                <h3 class="text-lg font-bold mb-4">Demographics</h3>
                <div class="w-full h-64 md:h-80 relative">
                    <canvas id="demoChart"></canvas>
                </div>
            </div>
        </div>

        <!-- 3. Recent Activity Table -->
        <div class="card overflow-x-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Recent Submissions</h3>
                <button id="openSubmissionsModal" class="btn-apricot px-3 py-1 rounded-md text-sm font-semibold shadow-sm">Expand</button>
            </div>
            <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                <tr class="border-b border-gray-200">
                    <th class="py-3 px-4 font-semibold text-sm">Date</th>
                    <th class="py-3 px-4 font-semibold text-sm">Name</th>
                    <th class="py-3 px-4 font-semibold text-sm">College</th>
                    <th class="py-3 px-4 font-semibold text-sm">User Type</th>
                    <th class="py-3 px-4 font-semibold text-sm">Library Dept.</th>
                    <th class="py-3 px-4 font-semibold text-sm text-right">Action</th>
                </tr>
                </thead>
            <tbody class="text-sm">
                <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                    <td class="py-3 px-4">2026-04-28</td>
                    <td class="py-3 px-4">John Doe</td>
                    <td class="py-3 px-4">CCS</td>
                    <td class="py-3 px-4">Student</td>
                    <td class="py-3 px-4">Circulation</td>
                    <td class="py-3 px-4 text-right"><button class="text-slate hover:text-apricot font-medium transition-colors">View</button></td>
                </tr>
                <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                    <td class="py-3 px-4">2026-04-27</td>
                    <td class="py-3 px-4">Jane Smith</td>
                    <td class="py-3 px-4">CBA</td>
                    <td class="py-3 px-4">Faculty</td>
                    <td class="py-3 px-4">Reference</td>
                    <td class="py-3 px-4 text-right"><button class="text-slate hover:text-apricot font-medium transition-colors">View</button></td>
                </tr>
                <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                    <td class="py-3 px-4">2026-04-26</td>
                    <td class="py-3 px-4">Alice Johnson</td>
                    <td class="py-3 px-4">CAS</td>
                    <td class="py-3 px-4">Student</td>
                    <td class="py-3 px-4">Periodicals</td>
                    <td class="py-3 px-4 text-right"><button class="text-slate hover:text-apricot font-medium transition-colors">View</button></td>
                </tr>
                <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                    <td class="py-3 px-4">2026-04-25</td>
                    <td class="py-3 px-4">Bob Williams</td>
                    <td class="py-3 px-4">CEA</td>
                    <td class="py-3 px-4">NTP</td>
                    <td class="py-3 px-4">Reserved</td>
                    <td class="py-3 px-4 text-right"><button class="text-slate hover:text-apricot font-medium transition-colors">View</button></td>
                </tr>
                <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                    <td class="py-3 px-4">2026-04-24</td>
                    <td class="py-3 px-4">Charlie Brown</td>
                    <td class="py-3 px-4">CON</td>
                    <td class="py-3 px-4">Student</td>
                    <td class="py-3 px-4">Circulation</td>
                    <td class="py-3 px-4 text-right"><button class="text-slate hover:text-apricot font-medium transition-colors">View</button></td>
                </tr>
            </tbody>
            </table>
        </div>

    </main>

    <!-- Expanded Submissions Modal -->
    <div id="submissionsModal"
        class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl w-11/12 max-w-6xl max-h-[90vh] flex flex-col">
            <div class="flex justify-between items-center p-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-slate">All Recent Submissions</h3>
                <button id="closeSubmissionsModalBtn" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto flex-grow flex flex-col gap-4">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-grow">
                        <input type="text" id="advancedSearch"
                            class="form-control w-full border border-gray-300 rounded-md p-2 text-sm shadow-sm outline-none focus:border-apricot focus:ring-1 focus:ring-apricot transition-colors"
                            placeholder="Search evaluations... (e.g., college:ccs type:student smith)">
                        <p class="text-xs text-slate opacity-80 mt-1">Pro tip: Use filters like college:, dept:, type:, or
                            rating:</p>
                    </div>
                    <div class="flex gap-4">
                        <select id="filterCollege"
                            class="form-select border border-gray-300 rounded-md p-2 text-sm shadow-sm outline-none focus:border-apricot focus:ring-1 focus:ring-apricot transition-colors bg-white h-fit">
                            <option value="All Colleges">All Colleges</option>
                            <option value="CAMP">CAMP</option>
                            <option value="CAS">CAS</option>
                            <option value="CBA">CBA</option>
                            <option value="CCS">CCS</option>
                            <option value="CCJE">CCJE</option>
                            <option value="CEA">CEA</option>
                            <option value="CED">CED</option>
                            <option value="CON">CON</option>
                            <option value="SOL">SOL</option>
                            <option value="SOM">SOM</option>
                            <option value="GS">GS</option>
                            <option value="IS">IS</option>
                            <option value="N/A">N/A</option>
                        </select>
                        <select id="filterUserType"
                            class="form-select border border-gray-300 rounded-md p-2 text-sm shadow-sm outline-none focus:border-apricot focus:ring-1 focus:ring-apricot transition-colors bg-white h-fit">
                            <option value="All User Types">All User Types</option>
                            <option value="Student">Student</option>
                            <option value="Faculty">Faculty</option>
                            <option value="Alumni">Alumni</option>
                            <option value="NTP">NTP</option>
                            <option value="Other Researcher">Other Researcher</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="w-full text-left border-collapse min-w-[600px]" id="expandedSubmissionsTable">
                        <thead class="bg-gray-50">
                            <tr class="border-b border-gray-200">
                                <th class="py-3 px-4 font-semibold text-sm">Date</th>
                                <th class="py-3 px-4 font-semibold text-sm">Name</th>
                                <th class="py-3 px-4 font-semibold text-sm">College</th>
                                <th class="py-3 px-4 font-semibold text-sm">User Type</th>
                                <th class="py-3 px-4 font-semibold text-sm">Library Dept.</th>
                                <th class="py-3 px-4 font-semibold text-sm text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">2026-04-28</td><td class="py-3 px-4">John Doe</td><td class="py-3 px-4">CCS</td><td class="py-3 px-4">Student</td><td class="py-3 px-4">Circulation</td><td class="py-3 px-4 text-right"><button class="text-slate hover:text-apricot font-medium transition-colors">View</button></td>
                            </tr>
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">2026-04-27</td><td class="py-3 px-4">Jane Smith</td><td class="py-3 px-4">CBA</td><td class="py-3 px-4">Faculty</td><td class="py-3 px-4">Reference</td><td class="py-3 px-4 text-right"><button class="text-slate hover:text-apricot font-medium transition-colors">View</button></td>
                            </tr>
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">2026-04-26</td><td class="py-3 px-4">Alice Johnson</td><td class="py-3 px-4">CAS</td><td class="py-3 px-4">Student</td><td class="py-3 px-4">Periodicals</td><td class="py-3 px-4 text-right"><button class="text-slate hover:text-apricot font-medium transition-colors">View</button></td>
                            </tr>
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">2026-04-25</td><td class="py-3 px-4">Bob Williams</td><td class="py-3 px-4">CEA</td><td class="py-3 px-4">NTP</td><td class="py-3 px-4">Reserved</td><td class="py-3 px-4 text-right"><button class="text-slate hover:text-apricot font-medium transition-colors">View</button></td>
                            </tr>
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">2026-04-24</td><td class="py-3 px-4">Charlie Brown</td><td class="py-3 px-4">CON</td><td class="py-3 px-4">Student</td><td class="py-3 px-4">Circulation</td><td class="py-3 px-4 text-right"><button class="text-slate hover:text-apricot font-medium transition-colors">View</button></td>
                            </tr>
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">2026-04-23</td><td class="py-3 px-4">Diana Prince</td><td class="py-3 px-4">CAMP</td><td class="py-3 px-4">Student</td><td class="py-3 px-4">Multimedia</td><td class="py-3 px-4 text-right"><button class="text-slate hover:text-apricot font-medium transition-colors">View</button></td>
                            </tr>
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">2026-04-22</td><td class="py-3 px-4">Evan Wright</td><td class="py-3 px-4">CCJE</td><td class="py-3 px-4">Faculty</td><td class="py-3 px-4">Reference</td><td class="py-3 px-4 text-right"><button class="text-slate hover:text-apricot font-medium transition-colors">View</button></td>
                            </tr>
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">2026-04-21</td><td class="py-3 px-4">Fiona Gallagher</td><td class="py-3 px-4">CED</td><td class="py-3 px-4">Student</td><td class="py-3 px-4">Periodicals</td><td class="py-3 px-4 text-right"><button class="text-slate hover:text-apricot font-medium transition-colors">View</button></td>
                            </tr>
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">2026-04-20</td><td class="py-3 px-4">George Miller</td><td class="py-3 px-4">SOL</td><td class="py-3 px-4">Alumni</td><td class="py-3 px-4">Reserved</td><td class="py-3 px-4 text-right"><button class="text-slate hover:text-apricot font-medium transition-colors">View</button></td>
                            </tr>
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">2026-04-19</td><td class="py-3 px-4">Hannah Abbott</td><td class="py-3 px-4">SOM</td><td class="py-3 px-4">Faculty</td><td class="py-3 px-4">Circulation</td><td class="py-3 px-4 text-right"><button class="text-slate hover:text-apricot font-medium transition-colors">View</button></td>
                            </tr>
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">2026-04-18</td><td class="py-3 px-4">Ian Somerhalder</td><td class="py-3 px-4">GS</td><td class="py-3 px-4">Student</td><td class="py-3 px-4">Multimedia</td><td class="py-3 px-4 text-right"><button class="text-slate hover:text-apricot font-medium transition-colors">View</button></td>
                            </tr>
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">2026-04-17</td><td class="py-3 px-4">Jessica Alba</td><td class="py-3 px-4">IS</td><td class="py-3 px-4">Other Researcher</td><td class="py-3 px-4">Reference</td><td class="py-3 px-4 text-right"><button class="text-slate hover:text-apricot font-medium transition-colors">View</button></td>
                            </tr>
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">2026-04-16</td><td class="py-3 px-4">Kevin Hart</td><td class="py-3 px-4">CCS</td><td class="py-3 px-4">Student</td><td class="py-3 px-4">Periodicals</td><td class="py-3 px-4 text-right"><button class="text-slate hover:text-apricot font-medium transition-colors">View</button></td>
                            </tr>
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">2026-04-15</td><td class="py-3 px-4">Laura Dern</td><td class="py-3 px-4">CBA</td><td class="py-3 px-4">Faculty</td><td class="py-3 px-4">Reserved</td><td class="py-3 px-4 text-right"><button class="text-slate hover:text-apricot font-medium transition-colors">View</button></td>
                            </tr>
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">2026-04-14</td><td class="py-3 px-4">Michael Scott</td><td class="py-3 px-4">CAS</td><td class="py-3 px-4">NTP</td><td class="py-3 px-4">Circulation</td><td class="py-3 px-4 text-right"><button class="text-slate hover:text-apricot font-medium transition-colors">View</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaderboard Modal -->
    <div id="leaderboardModal"
        class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl w-11/12 md:w-1/2 lg:w-1/3 max-h-[80vh] flex flex-col">
            <div class="flex justify-between items-center p-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-slate">College Activity</h3>
                <button id="closeLeaderboardBtn" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
            <div class="p-4 overflow-y-auto flex-grow space-y-2" id="leaderboardContent">
                <!-- Data injected here -->
            </div>
        </div>
    </div>

    <script>
        function validateDateRange() {
            const months = ["JANUARY", "FEBRUARY", "MARCH", "APRIL", "MAY", "JUNE", "JULY", "AUGUST", "SEPTEMBER", "OCTOBER", "NOVEMBER", "DECEMBER"];

            const startMonth = document.getElementById('start_month').value;
            const startYear = parseInt(document.getElementById('start_year').value);
            const endMonth = document.getElementById('end_month').value;
            const endYear = parseInt(document.getElementById('end_year').value);

            const startMonthIdx = months.indexOf(startMonth);
            const endMonthIdx = months.indexOf(endMonth);

            const startDate = new Date(startYear, startMonthIdx, 1);
            const endDate = new Date(endYear, endMonthIdx, 1);
            const now = new Date();
            const currentPeriod = new Date(now.getFullYear(), now.getMonth(), 1);

            // 1. Future Check
            if (startDate > currentPeriod || endDate > currentPeriod) {
                alert("Error: You cannot select a period in the future.");
                return false;
            }

            // 2. Logic Check (From < To)
            if (startDate > endDate) {
                alert("Error: 'From' date cannot be later than 'To' date.");
                return false;
            }

            // 3. Same Month Check (Informational, but processed correctly by backend)
            if (startYear === endYear && startMonth === endMonth) {
                console.log("Generating report for a single month.");
            }

            return true;
        }

        // Initialize dummy charts so they render something visually
        document.addEventListener('DOMContentLoaded', function () {
            const trendCtx = document.getElementById('trendChart').getContext('2d');
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($trendLabels); ?>,
                    datasets: [{
                        label: 'Evaluations',
                        data: <?php echo json_encode($trendCounts); ?>,
                        borderColor: '#6B7A8F',
                        backgroundColor: 'rgba(107, 122, 143, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
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
                        backgroundColor: ['#6B7A8F', '#F7882F', '#D1D5DB', '#4CAF50', '#9C27B0', '#03A9F4', '#FFC107', '#E91E63'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        });
    </script>

    <script>
        // Advanced Tokenized Search System
        const collegeAliases = {
            "camp": "college of allied medical professions",
            "cas": "college of arts and sciences",
            "cba": "college of business and accountancy",
            "ccs": "college of computer studies",
            "ccje": "college of criminal justice education",
            "cea": "college of engineering", // Matching the table's current output
            "ced": "college of education",
            "con": "college of nursing",
            "sol": "school of law",
            "som": "school of medicine",
            "gs": "graduate school",
            "is": "integrated school"
        };

        function checkMatch(text, term) {
            if (text.includes(term)) return true;
            if (collegeAliases[term] && text.includes(collegeAliases[term])) return true;

            for (const key in collegeAliases) {
                if (collegeAliases[key].includes(term) && text.includes(key)) {
                    return true;
                }
            }
            return false;
        }

        function parseSearchQuery(input) {
            const tokens = input.trim().split(/\s+/);
            const filters = { tokens: {}, freeText: [] };

            tokens.forEach(token => {
                if (token.includes(':') && !token.startsWith(':') && !token.endsWith(':')) {
                    const parts = token.split(':');
                    const key = parts[0].toLowerCase();
                    const value = parts.slice(1).join(':').toLowerCase();
                    filters.tokens[key] = value;
                } else {
                    if (token) filters.freeText.push(token.toLowerCase());
                }
            });
            return filters;
        }

        const advancedSearch = document.getElementById('advancedSearch');
        const filterCollege = document.getElementById('filterCollege');
        const filterUserType = document.getElementById('filterUserType');

        function applyFilters() {
            const searchQuery = advancedSearch ? advancedSearch.value : '';
            const filters = parseSearchQuery(searchQuery);
            const collegeVal = filterCollege ? filterCollege.value : 'All Colleges';
            const userTypeVal = filterUserType ? filterUserType.value : 'All User Types';

            const tbody = document.querySelector('#expandedSubmissionsTable tbody');
            if (!tbody) return;
            const rows = tbody.querySelectorAll('tr');

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length < 6) return;

            const rowDate = cells[0].textContent.toLowerCase();
            const rowName = cells[1].textContent.toLowerCase();
            const rowCollege = cells[2].textContent.trim().toLowerCase();
            const rowType = cells[3].textContent.trim().toLowerCase();

            const rowData = {
                date: rowDate,
                name: rowName,
                college: rowCollege,
                type: rowType
            };

                let isMatch = true;

                // 1. Dropdown Matches
                if (collegeVal !== 'All Colleges' && !checkMatch(rowCollege, collegeVal.toLowerCase())) {
                    isMatch = false;
                }

                if (isMatch && userTypeVal !== 'All User Types' && rowType !== userTypeVal.toLowerCase()) {
                    isMatch = false;
                }

                // 2. Search Bar Matches
                if (isMatch) {
                    // Check tokenized filters
                    for (const key in filters.tokens) {
                        const value = filters.tokens[key];
                        if (rowData[key] !== undefined) {
                            if (!checkMatch(rowData[key], value)) {
                                isMatch = false;
                                break;
                            }
                        } else {
                            const rowText = row.textContent.toLowerCase();
                            if (!checkMatch(rowText, value)) {
                                isMatch = false;
                                break;
                            }
                        }
                    }
                }

                if (isMatch && filters.freeText.length > 0) {
                    const rowText = row.textContent.toLowerCase();
                    for (const text of filters.freeText) {
                        if (!checkMatch(rowText, text)) {
                            isMatch = false;
                            break;
                        }
                    }
                }

                row.style.display = isMatch ? '' : 'none';
            });
        }

        if (advancedSearch) advancedSearch.addEventListener('input', applyFilters);
        if (filterCollege) filterCollege.addEventListener('change', applyFilters);
        if (filterUserType) filterUserType.addEventListener('change', applyFilters);

        // Leaderboard Modal Logic
        const kpiMostActive = document.getElementById('kpiMostActive');
        const leaderboardModal = document.getElementById('leaderboardModal');
        const closeLeaderboardBtn = document.getElementById('closeLeaderboardBtn');
        const leaderboardContent = document.getElementById('leaderboardContent');

        if (kpiMostActive) {
            kpiMostActive.addEventListener('click', function () {
                const tbody = document.querySelector('#expandedSubmissionsTable tbody');
                if (!tbody) return;

                const rows = tbody.querySelectorAll('tr');
                const tally = {};

            rows.forEach(row => {
                // Only count visible rows
                if (row.style.display !== 'none' && row.cells.length >= 6) {
                    const college = row.cells[2].textContent.trim();
                    // Ignore the "No recent submissions found" row
                    if (college && college !== 'No recent submissions found.') {
                        tally[college] = (tally[college] || 0) + 1;
                    }
                }
            });

                // Convert object to array and sort descending
                const sortedColleges = Object.entries(tally).sort((a, b) => b[1] - a[1]);

                // Generate HTML
                leaderboardContent.innerHTML = '';
                if (sortedColleges.length === 0) {
                    leaderboardContent.innerHTML = '<p class="text-center text-gray-500 py-4">No active data to display.</p>';
                } else {
                    sortedColleges.forEach(([college, count]) => {
                        const itemHTML = `
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-md border border-gray-100">
                                <span class="font-bold text-slate">${college}</span>
                                <span class="text-sm font-semibold bg-apricot text-white px-3 py-1 rounded-full">${count} evaluation${count !== 1 ? 's' : ''}</span>
                            </div>
                        `;
                        leaderboardContent.insertAdjacentHTML('beforeend', itemHTML);
                    });
                }

                // Show Modal
                leaderboardModal.classList.remove('hidden');
            });
        }

        if (closeLeaderboardBtn) {
            closeLeaderboardBtn.addEventListener('click', function () {
                leaderboardModal.classList.add('hidden');
            });
        }

        // Close modal when clicking outside
        if (leaderboardModal) {
            leaderboardModal.addEventListener('click', function (e) {
                if (e.target === leaderboardModal) {
                    leaderboardModal.classList.add('hidden');
                }
            });
        }
        // Submissions Modal Logic
        const openSubmissionsModal = document.getElementById('openSubmissionsModal');
        const submissionsModal = document.getElementById('submissionsModal');
        const closeSubmissionsModalBtn = document.getElementById('closeSubmissionsModalBtn');

        if (openSubmissionsModal) {
            openSubmissionsModal.addEventListener('click', function () {
                submissionsModal.classList.remove('hidden');
                // Optional: Trigger applyFilters here if needed when opening to ensure state matches
            });
        }

        if (closeSubmissionsModalBtn) {
            closeSubmissionsModalBtn.addEventListener('click', function () {
                submissionsModal.classList.add('hidden');
            });
        }

        if (submissionsModal) {
            submissionsModal.addEventListener('click', function (e) {
                if (e.target === submissionsModal) {
                    submissionsModal.classList.add('hidden');
                }
            });
        }
    </script>
</body>

</html>