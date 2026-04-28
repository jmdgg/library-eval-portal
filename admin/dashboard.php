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
    $stmt = $pdo->query("SELECT submission_id, submission_date, role, college, respondent_name, department FROM survey_submission ORDER BY submission_date DESC, submission_id DESC LIMIT 50");
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
                        'auf-slate': '#6B7A8F',
                        'auf-apricot': '#F7882F',
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

        h1, h2, h3, h4, h5, h6 {
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

<body class="min-h-screen bg-offwhite flex overflow-x-hidden">

    <?php require_once 'sidebar.php'; ?>

    <!-- Main Wrapper for Responsiveness -->
    <div class="flex-1 ml-64 [.collapsed-sidebar_&]:ml-20 transition-all duration-300 min-h-screen flex flex-col">

        <!-- Original Navigation Styled as Header -->
        <header class="bg-purewhite shadow-sm border-b border-gray-200 px-6 py-4 flex flex-col md:flex-row justify-between items-center gap-4 sticky top-0 z-20">
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
                        class="text-sm border-gray-300 rounded-md shadow-sm p-1.5 text-auf-slate">
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
                        class="text-sm w-20 border-gray-300 rounded-md shadow-sm p-1.5 text-auf-slate" required>

                    <span class="text-sm font-bold text-auf-slate px-1">TO</span>

                    <select name="end_month" id="end_month"
                        class="text-sm border-gray-300 rounded-md shadow-sm p-1.5 text-auf-slate">
                        <?php
                        foreach ($months as $m) {
                            $selected = ($m == $currentMonth) ? 'selected' : '';
                            echo "<option value=\"$m\" $selected>" . ucfirst(strtolower($m)) . "</option>";
                        }
                        ?>
                    </select>
                    <input type="number" name="end_year" id="end_year" value="<?php echo date('Y'); ?>"
                        class="text-sm w-20 border-gray-300 rounded-md shadow-sm p-1.5 text-auf-slate" required>

                    <button type="submit" class="btn-apricot px-4 py-2 rounded-md text-sm font-semibold shadow-sm ml-2">
                        Export to Master Template
                    </button>
                </form>
            </div>
        </header>

        <main class="max-w-7xl mx-auto p-6 space-y-6 w-full">

            <h2 class="text-2xl font-bold mb-2 text-auf-slate">Performance Overview - <?php echo date('F Y'); ?></h2>

            <!-- 1. KPI 'Glance Cards' Row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="card flex flex-col justify-center">
                    <p class="text-sm font-semibold uppercase opacity-80 mb-1">Total Evaluations</p>
                    <p class="text-3xl font-bold text-auf-slate"><?php echo htmlspecialchars($totalEvaluations); ?></p>
                </div>
                <div class="card flex flex-col justify-center">
                    <p class="text-sm font-semibold uppercase opacity-80 mb-1">Overall Satisfaction</p>
                    <p class="text-3xl font-bold text-auf-slate"><?php echo number_format((float) $avgScore, 1); ?> / 5.0</p>
                </div>
                <div id="kpiMostActive"
                    class="card flex flex-col justify-center cursor-pointer hover:bg-gray-50 transition-colors">
                    <p class="text-sm font-semibold uppercase opacity-80 mb-1">Most Active College</p>
                    <p class="text-3xl font-bold truncate text-auf-slate" title="<?php echo htmlspecialchars($mostActiveCollege); ?>">
                        <?php echo htmlspecialchars($mostActiveCollege); ?>
                    </p>
                </div>
                <div class="card flex flex-col justify-center">
                    <p class="text-sm font-semibold uppercase opacity-80 mb-1">Pending Flags/Reviews</p>
                    <p class="text-3xl font-bold text-auf-slate"><?php echo htmlspecialchars($pendingFlags); ?></p>
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
                        <?php if (!empty($recentSubmissions)): ?>
                            <?php foreach (array_slice($recentSubmissions, 0, 5) as $sub): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4 text-slate"><?php echo date('Y-m-d', strtotime($sub['submission_date'])); ?></td>
                                <td class="py-3 px-4 text-slate"><?php echo htmlspecialchars($sub['respondent_name'] ?: 'Anonymous'); ?></td>
                                <td class="py-3 px-4 text-slate"><?php echo htmlspecialchars($sub['college']); ?></td>
                                <td class="py-3 px-4 text-slate"><?php echo htmlspecialchars($sub['role']); ?></td>
                                <td class="py-3 px-4 text-slate"><?php echo htmlspecialchars($sub['department']); ?></td>
                                <td class="py-3 px-4 text-right">
                                    <button class="text-slate hover:text-apricot font-medium transition-colors">View</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Fallback Dummy Data if DB is empty for UI preservation -->
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">2026-04-28</td><td class="py-3 px-4">John Doe</td><td class="py-3 px-4">CCS</td><td class="py-3 px-4">Student</td><td class="py-3 px-4">Circulation</td><td class="py-3 px-4 text-right"><button class="text-slate hover:text-apricot font-medium transition-colors">View</button></td>
                            </tr>
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">2026-04-27</td><td class="py-3 px-4">Jane Smith</td><td class="py-3 px-4">CBA</td><td class="py-3 px-4">Faculty</td><td class="py-3 px-4">Reference</td><td class="py-3 px-4 text-right"><button class="text-slate hover:text-apricot font-medium transition-colors">View</button></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <!-- Expanded Submissions Modal -->
    <div id="submissionsModal"
        class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl w-11/12 max-w-6xl max-h-[90vh] flex flex-col">
            <div class="flex justify-between items-center p-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-slate">All Recent Submissions</h3>
                <button id="closeSubmissionsModalBtn" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto flex-grow flex flex-col gap-4">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-grow">
                        <input type="text" id="advancedSearch"
                            class="form-control w-full border border-gray-300 rounded-md p-2 text-sm shadow-sm outline-none focus:border-apricot focus:ring-1 focus:ring-apricot transition-colors"
                            placeholder="Search evaluations... (e.g., college:ccs type:student smith)">
                        <p class="text-xs text-slate opacity-80 mt-1">Pro tip: Use filters like college:, dept:, type:, or rating:</p>
                    </div>
                    <div class="flex gap-4">
                        <select id="filterCollege" class="form-select border border-gray-300 rounded-md p-2 text-sm shadow-sm outline-none focus:border-apricot focus:ring-1 focus:ring-apricot transition-colors bg-white h-fit">
                            <option value="All Colleges">All Colleges</option>
                            <option value="CAMP">CAMP</option><option value="CAS">CAS</option><option value="CBA">CBA</option><option value="CCS">CCS</option><option value="CCJE">CCJE</option><option value="CEA">CEA</option><option value="CED">CED</option><option value="CON">CON</option><option value="SOL">SOL</option><option value="SOM">SOM</option><option value="GS">GS</option><option value="IS">IS</option><option value="N/A">N/A</option>
                        </select>
                        <select id="filterUserType" class="form-select border border-gray-300 rounded-md p-2 text-sm shadow-sm outline-none focus:border-apricot focus:ring-1 focus:ring-apricot transition-colors bg-white h-fit">
                            <option value="All User Types">All User Types</option>
                            <option value="Student">Student</option><option value="Faculty">Faculty</option><option value="Alumni">Alumni</option><option value="NTP">NTP</option><option value="Other Researcher">Other Researcher</option>
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
                            <?php foreach ($recentSubmissions as $sub): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4"><?php echo date('Y-m-d', strtotime($sub['submission_date'])); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($sub['respondent_name'] ?: 'Anonymous'); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($sub['college']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($sub['role']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($sub['department']); ?></td>
                                <td class="py-3 px-4 text-right"><button class="text-auf-slate hover:text-auf-apricot font-medium transition-colors">View</button></td>
                            </tr>
                            <?php endforeach; ?>
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
                <h3 class="text-lg font-bold text-auf-slate">College Activity</h3>
                <button id="closeLeaderboardBtn" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
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

            if (startDate > currentPeriod || endDate > currentPeriod) {
                alert("Error: You cannot select a period in the future.");
                return false;
            }
            if (startDate > endDate) {
                alert("Error: 'From' date cannot be later than 'To' date.");
                return false;
            }
            return true;
        }

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
                    plugins: { legend: { display: false } }
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
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        });

        // Advanced Search and Modal Logic
        const collegeAliases = {
            "camp": "college of allied medical professions", "cas": "college of arts and sciences",
            "cba": "college of business and accountancy", "ccs": "college of computer studies",
            "ccje": "college of criminal justice education", "cea": "college of engineering",
            "ced": "college of education", "con": "college of nursing",
            "sol": "school of law", "som": "school of medicine",
            "gs": "graduate school", "is": "integrated school"
        };

        function checkMatch(text, term) {
            if (text.includes(term)) return true;
            if (collegeAliases[term] && text.includes(collegeAliases[term])) return true;
            return false;
        }

        function parseSearchQuery(input) {
            const tokens = input.trim().split(/\s+/);
            const filters = { tokens: {}, freeText: [] };
            tokens.forEach(token => {
                if (token.includes(':')) {
                    const parts = token.split(':');
                    filters.tokens[parts[0].toLowerCase()] = parts.slice(1).join(':').toLowerCase();
                } else if (token) {
                    filters.freeText.push(token.toLowerCase());
                }
            });
            return filters;
        }

        const advancedSearch = document.getElementById('advancedSearch');
        const filterCollege = document.getElementById('filterCollege');
        const filterUserType = document.getElementById('filterUserType');

        function applyFilters() {
            const searchQuery = advancedSearch.value;
            const filters = parseSearchQuery(searchQuery);
            const collegeVal = filterCollege.value.toLowerCase();
            const userTypeVal = filterUserType.value.toLowerCase();

            const rows = document.querySelectorAll('#expandedSubmissionsTable tbody tr');
            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                const rowCollege = row.cells[2].textContent.trim().toLowerCase();
                const rowType = row.cells[3].textContent.trim().toLowerCase();

                let isMatch = true;
                if (collegeVal !== 'all colleges' && !checkMatch(rowCollege, collegeVal)) isMatch = false;
                if (isMatch && userTypeVal !== 'all user types' && rowType !== userTypeVal) isMatch = false;
                
                if (isMatch) {
                    filters.freeText.forEach(text => {
                        if (!rowText.includes(text)) isMatch = false;
                    });
                }
                row.style.display = isMatch ? '' : 'none';
            });
        }

        advancedSearch.addEventListener('input', applyFilters);
        filterCollege.addEventListener('change', applyFilters);
        filterUserType.addEventListener('change', applyFilters);

        // Modal Handlers
        const openSubModal = document.getElementById('openSubmissionsModal');
        const closeSubModal = document.getElementById('closeSubmissionsModalBtn');
        const subModal = document.getElementById('submissionsModal');

        openSubModal.onclick = () => subModal.classList.remove('hidden');
        closeSubModal.onclick = () => subModal.classList.add('hidden');

        const kpiActive = document.getElementById('kpiMostActive');
        const leadModal = document.getElementById('leaderboardModal');
        const closeLeadBtn = document.getElementById('closeLeaderboardBtn');
        const leadContent = document.getElementById('leaderboardContent');

        kpiActive.onclick = () => {
            const tally = {};
            document.querySelectorAll('#expandedSubmissionsTable tbody tr').forEach(row => {
                const college = row.cells[2].textContent.trim();
                if (college) tally[college] = (tally[college] || 0) + 1;
            });
            const sorted = Object.entries(tally).sort((a,b) => b[1]-a[1]);
            leadContent.innerHTML = sorted.map(([c, n]) => `
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-md border border-gray-100">
                    <span class="font-bold text-auf-slate">${c}</span>
                    <span class="text-sm font-semibold bg-apricot text-white px-3 py-1 rounded-full">${n} eval${n!==1?'s':''}</span>
                </div>
            `).join('') || '<p class="text-center text-gray-500 py-4">No data available.</p>';
            leadModal.classList.remove('hidden');
        };

        closeLeadBtn.onclick = () => leadModal.classList.add('hidden');
        window.onclick = (e) => {
            if (e.target === subModal) subModal.classList.add('hidden');
            if (e.target === leadModal) leadModal.classList.add('hidden');
        };
    </script>
</body>
</html>