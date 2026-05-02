<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?error=unauthorized");
    exit;
}
require_once '../db_connect.php';

// --- DATE FILTER LOGIC ---

// Fetch absolute bounds for Reset functionality
$boundsStmt = $pdo->query("SELECT MIN(created_at) as earliest, MAX(created_at) as latest FROM survey_submission");
$bounds = $boundsStmt->fetch();
$dbEarliest = $bounds['earliest'] ? date('Y-m-d', strtotime($bounds['earliest'])) : date('Y-m-01');
$dbLatest = $bounds['latest'] ? date('Y-m-d', strtotime($bounds['latest'])) : date('Y-m-d');

// Helper to convert Month Name + Year to SQL Date
function getSqlDate($monthName, $year, $isEnd = false) {
    $monthNum = date('m', strtotime($monthName));
    if ($isEnd) {
        return date('Y-m-t 23:59:59', strtotime("$year-$monthNum-01"));
    }
    return "$year-$monthNum-01 00:00:00";
}

$startMonth = $_GET['start_month'] ?? date('F', strtotime($dbEarliest));
$startYear = $_GET['start_year'] ?? date('Y', strtotime($dbEarliest));
$endMonth = $_GET['end_month'] ?? date('F', strtotime($dbLatest));
$endYear = $_GET['end_year'] ?? date('Y', strtotime($dbLatest));

$startDate = getSqlDate($startMonth, $startYear);
$endDate = getSqlDate($endMonth, $endYear, true);

// --- DATA FETCHING FOR ANALYTICS (Filtered by Date) ---

