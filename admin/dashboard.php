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

<body class="bg-gray-50 min-h-screen">

    <nav class="bg-white shadow-sm border-b border-gray-200 px-6 py-4 flex justify-between items-center">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Library Evaluation Analytics</h1>
            <p class="text-sm text-gray-500">Welcome back, <?php echo htmlspecialchars($username); ?> &mdash; <?php echo $role_display; ?></p>
        </div>
        
        <div class="flex items-center gap-4">
            <form id="exportForm" action="generate_excel.php" method="GET" onsubmit="return validateDateRange()" class="flex items-center gap-2 bg-gray-50 p-2 rounded-lg border border-gray-200">
                <select name="start_month" id="start_month" class="text-sm border-gray-300 rounded-md shadow-sm">
                    <?php
                    $months = ["JANUARY", "FEBRUARY", "MARCH", "APRIL", "MAY", "JUNE", "JULY", "AUGUST", "SEPTEMBER", "OCTOBER", "NOVEMBER", "DECEMBER"];
                    $currentMonth = strtoupper(date('F'));
                    foreach ($months as $m) {
                        $selected = ($m == $currentMonth) ? 'selected' : '';
                        echo "<option value=\"$m\" $selected>" . ucfirst(strtolower($m)) . "</option>";
                    }
                    ?>
                </select>
                <input type="number" name="start_year" id="start_year" value="<?php echo date('Y'); ?>" class="text-sm w-20 border-gray-300 rounded-md shadow-sm" required>
                
                <span class="text-gray-400 text-sm font-bold">TO</span>
                
                <select name="end_month" id="end_month" class="text-sm border-gray-300 rounded-md shadow-sm">
                    <?php
                    foreach ($months as $m) {
                        $selected = ($m == $currentMonth) ? 'selected' : '';
                        echo "<option value=\"$m\" $selected>" . ucfirst(strtolower($m)) . "</option>";
                    }
                    ?>
                </select>
                <input type="number" name="end_year" id="end_year" value="<?php echo date('Y'); ?>" class="text-sm w-20 border-gray-300 rounded-md shadow-sm" required>

                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-semibold transition shadow-sm ml-2">
                    Export to Master Template
                </button>
            </form>

            <a href="logout.php" class="text-red-600 hover:text-red-800 text-sm font-semibold px-2">Logout</a>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto p-6">

        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 min-h-[500px]">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Performance Overview -
                <?php echo date('F Y'); ?>
            </h2>

            <div id="chart-container"
                class="w-full h-96 flex items-center justify-center border-2 border-dashed border-gray-300 rounded-xl bg-gray-50">
                <p class="text-gray-500 font-medium">Chart.js wireframes go here.</p>
            </div>

        </div>

    </main>

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
    </script>
</body>

</html>