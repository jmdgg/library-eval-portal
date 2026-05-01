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

require_once '../db_connect.php';

// Fetch All Submissions
try {
    $stmt = $pdo->query("SELECT submission_id, submission_date, role, college, respondent_name, department, overall_rating FROM survey_submission ORDER BY submission_date DESC, submission_id DESC");
    $allSubmissions = $stmt->fetchAll();
} catch (Exception $e) {
    $allSubmissions = [];
}

// 2. Fetch Admin Details to personalize the UI
$username = $_SESSION['username'] ?? 'Admin';
$is_superadmin = $_SESSION['is_superadmin'] ?? 0;
$role_display = $is_superadmin ? 'Super Administrator' : 'Branch Administrator';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AUF Library Evaluation Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-50 flex">

    <?php require_once 'sidebar.php'; ?>

    <div class="flex-1 ml-64 [.collapsed-sidebar_&]:ml-20 transition-all duration-300 min-h-screen">

        <header class="bg-white shadow-sm border-b border-gray-200 h-16 flex items-center justify-between px-8">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Library Evaluation Dashboard</h1>
                <p class="text-xs text-gray-500">Welcome,
                    <?php echo htmlspecialchars($username); ?> &mdash;
                    <?php echo $role_display; ?>
                </p>
            </div>

            <div class="flex items-center gap-4">
                <form id="exportForm" action="generate_excel.php" method="GET" onsubmit="return validateDateRange()"
                    class="flex items-center gap-2 bg-gray-50 p-1.5 rounded-lg border border-gray-200">
                    <select name="start_month" id="start_month"
                        class="text-xs border-gray-300 rounded-md shadow-sm py-1">
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
                        class="text-xs w-16 border-gray-300 rounded-md shadow-sm py-1" required>

                    <span class="text-gray-400 text-xs font-bold px-1">TO</span>

                    <select name="end_month" id="end_month" class="text-xs border-gray-300 rounded-md shadow-sm py-1">
                        <?php
                        foreach ($months as $m) {
                            $selected = ($m == $currentMonth) ? 'selected' : '';
                            echo "<option value=\"$m\" $selected>" . ucfirst(strtolower($m)) . "</option>";
                        }
                        ?>
                    </select>
                    <input type="number" name="end_year" id="end_year" value="<?php echo date('Y'); ?>"
                        class="text-xs w-16 border-gray-300 rounded-md shadow-sm py-1" required>

                    <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-md text-xs font-semibold transition shadow-sm ml-1">
                        Export XLSX
                    </button>
                </form>
            </div>
        </header>

        <main class="p-8 space-y-6">
            <!-- Filter Toolbar -->
            <div class="flex flex-col md:flex-row gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex-grow relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" id="advancedSearch" class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-10 pr-4 py-3 text-sm outline-none focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-medium" placeholder="Try 'college:ccs student' or 'smith'...">
                </div>
                <div class="flex gap-2">
                    <select id="filterCollege" class="bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm font-bold text-gray-700 shadow-sm outline-none focus:border-blue-500 transition-colors">
                        <option value="All Colleges">All Colleges</option>
                        <?php
                        $colleges = ["CAMP", "CAS", "CBA", "CCS", "CCJE", "CEA", "CED", "CON", "SOL", "SOM", "GS", "IS", "N/A"];
                        foreach ($colleges as $c) echo "<option value=\"$c\">$c</option>";
                        ?>
                    </select>
                    <select id="filterUserType" class="bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm font-bold text-gray-700 shadow-sm outline-none focus:border-blue-500 transition-colors">
                        <option value="All User Types">All User Types</option>
                        <option value="Student">Student</option>
                        <option value="Faculty">Faculty</option>
                        <option value="Alumni">Alumni</option>
                        <option value="NTP">NTP</option>
                        <option value="Other Researcher">Other Researcher</option>
                    </select>
                </div>
            </div>

            <!-- Data Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse" id="expandedSubmissionsTable">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider">College</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider">User Type</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider">Library Dept.</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-50">
                            <?php if (!empty($allSubmissions)): ?>
                                <?php foreach ($allSubmissions as $sub): ?>
                                    <?php
                                    $name = $sub['respondent_name'];
                                    if (empty($name)) {
                                        $filipinoNames = ["Juan Dela Cruz", "Maria Clara", "Jose Rizal", "Andres Bonifacio", "Emilio Aguinaldo", "Apolinario Mabini", "Marcelo H. del Pilar", "Sultan Kudarat", "Lapu-Lapu", "Gabriela Silang"];
                                        $name = $filipinoNames[array_rand($filipinoNames)] . " (Random)";
                                    }
                                    ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="py-4 px-6 text-gray-500 font-medium"><?php echo htmlspecialchars(date('M d, Y', strtotime($sub['submission_date']))); ?></td>
                                        <td class="py-4 px-6 font-bold text-gray-800"><?php echo htmlspecialchars($name); ?></td>
                                        <td class="py-4 px-6 font-bold text-blue-600"><?php echo htmlspecialchars($sub['college'] ?: 'N/A'); ?></td>
                                        <td class="py-4 px-6 font-medium text-gray-500"><?php echo htmlspecialchars($sub['role'] ?: 'N/A'); ?></td>
                                        <td class="py-4 px-6 font-medium text-gray-500"><?php echo htmlspecialchars($sub['department'] ?: 'N/A'); ?></td>
                                        <td class="py-4 px-6 text-right">
                                            <button class="text-blue-600 hover:text-blue-800 font-bold text-xs uppercase tracking-wider transition-colors">View</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-gray-400 font-medium italic">No evaluation records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        const collegeAliases = {
            "camp": "college of allied medical professions",
            "cas": "college of arts and sciences",
            "cba": "college of business and accountancy",
            "ccs": "college of computer studies",
            "ccje": "college of criminal justice education",
            "cea": "college of engineering",
            "ced": "college of education",
            "con": "college of nursing",
            "sol": "school of law",
            "som": "school of medicine",
            "gs": "graduate school",
            "is": "integrated school"
        };

        function parseSearchQuery(input) {
            const tokens = input.trim().split(/\s+/);
            const filters = { tokens: {}, freeText: [] };
            
            tokens.forEach(token => {
                if (token.includes(':') && !token.startsWith(':') && !token.endsWith(':')) {
                    const parts = token.split(':');
                    filters.tokens[parts[0].toLowerCase()] = parts[1].toLowerCase();
                } else {
                    if (token) filters.freeText.push(token.toLowerCase());
                }
            });
            return filters;
        }

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

        const advancedSearch = document.getElementById('advancedSearch');
        const filterCollege = document.getElementById('filterCollege');
        const filterUserType = document.getElementById('filterUserType');

        function applyFilters() {
            const query = advancedSearch.value.toLowerCase();
            const collegeVal = filterCollege.value;
            const userTypeVal = filterUserType.value;
            const parsedQuery = parseSearchQuery(query);
            
            const rows = document.querySelectorAll('#expandedSubmissionsTable tbody tr');
            rows.forEach(row => {
                if(row.cells.length < 6) return;
                
                let isMatch = true;
                
                // Indices: Date=0, Name=1, College=2, User Type=3, Library Dept=4, Action=5
                const rowCollege = row.cells[2].innerText.toLowerCase();
                const rowType = row.cells[3].innerText.toLowerCase();
                const rowDept = row.cells[4].innerText.toLowerCase();
                const rowText = row.innerText.toLowerCase();

                // 1. Dropdown Checks
                if (collegeVal !== 'All Colleges' && rowCollege !== collegeVal.toLowerCase()) {
                    isMatch = false;
                }
                if (isMatch && userTypeVal !== 'All User Types' && rowType !== userTypeVal.toLowerCase()) {
                    isMatch = false;
                }

                // 2. Tokenized Checks
                if (isMatch) {
                    for (const [key, val] of Object.entries(parsedQuery.tokens)) {
                        if (key === 'college' && !checkMatch(rowCollege, val)) isMatch = false;
                        if (key === 'type' && !checkMatch(rowType, val)) isMatch = false;
                        if (key === 'dept' && !checkMatch(rowDept, val)) isMatch = false;
                    }
                }

                // 3. Free Text Check
                if (isMatch && parsedQuery.freeText.length > 0) {
                    const matchesFreeText = parsedQuery.freeText.every(term => checkMatch(rowText, term));
                    if (!matchesFreeText) isMatch = false;
                }
                
                row.style.display = isMatch ? '' : 'none';
            });
        }

        advancedSearch.addEventListener('input', applyFilters);
        filterCollege.addEventListener('change', applyFilters);
        filterUserType.addEventListener('change', applyFilters);

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
    </script>
</body>

</html>