// 1. Service Utilization
$serviceStmt = $pdo->prepare("
    SELECT ls.service_name, COUNT(ss.submission_id) as usage_count 
    FROM library_service ls
    LEFT JOIN submission_service ss ON ls.service_id = ss.service_id
    LEFT JOIN survey_submission sub ON ss.submission_id = sub.submission_id
    WHERE sub.created_at BETWEEN ? AND ? OR sub.created_at IS NULL
    GROUP BY ls.service_id
    ORDER BY usage_count DESC
");
$serviceStmt->execute([$startDate, $endDate]);
$serviceData = $serviceStmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Metric Performance
$metricStmt = $pdo->prepare("
    SELECT qm.question_text, COALESCE(AVG(rd.score), 0) as avg_score
    FROM question_metric qm
    LEFT JOIN response_detail rd ON qm.question_id = rd.question_id
    LEFT JOIN survey_submission ss ON rd.submission_id = ss.submission_id
    WHERE ss.created_at BETWEEN ? AND ? OR ss.created_at IS NULL
    GROUP BY qm.question_id
");
$metricStmt->execute([$startDate, $endDate]);
$metricData = $metricStmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Departmental Bottlenecks
$deptStmt = $pdo->prepare("
    SELECT ld.dept_name, COALESCE(AVG(ss.overall_rating), 0) as avg_rating
    FROM library_department ld
    LEFT JOIN survey_submission ss ON ld.lib_dept_id = ss.lib_dept_id
    WHERE ss.created_at BETWEEN ? AND ? OR ss.created_at IS NULL
    GROUP BY ld.lib_dept_id
    ORDER BY avg_rating ASC
");
$deptStmt->execute([$startDate, $endDate]);
$deptData = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Sentiment by Department (YouTube-style Ratio)
$sentimentStmt = $pdo->prepare("
    SELECT 
        ld.dept_name,
        SUM(CASE WHEN ss.is_satisfied = 1 THEN 1 ELSE 0 END) as yes_count,
        SUM(CASE WHEN ss.is_satisfied = 0 THEN 1 ELSE 0 END) as no_count,
        COUNT(ss.submission_id) as total_count
    FROM library_department ld
    LEFT JOIN survey_submission ss ON ld.lib_dept_id = ss.lib_dept_id
    WHERE ss.created_at BETWEEN ? AND ? OR ss.created_at IS NULL
    GROUP BY ld.lib_dept_id
");
$sentimentStmt->execute([$startDate, $endDate]);
$sentimentData = $sentimentStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reports - AUF Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        slate: { 50: '#f8fafc', 100: '#f1f5f9', 200: '#e2e8f0', 300: '#cbd5e1', 400: '#94a3b8', 500: '#64748b', 600: '#475569', 700: '#334155', 800: '#1e293b', 900: '#0f172a' },
                        apricot: '#F7882F', indigo: { 600: '#4F46E5', 700: '#4338CA' }
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body { background-color: #e2e8f0; color: #334155; font-family: 'Inter', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body class="min-h-screen bg-slate-200 flex overflow-x-hidden">
    <?php require_once 'sidebar.php'; ?>
    <div class="flex-1 ml-64 [.collapsed-sidebar_&]:ml-20 transition-all duration-300 min-h-screen flex flex-col relative z-0">
        <header class="bg-white/60 backdrop-blur-lg shadow-sm border-b border-slate-200/60 h-20 flex items-center justify-between px-8 sticky top-0 z-30 flex-shrink-0">
            <div class="flex flex-col">
                <h1 class="text-xl font-extrabold text-slate-800 tracking-tight flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    Analytics & Performance
                </h1>
                <p class="text-sm text-slate-500 font-medium">Actionable intelligence reporting.</p>
            </div>
            <!-- Redesigned 'Control Center' Date Filters -->
            <div class="flex items-center gap-3">
                <div class="flex items-center bg-slate-900/90 border border-slate-800 rounded-2xl p-1.5 shadow-xl backdrop-blur-md">
                    <!-- Start Date Pill -->
                    <div class="flex items-center gap-1 px-4 py-1.5 bg-slate-800/50 rounded-xl border border-slate-700/50 group hover:border-indigo-500/50 transition-colors">
                        <span class="text-[9px] font-black text-slate-500 uppercase tracking-tighter mr-1 group-hover:text-indigo-400">From</span>
                        <select id="header_start_month" class="text-sm font-extrabold text-slate-100 bg-transparent border-none focus:ring-0 cursor-pointer py-0 px-1">
                            <?php $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                            foreach($months as $m) { $selected = (strtoupper($m) === strtoupper($startMonth)) ? 'selected' : ''; echo "<option value='".strtoupper($m)."' class='bg-slate-900 text-white' $selected>$m</option>"; } ?>
                        </select>
                        <input type="number" id="header_start_year" value="<?php echo $startYear; ?>" class="text-sm font-extrabold text-slate-100 bg-transparent border-none focus:ring-0 w-16 py-0 px-1">
                    </div>

                    <!-- Separator Icon -->
                    <div class="px-2 text-indigo-500/50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                    </div>

                    <!-- End Date Pill -->
                    <div class="flex items-center gap-1 px-4 py-1.5 bg-slate-800/50 rounded-xl border border-slate-700/50 group hover:border-indigo-500/50 transition-colors">
                        <span class="text-[9px] font-black text-slate-500 uppercase tracking-tighter mr-1 group-hover:text-indigo-400">To</span>
                        <select id="header_end_month" class="text-sm font-extrabold text-slate-100 bg-transparent border-none focus:ring-0 cursor-pointer py-0 px-1">
                            <?php foreach($months as $m) { $selected = (strtoupper($m) === strtoupper($endMonth)) ? 'selected' : ''; echo "<option value='".strtoupper($m)."' class='bg-slate-900 text-white' $selected>$m</option>"; } ?>
                        </select>
                        <input type="number" id="header_end_year" value="<?php echo $endYear; ?>" class="text-sm font-extrabold text-slate-100 bg-transparent border-none focus:ring-0 w-16 py-0 px-1">
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2">
                    <button onclick="applyDateFilter()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-2xl text-[11px] font-black uppercase tracking-widest transition shadow-lg shadow-indigo-900/20 hover:-translate-y-0.5 active:translate-y-0">Apply</button>
                    <button onclick="resetDateFilter()" class="bg-white hover:bg-slate-50 text-slate-500 px-6 py-3 rounded-2xl text-[11px] font-black uppercase tracking-widest border border-slate-200 transition shadow-sm hover:text-slate-700">Reset</button>
                </div>
            </div>
        </header>
        <main class="max-w-7xl mx-auto p-8 space-y-8 w-full flex-1">
            <div class="flex gap-8">
                <div class="w-1/2 bg-white p-8 rounded-2xl shadow-[0_2px_10px_rgb(0,0,0,0.02)] border border-slate-100 relative overflow-hidden">
                    <div id="export-overlay" class="hidden absolute inset-0 bg-white/90 backdrop-blur-sm z-10 flex flex-col items-center justify-center transition-all duration-300">
                        <div class="w-10 h-10 border-4 border-slate-200 border-t-indigo-600 rounded-full animate-spin mb-3"></div>
                        <p class="text-sm font-bold text-slate-800 tracking-tight">Generating Report...</p>
                    </div>
                    <div class="flex flex-col gap-4">
                        <div>
                            <h2 class="text-lg font-extrabold text-slate-800 tracking-tight mb-1">Export Evaluation Report</h2>
                            <p class="text-xs text-slate-500">Download XLSX report using active filters.</p>
                        </div>
                        <div class="flex flex-col gap-6 bg-slate-50 p-6 rounded-2xl border border-slate-100 mt-2">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Active Range</span>
                                <span class="text-sm font-bold text-slate-700"><?php echo "$startMonth $startYear — $endMonth $endYear"; ?></span>
                            </div>
                            <button onclick="handleExport()" id="export-btn" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl text-sm font-black uppercase tracking-widest transition shadow-[0_4px_12px_rgba(79,70,229,0.25)] flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Download Report (.xlsx)
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Departmental Sentiment Breakdown (YouTube-style) -->
                <div class="w-1/2 bg-white p-8 rounded-2xl shadow-[0_2px_10px_rgb(0,0,0,0.02)] border border-slate-100 flex flex-col">
                    <div class="mb-4">
                        <h2 class="text-lg font-extrabold text-slate-800 tracking-tight mb-1">Satisfaction by Department</h2>
                        <p class="text-xs text-slate-500">Operational bottlenecks based on Yes/No sentiment.</p>
                    </div>
                    <div class="flex-1 space-y-4 custom-scrollbar overflow-y-auto max-h-[140px] pr-2">
                        <?php foreach($sentimentData as $row): 
                            $total = $row['total_count'] ?: 1;
                            $yes_perc = round(($row['yes_count'] / $total) * 100);
                            $no_perc = 100 - $yes_perc;
                        ?>
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-[10px] font-black uppercase tracking-widest">
                                <span class="text-slate-700"><?php echo htmlspecialchars($row['dept_name']); ?></span>
                                <span class="<?php echo $yes_perc > 70 ? 'text-emerald-600' : ($yes_perc > 40 ? 'text-amber-600' : 'text-rose-600'); ?>">
                                    <?php echo $yes_perc; ?>% Positive
                                </span>
                            </div>
                            <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden flex shadow-inner">
                                <div class="h-full bg-emerald-500 transition-all duration-500 shadow-[inset_-2px_0_4px_rgba(0,0,0,0.05)]" style="width: <?php echo $yes_perc; ?>%"></div>
                                <div class="h-full bg-rose-500 transition-all duration-500" style="width: <?php echo $no_perc; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="lg:col-span-2 bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h2 class="text-xl font-extrabold text-slate-800 tracking-tight">Service Utilization Breakdown</h2>
                            <p class="text-sm text-slate-500 font-medium">Patron usage frequency by service.</p>
                        </div>
                        <div class="bg-indigo-50 text-indigo-600 px-4 py-1.5 rounded-xl text-xs font-black uppercase tracking-widest">TOP: <?php echo htmlspecialchars($serviceData[0]['service_name'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="h-[400px]"><canvas id="serviceChart"></canvas></div>
                </div>
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 flex flex-col">
                    <h2 class="text-xl font-extrabold text-slate-800 tracking-tight mb-2">Metric Performance Radar</h2>
                    <p class="text-sm text-slate-500 mb-8">Multi-dimensional service quality view.</p>
                    <div class="flex-1 min-h-[400px] flex items-center justify-center"><canvas id="metricChart"></canvas></div>
                </div>
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 flex flex-col">
                    <h2 class="text-xl font-extrabold text-slate-800 tracking-tight mb-2">Departmental Averages</h2>
                    <p class="text-sm text-slate-500 mb-8">Performance gaps between library sections.</p>
                    <div class="flex-1 min-h-[400px]"><canvas id="deptChart"></canvas></div>
                </div>
            </div>
        </main>
    </div>
    <script>
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

            // 1. No future dates
            if (startDate > currentMonth || endDate > currentMonth) {
                alert("Error: Future dates are not allowed. Please select current or past months.");
                return;
            }

            // 2. From date cannot be ahead of To date
            if (startDate > endDate) {
                alert("Error: 'From' date cannot be later than 'To' date.");
                return;
            }

            window.location.href = `?start_month=${sm}&start_year=${sy}&end_month=${em}&end_year=${ey}`;
        }
        function resetDateFilter() { window.location.href = window.location.pathname; }
        async function handleExport() {
            const overlay = document.getElementById('export-overlay');
            const btn = document.getElementById('export-btn');
            overlay.classList.remove('hidden'); btn.disabled = true;
            try {
                const response = await fetch(`generate_excel.php${window.location.search}`, { method: 'GET' });
                if (!response.ok) throw new Error('Export failed');
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a'); a.href = url; a.download = `EVAL_REPORT_${Date.now()}.xlsx`;
                document.body.appendChild(a); a.click(); a.remove();
            } catch (error) { alert(error.message); } finally { overlay.classList.add('hidden'); btn.disabled = false; }
        }
        const serviceData = <?php echo json_encode($serviceData); ?>;
        const metricData = <?php echo json_encode($metricData); ?>;
        const deptData = <?php echo json_encode($deptData); ?>;
        new Chart(document.getElementById('serviceChart'), {
            type: 'bar', data: { labels: serviceData.map(d => d.service_name), datasets: [{ label: 'Uses', data: serviceData.map(d => d.usage_count), backgroundColor: 'rgba(79, 70, 229, 0.6)', borderColor: 'rgb(79, 70, 229)', borderWidth: 1, borderRadius: 8, barThickness: 30 }] },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, beginAtZero: true }, y: { grid: { display: false } } } }
        });
        new Chart(document.getElementById('metricChart'), {
            type: 'radar', data: { labels: metricData.map(d => d.question_text.substring(0, 20) + '...'), datasets: [{ label: 'Avg', data: metricData.map(d => d.avg_score), backgroundColor: 'rgba(79, 70, 229, 0.2)', borderColor: 'rgb(79, 70, 229)', pointBackgroundColor: 'rgb(79, 70, 229)', pointBorderColor: '#fff' }] },
            options: { responsive: true, maintainAspectRatio: false, scales: { r: { suggestedMin: 0, suggestedMax: 5, ticks: { stepSize: 1 } } }, plugins: { legend: { display: false } } }
        });
        new Chart(document.getElementById('deptChart'), {
            type: 'bar', data: { labels: deptData.map(d => d.dept_name), datasets: [{ label: 'Avg', data: deptData.map(d => d.avg_rating), backgroundColor: deptData.map(d => d.avg_rating < 3 ? 'rgba(239, 68, 68, 0.6)' : (d.avg_rating < 4 ? 'rgba(245, 158, 11, 0.6)' : 'rgba(79, 70, 229, 0.6)')), borderColor: deptData.map(d => d.avg_rating < 3 ? 'rgb(239, 68, 68)' : (d.avg_rating < 4 ? 'rgb(245, 158, 11)' : 'rgb(79, 70, 229)')), borderWidth: 1, borderRadius: 8 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 5 }, x: { grid: { display: false } } } }
        });
    </script>
</body>
</html>