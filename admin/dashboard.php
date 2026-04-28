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
                <p class="text-xs text-gray-500">Welcome, <?php echo htmlspecialchars($username); ?> &mdash;
                    <?php echo $role_display; ?></p>
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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- KPI Cards Placeholder -->
                <div
                    class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center justify-center h-32">
                    <p class="text-xs font-bold text-gray-400 uppercase mb-1">Total Respondents</p>
                    <p class="text-3xl font-black text-gray-800">--</p>
                </div>
                <div
                    class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center justify-center h-32">
                    <p class="text-xs font-bold text-gray-400 uppercase mb-1">Avg Satisfaction</p>
                    <p class="text-3xl font-black text-gray-800">--</p>
                </div>
                <div
                    class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center justify-center h-32">
                    <p class="text-xs font-bold text-gray-400 uppercase mb-1">System Status</p>
                    <p class="text-sm font-bold text-green-600 bg-green-50 px-3 py-1 rounded-full">ACTIVE</p>
                </div>
            </div>

            <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                <h2 class="text-lg font-bold text-gray-800 mb-6">Performance Overview - <?php echo date('F Y'); ?></h2>

                <div id="chart-container"
                    class="w-full h-96 flex items-center justify-center border-2 border-dashed border-gray-200 rounded-xl bg-gray-50">
                    <div class="text-center">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                        <p class="text-gray-400 font-medium">Chart.js visualizations will be rendered here.</p>
                    </div>
                </div>
            </div>
        </main>
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
    </script>
</body>

</html>